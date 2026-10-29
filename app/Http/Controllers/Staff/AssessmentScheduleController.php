<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssessmentScheduleRequest;
use Illuminate\Http\Request;
use App\Models\AssessmentSchedule;
use App\Models\AcademicYear;
use Illuminate\Support\Facades\Cache;

class AssessmentScheduleController extends Controller
{
    public function index()
    {
        $schedules = AssessmentSchedule::with('academicYear')->orderBy('open_date', 'desc')->paginate(15);
        return view('staff.schedules.index', compact('schedules'));
    }

    public function create()
    {
        $academicYears = Cache::remember('filter_academic_years', 3600, fn() => AcademicYear::orderBy('label', 'desc')->get());
        $departments = Cache::remember('filter_departments_with_programs', 3600, fn() => \App\Models\Department::with('programs')->orderBy('name')->get());
        return view('staff.schedules.create', compact('academicYears', 'departments'));
    }

    public function store(AssessmentScheduleRequest $request)
    {
        $validated = $request->validated();

        AssessmentSchedule::create($validated);

        return redirect()->route('schedules.index')->with('success', 'Schedule created successfully.');
    }

    public function edit(AssessmentSchedule $schedule)
    {
        $academicYears = Cache::remember('filter_academic_years', 3600, fn() => AcademicYear::orderBy('label', 'desc')->get());
        $departments = Cache::remember('filter_departments_with_programs', 3600, fn() => \App\Models\Department::with('programs')->orderBy('name')->get());
        return view('staff.schedules.edit', compact('schedule', 'academicYears', 'departments'));
    }

    public function update(AssessmentScheduleRequest $request, AssessmentSchedule $schedule)
    {
        $validated = $request->validated();

        $schedule->update($validated);

        return redirect()->route('schedules.index')->with('success', 'Schedule updated successfully.');
    }

    public function destroy(AssessmentSchedule $schedule)
    {
        $schedule->delete();
        return redirect()->route('schedules.index')->with('success', 'Schedule deleted successfully.');
    }
}
