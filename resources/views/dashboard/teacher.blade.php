@extends('layouts.app')

@section('title', 'Teacher Dashboard')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Welcome Back, {{ Auth::user()->name }}!</h1>
        <p class="page-subtitle">Here is your class activity summary</p>
    </div>
    <button class="btn btn-primary" onclick="toggleModal('createClassroomModal')">
        <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
        </svg>
        New Classroom
    </button>
</div>

<!-- Stats Overview -->
<div class="stats-grid">
    <div class="stat-card">
        <div>
            <div class="stat-value">{{ $classrooms->count() }}</div>
            <div class="stat-label">Active Classrooms</div>
        </div>
        <div class="stat-icon">
            <svg style="width: 24px; height: 24px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
            </svg>
        </div>
    </div>
    <div class="stat-card">
        <div>
            <div class="stat-value">{{ $totalStudents }}</div>
            <div class="stat-label">Enrolled Students</div>
        </div>
        <div class="stat-icon">
            <svg style="width: 24px; height: 24px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
            </svg>
        </div>
    </div>
    <div class="stat-card">
        <div>
            <div class="stat-value">{{ $totalAssignments }}</div>
            <div class="stat-label">Assignments</div>
        </div>
        <div class="stat-icon">
            <svg style="width: 24px; height: 24px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
        </div>
    </div>
    <div class="stat-card" style="{{ $pendingGrading > 0 ? 'border-color: var(--warning);' : '' }}">
        <div>
            <div class="stat-value" style="{{ $pendingGrading > 0 ? 'color: var(--warning);' : '' }}">{{ $pendingGrading }}</div>
            <div class="stat-label">Submissions to Grade</div>
        </div>
        <div class="stat-icon">
            <svg style="width: 24px; height: 24px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
            </svg>
        </div>
    </div>
</div>

<!-- Classrooms section -->
<h2 style="margin-bottom: 1.5rem; font-size: 1.5rem;">Your Classrooms</h2>
@if($classrooms->isEmpty())
    <div class="stat-card" style="justify-content: center; text-align: center; padding: 3rem; margin-bottom: 3.5rem;">
        <div>
            <p style="color: var(--text-secondary); margin-bottom: 1rem;">You haven't created any classrooms yet.</p>
            <button class="btn btn-primary" onclick="toggleModal('createClassroomModal')">Create Your First Class</button>
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
                        Code: <strong style="color: var(--primary); letter-spacing: 0.5px;">{{ $class->code }}</strong>
                    </div>
                    <div class="classroom-footer">
                        <span>
                            <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                            {{ $class->students_count }} Students
                        </span>
                        <span>Manage &rarr;</span>
                    </div>
                </div>
            </a>
        @endforeach
    </div>
@endif

<!-- Recent Activity -->
<h2 style="margin-bottom: 1.5rem; font-size: 1.5rem;">Recent Submissions</h2>
@if($recentSubmissions->isEmpty())
    <div class="stat-card" style="justify-content: center; text-align: center; padding: 2.5rem; color: var(--text-muted);">
        No recent submissions to display.
    </div>
@else
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Assignment</th>
                    <th>Class</th>
                    <th>Submitted At</th>
                    <th>Status</th>
                    <th>Grade</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recentSubmissions as $sub)
                    <tr>
                        <td>
                            <strong style="color: var(--text-primary);">{{ $sub->student->name }}</strong>
                        </td>
                        <td>{{ $sub->assignment->title }}</td>
                        <td>{{ $sub->assignment->classroom->name }}</td>
                        <td>{{ $sub->created_at->format('M d, Y h:i A') }}</td>
                        <td>
                            <span class="badge badge-{{ $sub->status }}">{{ $sub->status }}</span>
                        </td>
                        <td>
                            @if($sub->status === 'graded')
                                <strong style="color: var(--success);">{{ round($sub->score) }}/{{ round($sub->assignment->max_points) }}</strong>
                            @else
                                <span style="color: var(--text-muted);">Pending</span>
                            @endif
                        </td>
                        <td>
                            @if($sub->status === 'submitted')
                                <a href="{{ route('submission.grade', $sub->id) }}" class="btn btn-primary" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;">Grade</a>
                            @else
                                <a href="{{ route('submission.grade', $sub->id) }}" class="btn btn-secondary" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;">Review</a>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

<!-- Create Classroom Modal -->
<div class="modal" id="createClassroomModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 style="font-size: 1.4rem;">Create Classroom</h3>
            <button class="modal-close" onclick="toggleModal('createClassroomModal')">&times;</button>
        </div>
        <form action="{{ route('classroom.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="name" class="form-label">Classroom Name</label>
                <input type="text" name="name" id="name" class="form-control" placeholder="e.g. Intro to PHP Programming" required>
            </div>
            <div class="form-group">
                <label for="subject" class="form-label">Subject (Optional)</label>
                <input type="text" name="subject" id="subject" class="form-control" placeholder="e.g. Computer Science">
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 2rem;">
                <button type="button" class="btn btn-secondary" onclick="toggleModal('createClassroomModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Class</button>
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
