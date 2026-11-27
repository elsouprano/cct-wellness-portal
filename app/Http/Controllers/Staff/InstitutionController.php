<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\AssessmentSchedule;
use App\Models\Department;
use App\Models\Program;
use App\Models\User;
use App\Services\StudentPromotionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InstitutionController extends Controller
{
    public function index(Request $request)
    {
        $showArchived = $request->boolean('archived') || $request->boolean('show_archived');

        $departments = Department::notArchived()->with(['programs' => function ($query) {
            $query->notArchived()->withCount('users');
        }])->withCount(['programs' => fn($q) => $q->notArchived()])->orderBy('name')->get();

        $academicYears = AcademicYear::notArchived()->orderByDesc('label')->get();
        $currentActiveYear = $academicYears->firstWhere('is_current', true) ?? AcademicYear::where('is_current', true)->first();

        $archivedDepartments = collect();
        $archivedAcademicYears = collect();
        $archivedPrograms = collect();

        if ($showArchived) {
            $archivedDepartments = Department::archived()->with(['programs' => fn($q) => $q->withCount('users')])->withCount('programs')->orderBy('name')->get();
            $archivedAcademicYears = AcademicYear::archived()->orderByDesc('label')->get();
            $archivedPrograms = Program::archived()->with('department')->withCount('users')->orderBy('name')->get();
        }

        $studentsToPromoteCount = User::where('role', 'student')
            ->where('is_active', true)
            ->whereIn('year_level', ['1st', '2nd', '3rd'])
            ->count();

        $fourthYearCount = User::where('role', 'student')
            ->where('is_active', true)
            ->where('year_level', '4th')
            ->count();

        return view('staff.institution.index', compact(
            'departments', 
            'academicYears', 
            'currentActiveYear',
            'showArchived', 
            'archivedDepartments', 
            'archivedAcademicYears', 
            'archivedPrograms',
            'studentsToPromoteCount',
            'fourthYearCount'
        ));
    }

    public function storeDepartment(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:departments,name',
            'programs' => 'nullable|array',
            'programs.*.name' => 'required|string|max:255',
            'programs.*.code' => 'required|string|max:50|distinct:ignore_case|unique:programs,code',
        ], [
            'programs.*.code.distinct' => 'Each program code in the list must be unique.',
            'programs.*.code.unique' => 'The program code ":input" is already taken.',
            'programs.*.name.required' => 'The program name is required.',
            'programs.*.code.required' => 'The program code is required.',
        ]);

        $priorActiveDeptIds = Department::notArchived()->pluck('id')->all();

        DB::transaction(function () use ($validated, $priorActiveDeptIds) {
            $department = Department::create(['name' => $validated['name']]);
            $programCodes = [];

            if (!empty($validated['programs'])) {
                foreach ($validated['programs'] as $prog) {
                    $createdProg = $department->programs()->create([
                        'name' => trim($prog['name']),
                        'code' => trim($prog['code']),
                    ]);
                    $programCodes[] = $createdProg->code;
                }
            }

            // Automatically assign the new department to counselors who already had access
            // to all prior active departments, without expanding intentionally restricted counselors.
            $counselors = \App\Models\User::where('role', 'guidance_counselor')
                ->with('assignedDepartments:id')
                ->get();

            foreach ($counselors as $counselor) {
                $allowed = $counselor->getAllowedDepartmentIds();
                $hadAllPrior = ! empty($priorActiveDeptIds) && empty(array_diff($priorActiveDeptIds, $allowed));
                if ($hadAllPrior) {
                    $counselor->assignedDepartments()->syncWithoutDetaching([$department->id]);
                    if ($counselor->department_id === null) {
                        $counselor->update(['department_id' => $department->id]);
                    }
                }
            }

            app(\App\Services\ActivityLogService::class)->log(
                action: 'institution.department_created',
                module: 'Institution Management',
                description: "Created school '{$department->name}'" . (count($programCodes) ? ' with programs: ' . implode(', ', $programCodes) : '') . '.',
                subject: $department,
                newValues: ['name' => $department->name, 'programs' => $programCodes],
                critical: true,
            );
        });

        $this->clearInstitutionCaches();

        return redirect()->route('institution.index')->with('status', 'School created successfully.');
    }

    public function updateDepartment(Request $request, Department $department)
    {
        $request->validate(['name' => 'required|string|max:255|unique:departments,name,' . $department->id]);
        $oldName = $department->name;
        $department->update($request->only('name'));
        $this->clearInstitutionCaches();

        app(\App\Services\ActivityLogService::class)->log(
            action: 'institution.department_updated',
            module: 'Institution Management',
            description: "Updated school name from '{$oldName}' to '{$department->name}'.",
            subject: $department,
            oldValues: ['name' => $oldName],
            newValues: ['name' => $department->name],
        );

        return redirect()->route('institution.index')->with('status', 'School updated successfully.');
    }

    public function destroyDepartment(Department $department)
    {
        if ($department->assignedCounselorCount() > 0) {
            return redirect()->route('institution.index')->withErrors([
                'error' => "Cannot archive '{$department->name}': it still has counselors assigned. Please reassign counselors first."
            ]);
        }

        $hasActiveSchedule = AssessmentSchedule::whereIn('program_id', $department->programs()->pluck('id'))
            ->activeSchedules()
            ->exists();

        if ($hasActiveSchedule) {
            return redirect()->route('institution.index')->withErrors([
                'error' => "Cannot archive '{$department->name}': one or more of its programs has a currently open or upcoming assessment schedule."
            ]);
        }

        DB::transaction(function () use ($department) {
            $archivedAt = now();
            $department->update(['archived_at' => $archivedAt]);
            $department->programs()->notArchived()->update(['archived_at' => $archivedAt]);

            app(\App\Services\ActivityLogService::class)->log(
                action: 'institution.department_archived',
                module: 'Institution Management',
                description: "Archived school '{$department->name}'.",
                subject: $department,
                oldValues: ['archived_at' => null],
                newValues: ['archived_at' => $department->archived_at?->toDateTimeString()],
                critical: true,
            );
        });

        $this->clearInstitutionCaches();

        return redirect()->route('institution.index', ['archived' => 1])->with('status', "School '{$department->name}' archived successfully.");
    }

    public function restoreDepartment(Department $department)
    {
        $oldArchivedAtCarbon = $department->archived_at?->copy();
        $oldArchivedAt = $oldArchivedAtCarbon?->toDateTimeString();

        DB::transaction(function () use ($department, $oldArchivedAtCarbon, $oldArchivedAt) {
            $department->update(['archived_at' => null]);

            if ($oldArchivedAtCarbon) {
                $department->programs()
                    ->where('archived_at', '>=', $oldArchivedAtCarbon->copy()->subSeconds(5))
                    ->update(['archived_at' => null]);
            }

            app(\App\Services\ActivityLogService::class)->log(
                action: 'institution.department_restored',
                module: 'Institution Management',
                description: "Restored school '{$department->name}'.",
                subject: $department,
                oldValues: ['archived_at' => $oldArchivedAt],
                newValues: ['archived_at' => null],
                critical: true,
            );
        });

        $this->clearInstitutionCaches();

        return redirect()->route('institution.index', ['archived' => 1])->with('status', "School '{$department->name}' restored successfully.");
    }

    public function storeProgram(Request $request)
    {
        $request->validate([
            'department_id' => ['required', Rule::exists('departments', 'id')->whereNull('archived_at')],
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:programs,code'
        ]);

        $program = Program::create($request->only('department_id', 'name', 'code'));
        $this->clearInstitutionCaches();

        app(\App\Services\ActivityLogService::class)->log(
            action: 'institution.program_created',
            module: 'Institution Management',
            description: "Created program '{$program->name}'" . ($program->code ? " ({$program->code})" : '') . '.',
            subject: $program,
            newValues: $program->only(['department_id', 'name', 'code']),
        );

        return redirect()->route('institution.index')->with('status', 'Program created successfully.');
    }

    public function updateProgram(Request $request, Program $program)
    {
        $request->validate([
            'department_id' => ['required', Rule::exists('departments', 'id')->whereNull('archived_at')],
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:programs,code,' . $program->id
        ]);

        $oldValues = $program->only(['department_id', 'name', 'code']);
        $program->update($request->only('department_id', 'name', 'code'));
        $this->clearInstitutionCaches();

        app(\App\Services\ActivityLogService::class)->log(
            action: 'institution.program_updated',
            module: 'Institution Management',
            description: "Updated program '{$program->name}'.",
            subject: $program,
            oldValues: $oldValues,
            newValues: $program->only(['department_id', 'name', 'code']),
        );

        return redirect()->route('institution.index')->with('status', 'Program updated successfully.');
    }

    public function destroyProgram(Program $program)
    {
        $hasActiveSchedule = $program->assessmentSchedules()
            ->where(function ($q) {
                $today = now()->format('Y-m-d');
                $nowTime = now()->format('H:i');
                $q->where('close_date', '>', $today)
                  ->orWhere(function ($sub) use ($today, $nowTime) {
                      $sub->where('close_date', $today)
                          ->where('close_time', '>=', $nowTime);
                  });
            })->exists();

        if ($hasActiveSchedule) {
            return redirect()->route('institution.index')->withErrors([
                'error' => "Cannot archive '{$program->name}': it has a currently open or upcoming assessment schedule."
            ]);
        }

        $program->update(['archived_at' => now()]);
        $this->clearInstitutionCaches();

        app(\App\Services\ActivityLogService::class)->log(
            action: 'institution.program_archived',
            module: 'Institution Management',
            description: "Archived program '{$program->name}'.",
            subject: $program,
            oldValues: ['archived_at' => null],
            newValues: ['archived_at' => $program->archived_at?->toDateTimeString()],
            critical: true,
        );

        return redirect()->route('institution.index', ['archived' => 1])->with('status', "Program '{$program->name}' archived successfully.");
    }

    public function restoreProgram(Program $program)
    {
        if ($program->department && $program->department->isArchived()) {
            return redirect()->route('institution.index', ['archived' => 1])->withErrors([
                'error' => "Cannot restore program '{$program->name}': its school '{$program->department->name}' is currently archived. Please restore the school first."
            ]);
        }

        $oldArchivedAt = $program->archived_at?->toDateTimeString();
        $program->update(['archived_at' => null]);
        $this->clearInstitutionCaches();

        app(\App\Services\ActivityLogService::class)->log(
            action: 'institution.program_restored',
            module: 'Institution Management',
            description: "Restored program '{$program->name}'.",
            subject: $program,
            oldValues: ['archived_at' => $oldArchivedAt],
            newValues: ['archived_at' => null],
            critical: true,
        );

        return redirect()->route('institution.index', ['archived' => 1])->with('status', "Program '{$program->name}' restored successfully.");
    }

    public function storeAcademicYear(Request $request, StudentPromotionService $promotionService)
    {
        $request->validate([
            'label' => 'required|string|max:50|unique:academic_years',
            'set_as_current' => 'nullable|boolean',
        ]);

        $setAsCurrent = $request->boolean('set_as_current');

        if ($setAsCurrent) {
            $tempYear = new AcademicYear(['label' => $request->label]);
            if (! $tempYear->canBeSetActive()) {
                return redirect()->route('institution.index')->withErrors([
                    'error' => "Cannot set '{$request->label}' as active: only a later academic year can be activated."
                ]);
            }
        }

        $promotedCount = 0;
        $academicYear = null;

        DB::transaction(function () use ($request, $setAsCurrent, &$academicYear, &$promotedCount, $promotionService) {
            if ($setAsCurrent) {
                AcademicYear::where('is_current', true)->update(['is_current' => false]);
            }

            $academicYear = AcademicYear::create([
                'label' => $request->label,
                'is_current' => $setAsCurrent,
            ]);

            if ($setAsCurrent) {
                $promotedCount = $promotionService->promoteStudentsToNextYearLevel(auth()->id());
            }

            app(\App\Services\ActivityLogService::class)->log(
                action: 'institution.academic_year_created',
                module: 'Institution Management',
                description: "Created academic year '{$academicYear->label}'" . ($setAsCurrent ? ' and set as active' : '') . '.',
                subject: $academicYear,
                newValues: ['label' => $academicYear->label, 'is_current' => $setAsCurrent],
                critical: true,
            );
        });

        $this->clearAcademicYearCaches();

        if ($setAsCurrent) {
            $message = "Academic Year '{$academicYear->label}' created and set as active.";
            if ($promotedCount > 0) {
                $message .= " {$promotedCount} students were automatically promoted to their next year level.";
            }
        } else {
            $message = 'Academic Year created successfully.';
        }

        return redirect()->route('institution.index')->with('status', $message);
    }

    public function updateAcademicYear(Request $request, AcademicYear $academic_year)
    {
        if (! $academic_year->isEditable()) {
            return redirect()->route('institution.index')->withErrors([
                'error' => "Cannot edit '{$academic_year->label}': active or past academic years cannot be edited.",
            ]);
        }

        $request->validate(['label' => 'required|string|max:50|unique:academic_years,label,' . $academic_year->id]);
        $oldLabel = $academic_year->label;
        $newLabel = trim($request->label);

        DB::transaction(function () use ($academic_year, $oldLabel, $newLabel) {
            $academic_year->update(['label' => $newLabel]);

            if ($oldLabel !== $newLabel) {
                \App\Models\InventorySubmission::where('academic_year', $oldLabel)
                    ->update(['academic_year' => $newLabel]);
            }

            app(\App\Services\ActivityLogService::class)->log(
                action: 'institution.academic_year_updated',
                module: 'Institution Management',
                description: "Updated academic year from '{$oldLabel}' to '{$academic_year->label}'.",
                subject: $academic_year,
                oldValues: ['label' => $oldLabel],
                newValues: ['label' => $academic_year->label],
            );
        });

        $this->clearAcademicYearCaches();
        \App\Services\DashboardStatsService::clearCache();
        AnalyticsController::clearCache();

        return redirect()->route('institution.index')->with('status', 'Academic Year updated successfully.');
    }

    public function destroyAcademicYear(AcademicYear $academic_year)
    {
        if ($academic_year->is_current) {
            return redirect()->route('institution.index')->withErrors([
                'error' => "Cannot archive '{$academic_year->label}': the active academic year cannot be archived."
            ]);
        }

        $hasActiveSchedule = $academic_year->assessmentSchedules()
            ->activeSchedules()
            ->exists();

        if ($hasActiveSchedule) {
            return redirect()->route('institution.index')->withErrors([
                'error' => "Cannot archive '{$academic_year->label}': it has a currently open or upcoming assessment schedule."
            ]);
        }

        DB::transaction(function () use ($academic_year) {
            $academic_year->update(['archived_at' => now()]);

            app(\App\Services\ActivityLogService::class)->log(
                action: 'institution.academic_year_archived',
                module: 'Institution Management',
                description: "Archived academic year '{$academic_year->label}'.",
                subject: $academic_year,
                oldValues: ['archived_at' => null],
                newValues: ['archived_at' => $academic_year->archived_at?->toDateTimeString()],
                critical: true,
            );
        });

        $this->clearAcademicYearCaches();

        return redirect()->route('institution.index', ['archived' => 1])->with('status', "Academic Year '{$academic_year->label}' archived successfully.");
    }

    public function restoreAcademicYear(AcademicYear $academic_year)
    {
        $oldArchivedAt = $academic_year->archived_at?->toDateTimeString();

        DB::transaction(function () use ($academic_year, $oldArchivedAt) {
            $academic_year->update(['archived_at' => null]);

            app(\App\Services\ActivityLogService::class)->log(
                action: 'institution.academic_year_restored',
                module: 'Institution Management',
                description: "Restored academic year '{$academic_year->label}'.",
                subject: $academic_year,
                oldValues: ['archived_at' => $oldArchivedAt],
                newValues: ['archived_at' => null],
                critical: true,
            );
        });

        $this->clearAcademicYearCaches();

        return redirect()->route('institution.index', ['archived' => 1])->with('status', "Academic Year '{$academic_year->label}' restored successfully.");
    }

    public function setCurrentAcademicYear(Request $request, AcademicYear $academic_year, StudentPromotionService $promotionService)
    {
        if ($academic_year->is_current) {
            return redirect()->route('institution.index')->with('info', "Academic Year '{$academic_year->label}' is already the active academic year.");
        }

        if ($academic_year->isArchived()) {
            return redirect()->route('institution.index')->withErrors([
                'error' => "Cannot set '{$academic_year->label}' as active: archived academic years must be restored first."
            ]);
        }

        if (! $academic_year->canBeSetActive()) {
            return redirect()->route('institution.index')->withErrors([
                'error' => "Cannot set '{$academic_year->label}' as active: only a later academic year can be activated."
            ]);
        }

        $request->validate([
            'confirmed' => ['required', 'accepted'],
        ], [
            'confirmed.accepted' => 'You must check the confirmation box to activate this academic year and promote students.',
            'confirmed.required' => 'Confirmation is required to activate an academic year.',
        ]);

        $promotedCount = 0;

        DB::transaction(function () use ($academic_year, &$promotedCount, $promotionService) {
            AcademicYear::where('is_current', true)->update(['is_current' => false]);
            $academic_year->update(['is_current' => true]);

            $promotedCount = $promotionService->promoteStudentsToNextYearLevel(auth()->id());

            app(\App\Services\ActivityLogService::class)->log(
                action: 'institution.academic_year_activated',
                module: 'Institution Management',
                description: "Set active academic year to '{$academic_year->label}' ({$promotedCount} students promoted).",
                subject: $academic_year,
                oldValues: ['is_current' => false],
                newValues: ['is_current' => true, 'promoted_count' => $promotedCount],
                critical: true,
            );
        });

        $this->clearAcademicYearCaches();

        if ($promotedCount > 0) {
            $status = "Active Academic Year has been set to {$academic_year->label}. {$promotedCount} students were automatically promoted to their next year level.";
        } else {
            $status = 'Active Academic Year has been set.';
        }

        return redirect()->route('institution.index')->with('status', $status);
    }

    private function clearInstitutionCaches(): void
    {
        Cache::forget('filter_departments');
        Cache::forget('filter_departments_with_programs');
        Cache::forget('filter_programs');
        User::clearRequestScopeCache();
    }

    private function clearAcademicYearCaches(): void
    {
        Cache::forget('filter_academic_years');
        Cache::forget('filter_academic_years_all');
        Cache::forget('filter_academic_years_active');
        Cache::forget('current_academic_year');
    }
}
