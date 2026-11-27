<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request, ActivityLogService $activityLogService)
    {
        abort_if(! auth()->user()?->isAdmin(), 403, 'Unauthorized.');

        $activityLogService->syncHistoricalAuditLogs();

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'person' => ['nullable', 'integer'],
            'area' => ['nullable', 'string', 'max:100'],
            'module' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', 'in:system_admin,guidance_counselor'],
            'action' => ['nullable', 'string', 'max:100'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'sort' => ['nullable', 'in:newest,oldest'],
            'per_page' => ['nullable', 'integer', 'in:10,15,25,50'],
        ], [
            'search.max' => 'Please enter a shorter search term (up to 100 characters).',
            'person.integer' => 'Please choose a valid staff member from the list.',
            'role.in' => 'Please choose a valid staff role from the list.',
            'date_from.date_format' => 'Please select a valid start date.',
            'date_to.date_format' => 'Please select a valid end date.',
            'date_to.after_or_equal' => 'The end date must be the same as or after the start date.',
            'sort.in' => 'Please choose a valid sort order.',
            'per_page.in' => 'Please choose 10, 15, 25, or 50 rows per page.',
        ]);

        $search = trim((string) ($validated['search'] ?? ''));
        $person = isset($validated['person']) ? (int) $validated['person'] : null;
        $area = (string) ($validated['area'] ?? '');
        $module = (string) ($validated['module'] ?? '');
        $effectiveArea = $area !== '' ? $area : $module;
        $role = (string) ($validated['role'] ?? '');
        $action = (string) ($validated['action'] ?? '');
        $dateFrom = (string) ($validated['date_from'] ?? '');
        $dateTo = (string) ($validated['date_to'] ?? '');
        $sort = (string) ($validated['sort'] ?? 'newest');
        $perPage = (int) ($validated['per_page'] ?? 15);

        $query = ActivityLog::staffOnly()->with('actor');

        if ($person !== null) {
            $query->where('actor_id', $person);
        }

        if ($search !== '') {
            $escaped = addcslashes($search, '%_\\');
            $like = "%{$escaped}%";
            $query->where(function ($q) use ($like) {
                $q->where('actor_name', 'like', $like)
                  ->orWhere('description', 'like', $like);
            });
        }

        if ($effectiveArea !== '') {
            $query->inArea($effectiveArea);
        }

        if ($role !== '') {
            $query->where('actor_role', $role);
        }

        if ($action !== '') {
            $query->where('action', $action);
        }

        if ($dateFrom !== '') {
            $query->where('created_at', '>=', $dateFrom . ' 00:00:00');
        }

        if ($dateTo !== '') {
            $query->where('created_at', '<=', $dateTo . ' 23:59:59');
        }

        $query->orderBy('created_at', $sort === 'oldest' ? 'asc' : 'desc')
              ->orderBy('id', $sort === 'oldest' ? 'asc' : 'desc');

        $logs = $query->paginate($perPage)->withQueryString();

        $staffPeople = User::whereIn('role', ActivityLog::STAFF_ROLES)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'role']);

        $areas = ActivityLog::AREAS;
        $actionOptions = ActivityLog::filterActionOptions();
        $isCounselorView = false;

        return view('staff.activity-logs.index', [
            'logs' => $logs,
            'staffPeople' => $staffPeople,
            'areas' => $areas,
            'actionOptions' => $actionOptions,
            'search' => $search,
            'person' => $person,
            'area' => $effectiveArea,
            'role' => $role,
            'action' => $action,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'sort' => $sort,
            'perPage' => $perPage,
            'isCounselorView' => $isCounselorView,
        ]);
    }

    public function show(ActivityLog $activityLog)
    {
        abort_if(! auth()->user()?->isAdmin(), 403, 'Unauthorized.');
        abort_if(! in_array($activityLog->actor_role, ActivityLog::STAFF_ROLES, true), 404);

        $activityLog->load('actor');

        return view('staff.activity-logs.show', [
            'activityLog' => $activityLog,
            'isCounselorView' => false,
        ]);
    }

    public function myActivity(Request $request, ActivityLogService $activityLogService)
    {
        $user = auth()->user();
        abort_if(! $user || (! $user->isCounselor() && ! $user->isAdmin()), 403, 'Unauthorized.');

        $activityLogService->syncHistoricalAuditLogs();

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'area' => ['nullable', 'string', 'max:100'],
            'action' => ['nullable', 'string', 'max:100'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'sort' => ['nullable', 'in:newest,oldest'],
            'per_page' => ['nullable', 'integer', 'in:10,15,25,50'],
        ], [
            'search.max' => 'Please enter a shorter search term (up to 100 characters).',
            'date_from.date_format' => 'Please select a valid start date.',
            'date_to.date_format' => 'Please select a valid end date.',
            'date_to.after_or_equal' => 'The end date must be the same as or after the start date.',
            'sort.in' => 'Please choose a valid sort order.',
            'per_page.in' => 'Please choose 10, 15, 25, or 50 rows per page.',
        ]);

        $search = trim((string) ($validated['search'] ?? ''));
        $area = (string) ($validated['area'] ?? '');
        $action = (string) ($validated['action'] ?? '');
        $dateFrom = (string) ($validated['date_from'] ?? '');
        $dateTo = (string) ($validated['date_to'] ?? '');
        $sort = (string) ($validated['sort'] ?? 'newest');
        $perPage = (int) ($validated['per_page'] ?? 15);

        $query = ActivityLog::staffOnly()
            ->where('actor_id', $user->id)
            ->with('actor');

        if ($search !== '') {
            $escaped = addcslashes($search, '%_\\');
            $like = "%{$escaped}%";
            $query->where('description', 'like', $like);
        }

        if ($area !== '') {
            $query->inArea($area);
        }

        if ($action !== '') {
            $query->where('action', $action);
        }

        if ($dateFrom !== '') {
            $query->where('created_at', '>=', $dateFrom . ' 00:00:00');
        }

        if ($dateTo !== '') {
            $query->where('created_at', '<=', $dateTo . ' 23:59:59');
        }

        $query->orderBy('created_at', $sort === 'oldest' ? 'asc' : 'desc')
              ->orderBy('id', $sort === 'oldest' ? 'asc' : 'desc');

        $logs = $query->paginate($perPage)->withQueryString();
        $areas = ActivityLog::AREAS;
        $actionOptions = ActivityLog::filterActionOptions($user->id);

        return view('staff.activity-logs.index', [
            'logs' => $logs,
            'staffPeople' => collect(),
            'areas' => $areas,
            'actionOptions' => $actionOptions,
            'search' => $search,
            'person' => null,
            'area' => $area,
            'role' => '',
            'action' => $action,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'sort' => $sort,
            'perPage' => $perPage,
            'isCounselorView' => true,
        ]);
    }

    public function showMyActivity(ActivityLog $activityLog)
    {
        $user = auth()->user();
        abort_if(! $user || (! $user->isCounselor() && ! $user->isAdmin()), 403, 'Unauthorized.');
        abort_if(
            $activityLog->actor_id !== $user->id
            || ! in_array($activityLog->actor_role, ActivityLog::STAFF_ROLES, true),
            404
        );

        $activityLog->load('actor');

        return view('staff.activity-logs.show', [
            'activityLog' => $activityLog,
            'isCounselorView' => true,
        ]);
    }
}
