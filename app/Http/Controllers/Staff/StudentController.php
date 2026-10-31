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

        $currentYear = \App\Models\AcademicYear::where('is_current', true)->first();
        $academicYear = $currentYear?->label;

        $query = User::where('role', 'student')
            ->accessibleByStaff($user)
            ->with([
                'structuredProgram.department', 
                'inventorySubmissions' => function($q) use ($academicYear) {
                    if ($academicYear) {
                        $q->where('academic_year', $academicYear)->whereNotNull('submitted_at');
                    }
                }
            ]);

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'archived') {
            $query->where('is_active', false);
        }

        if ($programFilter) {
            $query->where(function ($q) use ($programFilter) {
                $q->where('program_id', $programFilter)
                  ->orWhere('program', $programFilter);
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
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('student_id', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $students = $query->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(15)
            ->withQueryString();

        // Get allowed programs for dropdown
        $allowedProgramIds = $user->getAllowedProgramIds();
        $programs = $allowedProgramIds !== null
            ? \App\Models\Program::whereIn('id', $allowedProgramIds)->orderBy('name')->get()
            : \App\Models\Program::orderBy('name')->get();

        $sections = User::where('role', 'student')
            ->accessibleByStaff($user)
            ->whereNotNull('section')
            ->where('section', '!=', '')
            ->distinct()
            ->orderBy('section')
            ->pluck('section');

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
            'academicYear'
        ));
    }

    /**
     * Display the specified student's profile.
     */
    public function show(User $student)
    {
        // Ensure the user is a student and accessible by this staff user
        if ($student->role !== 'student' || ! auth()->user()->canAccessStudent($student)) {
            abort(404);
        }

        $student->load([
            'structuredProgram.department',
            'inventorySubmissions' => function ($q) {
                $q->latest('submitted_at');
            },
            'inventorySubmissions.flags',
            'inventorySubmissions.scores',
            'inventorySubmissions.qualityAssessment',
            'inventorySubmissions.responses',
        ]);

        $latestSubmission = $student->inventorySubmissions->first();

        $responsesByCategory = collect();
        $inventoryConfig = collect();

        if ($latestSubmission) {
            $academicYearModel = \App\Models\AcademicYear::where('label', $latestSubmission->academic_year)->first();
            $yearLevel = $student->year_level ?? '3rd';

            $query = \App\Models\QuestionCategory::with(['questionItems.subcategory', 'correlatedPairs'])
                ->where('year_level', $yearLevel)
                ->orderBy('display_order');

            if ($academicYearModel) {
                $inventoryConfig = (clone $query)->where('academic_year_id', $academicYearModel->id)->get();
            }

            if ($inventoryConfig->isEmpty()) {
                $inventoryConfig = $query->get();
            }

            foreach ($latestSubmission->responses as $response) {
                $cat = $inventoryConfig->firstWhere('name', $response->category);
                if ($cat) {
                    $response->questionItem = $cat->questionItems->firstWhere('item_number', $response->item_number);
                }
            }

            $responsesByCategory = $latestSubmission->responses->groupBy('category');
        }

        return view('staff.students.show', compact('student', 'latestSubmission', 'responsesByCategory', 'inventoryConfig'));
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

        $student->update([
            'is_active' => false,
            'deactivated_at' => now(),
            'deactivated_by' => auth()->id(),
        ]);

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

        $student->update([
            'is_active' => true,
            'deactivated_at' => null,
            'deactivated_by' => null,
        ]);

        return back()->with('success', 'Student unarchived successfully.');
    }
}
