<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\InstitutionalEmail;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use App\Models\YearLevelAuditLog;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        $departments = \App\Models\Department::with('programs')->orderBy('name')->get();
        return view('auth.register', compact('departments'));
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'middle_initial' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'birthdate' => ['required', 'date'],
            'program_id' => ['required', 'integer', 'exists:programs,id'],
            'section' => ['required', 'string', 'max:255'],
            'contact_number' => ['required', 'string', 'max:255'],
            'address_line1' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'province' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class, new InstitutionalEmail],
            'student_id' => ['required', 'string', 'unique:'.User::class.',student_id'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'year_level' => ['required', 'string', 'in:1st,2nd,3rd,4th'],
        ]);

        $program = \App\Models\Program::find($request->program_id);

        $user = User::forceCreate([
            'first_name' => $request->first_name,
            'middle_initial' => $request->middle_initial,
            'last_name' => $request->last_name,
            'birthdate' => $request->birthdate,
            'program_id' => $request->program_id,
            'program' => $program ? $program->code : '',
            'section' => $request->section,
            'contact_number' => $request->contact_number,
            'address_line1' => $request->address_line1,
            'city' => $request->city,
            'province' => $request->province,
            'role' => 'student',
            'is_paying_student' => false,
            'email' => $request->email,
            'student_id' => $request->student_id,
            'password' => Hash::make($request->password),
            'year_level' => $request->year_level,
            'year_level_confirmed' => true,
        ]);

        YearLevelAuditLog::create([
            'user_id' => $user->id,
            'actor_id' => $user->id,
            'action' => 'registration',
            'old_year_level' => null,
            'new_year_level' => $request->year_level,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
