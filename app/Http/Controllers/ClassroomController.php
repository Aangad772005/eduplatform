<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClassroomController extends Controller
{
    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user->isTeacher()) {
            abort(403, 'Only teachers can create classrooms.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'nullable|string|max:255',
        ]);

        Classroom::create([
            'name' => $request->name,
            'subject' => $request->subject,
            'teacher_id' => $user->id,
        ]);

        return redirect()->route('dashboard')->with('success', 'Classroom created successfully.');
    }

    public function join(Request $request)
    {
        $user = Auth::user();
        if (!$user->isStudent()) {
            abort(403, 'Only students can join classrooms.');
        }

        $request->validate([
            'code' => 'required|string',
        ]);

        $classroom = Classroom::where('code', trim(strtoupper($request->code)))->first();

        if (!$classroom) {
            return back()->withErrors(['code' => 'Invalid classroom code. Check code and try again.'])->withInput();
        }

        // Check if already joined
        if ($classroom->students()->where('student_id', $user->id)->exists()) {
            return back()->withErrors(['code' => 'You are already enrolled in this classroom.'])->withInput();
        }

        $classroom->students()->attach($user->id);

        return redirect()->route('classroom.show', $classroom->id)->with('success', 'Successfully joined classroom!');
    }

    public function show(Classroom $classroom)
    {
        $user = Auth::user();

        // Authorization
        if ($user->isTeacher()) {
            if ($classroom->teacher_id !== $user->id) {
                abort(403, 'Unauthorized access to classroom.');
            }
        } else {
            if (!$classroom->students()->where('student_id', $user->id)->exists()) {
                abort(403, 'You are not enrolled in this classroom.');
            }
        }

        // Load assignments with submissions count for teachers
        if ($user->isTeacher()) {
            $assignments = $classroom->assignments()
                ->withCount('submissions')
                ->latest()
                ->get();
            $studentsCount = $classroom->students()->count();
        } else {
            // Load assignments with student's own submission
            $assignments = $classroom->assignments()
                ->latest()
                ->get()
                ->map(function ($assignment) use ($user) {
                    $assignment->user_submission = $assignment->submissions()
                        ->where('student_id', $user->id)
                        ->first();
                    return $assignment;
                });
            $studentsCount = null;
        }

        $students = $classroom->students()->orderBy('name')->get();
        $teacher = $classroom->teacher;

        return view('classrooms.show', compact('classroom', 'assignments', 'students', 'teacher', 'studentsCount'));
    }
}
