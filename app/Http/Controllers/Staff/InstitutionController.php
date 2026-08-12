<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Program;
use App\Models\AcademicYear;
use Illuminate\Http\Request;

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
        return redirect()->route('institution.index')->with('status', 'Department created successfully.');
    }

    public function updateDepartment(Request $request, Department $department)
    {
        $request->validate(['name' => 'required|string|max:255|unique:departments,name,' . $department->id]);
        $department->update($request->only('name'));
        return redirect()->route('institution.index')->with('status', 'Department updated successfully.');
    }

    public function destroyDepartment(Department $department)
    {
        if ($department->programs()->count() > 0) {
            return redirect()->route('institution.index')->withErrors(['error' => 'Cannot delete department: it still contains programs.']);
        }
        $department->delete();
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
        return redirect()->route('institution.index')->with('status', 'Program updated successfully.');
    }

    public function destroyProgram(Program $program)
    {
        if ($program->users()->count() > 0 || $program->assessmentSchedules()->count() > 0) {
            return redirect()->route('institution.index')->withErrors(['error' => 'Cannot delete program: it is currently referenced by users or schedules.']);
        }
        $program->delete();
        return redirect()->route('institution.index')->with('status', 'Program deleted successfully.');
    }

    public function storeAcademicYear(Request $request)
    {
        $request->validate(['label' => 'required|string|max:50|unique:academic_years']);
        AcademicYear::create(['label' => $request->label, 'is_current' => false]);
        return redirect()->route('institution.index')->with('status', 'Academic Year created successfully.');
    }

    public function updateAcademicYear(Request $request, AcademicYear $academic_year)
    {
        $request->validate(['label' => 'required|string|max:50|unique:academic_years,label,' . $academic_year->id]);
        $academic_year->update(['label' => $request->label]);
        return redirect()->route('institution.index')->with('status', 'Academic Year updated successfully.');
    }

    public function destroyAcademicYear(AcademicYear $academic_year)
    {
        if ($academic_year->assessmentSchedules()->count() > 0 || $academic_year->is_current) {
            return redirect()->route('institution.index')->withErrors(['error' => 'Cannot delete this academic year as it is currently active or has schedules.']);
        }
        $academic_year->delete();
        return redirect()->route('institution.index')->with('status', 'Academic Year deleted successfully.');
    }

    public function setCurrentAcademicYear(Request $request, AcademicYear $academic_year)
    {
        AcademicYear::where('is_current', true)->update(['is_current' => false]);
        $academic_year->update(['is_current' => true]);
        return redirect()->route('institution.index')->with('status', 'Active Academic Year has been set.');
    }
}
