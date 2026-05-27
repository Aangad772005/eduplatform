@extends('layouts.app')

@section('title', $assignment->title)

@section('content')
<div class="page-header">
    <div>
        <span class="badge badge-{{ $assignment->type }}" style="margin-bottom: 0.5rem;">{{ $assignment->type }}</span>
        <h1 class="page-title" style="margin-top: 0.15rem;">{{ $assignment->title }}</h1>
        <p class="page-subtitle">Classroom: <a href="{{ route('classroom.show', $classroom->id) }}" style="color: var(--primary); text-decoration: none;">{{ $classroom->name }}</a></p>
    </div>
    <a href="{{ route('classroom.show', $classroom->id) }}" class="btn btn-secondary">&larr; Back to Class</a>
</div>

<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 2.5rem;">
    <div class="stat-card">
        <div>
            <div class="stat-value" style="font-size: 1.6rem;">{{ $assignment->max_points }} pts</div>
            <div class="stat-label">Max Score</div>
        </div>
    </div>
    <div class="stat-card">
        <div>
            <div class="stat-value" style="font-size: 1.6rem;">{{ $assignment->due_date->format('M d, Y') }}</div>
            <div class="stat-label">Due Date</div>
        </div>
    </div>
    <div class="stat-card">
        <div>
            <div class="stat-value" style="font-size: 1.6rem;">
                {{ $studentSubmissions->filter(fn($s) => $s->submission !== null)->count() }} / {{ $studentSubmissions->count() }}
            </div>
            <div class="stat-label">Turned In</div>
        </div>
    </div>
    <div class="stat-card">
        <div>
            <div class="stat-value" style="font-size: 1.6rem;">
                {{ $studentSubmissions->filter(fn($s) => $s->submission !== null && $s->submission->status === 'graded')->count() }}
            </div>
            <div class="stat-label">Graded</div>
        </div>
    </div>
</div>

<div class="grading-panel" style="grid-template-columns: 1.4fr 0.6fr;">
    <!-- Submissions Table -->
    <div class="submission-content-panel" style="padding: 2rem;">
        <h3 style="font-size: 1.3rem; margin-bottom: 1.5rem;">Student Submissions</h3>
        @if($studentSubmissions->isEmpty())
            <div style="text-align: center; padding: 3rem; color: var(--text-muted);">
                No students enrolled in this classroom yet.
            </div>
        @else
            <div class="table-container" style="border: none; border-radius: 0;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Date Submitted</th>
                            <th>Status</th>
                            <th>Final Grade</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($studentSubmissions as $student)
                            <tr>
                                <td>
                                    <div style="font-weight: 700; color: var(--text-primary);">{{ $student->name }}</div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">{{ $student->email }}</div>
                                </td>
                                <td>
                                    @if($student->submission)
                                        {{ $student->submission->created_at->format('M d, Y h:i A') }}
                                        @if($student->submission->created_at > $assignment->due_date)
                                            <span style="color: var(--danger); font-size: 0.75rem; font-weight: 600; display: block;">Late</span>
                                        @endif
                                    @else
                                        <span style="color: var(--text-muted); font-style: italic;">No Submission</span>
                                    @endif
                                </td>
                                <td>
                                    @if($student->submission)
                                        <span class="badge badge-{{ $student->submission->status }}">{{ $student->submission->status }}</span>
                                    @else
                                        <span class="badge badge-late" style="background-color: rgba(255,255,255,0.05); color: var(--text-muted);">Missing</span>
                                    @endif
                                </td>
                                <td>
                                    @if($student->submission && $student->submission->status === 'graded')
                                        <strong style="color: var(--success); font-size: 1rem;">{{ round($student->submission->score) }} / {{ $assignment->max_points }}</strong>
                                    @elseif($student->submission)
                                        <span style="color: var(--text-secondary); font-size: 0.9rem;">Auto-grade: {{ round($student->submission->auto_graded_score) }}</span>
                                    @else
                                        <span style="color: var(--text-muted); font-size: 0.9rem;">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($student->submission)
                                        @if($student->submission->status === 'submitted')
                                            <a href="{{ route('submission.grade', $student->submission->id) }}" class="btn btn-primary" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;">Grade</a>
                                        @else
                                            <a href="{{ route('submission.grade', $student->submission->id) }}" class="btn btn-secondary" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;">Review</a>
                                        @endif
                                    @else
                                        <button class="btn btn-secondary" disabled style="padding: 0.4rem 0.8rem; font-size: 0.8rem; opacity: 0.4; cursor: not-allowed;">Grade</button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Assignment Instructions Sidebar -->
    <div class="grading-form-panel">
        <div class="grading-card" style="padding: 2rem;">
            <h3 style="font-size: 1.2rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.75rem; margin-bottom: 1rem;">Instructions</h3>
            <p style="color: var(--text-secondary); font-size: 0.95rem; white-space: pre-wrap; line-height: 1.5; margin-bottom: 1.5rem;">{{ $assignment->description }}</p>
            
            <h3 style="font-size: 1.1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem; margin-bottom: 1rem;">Auto-grader Spec</h3>
            @if($assignment->type === 'quiz')
                <div style="font-size: 0.85rem; color: var(--text-secondary);">
                    <strong>Type:</strong> Multiple Choice Quiz<br>
                    <strong>Total questions:</strong> {{ count($assignment->config['questions'] ?? []) }}
                </div>
            @elseif($assignment->type === 'code')
                <div style="font-size: 0.85rem; color: var(--text-secondary); display: flex; flex-direction: column; gap: 0.5rem;">
                    <div><strong>Required function:</strong> <code style="background-color:rgba(0,0,0,0.2); padding:0.15rem 0.4rem; border-radius:4px; color:var(--primary);">{{ $assignment->config['required_function'] ?? 'None' }}</code></div>
                    <div><strong>Test cases count:</strong> {{ count($assignment->config['test_cases'] ?? []) }}</div>
                </div>
            @elseif($assignment->type === 'written')
                <div style="font-size: 0.85rem; color: var(--text-secondary); display: flex; flex-direction: column; gap: 0.5rem;">
                    <div><strong>Rubric criteria:</strong> {{ count($assignment->config['rubrics'] ?? []) }}</div>
                    @foreach($assignment->config['rubrics'] ?? [] as $r)
                        <div style="border-top:1px solid rgba(255,255,255,0.05); padding-top:0.3rem;">
                            <strong>{{ $r['name'] }} ({{ $r['points'] }} pts):</strong><br>
                            <span style="color: var(--text-muted);">Keywords: {{ implode(', ', $r['keywords']) }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
