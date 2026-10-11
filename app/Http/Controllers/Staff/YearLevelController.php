<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\YearLevelAuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class YearLevelController extends Controller
{
    public function index(Request $request)
    {
        $query = User::where('role', 'student');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('program', 'like', "%{$search}%")
                  ->orWhere('section', 'like', "%{$search}%");
            });
        }

        if ($request->filled('year_level')) {
            if ($request->year_level === 'unconfirmed') {
                $query->where('year_level_confirmed', false);
            } else {
                $query->where('year_level', $request->year_level);
            }
        }

        $students = $query->orderBy('last_name')->paginate(20)->withQueryString();

        return view('staff.year-level.index', compact('students'));
    }

    public function bulkPromote(Request $request)
    {
        $students = User::where('role', 'student')->where(function($q) {
            $q->where('year_level', '!=', '4th')->orWhereNull('year_level');
        })->get();
        $affectedCount = 0;
        $manualSectionUpdates = 0;

        DB::transaction(function () use ($students, $request, &$affectedCount, &$manualSectionUpdates) {
            foreach ($students as $student) {
                $oldLevel = $student->year_level;
                $newLevel = match ($oldLevel) {
                    '1st' => '2nd',
                    '2nd' => '3rd',
                    '3rd' => '4th',
                    default => '1st', // If null, assume 1st
                };

                $oldSection = $student->section;
                if (!$student->syncSectionToYearLevel($newLevel)) {
                    $manualSectionUpdates++;
                }

                $student->year_level = $newLevel;
                $student->year_level_confirmed = true;
                $student->save();

                YearLevelAuditLog::create([
                    'user_id' => $student->id,
                    'actor_id' => $request->user()->id,
                    'action' => 'bulk_promote',
                    'old_year_level' => $oldLevel,
                    'new_year_level' => $newLevel,
                    'old_section' => $oldSection,
                    'new_section' => $student->section,
                ]);

                $affectedCount++;
            }
        });

        $message = "Successfully promoted {$affectedCount} students.";
        if ($manualSectionUpdates > 0) {
            $message .= " {$manualSectionUpdates} students need their section updated manually.";
        }

        return redirect()->route('year-levels.index')->with($manualSectionUpdates > 0 ? 'warning' : 'success', $message);
    }

    public function override(Request $request, User $user)
    {
        if ($user->role !== 'student') {
            return back()->withErrors(['error' => 'Can only update students.']);
        }

        $validated = $request->validate([
            'year_level' => 'required|in:1st,2nd,3rd,4th'
        ]);

        $oldLevel = $user->year_level;
        $newLevel = $validated['year_level'];

        if ($oldLevel !== $newLevel || !$user->year_level_confirmed) {
            $manualUpdateNeeded = false;
            $oldSection = $user->section;
            if ($oldLevel !== $newLevel) {
                if (!$user->syncSectionToYearLevel($newLevel)) {
                    $manualUpdateNeeded = true;
                }
            }

            DB::transaction(function () use ($user, $request, $oldLevel, $newLevel, $oldSection) {
                $user->year_level = $newLevel;
                $user->year_level_confirmed = true;
                $user->save();

                YearLevelAuditLog::create([
                    'user_id' => $user->id,
                    'actor_id' => $request->user()->id,
                    'action' => 'individual_override',
                    'old_year_level' => $oldLevel,
                    'new_year_level' => $newLevel,
                    'old_section' => $oldSection,
                    'new_section' => $user->section,
                ]);
            });
            
            $msg = "Updated year level for {$user->first_name} {$user->last_name}.";
            if ($manualUpdateNeeded) {
                return back()->with('warning', $msg . " The section was left unchanged and needs manual update.");
            }
            return back()->with('success', $msg);
        }

        return back();
    }

    public function audit()
    {
        $logs = YearLevelAuditLog::with(['user', 'actor'])->latest()->paginate(50);
        return view('staff.year-level.audit', compact('logs'));
    }
}
