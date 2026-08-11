<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Program;
use Illuminate\Http\Request;

class InstitutionController extends Controller
{
    public function index()
    {
        $departments = Department::with('programs')->orderBy('name')->get();
        return view('staff.institution.index', compact('departments'));
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
}
