<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Submission;
use App\Services\AutograderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubmissionController extends Controller
{
    protected $autograder;

    public function __construct(AutograderService $autograder)
    {
        $this->autograder = $autograder;
    }

    public function store(Request $request, Assignment $assignment)
    {
        $user = Auth::user();
        if (!$user->isStudent()) {
            abort(403, 'Only students can submit homework.');
        }

        // Check if student is in classroom
        if (!$assignment->classroom->students()->where('student_id', $user->id)->exists()) {
            abort(403, 'Unauthorized.');
        }

        // Check if already submitted
        $existing = Submission::where('assignment_id', $assignment->id)
            ->where('student_id', $user->id)
            ->first();

        if ($existing) {
            return back()->withErrors(['submission' => 'You have already submitted this assignment.']);
        }

        $request->validate([
            'content' => 'nullable|string',
            'file' => 'nullable|file|mimes:pdf,doc,docx,txt,zip,png,jpg,jpeg|max:10240',
        ]);

        $filePath = null;
        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('submissions', 'public');
        }

        $content = $request->content;

        // If quiz, we format the choices into a JSON string
        if ($assignment->type === 'quiz') {
            $choices = $request->quiz_answers ?? [];
            $content = json_encode($choices);
        }

        $status = 'submitted';
        if (now() > $assignment->due_date) {
            $status = 'submitted'; // we can tag it or handle in UI
        }

        // Create submission
        $submission = Submission::create([
            'assignment_id' => $assignment->id,
            'student_id' => $user->id,
            'content' => $content,
            'file_path' => $filePath,
            'status' => $status,
        ]);

        // Run Auto-grader
        $gradeResult = $this->autograder->grade($submission);

        $submission->auto_graded_score = $gradeResult['score'];
        $submission->auto_graded_feedback = $gradeResult['feedback'];

        // If it's a quiz, lock final score immediately
        if ($assignment->type === 'quiz') {
            $submission->score = $gradeResult['score'];
            $submission->status = 'graded';
            $submission->graded_at = now();
        } else {
            // For code or written, prefill the score for the teacher
            $submission->score = $gradeResult['score'];
        }

        $submission->save();

        return redirect()->route('assignment.show', $assignment->id)->with('success', 'Homework submitted successfully!');
    }

    public function grade(Submission $submission)
    {
        $user = Auth::user();
        $classroom = $submission->assignment->classroom;

        if (!$user->isTeacher() || $classroom->teacher_id !== $user->id) {
            abort(403, 'Unauthorized.');
        }

        // Load comments with user info
        $comments = $submission->comments()->with('user')->orderBy('created_at', 'asc')->get();

        return view('submissions.grade', compact('submission', 'comments'));
    }

    public function updateGrade(Request $request, Submission $submission)
    {
        $user = Auth::user();
        $classroom = $submission->assignment->classroom;

        if (!$user->isTeacher() || $classroom->teacher_id !== $user->id) {
            abort(403, 'Unauthorized.');
        }

        $request->validate([
            'score' => 'required|numeric|min:0|max:' . $submission->assignment->max_points,
            'feedback' => 'nullable|string',
        ]);

        $submission->update([
            'score' => $request->score,
            'feedback' => $request->feedback,
            'status' => 'graded',
            'graded_by' => $user->id,
            'graded_at' => now(),
        ]);

        return redirect()->route('assignment.show', $submission->assignment_id)->with('success', 'Submission graded successfully.');
    }

    public function show(Submission $submission)
    {
        $user = Auth::user();
        $classroom = $submission->assignment->classroom;

        // Check if student owns it, or teacher owns classroom
        if ($user->isStudent() && $submission->student_id !== $user->id) {
            abort(403, 'Unauthorized.');
        }
        if ($user->isTeacher() && $classroom->teacher_id !== $user->id) {
            abort(403, 'Unauthorized.');
        }

        $comments = $submission->comments()->with('user')->orderBy('created_at', 'asc')->get();

        return view('submissions.show', compact('submission', 'comments'));
    }
}
