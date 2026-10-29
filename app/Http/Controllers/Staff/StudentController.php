<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    /**
     * Display a listing of students.
     */
    public function index(Request $request)
    {
        $status = $request->input('status', 'active');

        $query = User::where('role', 'student')
            ->with('structuredProgram.department');

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'archived') {
            $query->where('is_active', false);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('student_id', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $students = $query->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(15)
            ->withQueryString();

        return view('staff.students.index', compact('students', 'search', 'status'));
    }

    /**
     * Display the specified student's profile.
     */
    public function show(User $student)
    {
        // Ensure the user is a student
        if ($student->role !== 'student') {
            abort(404);
        }

        $student->load([
            'structuredProgram.department',
            'inventorySubmissions' => function ($q) {
                $q->latest('submitted_at');
            },
            'inventorySubmissions.flags',
            'inventorySubmissions.scores',
        ]);

        $latestSubmission = $student->inventorySubmissions->first();

        return view('staff.students.show', compact('student', 'latestSubmission'));
    }

    /**
     * Archive the specified student.
     */
    public function archive(User $student)
    {
        abort_if(! auth()->user()->isAdmin(), 403, 'Unauthorized access.');

        if ($student->role !== 'student') {
            abort(404);
        }

        $student->update([
            'is_active' => false,
            'deactivated_at' => now(),
            'deactivated_by' => auth()->id(),
        ]);

        return back()->with('success', 'Student archived successfully.');
    }

    /**
     * Unarchive / restore the specified student.
     */
    public function unarchive(User $student)
    {
        abort_if(! auth()->user()->isAdmin(), 403, 'Unauthorized access.');

        if ($student->role !== 'student') {
            abort(404);
        }

        $student->update([
            'is_active' => true,
            'deactivated_at' => null,
            'deactivated_by' => null,
        ]);

        return back()->with('success', 'Student unarchived successfully.');
    }
}
