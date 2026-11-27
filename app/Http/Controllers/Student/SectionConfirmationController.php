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

        $confirmedSection = null;
        $confirmedYearLevel = $user->year_level;

        \Illuminate\Support\Facades\DB::transaction(function () use ($user, $validated, &$confirmedSection, &$confirmedYearLevel) {
            $lockedUser = \App\Models\User::whereKey($user->id)->lockForUpdate()->firstOrFail();

            if (! $lockedUser->needs_section_confirmation) {
                $confirmedSection = $lockedUser->section;
                $confirmedYearLevel = $lockedUser->year_level;

                return;
            }

            $oldSection = $lockedUser->section;
            $lockedUser->section = $validated['section'];
            $lockedUser->needs_section_confirmation = false;
            $lockedUser->save();

            $confirmedSection = $lockedUser->section;
            $confirmedYearLevel = $lockedUser->year_level;

            YearLevelAuditLog::create([
                'user_id' => $lockedUser->id,
                'actor_id' => $lockedUser->id,
                'action' => 'student_section_confirmation',
                'old_year_level' => $lockedUser->year_level,
                'new_year_level' => $lockedUser->year_level,
                'old_section' => $oldSection,
                'new_section' => $lockedUser->section,
            ]);
        });

        return redirect()->route('dashboard')->with('success', "Section {$confirmedSection} successfully confirmed! Welcome to your {$confirmedYearLevel} Year.");
    }
}
