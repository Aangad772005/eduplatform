@extends('layouts.app')

@section('title', 'Submission Feedback')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Homework Feedback</h1>
        <p class="page-subtitle">Assignment: <a href="{{ route('assignment.show', $submission->assignment_id) }}" style="color: var(--primary); text-decoration: none;">{{ $submission->assignment->title }}</a></p>
    </div>
    <a href="{{ route('assignment.show', $submission->assignment_id) }}" class="btn btn-secondary">&larr; Back to Assignment</a>
</div>

<div class="grading-panel">
    <!-- Left Panel: Submitted Work & Autograding -->
    <div class="submission-content-panel">
        <h3 style="font-size: 1.25rem; margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.75rem; color: var(--primary);">Your Submitted Work</h3>
        
        @if($submission->file_path)
            <div class="stat-card" style="padding: 1.25rem; justify-content: flex-start; gap: 1rem; margin-bottom: 1.5rem; border-color: var(--primary-glow);">
                <svg style="width: 28px; height: 28px; color: var(--primary);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                <div style="flex: 1;">
                    <div style="font-size: 0.95rem; font-weight: 700;">Uploaded Homework File</div>
                    <span style="font-size: 0.75rem; color: var(--text-muted);">You uploaded this document as part of your homework</span>
                </div>
                <a href="{{ asset('storage/' . $submission->file_path) }}" target="_blank" class="btn btn-secondary" style="padding: 0.5rem 1rem; font-size: 0.85rem;">Download</a>
            </div>
        @endif

        @if($submission->assignment->type === 'quiz')
            <div style="display: flex; flex-direction: column; gap: 1rem; margin-bottom: 2rem;">
                @php
                    $questions = $submission->assignment->config['questions'] ?? [];
                    $answers = json_decode($submission->content, true) ?: [];
                @endphp
                @foreach($questions as $index => $q)
                    @php
                        $studentAns = $answers[$index] ?? null;
                        $correctAns = $q['correct_option'] ?? null;
                        $isCorrect = $studentAns !== null && (int)$studentAns === (int)$correctAns;
                    @endphp
                    <div class="quiz-review-item" style="{{ $isCorrect ? 'border-color: rgba(16,185,129,0.2);' : 'border-color: rgba(239,68,68,0.2);' }}">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <strong>Question {{ $index + 1 }}</strong>
                            <span style="{{ $isCorrect ? 'color: var(--success);' : 'color: var(--danger);' }} font-weight:700;">
                                {{ $isCorrect ? '✅ Correct' : '❌ Incorrect' }}
                            </span>
                        </div>
                        <p style="font-size: 0.95rem; margin-bottom: 0.5rem;">{{ $q['question'] }}</p>
                        <div style="font-size: 0.85rem; color: var(--text-secondary); display: flex; flex-direction: column; gap: 0.25rem;">
                            <div>You selected: <span style="{{ $isCorrect ? 'color: var(--success);' : 'color: var(--danger);' }} font-weight:600;">{{ $q['options'][$studentAns] ?? 'Unanswered' }}</span></div>
                            @if(!$isCorrect)
                                <div>Correct answer: <span style="color: var(--success); font-weight:600;">{{ $q['options'][$correctAns] ?? 'N/A' }}</span></div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @elseif($submission->assignment->type === 'code')
            <pre class="code-editor" style="margin-bottom: 2rem;">{{ $submission->content }}</pre>
        @else
            <div style="background-color: rgba(0,0,0,0.15); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border-color); white-space: pre-wrap; font-size: 0.95rem; margin-bottom: 2rem;">{{ $submission->content }}</div>
        @endif

        <!-- Autograding Feedback Detail -->
        <div class="grading-card" style="border-color: var(--border-color); background-color: rgba(255,255,255,0.01);">
            <h3 style="font-size: 1.1rem; color: var(--primary); margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">🤖 Automated Grading Breakdown</h3>
            <pre style="font-family: 'Courier New', Courier, monospace; font-size: 0.85rem; color: var(--text-secondary); white-space: pre-wrap; line-height: 1.5; background-color: rgba(0,0,0,0.2); padding: 1.25rem; border-radius: var(--radius-md); border: 1px solid var(--border-color);">{{ $submission->auto_graded_feedback }}</pre>
        </div>
    </div>

    <!-- Right Panel: Final Grade & Discussion Thread -->
    <div class="grading-form-panel">
        <!-- Grade Summary -->
        <div class="grading-card">
            <h3 style="font-size: 1.25rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.75rem; margin-bottom: 1.25rem;">Grade Summary</h3>
            
            <div style="display: flex; flex-direction: column; gap: 1.25rem;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-weight: 600; color: var(--text-secondary);">Final Score:</span>
                    @if($submission->status === 'graded')
                        <strong style="color: var(--success); font-size: 1.8rem; font-family: var(--font-heading);">
                            {{ round($submission->score) }} / {{ $submission->assignment->max_points }}
                        </strong>
                    @else
                        <div style="text-align: right;">
                            <strong style="color: var(--warning); font-size: 1.6rem;">{{ round($submission->auto_graded_score) }} / {{ $submission->assignment->max_points }}</strong>
                            <span style="font-size: 0.75rem; color: var(--text-muted); display: block;">Provisional Auto-grade</span>
                        </div>
                    @endif
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border-color); padding-top: 1rem;">
                    <span style="font-weight: 600; color: var(--text-secondary);">Status:</span>
                    <span class="badge badge-{{ $submission->status }}">{{ $submission->status }}</span>
                </div>

                @if($submission->status === 'graded')
                    <div style="border-top: 1px solid var(--border-color); padding-top: 1rem;">
                        <span style="font-weight: 700; color: var(--primary); display: block; margin-bottom: 0.5rem; font-size: 0.95rem;">Teacher Remarks:</span>
                        <p style="color: var(--text-secondary); font-size: 0.95rem; line-height: 1.5; white-space: pre-wrap;">{{ $submission->feedback ?: 'No remarks provided.' }}</p>
                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.75rem;">Graded by {{ $submission->grader ? $submission->grader->name : 'System Auto-grader' }} on {{ $submission->graded_at ? $submission->graded_at->format('M d, Y') : $submission->updated_at->format('M d, Y') }}</div>
                    </div>
                @else
                    <div style="border-top: 1px solid var(--border-color); padding-top: 1rem; color: var(--text-muted); font-size: 0.85rem; line-height: 1.4;">
                        ℹ️ This submission has been run through the autograder. The final score is pending verification from your instructor, who may review or override it.
                    </div>
                @endif
            </div>
        </div>

        <!-- Discussions thread -->
        <div class="grading-card" style="padding: 1.5rem 2rem;">
            <h3 style="font-size: 1.15rem; margin-bottom: 1.25rem;">Discussion Thread</h3>
            
            <div class="comment-list">
                @if($comments->isEmpty())
                    <p style="color: var(--text-muted); font-size: 0.85rem; text-align: center; padding: 1rem 0;">No comments yet. Have a question about your grade? Ask below.</p>
                @else
                    @foreach($comments as $comment)
                        <div class="comment-item">
                            <div class="comment-meta">
                                <span class="comment-author" style="{{ $comment->user->isTeacher() ? 'color: var(--primary);' : 'color: var(--success);' }}">
                                    {{ $comment->user->name }} ({{ $comment->user->role }})
                                </span>
                                <span class="comment-time">{{ $comment->created_at->diffForHumans() }}</span>
                            </div>
                            <div class="comment-body">{{ $comment->content }}</div>
                        </div>
                    @endforeach
                @endif
            </div>

            <form action="{{ route('comment.store', $submission->id) }}" method="POST" style="border-top: 1px solid var(--border-color); padding-top: 1.25rem;">
                @csrf
                <div class="form-group" style="margin-bottom: 1rem;">
                    <textarea name="content" rows="3" class="form-control" placeholder="Write a comment..." required style="padding: 0.6rem 0.9rem; font-size: 0.85rem;"></textarea>
                </div>
                <button type="submit" class="btn btn-secondary" style="width: 100%; padding: 0.5rem; font-size: 0.85rem;">
                    Post Comment
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
