@extends('layouts.app')

@section('title', 'Student Dashboard')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Welcome, {{ Auth::user()->name }}!</h1>
        <p class="page-subtitle">Here is your academic progress overview</p>
    </div>
    <button class="btn btn-primary" onclick="toggleModal('joinClassroomModal')">
        <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
        </svg>
        Join Classroom
    </button>
</div>

<!-- Classrooms Grid -->
<h2 style="margin-bottom: 1.5rem; font-size: 1.5rem;">Your Enrolled Classrooms</h2>
@if($classrooms->isEmpty())
    <div class="stat-card" style="justify-content: center; text-align: center; padding: 3rem; margin-bottom: 3.5rem;">
        <div>
            <p style="color: var(--text-secondary); margin-bottom: 1rem;">You are not enrolled in any classrooms yet.</p>
            <button class="btn btn-primary" onclick="toggleModal('joinClassroomModal')">Join Your First Class</button>
        </div>
    </div>
@else
    <div class="classroom-grid">
        @foreach($classrooms as $class)
            <a href="{{ route('classroom.show', $class->id) }}" class="classroom-card">
                <div class="classroom-banner">
                    <span class="classroom-subject">{{ $class->subject ?? 'General' }}</span>
                </div>
                <div class="classroom-body">
                    <h3 class="classroom-title">{{ $class->name }}</h3>
                    <div style="font-size: 0.85rem; color: var(--text-secondary);">
                        Teacher: <strong>{{ $class->teacher->name }}</strong>
                    </div>
                    <div class="classroom-footer">
                        <span>Subject: {{ $class->subject ?? 'N/A' }}</span>
                        <span>View Class &rarr;</span>
                    </div>
                </div>
            </a>
        @endforeach
    </div>
@endif

<!-- Pending vs Graded Row -->
<div class="grading-panel">
    <!-- Pending Assignments -->
    <div class="submission-content-panel" style="padding: 2rem;">
        <h3 style="font-size: 1.3rem; margin-bottom: 1.5rem;">Upcoming Assignments</h3>
        @if($pendingAssignments->isEmpty())
            <div style="text-align: center; padding: 3rem; color: var(--text-muted);">
                🎉 All caught up! No pending assignments.
            </div>
        @else
            <div class="assignment-list">
                @foreach($pendingAssignments as $assignment)
                    <div class="assignment-item" style="padding: 1.2rem;">
                        <div class="assignment-details">
                            <span class="assignment-title" style="font-size: 1.05rem;">{{ $assignment->title }}</span>
                            <span style="font-size: 0.8rem; color: var(--primary);">{{ $assignment->classroom->name }}</span>
                            <span class="assignment-meta" style="gap: 1rem; margin-top: 0.2rem;">
                                <span>Due: {{ $assignment->due_date->format('M d, Y h:i A') }}</span>
                                <span>{{ $assignment->max_points }} pts</span>
                            </span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <span class="badge badge-{{ $assignment->type }}">{{ $assignment->type }}</span>
                            <a href="{{ route('assignment.show', $assignment->id) }}" class="btn btn-primary" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;">Submit</a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Recent Grades -->
    <div class="grading-form-panel">
        <div class="grading-card" style="padding: 2rem;">
            <h3 style="font-size: 1.3rem; margin-bottom: 1.5rem;">Recent Feedback</h3>
            @if($recentGrades->isEmpty())
                <div style="text-align: center; padding: 3rem; color: var(--text-muted);">
                    No graded work yet.
                </div>
            @else
                <div class="assignment-list">
                    @foreach($recentGrades as $grade)
                        <a href="{{ route('submission.show', $grade->id) }}" class="assignment-item" style="padding: 1.2rem; border-color: rgba(16, 185, 129, 0.2);">
                            <div class="assignment-details">
                                <span class="assignment-title" style="font-size: 1.05rem;">{{ $grade->assignment->title }}</span>
                                <span style="font-size: 0.8rem; color: var(--text-secondary);">{{ $grade->assignment->classroom->name }}</span>
                                <span class="assignment-meta" style="margin-top: 0.2rem;">
                                    <span>Graded on {{ $grade->graded_at ? $grade->graded_at->format('M d') : $grade->updated_at->format('M d') }}</span>
                                </span>
                            </div>
                            <div style="text-align: right; display: flex; flex-direction: column; align-items: flex-end; gap: 0.3rem;">
                                <strong style="color: var(--success); font-size: 1.1rem;">
                                    {{ round($grade->score) }} / {{ round($grade->assignment->max_points) }}
                                </strong>
                                <span class="badge badge-graded" style="font-size: 0.65rem;">Graded</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Join Classroom Modal -->
<div class="modal" id="joinClassroomModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 style="font-size: 1.4rem;">Join Classroom</h3>
            <button class="modal-close" onclick="toggleModal('joinClassroomModal')">&times;</button>
        </div>
        <form action="{{ route('classroom.join') }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="code" class="form-label">Classroom Code</label>
                <input type="text" name="code" id="code" class="form-control" placeholder="e.g. ABC-123-XYZ" required autofocus>
                <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.4rem;">Ask your teacher for the class join code.</p>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 2rem;">
                <button type="button" class="btn btn-secondary" onclick="toggleModal('joinClassroomModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Join Class</button>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleModal(id) {
        const modal = document.getElementById(id);
        modal.classList.toggle('active');
    }
</script>
@endsection
