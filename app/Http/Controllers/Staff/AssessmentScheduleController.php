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
        $allowedDeptIds = $user->isCounselor() ? $user->getAllowedDepartmentIds() : [];
        if ($user->isCounselor() && empty($allowedDeptIds)) {
            abort(403, 'No school assigned yet. Please contact the system administrator.');
        }

        $academicYears = Cache::remember('filter_academic_years_active', 3600, fn () => AcademicYear::notArchived()->orderBy('label', 'desc')->get());
        
        if ($user->isCounselor()) {
            $departments = \App\Models\Department::notArchived()
                ->with(['programs' => fn($q) => $q->notArchived()])
                ->whereIn('id', $allowedDeptIds)
                ->orderBy('name')
                ->get();
        } else {
            $departments = Cache::remember('filter_departments_with_programs', 3600, fn () => \App\Models\Department::notArchived()->with(['programs' => fn($q) => $q->notArchived()])->orderBy('name')->get());
        }

        return view('staff.schedules.create', compact('academicYears', 'departments'));
    }

    public function store(AssessmentScheduleRequest $request)
    {
        $validated = $request->validated();

        $schedule = AssessmentSchedule::create($validated);

        app(\App\Services\ActivityLogService::class)->log(
            action: 'schedule.created',
            module: 'Assessment Schedules',
            description: "Created assessment schedule for {$schedule->year_level} Year ({$schedule->open_date} {$schedule->open_time} to {$schedule->close_date} {$schedule->close_time}).",
            subject: $schedule,
            newValues: [
                'academic_year_id' => $schedule->academic_year_id,
                'year_level' => $schedule->year_level,
                'program_id' => $schedule->program_id,
                'open_date' => $schedule->open_date,
                'open_time' => $schedule->open_time,
                'close_date' => $schedule->close_date,
                'close_time' => $schedule->close_time,
            ],
        );

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

        $academicYears = Cache::remember('filter_academic_years_active', 3600, fn () => AcademicYear::notArchived()->orderBy('label', 'desc')->get());
        
        if ($user->isCounselor()) {
            $allowedDeptIds = $user->getAllowedDepartmentIds();
            $departments = \App\Models\Department::notArchived()
                ->with(['programs' => fn($q) => $q->notArchived()])
                ->whereIn('id', $allowedDeptIds)
                ->orderBy('name')
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
        $oldValues = $schedule->only(['academic_year_id', 'year_level', 'program_id', 'open_date', 'open_time', 'close_date', 'close_time']);

        $schedule->update($validated);

        app(\App\Services\ActivityLogService::class)->log(
            action: 'schedule.updated',
            module: 'Assessment Schedules',
            description: "Updated assessment schedule for {$schedule->year_level} Year ({$schedule->open_date} to {$schedule->close_date}).",
            subject: $schedule,
            oldValues: $oldValues,
            newValues: $schedule->only(['academic_year_id', 'year_level', 'program_id', 'open_date', 'open_time', 'close_date', 'close_time']),
        );

        return redirect()->route('schedules.index')->with('success', 'Schedule updated successfully.');
    }
}
