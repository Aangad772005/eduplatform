@extends('layouts.app')

@section('title', $classroom->name)

@section('content')
<div class="page-header" style="margin-bottom: 1.5rem;">
    <div>
        <h1 class="page-title">{{ $classroom->name }}</h1>
        <p class="page-subtitle">{{ $classroom->subject ?? 'No Subject Specified' }} &bull; Taught by {{ $classroom->teacher->name }}</p>
    </div>
    
    @if(Auth::user()->isTeacher())
        <div style="background-color: var(--bg-card); border: 1px solid var(--border-color); padding: 0.75rem 1.25rem; border-radius: var(--radius-md); text-align: center;">
            <span style="font-size: 0.75rem; color: var(--text-muted); display: block; text-transform: uppercase; font-weight: 700; margin-bottom: 0.15rem;">Class Code</span>
            <strong style="color: var(--primary); font-size: 1.2rem; letter-spacing: 0.5px; font-family: var(--font-heading);">{{ $classroom->code }}</strong>
        </div>
    @endif
</div>

<!-- Navigation Tabs -->
<div class="class-tabs">
    <button class="tab-btn active" onclick="switchTab('stream', this)">Stream</button>
    <button class="tab-btn" onclick="switchTab('assignments', this)">Assignments</button>
    <button class="tab-btn" onclick="switchTab('people', this)">People</button>
</div>

<!-- Tab Contents -->
<div id="tab-stream" class="tab-content">
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        <!-- Announcement Card -->
        <div class="stat-card" style="justify-content: flex-start; gap: 1.5rem; padding: 2rem;">
            <div class="user-avatar" style="flex-shrink: 0; width: 48px; height: 48px;">
                {{ strtoupper(substr($classroom->teacher->name, 0, 1)) }}
            </div>
            <div style="flex: 1;">
                <h3 style="font-size: 1.1rem; margin-bottom: 0.25rem;">Welcome to {{ $classroom->name }}!</h3>
                <p style="color: var(--text-secondary); font-size: 0.95rem;">This stream will display course updates and announcement events. Navigate to the **Assignments** tab to view your course assignments and submit work.</p>
            </div>
        </div>

        <!-- Recent Activities list -->
        <h3 style="font-size: 1.2rem; margin-top: 1rem; margin-bottom: 0.5rem;">Course Feed</h3>
        @if($assignments->isEmpty())
            <div class="stat-card" style="justify-content: center; padding: 2.5rem; color: var(--text-muted);">
                No activity yet.
            </div>
        @else
            @foreach($assignments as $asg)
                <div class="stat-card" style="justify-content: flex-start; gap: 1.25rem; align-items: flex-start; padding: 1.5rem;">
                    <div style="background-color: var(--primary-glow); padding: 0.6rem; border-radius: var(--radius-sm); color: var(--primary); margin-top: 0.25rem;">
                        <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                        </svg>
                    </div>
                    <div style="flex: 1;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 0.5rem;">
                            <h4 style="font-size: 1.05rem; font-weight: 600;">
                                Teacher posted a new assignment: 
                                <a href="{{ route('assignment.show', $asg->id) }}" style="color: var(--primary); text-decoration: none; font-weight: 700;">{{ $asg->title }}</a>
                            </h4>
                            <span style="font-size: 0.8rem; color: var(--text-muted);">{{ $asg->created_at->diffForHumans() }}</span>
                        </div>
                        <p style="color: var(--text-secondary); font-size: 0.9rem; margin-top: 0.4rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 700px;">
                            {{ strip_tags($asg->description) }}
                        </p>
                    </div>
                </div>
            @endforeach
        @endif
    </div>
</div>

<div id="tab-assignments" class="tab-content" style="display: none;">
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        @if(Auth::user()->isTeacher())
            <div style="display: flex; justify-content: flex-end;">
                <a href="{{ route('assignment.create', $classroom->id) }}" class="btn btn-primary">
                    <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Create Assignment
                </a>
            </div>
        @endif

        @if($assignments->isEmpty())
            <div class="stat-card" style="justify-content: center; text-align: center; padding: 4rem; color: var(--text-muted);">
                <div>
                    <p style="margin-bottom: 1rem;">No assignments have been posted in this classroom yet.</p>
                    @if(Auth::user()->isTeacher())
                        <a href="{{ route('assignment.create', $classroom->id) }}" class="btn btn-primary">Add Assignment</a>
                    @endif
                </div>
            </div>
        @else
            <div class="assignment-list">
                @foreach($assignments as $asg)
                    <a href="{{ route('assignment.show', $asg->id) }}" class="assignment-item">
                        <div class="assignment-details">
                            <span class="assignment-title">{{ $asg->title }}</span>
                            <span class="assignment-meta">
                                <span>Due: {{ $asg->due_date->format('M d, Y h:i A') }}</span>
                                <span>Points: {{ $asg->max_points }}</span>
                            </span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 1.25rem;">
                            <span class="badge badge-{{ $asg->type }}">{{ $asg->type }}</span>
                            
                            @if(Auth::user()->isTeacher())
                                <span style="font-size: 0.85rem; color: var(--text-secondary); background-color: rgba(255,255,255,0.05); padding: 0.3rem 0.75rem; border-radius: 20px;">
                                    {{ $asg->submissions_count }} / {{ $studentsCount }} Submissions
                                </span>
                            @else
                                @if($asg->user_submission)
                                    @if($asg->user_submission->status === 'graded')
                                        <span class="badge badge-graded">Graded: {{ round($asg->user_submission->score) }}/{{ $asg->max_points }}</span>
                                    @else
                                        <span class="badge badge-submitted">Submitted</span>
                                    @endif
                                @else
                                    <span class="badge badge-late" style="background-color: rgba(255,255,255,0.05); color: var(--text-muted);">Missing</span>
                                @endif
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</div>

<div id="tab-people" class="tab-content" style="display: none;">
    <div style="display: grid; grid-template-columns: 1fr; gap: 2rem;">
        <!-- Teacher Card -->
        <div class="grading-card" style="padding: 1.5rem 2rem;">
            <h3 style="font-size: 1.1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.75rem; margin-bottom: 1.25rem; color: var(--primary);">Teacher</h3>
            <div style="display: flex; align-items: center; gap: 1rem;">
                <div class="user-avatar" style="width: 40px; height: 40px; font-size: 1rem;">
                    {{ strtoupper(substr($teacher->name, 0, 1)) }}
                </div>
                <div>
                    <strong style="color: var(--text-primary);">{{ $teacher->name }}</strong>
                    <div style="font-size: 0.8rem; color: var(--text-muted);">{{ $teacher->email }}</div>
                </div>
            </div>
        </div>

        <!-- Classmates Card -->
        <div class="grading-card" style="padding: 1.5rem 2rem;">
            <h3 style="font-size: 1.1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.75rem; margin-bottom: 1.25rem; color: var(--primary);">Classmates</h3>
            @if($students->isEmpty())
                <p style="color: var(--text-muted); font-size: 0.9rem; padding: 1rem 0;">No students enrolled yet.</p>
            @else
                <div style="display: flex; flex-direction: column; gap: 1.25rem;">
                    @foreach($students as $st)
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <div class="user-avatar" style="width: 36px; height: 36px; font-size: 0.9rem; background: linear-gradient(135deg, HSL(280, 95%, 68%) 0%, HSL(330, 95%, 68%) 100%);">
                                {{ strtoupper(substr($st->name, 0, 1)) }}
                            </div>
                            <div>
                                <strong style="color: var(--text-primary); font-size: 0.95rem;">{{ $st->name }}</strong>
                                <div style="font-size: 0.8rem; color: var(--text-muted);">{{ $st->email }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

<script>
    function switchTab(tabName, element) {
        // Hide all tab contents
        document.querySelectorAll('.tab-content').forEach(content => {
            content.style.display = 'none';
        });
        
        // Show selected tab content
        document.getElementById('tab-' + tabName).style.display = 'block';
        
        // Remove active class from all buttons
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Add active class to clicked button
        element.classList.add('active');
    }
</script>
@endsection
