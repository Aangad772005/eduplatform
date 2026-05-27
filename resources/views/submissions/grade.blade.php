@extends('layouts.app')

@section('title', 'Grade Submission')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Grade Submission</h1>
        <p class="page-subtitle">Assignment: <a href="{{ route('assignment.show', $submission->assignment_id) }}" style="color: var(--primary); text-decoration: none;">{{ $submission->assignment->title }}</a></p>
    </div>
    <a href="{{ route('assignment.show', $submission->assignment_id) }}" class="btn btn-secondary">&larr; Back to Submissions</a>
</div>

<div class="grading-panel">
    <!-- Left Panel: Submission Details & Work -->
    <div class="submission-content-panel">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2rem; border-bottom: 1px solid var(--border-color); padding-bottom: 1rem;">
            <div>
                <span style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Student</span>
                <h3 style="font-size: 1.15rem; margin-top: 0.15rem;">{{ $submission->student->name }}</h3>
                <span style="font-size: 0.8rem; color: var(--text-secondary);">{{ $submission->student->email }}</span>
            </div>
            <div style="text-align: right;">
                <span style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Submitted At</span>
                <div style="font-size: 0.9rem; margin-top: 0.15rem; font-weight: 600;">{{ $submission->created_at->format('M d, Y h:i A') }}</div>
                @if($submission->created_at > $submission->assignment->due_date)
                    <span class="badge badge-late" style="margin-top: 0.25rem;">Late Submission</span>
                @endif
            </div>
        </div>

        <!-- Student Work -->
        <div style="margin-bottom: 2.5rem;">
            <h3 style="font-size: 1.1rem; margin-bottom: 1rem; color: var(--primary);">Submitted Homework Content</h3>
            
            @if($submission->file_path)
                <div class="stat-card" style="padding: 1.25rem; justify-content: flex-start; gap: 1rem; margin-bottom: 1.5rem; border-color: var(--primary-glow);">
                    <svg style="width: 28px; height: 28px; color: var(--primary);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <div style="flex: 1;">
                        <div style="font-size: 0.95rem; font-weight: 700;">Uploaded Homework File</div>
                        <span style="font-size: 0.75rem; color: var(--text-muted);">Click button to download or inspect student document</span>
                    </div>
                    <a href="{{ asset('storage/' . $submission->file_path) }}" target="_blank" class="btn btn-secondary" style="padding: 0.5rem 1rem; font-size: 0.85rem;">Download</a>
                </div>
            @endif

            @if($submission->assignment->type === 'quiz')
                <div style="display: flex; flex-direction: column; gap: 1rem;">
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
                                <div>Student selected: <span style="{{ $isCorrect ? 'color: var(--success);' : 'color: var(--danger);' }} font-weight:600;">{{ $q['options'][$studentAns] ?? 'Unanswered' }}</span></div>
                                @if(!$isCorrect)
                                    <div>Correct answer: <span style="color: var(--success); font-weight:600;">{{ $q['options'][$correctAns] ?? 'N/A' }}</span></div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @elseif($submission->assignment->type === 'code')
                <pre class="code-editor">{{ $submission->content }}</pre>
            @else
                <div style="background-color: rgba(0,0,0,0.15); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border-color); white-space: pre-wrap; font-size: 0.95rem;">{{ $submission->content }}</div>
            @endif
        </div>

        <!-- Autograder Report -->
        <div class="grading-card" style="border-color: var(--border-hover); background-color: rgba(255,255,255,0.01);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.75rem;">
                <h3 style="font-size: 1.1rem; color: var(--primary);">🤖 Autograder Evaluation Report</h3>
                <span class="badge badge-graded" style="background-color: var(--primary-glow); color: var(--primary);">Auto-score: {{ round($submission->auto_graded_score) }} / {{ $submission->assignment->max_points }}</span>
            </div>
            <pre style="font-family: 'Courier New', Courier, monospace; font-size: 0.85rem; color: var(--text-secondary); white-space: pre-wrap; line-height: 1.5; background-color: rgba(0,0,0,0.2); padding: 1.25rem; border-radius: var(--radius-md); border: 1px solid var(--border-color);">{{ $submission->auto_graded_feedback }}</pre>
        </div>
    </div>

    <!-- Right Panel: Grading Form & Discussions -->
    <div class="grading-form-panel">
        <!-- Manual Grading Box -->
        <div class="grading-card">
            <h3 style="font-size: 1.2rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.75rem; margin-bottom: 1.5rem;">Grading Decision</h3>
            
            <form action="{{ route('submission.update_grade', $submission->id) }}" method="POST">
                @csrf
                <div class="form-group">
                    <label for="score" class="form-label">Awarded Score (Max {{ $submission->assignment->max_points }} pts)</label>
                    <input type="number" step="0.01" name="score" id="score" class="form-control" value="{{ old('score', $submission->score ?? $submission->auto_graded_score) }}" min="0" max="{{ $submission->assignment->max_points }}" required>
                </div>

                <div class="form-group">
                    <label for="feedback" class="form-label">Teacher Feedback Notes</label>
                    <textarea name="feedback" id="feedback" rows="5" class="form-control" placeholder="Provide detailed remarks and grading notes here...">{{ old('feedback', $submission->feedback) }}</textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.8rem; margin-top: 0.5rem;">
                    Submit Score & Feedback
                </button>
            </form>
        </div>

        <!-- Discussions/Comments -->
        <div class="grading-card" style="padding: 1.5rem 2rem;">
            <h3 style="font-size: 1.15rem; margin-bottom: 1.25rem;">Discussion Thread</h3>
            
            <div class="comment-list">
                @if($comments->isEmpty())
                    <p style="color: var(--text-muted); font-size: 0.85rem; text-align: center; padding: 1rem 0;">No comments yet. Start a discussion with the student.</p>
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
