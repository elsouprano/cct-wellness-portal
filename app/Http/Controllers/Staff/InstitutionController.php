<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\AssessmentSchedule;
use App\Models\Department;
use App\Models\Program;
use App\Services\StudentPromotionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class InstitutionController extends Controller
{
    public function index(Request $request)
    {
        $showArchived = $request->boolean('show_archived');

        $departments = Department::notArchived()->with(['programs' => function ($query) {
            $query->notArchived()->withCount('users');
        }])->withCount(['programs' => fn($q) => $q->notArchived()])->orderBy('name')->get();

        $academicYears = AcademicYear::notArchived()->orderByDesc('label')->get();

        $archivedDepartments = collect();
        $archivedAcademicYears = collect();
        $archivedPrograms = collect();

        if ($showArchived) {
            $archivedDepartments = Department::archived()->with(['programs' => fn($q) => $q->withCount('users')])->withCount('programs')->orderBy('name')->get();
            $archivedAcademicYears = AcademicYear::archived()->orderByDesc('label')->get();
            $archivedPrograms = Program::archived()->with('department')->withCount('users')->orderBy('name')->get();
        }

        return view('staff.institution.index', compact(
            'departments', 
            'academicYears', 
            'showArchived', 
            'archivedDepartments', 
            'archivedAcademicYears', 
            'archivedPrograms'
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

        DB::transaction(function () use ($validated) {
            $department = Department::create(['name' => $validated['name']]);

            if (!empty($validated['programs'])) {
                foreach ($validated['programs'] as $prog) {
                    if (!empty($prog['name']) && !empty($prog['code'])) {
                        $department->programs()->create([
                            'name' => trim($prog['name']),
                            'code' => trim($prog['code']),
                        ]);
                    }
                }
            }
        });

        $this->clearInstitutionCaches();

        return redirect()->route('institution.index')->with('status', 'School created successfully.');
    }

    public function updateDepartment(Request $request, Department $department)
    {
        $request->validate(['name' => 'required|string|max:255|unique:departments,name,' . $department->id]);
        $department->update($request->only('name'));
        $this->clearInstitutionCaches();

        return redirect()->route('institution.index')->with('status', 'School updated successfully.');
    }

    public function destroyDepartment(Department $department)
    {
        if ($department->counselors()->count() > 0) {
            return redirect()->route('institution.index')->withErrors([
                'error' => "Cannot archive '{$department->name}': it still has counselors assigned. Please reassign counselors first."
            ]);
        }

        $hasActiveSchedule = AssessmentSchedule::whereIn('program_id', $department->programs()->pluck('id'))
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
                'error' => "Cannot archive '{$department->name}': one or more of its programs has a currently open or upcoming assessment schedule."
            ]);
        }

        $department->update(['archived_at' => now()]);
        $department->programs()->notArchived()->update(['archived_at' => now()]);

        $this->clearInstitutionCaches();

        return redirect()->route('institution.index')->with('status', "School '{$department->name}' archived successfully.");
    }

    public function restoreDepartment(Department $department)
    {
        $department->update(['archived_at' => null]);
        $department->programs()->where('archived_at', '>=', $department->archived_at ?? now()->subDay())->update(['archived_at' => null]);

        $this->clearInstitutionCaches();

        return redirect()->route('institution.index')->with('status', "School '{$department->name}' restored successfully.");
    }

    public function storeProgram(Request $request)
    {
        $request->validate([
            'department_id' => 'required|exists:departments,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:programs,code'
        ]);

        Program::create($request->only('department_id', 'name', 'code'));
        $this->clearInstitutionCaches();

        return redirect()->route('institution.index')->with('status', 'Program created successfully.');
    }

    public function updateProgram(Request $request, Program $program)
    {
        $request->validate([
            'department_id' => 'required|exists:departments,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:programs,code,' . $program->id
        ]);

        $program->update($request->only('department_id', 'name', 'code'));
        $this->clearInstitutionCaches();

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

        return redirect()->route('institution.index')->with('status', "Program '{$program->name}' archived successfully.");
    }

    public function restoreProgram(Program $program)
    {
        if ($program->department && $program->department->isArchived()) {
            return redirect()->route('institution.index')->withErrors([
                'error' => "Cannot restore program '{$program->name}': its school '{$program->department->name}' is currently archived. Please restore the school first."
            ]);
        }

        $program->update(['archived_at' => null]);
        $this->clearInstitutionCaches();

        return redirect()->route('institution.index')->with('status', "Program '{$program->name}' restored successfully.");
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

        $academicYear = null;
        $promotedCount = 0;

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
        });

        Cache::forget('filter_academic_years');
        Cache::forget('current_academic_year');

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
        $request->validate(['label' => 'required|string|max:50|unique:academic_years,label,' . $academic_year->id]);
        $academic_year->update(['label' => $request->label]);
        Cache::forget('filter_academic_years');
        Cache::forget('current_academic_year');

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
                'error' => "Cannot archive '{$academic_year->label}': it has a currently open or upcoming assessment schedule."
            ]);
        }

        $academic_year->update(['archived_at' => now()]);
        Cache::forget('filter_academic_years');
        Cache::forget('current_academic_year');

        return redirect()->route('institution.index')->with('status', "Academic Year '{$academic_year->label}' archived successfully.");
    }

    public function restoreAcademicYear(AcademicYear $academic_year)
    {
        $academic_year->update(['archived_at' => null]);
        Cache::forget('filter_academic_years');
        Cache::forget('current_academic_year');

        return redirect()->route('institution.index')->with('status', "Academic Year '{$academic_year->label}' restored successfully.");
    }

    public function setCurrentAcademicYear(Request $request, AcademicYear $academic_year, StudentPromotionService $promotionService)
    {
        if ($academic_year->is_current) {
            return redirect()->route('institution.index')->with('info', "Academic Year '{$academic_year->label}' is already the active academic year.");
        }

        if (! $academic_year->canBeSetActive()) {
            return redirect()->route('institution.index')->withErrors([
                'error' => "Cannot set '{$academic_year->label}' as active: only a later academic year can be activated."
            ]);
        }

        $promotedCount = 0;

        DB::transaction(function () use ($academic_year, &$promotedCount, $promotionService) {
            AcademicYear::where('is_current', true)->update(['is_current' => false]);
            $academic_year->update(['is_current' => true]);

            $promotedCount = $promotionService->promoteStudentsToNextYearLevel(auth()->id());
        });

        Cache::forget('filter_academic_years');
        Cache::forget('current_academic_year');

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
    }
}
