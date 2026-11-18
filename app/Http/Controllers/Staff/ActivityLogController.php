<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
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
            'module' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', 'in:system_admin,guidance_counselor,student,system,guest'],
            'action' => ['nullable', 'string', 'max:100'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'sort' => ['nullable', 'in:newest,oldest'],
            'per_page' => ['nullable', 'integer', 'in:10,15,25,50'],
        ], [
            'search.max' => 'Please enter a shorter search term (up to 100 characters).',
            'role.in' => 'Please choose a valid user role from the list.',
            'date_from.date_format' => 'Please select a valid start date.',
            'date_to.date_format' => 'Please select a valid end date.',
            'date_to.after_or_equal' => 'The end date must be the same as or after the start date.',
            'sort.in' => 'Please choose a valid sort order.',
            'per_page.in' => 'Please choose 10, 15, 25, or 50 rows per page.',
        ]);

        $search = trim((string) ($validated['search'] ?? ''));
        $module = $validated['module'] ?? '';
        $role = $validated['role'] ?? '';
        $action = $validated['action'] ?? '';
        $dateFrom = $validated['date_from'] ?? '';
        $dateTo = $validated['date_to'] ?? '';
        $sort = $validated['sort'] ?? 'newest';
        $perPage = (int) ($validated['per_page'] ?? 15);

        $query = ActivityLog::query();

        if ($search !== '') {
            $escaped = addcslashes($search, '%_\\');
            $like = "%{$escaped}%";
            $query->where(function ($q) use ($search, $like) {
                $q->where('actor_name', 'like', $like)
                    ->orWhere('action', 'like', $like)
                    ->orWhere('description', 'like', $like);

                if (ctype_digit($search)) {
                    $q->orWhere('actor_id', (int) $search)
                      ->orWhere('subject_id', (int) $search);
                }
            });
        }

        if ($module !== '') {
            $query->where('module', $module);
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

        $modules = ActivityLog::query()->select('module')->distinct()->orderBy('module')->pluck('module');
        $actionOptions = ActivityLog::filterActionOptions();

        return view('staff.activity-logs.index', compact(
            'logs',
            'modules',
            'actionOptions',
            'search',
            'module',
            'role',
            'action',
            'dateFrom',
            'dateTo',
            'sort',
            'perPage'
        ));
    }

    public function show(ActivityLog $activityLog)
    {
        abort_if(! auth()->user()?->isAdmin(), 403, 'Unauthorized.');

        $activityLog->load('actor');

        return view('staff.activity-logs.show', compact('activityLog'));
    }
}

