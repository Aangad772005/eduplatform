<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    public function store(Request $request, Submission $submission)
    {
        $user = Auth::user();
        $classroom = $submission->assignment->classroom;

        // Authorization check
        if ($user->isStudent() && $submission->student_id !== $user->id) {
            abort(403, 'Unauthorized.');
        }
        if ($user->isTeacher() && $classroom->teacher_id !== $user->id) {
            abort(403, 'Unauthorized.');
        }

        $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        Comment::create([
            'submission_id' => $submission->id,
            'user_id' => $user->id,
            'content' => $request->content,
        ]);

        return back()->with('success', 'Comment posted successfully.');
    }
}
