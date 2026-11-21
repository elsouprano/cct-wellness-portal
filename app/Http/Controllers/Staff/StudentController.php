<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    /**
     * Display a listing of students.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $status = $request->input('status', 'active');
        $programFilter = $request->input('program');
        $yearLevelFilter = $request->input('year_level');
        $sectionFilter = $request->input('section');
        $submissionStatus = $request->input('submission_status'); // 'all', 'submitted', 'pending'

        $perPage = (int) $request->input('per_page', 10);
        if (!in_array($perPage, [10, 25, 50])) {
            $perPage = 10;
        }

        $currentYear = \App\Models\AcademicYear::where('is_current', true)->first();
        $academicYear = $currentYear?->label;

        $query = User::where('role', 'student')
            ->accessibleByStaff($user)
            ->with([
                'structuredProgram.department', 
                'inventorySubmissions' => function($q) use ($academicYear) {
                    $q->whereNotNull('submitted_at');
                    if ($academicYear) {
                        $q->where('academic_year', $academicYear);
                    }
                }
            ]);

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'archived') {
            $query->where('is_active', false);
        }

        if ($programFilter) {
            $matchedProgram = is_numeric($programFilter)
                ? \App\Models\Program::find((int) $programFilter)
                : \App\Models\Program::where('code', $programFilter)->orWhere('name', $programFilter)->first();

            $query->where(function ($q) use ($programFilter, $matchedProgram) {
                $q->where('program_id', $programFilter)
                  ->orWhere('program', $programFilter);
                if ($matchedProgram) {
                    $q->orWhere('program_id', $matchedProgram->id)
                      ->orWhereIn('program', array_filter([$matchedProgram->code, $matchedProgram->name]));
                }
            });
        }

        if ($yearLevelFilter) {
            $query->where('year_level', $yearLevelFilter);
        }

        if ($sectionFilter) {
            $query->where('section', $sectionFilter);
        }

        if ($submissionStatus === 'submitted' && $academicYear) {
            $query->whereHas('inventorySubmissions', function ($q) use ($academicYear) {
                $q->where('academic_year', $academicYear)->whereNotNull('submitted_at');
            });
        } elseif ($submissionStatus === 'pending' && $academicYear) {
            $query->whereDoesntHave('inventorySubmissions', function ($q) use ($academicYear) {
                $q->where('academic_year', $academicYear)->whereNotNull('submitted_at');
            });
        }

        if ($search = $request->input('search')) {
            $escaped = addcslashes((string) $search, '%_\\');
            $query->where(function ($q) use ($escaped) {
                $q->where('first_name', 'like', "%{$escaped}%")
                    ->orWhere('last_name', 'like', "%{$escaped}%")
                    ->orWhere('student_id', 'like', "%{$escaped}%")
                    ->orWhere('email', 'like', "%{$escaped}%");
            });
        }

        $students = $query->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate($perPage)
            ->withQueryString();

        // Get allowed programs for dropdown
        $allowedProgramIds = $user->getAllowedProgramIds();
        $programs = $allowedProgramIds !== null
            ? \App\Models\Program::notArchived()->whereIn('id', $allowedProgramIds)->orderBy('name')->get()
            : \App\Models\Program::notArchived()->orderBy('name')->get();

        $sections = User::where('role', 'student')
            ->accessibleByStaff($user)
            ->whereNotNull('section')
            ->where('section', '!=', '')
            ->distinct()
            ->orderBy('section')
            ->pluck('section');

        $whitelistedKeys = ['search', 'status', 'program', 'year_level', 'section', 'submission_status', 'per_page', 'page'];
        $filtersWhitelist = array_filter(
            $request->only($whitelistedKeys),
            fn ($value) => !is_null($value) && $value !== ''
        );

        return view('staff.students.index', compact(
            'students', 
            'search', 
            'status', 
            'programs', 
            'sections', 
            'programFilter', 
            'yearLevelFilter', 
            'sectionFilter', 
            'submissionStatus',
            'academicYear',
            'perPage',
            'filtersWhitelist'
        ));
    }

    /**
     * Display the specified student's profile.
     */
    public function show(Request $request, User $student)
    {
        // Ensure the user is a student and accessible by this staff user
        if ($student->role !== 'student' || ! auth()->user()->canAccessStudent($student)) {
            abort(404);
        }

        $student->load([
            'structuredProgram.department',
            'inventorySubmissions' => function ($q) {
                $q->whereNotNull('submitted_at')->latest('submitted_at');
            },
            'inventorySubmissions.flags',
            'inventorySubmissions.qualityAssessment',
        ]);

        $latestSubmission = $student->inventorySubmissions->first();
        if ($latestSubmission) {
            $latestSubmission->load(['scores', 'responses']);
        }

        $sortedScores = collect();
        if ($latestSubmission && $latestSubmission->scores) {
            $sortedScores = $latestSubmission->scores->sortByDesc(function ($score) {
                $label = strtolower($score->severity_label ?? $score->interpretation_label ?? '');
                $isSevere = str_contains($label, 'severe') || str_contains($label, 'high');
                $isModerate = str_contains($label, 'moderate');
                return $isSevere ? 2 : ($isModerate ? 1 : 0);
            });
        }

        $responsesByCategory = collect();
        $inventoryConfig = collect();

        if ($latestSubmission) {
            $academicYearModel = \App\Models\AcademicYear::where('label', $latestSubmission->academic_year)->first();
            $responseCategories = $latestSubmission->responses->pluck('category')->unique()->filter()->values()->all();

            if ($academicYearModel && ! empty($responseCategories)) {
                $inventoryConfig = \App\Models\QuestionCategory::with(['questionItems.subcategory', 'correlatedPairs'])
                    ->where('academic_year_id', $academicYearModel->id)
                    ->whereIn('name', $responseCategories)
                    ->orderBy('display_order')
                    ->get();
            }

            if ($inventoryConfig->isEmpty()) {
                $yearLevel = $student->year_level ?? '3rd';
                $query = \App\Models\QuestionCategory::with(['questionItems.subcategory', 'correlatedPairs'])
                    ->forYearLevel($yearLevel, $academicYearModel?->id)
                    ->orderBy('display_order');

                if ($academicYearModel) {
                    $inventoryConfig = (clone $query)->where('academic_year_id', $academicYearModel->id)->get();
                }

                if ($inventoryConfig->isEmpty()) {
                    $inventoryConfig = $query->get();
                }
            }

            foreach ($latestSubmission->responses as $response) {
                $cat = $inventoryConfig->firstWhere('name', $response->category);
                if ($cat) {
                    $response->questionItem = $cat->questionItems->firstWhere('item_number', $response->item_number);
                }
            }

            $responsesByCategory = $latestSubmission->responses->groupBy('category');
        }

        $whitelistedKeys = ['search', 'status', 'program', 'year_level', 'section', 'submission_status', 'per_page', 'page'];
        $filtersWhitelist = array_filter(
            $request->only($whitelistedKeys),
            fn ($value) => !is_null($value) && $value !== ''
        );

        $backUrl = !empty($filtersWhitelist)
            ? route('staff.students.index', $filtersWhitelist)
            : route('staff.students.index');

        return view('staff.students.show', compact(
            'student', 
            'latestSubmission', 
            'sortedScores',
            'responsesByCategory', 
            'inventoryConfig',
            'backUrl',
            'filtersWhitelist'
        ));
    }

    /**
     * Archive the specified student.
     */
    public function archive(User $student)
    {
        abort_if(! auth()->user()->isAdmin(), 403, 'Unauthorized access.');

        if ($student->role !== 'student') {
            abort(404);
        }

        if (! $student->is_active) {
            return back()->with('info', 'Student is already archived.');
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($student) {
            $student->update([
                'is_active' => false,
                'deactivated_at' => now(),
                'deactivated_by' => auth()->id(),
            ]);

            app(\App\Services\ActivityLogService::class)->log(
                action: 'student.archived',
                module: 'Student Management',
                description: "Archived student account for {$student->first_name} {$student->last_name} ({$student->student_id}).",
                subject: $student,
                oldValues: ['is_active' => true],
                newValues: ['is_active' => false],
                critical: true,
            );
        });

        \App\Services\DashboardStatsService::clearCache();
        AnalyticsController::clearCache();

        return back()->with('success', 'Student archived successfully.');
    }

    /**
     * Unarchive / restore the specified student.
     */
    public function unarchive(User $student)
    {
        abort_if(! auth()->user()->isAdmin(), 403, 'Unauthorized access.');

        if ($student->role !== 'student') {
            abort(404);
        }

        if ($student->is_active) {
            return back()->with('info', 'Student is already active.');
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($student) {
            $student->update([
                'is_active' => true,
                'deactivated_at' => null,
                'deactivated_by' => null,
            ]);

            app(\App\Services\ActivityLogService::class)->log(
                action: 'student.restored',
                module: 'Student Management',
                description: "Restored student account for {$student->first_name} {$student->last_name} ({$student->student_id}).",
                subject: $student,
                oldValues: ['is_active' => false],
                newValues: ['is_active' => true],
                critical: true,
            );
        });

        \App\Services\DashboardStatsService::clearCache();
        AnalyticsController::clearCache();

        return back()->with('success', 'Student unarchived successfully.');
    }
}
