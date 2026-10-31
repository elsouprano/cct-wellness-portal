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

        $departments = Department::orderBy('name')->get();

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

        $deptIds = $request->input('department_ids', []);
        if ($request->filled('department_id') && !in_array($request->department_id, $deptIds)) {
            $deptIds[] = (int) $request->department_id;
        }

        if (empty($deptIds)) {
            return back()->withErrors(['department_id' => 'At least one assigned department is required.'])->withInput();
        }

        $primaryDeptId = $deptIds[0];

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

        $deptIds = $request->input('department_ids', []);
        if ($request->filled('department_id') && !in_array($request->department_id, $deptIds)) {
            $deptIds[] = (int) $request->department_id;
        }

        if (empty($deptIds)) {
            return back()->withErrors(['department_id' => 'At least one assigned department is required.'])->withInput();
        }

        $primaryDeptId = $deptIds[0];

        $user->update([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'department_id' => $primaryDeptId,
        ]);

        $user->assignedDepartments()->sync($deptIds);

        return redirect()->route('manage.accounts.index')->with('success', 'Counselor account updated successfully.');
    }

    public function toggleStatus(User $user)
    {
        if ($user->role !== 'guidance_counselor') {
            abort(403);
        }

        if ($user->is_active) {
            $user->update([
                'is_active' => false,
                'deactivated_at' => now(),
                'deactivated_by' => auth()->id(),
            ]);
            $message = 'Counselor account deactivated successfully.';
        } else {
            $user->update([
                'is_active' => true,
                'deactivated_at' => null,
                'deactivated_by' => null,
            ]);
            $message = 'Counselor account reactivated successfully.';
        }

        return redirect()->route('manage.accounts.index')->with('success', $message);
    }

    public function triggerPasswordReset(User $user)
    {
        if ($user->role !== 'guidance_counselor') {
            abort(403);
        }

        Password::broker()->sendResetLink(['email' => $user->email]);

        return redirect()->route('manage.accounts.index')->with('success', 'Password reset link sent to counselor.');
    }
}
