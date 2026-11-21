<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use App\Rules\InstitutionalEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;

class AccountController extends Controller
{
    public function index()
    {
        $counselors = User::where('role', 'guidance_counselor')
            ->with(['deactivatedBy', 'assignedDepartment', 'assignedDepartments'])
            ->orderBy('last_name')
            ->get();

        $departments = Department::notArchived()->orderBy('name')->get();

        return view('staff.accounts.index', compact('counselors', 'departments'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class, new InstitutionalEmail],
            'department_ids' => ['nullable', 'array'],
            'department_ids.*' => ['exists:departments,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
        ]);

        $activeDepartmentIds = Department::notArchived()->pluck('id')->all();

        $deptIds = array_map('intval', $request->input('department_ids', []));
        if ($request->filled('department_id')) {
            $singleId = (int) $request->department_id;
            if (! in_array($singleId, $deptIds, true)) {
                $deptIds[] = $singleId;
            }
        }

        // Filter out any archived departments
        $deptIds = array_values(array_intersect($deptIds, $activeDepartmentIds));

        // If the request omitted department selections without explicitly submitting the checklist,
        // default to all active departments on the backend as well.
        if (empty($deptIds) && ! $request->boolean('departments_submitted') && ! $request->has('department_ids') && ! $request->filled('department_id')) {
            $deptIds = $activeDepartmentIds;
        }

        if (empty($deptIds)) {
            return back()->withErrors(['department_id' => 'At least one assigned department is required.'])->withInput();
        }

        $primaryDeptId = $deptIds[0];

        $user = null;
        \Illuminate\Support\Facades\DB::transaction(function () use ($request, $primaryDeptId, $deptIds, &$user) {
            $user = User::forceCreate([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'department_id' => $primaryDeptId,
                'password' => Hash::make(Str::random(16)),
                'role' => 'guidance_counselor',
                'is_active' => true,
                'email_verified_at' => now(),
                'birthdate' => '1990-01-01',
                'program' => 'N/A',
                'section' => 'N/A',
                'contact_number' => 'N/A',
                'address_line1' => 'CCT',
                'city' => 'Tagaytay',
                'province' => 'Cavite',
            ]);

            $user->assignedDepartments()->sync($deptIds);

            app(\App\Services\ActivityLogService::class)->log(
                action: 'account.created',
                module: 'Account Management',
                description: "Created guidance counselor account for {$user->first_name} {$user->last_name} ({$user->email}).",
                subject: $user,
                newValues: [
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'department_ids' => $deptIds,
                ],
                critical: true,
            );
        });

        User::clearRequestScopeCache();

        Password::broker()->sendResetLink(['email' => $user->email]);

        return redirect()->route('manage.accounts.index')->with('success', 'Guidance counselor created. A password setup link has been sent to their email.');
    }

    public function update(Request $request, User $user)
    {
        if ($user->role !== 'guidance_counselor') {
            abort(403);
        }

        $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class.',email,'.$user->id, new InstitutionalEmail],
            'department_ids' => ['nullable', 'array'],
            'department_ids.*' => ['exists:departments,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
        ]);

        $activeDepartmentIds = Department::notArchived()->pluck('id')->all();

        $deptIds = array_map('intval', $request->input('department_ids', []));
        if ($request->filled('department_id')) {
            $singleId = (int) $request->department_id;
            if (! in_array($singleId, $deptIds, true)) {
                $deptIds[] = $singleId;
            }
        }

        $deptIds = array_values(array_intersect($deptIds, $activeDepartmentIds));

        if (empty($deptIds)) {
            return back()->withErrors(['department_id' => 'At least one assigned department is required.'])->withInput();
        }

        $primaryDeptId = $deptIds[0];

        $oldValues = [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'department_id' => $user->department_id,
            'department_ids' => $user->assignedDepartments()->pluck('departments.id')->all(),
        ];

        \Illuminate\Support\Facades\DB::transaction(function () use ($request, $user, $primaryDeptId, $deptIds, $oldValues) {
            $user->update([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'department_id' => $primaryDeptId,
            ]);

            $user->assignedDepartments()->sync($deptIds);

            app(\App\Services\ActivityLogService::class)->log(
                action: 'account.updated',
                module: 'Account Management',
                description: "Updated guidance counselor account and department assignment for {$user->first_name} {$user->last_name}.",
                subject: $user,
                oldValues: $oldValues,
                newValues: [
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'department_id' => $primaryDeptId,
                    'department_ids' => array_map('intval', $deptIds),
                ],
                critical: true,
            );
        });

        User::clearRequestScopeCache();

        return redirect()->route('manage.accounts.index')->with('success', 'Counselor account updated successfully.');
    }

    public function toggleStatus(User $user)
    {
        if ($user->role !== 'guidance_counselor') {
            abort(403);
        }

        $message = '';
        \Illuminate\Support\Facades\DB::transaction(function () use ($user, &$message) {
            if ($user->is_active) {
                $user->update([
                    'is_active' => false,
                    'deactivated_at' => now(),
                    'deactivated_by' => auth()->id(),
                ]);
                app(\App\Services\ActivityLogService::class)->log(
                    action: 'account.deactivated',
                    module: 'Account Management',
                    description: "Deactivated counselor account for {$user->first_name} {$user->last_name}.",
                    subject: $user,
                    oldValues: ['is_active' => true],
                    newValues: ['is_active' => false],
                    critical: true,
                );
                $message = 'Counselor account deactivated successfully.';
            } else {
                $user->update([
                    'is_active' => true,
                    'deactivated_at' => null,
                    'deactivated_by' => null,
                ]);
                app(\App\Services\ActivityLogService::class)->log(
                    action: 'account.restored',
                    module: 'Account Management',
                    description: "Reactivated counselor account for {$user->first_name} {$user->last_name}.",
                    subject: $user,
                    oldValues: ['is_active' => false],
                    newValues: ['is_active' => true],
                    critical: true,
                );
                $message = 'Counselor account reactivated successfully.';
            }
        });

        return redirect()->route('manage.accounts.index')->with('success', $message);
    }

    public function triggerPasswordReset(User $user)
    {
        if ($user->role !== 'guidance_counselor') {
            abort(403);
        }

        Password::broker()->sendResetLink(['email' => $user->email]);

        app(\App\Services\ActivityLogService::class)->log(
            action: 'account.password_reset_sent',
            module: 'Account Management',
            description: "Sent administrative password reset link to {$user->first_name} {$user->last_name} ({$user->email}).",
            subject: $user,
        );

        return redirect()->route('manage.accounts.index')->with('success', 'Password reset link sent to counselor.');
    }
}
