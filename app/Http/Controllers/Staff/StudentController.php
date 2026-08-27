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
        $query = User::where('role', 'student')
            ->with('structuredProgram.department');

        if ($search = $request->input('search')) {
            $query->where(function($q) use ($search) {
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

        return view('staff.students.index', compact('students', 'search'));
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
            'inventorySubmissions' => function($q) {
                $q->latest('submitted_at');
            },
            'inventorySubmissions.flags'
        ]);

        $latestSubmission = $student->inventorySubmissions->first();

        return view('staff.students.show', compact('student', 'latestSubmission'));
    }
}
