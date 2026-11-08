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
    public function index(Request $request)
    {
        $user = auth()->user();
        $viewArchived = $request->boolean('archived');

        $activeCount = AssessmentSchedule::visibleToStaff($user)->activeSchedules()->count();
        $archivedCount = AssessmentSchedule::visibleToStaff($user)->archivedSchedules()->count();

        $query = AssessmentSchedule::visibleToStaff($user)
            ->with(['academicYear', 'structuredProgram.department']);

        if ($viewArchived) {
            $schedules = $query->archivedSchedules()
                ->orderBy('close_date', 'desc')
                ->orderBy('close_time', 'desc')
                ->paginate(15)
                ->withQueryString();
        } else {
            $schedules = $query->activeSchedules()
                ->orderBy('open_date', 'asc')
                ->orderBy('open_time', 'asc')
                ->paginate(15)
                ->withQueryString();
        }

        return view('staff.schedules.index', compact('schedules', 'viewArchived', 'activeCount', 'archivedCount'));
    }

    public function create()
    {
        $user = auth()->user();
        if ($user->isCounselor() && ! $user->department_id) {
            abort(403, 'No school assigned yet. Please contact the system administrator.');
        }

        $academicYears = Cache::remember('filter_academic_years', 3600, fn () => AcademicYear::notArchived()->orderBy('label', 'desc')->get());
        
        if ($user->isCounselor()) {
            $departments = \App\Models\Department::notArchived()
                ->with(['programs' => fn($q) => $q->notArchived()])
                ->where('id', $user->department_id)
                ->get();
        } else {
            $departments = Cache::remember('filter_departments_with_programs', 3600, fn () => \App\Models\Department::notArchived()->with(['programs' => fn($q) => $q->notArchived()])->orderBy('name')->get());
        }

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
        $user = auth()->user();
        if (! $user->canManageSchedule($schedule)) {
            abort(404);
        }

        if ($schedule->isArchived()) {
            abort(403, 'Archived schedules cannot be edited.');
        }

        $academicYears = Cache::remember('filter_academic_years', 3600, fn () => AcademicYear::notArchived()->orderBy('label', 'desc')->get());
        
        if ($user->isCounselor()) {
            $departments = \App\Models\Department::notArchived()
                ->with(['programs' => fn($q) => $q->notArchived()])
                ->where('id', $user->department_id)
                ->get();
        } else {
            $departments = Cache::remember('filter_departments_with_programs', 3600, fn () => \App\Models\Department::notArchived()->with(['programs' => fn($q) => $q->notArchived()])->orderBy('name')->get());
        }

        return view('staff.schedules.edit', compact('schedule', 'academicYears', 'departments'));
    }

    public function update(AssessmentScheduleRequest $request, AssessmentSchedule $schedule)
    {
        $user = auth()->user();
        if (! $user->canManageSchedule($schedule)) {
            abort(404);
        }

        if ($schedule->isArchived()) {
            abort(403, 'Archived schedules cannot be edited.');
        }

        $validated = $request->validated();

        $schedule->update($validated);

        return redirect()->route('schedules.index')->with('success', 'Schedule updated successfully.');
    }
}
