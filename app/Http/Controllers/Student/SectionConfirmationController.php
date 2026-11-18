<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\YearLevelAuditLog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SectionConfirmationController extends Controller
{
    /**
     * Show the prompt asking the student to select their section.
     */
    public function show()
    {
        $user = auth()->user();

        // If user does not need confirmation, redirect to dashboard
        if (! $user || $user->role !== 'student' || ! $user->needs_section_confirmation) {
            return redirect()->route('dashboard');
        }

        $validSections = $user->getValidSections();

        return view('student.confirm-section', compact('user', 'validSections'));
    }

    /**
     * Store the chosen section, clear the confirmation flag, and log the action.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        if (! $user || $user->role !== 'student' || ! $user->needs_section_confirmation) {
            return redirect()->route('dashboard');
        }

        $validSections = $user->getValidSections();

        $validated = $request->validate([
            'section' => [
                'required',
                'string',
                Rule::in($validSections),
            ],
        ], [
            'section.required' => 'Please select your section to continue.',
            'section.in' => 'The selected section is not valid for your program and year level.',
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($user, $validated) {
            $oldSection = $user->section;
            $user->section = $validated['section'];
            $user->needs_section_confirmation = false;
            $user->save();

            $auditLog = YearLevelAuditLog::create([
                'user_id' => $user->id,
                'actor_id' => $user->id,
                'action' => 'student_section_confirmation',
                'old_year_level' => $user->year_level,
                'new_year_level' => $user->year_level,
                'old_section' => $oldSection,
                'new_section' => $user->section,
            ]);

            app(\App\Services\ActivityLogService::class)->log(
                action: 'student.section_confirmed',
                module: 'Student Management',
                description: "{$user->first_name} {$user->last_name} confirmed section {$user->section} ({$user->year_level} Year).",
                subject: $user,
                oldValues: ['section' => $oldSection],
                newValues: ['section' => $user->section, 'year_level' => $user->year_level],
                actor: $user,
                sourceType: YearLevelAuditLog::class,
                sourceId: $auditLog->id,
                critical: true,
            );
        });

        return redirect()->route('dashboard')->with('success', "Section {$user->section} successfully confirmed! Welcome to your {$user->year_level} Year.");
    }
}
