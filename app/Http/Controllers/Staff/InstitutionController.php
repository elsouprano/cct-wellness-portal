<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\Program;
use App\Services\StudentPromotionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class InstitutionController extends Controller
{
    public function index()
    {
        $departments = Department::with('programs')->orderBy('name')->get();
        $academicYears = AcademicYear::orderByDesc('label')->get();
        return view('staff.institution.index', compact('departments', 'academicYears'));
    }

    public function storeDepartment(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255|unique:departments']);
        Department::create($request->only('name'));
        Cache::forget('filter_departments');
        Cache::forget('filter_departments_with_programs');
        return redirect()->route('institution.index')->with('status', 'Department created successfully.');
    }

    public function updateDepartment(Request $request, Department $department)
    {
        $request->validate(['name' => 'required|string|max:255|unique:departments,name,' . $department->id]);
        $department->update($request->only('name'));
        Cache::forget('filter_departments');
        Cache::forget('filter_departments_with_programs');
        return redirect()->route('institution.index')->with('status', 'Department updated successfully.');
    }

    public function destroyDepartment(Department $department)
    {
        if ($department->programs()->count() > 0) {
            return redirect()->route('institution.index')->withErrors(['error' => 'Cannot delete department: it still contains programs.']);
        }
        if ($department->counselors()->count() > 0) {
            return redirect()->route('institution.index')->withErrors(['error' => 'Cannot delete department: it still has counselors assigned.']);
        }
        $department->delete();
        Cache::forget('filter_departments');
        Cache::forget('filter_departments_with_programs');
        return redirect()->route('institution.index')->with('status', 'Department deleted successfully.');
    }

    public function storeProgram(Request $request)
    {
        $request->validate([
            'department_id' => 'required|exists:departments,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50'
        ]);
        Program::create($request->only('department_id', 'name', 'code'));
        Cache::forget('filter_programs');
        Cache::forget('filter_departments_with_programs');
        return redirect()->route('institution.index')->with('status', 'Program created successfully.');
    }

    public function updateProgram(Request $request, Program $program)
    {
        $request->validate([
            'department_id' => 'required|exists:departments,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50'
        ]);
        $program->update($request->only('department_id', 'name', 'code'));
        Cache::forget('filter_programs');
        Cache::forget('filter_departments_with_programs');
        return redirect()->route('institution.index')->with('status', 'Program updated successfully.');
    }

    public function destroyProgram(Program $program)
    {
        if ($program->users()->count() > 0 || $program->assessmentSchedules()->count() > 0) {
            return redirect()->route('institution.index')->withErrors(['error' => 'Cannot delete program: it is currently referenced by users or schedules.']);
        }
        $program->delete();
        Cache::forget('filter_programs');
        Cache::forget('filter_departments_with_programs');
        return redirect()->route('institution.index')->with('status', 'Program deleted successfully.');
    }

    public function storeAcademicYear(Request $request, StudentPromotionService $promotionService)
    {
        $request->validate([
            'label' => 'required|string|max:50|unique:academic_years',
            'set_as_current' => 'nullable|boolean',
        ]);

        $setAsCurrent = $request->boolean('set_as_current');
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
        if ($academic_year->assessmentSchedules()->count() > 0 || $academic_year->is_current) {
            return redirect()->route('institution.index')->withErrors(['error' => 'Cannot delete this academic year as it is currently active or has schedules.']);
        }
        $academic_year->delete();
        Cache::forget('filter_academic_years');
        Cache::forget('current_academic_year');
        return redirect()->route('institution.index')->with('status', 'Academic Year deleted successfully.');
    }

    public function setCurrentAcademicYear(Request $request, AcademicYear $academic_year, StudentPromotionService $promotionService)
    {
        if ($academic_year->is_current) {
            return redirect()->route('institution.index')->with('info', "Academic Year '{$academic_year->label}' is already the active academic year.");
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
}
