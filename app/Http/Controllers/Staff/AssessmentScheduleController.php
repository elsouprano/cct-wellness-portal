<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AssessmentSchedule;
use App\Models\AcademicYear;
use Illuminate\Support\Facades\Cache;

class AssessmentScheduleController extends Controller
{
    public function index()
    {
        $schedules = AssessmentSchedule::with('academicYear')->orderBy('open_date', 'desc')->get();
        return view('staff.schedules.index', compact('schedules'));
    }

    public function create()
    {
        $academicYears = Cache::remember('filter_academic_years', 3600, fn() => AcademicYear::orderBy('label', 'desc')->get());
        $departments = Cache::remember('filter_departments_with_programs', 3600, fn() => \App\Models\Department::with('programs')->orderBy('name')->get());
        return view('staff.schedules.create', compact('academicYears', 'departments'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'year_level' => 'required|in:1st,2nd,3rd,4th',
            'program_id' => 'nullable|exists:programs,id',
            'open_date' => 'required|date',
            'open_time' => 'required|date_format:H:i',
            'close_date' => 'required|date|after_or_equal:open_date',
            'close_time' => 'required|date_format:H:i',
        ]);

        AssessmentSchedule::create($validated);

        return redirect()->route('schedules.index')->with('success', 'Schedule created successfully.');
    }

    public function edit(AssessmentSchedule $schedule)
    {
        $academicYears = Cache::remember('filter_academic_years', 3600, fn() => AcademicYear::orderBy('label', 'desc')->get());
        $departments = Cache::remember('filter_departments_with_programs', 3600, fn() => \App\Models\Department::with('programs')->orderBy('name')->get());
        return view('staff.schedules.edit', compact('schedule', 'academicYears', 'departments'));
    }

    public function update(Request $request, AssessmentSchedule $schedule)
    {
        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'year_level' => 'required|in:1st,2nd,3rd,4th',
            'program_id' => 'nullable|exists:programs,id',
            'open_date' => 'required|date',
            'open_time' => 'required|date_format:H:i',
            'close_date' => 'required|date|after_or_equal:open_date',
            'close_time' => 'required|date_format:H:i',
        ]);

        $schedule->update($validated);

        return redirect()->route('schedules.index')->with('success', 'Schedule updated successfully.');
    }

    public function destroy(AssessmentSchedule $schedule)
    {
        $schedule->delete();
        return redirect()->route('schedules.index')->with('success', 'Schedule deleted successfully.');
    }
}
