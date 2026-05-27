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

<div class="grading-panel" style="grid-template-columns: 1.3fr 0.7fr;">
    <!-- Instructions & Submission Form -->
    <div class="submission-content-panel" style="padding: 2.5rem; gap: 2rem;">
        <!-- Instructions -->
        <div>
            <h3 style="font-size: 1.3rem; margin-bottom: 0.75rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">Instructions</h3>
            <p style="color: var(--text-secondary); font-size: 1rem; white-space: pre-wrap; line-height: 1.6;">{{ $assignment->description }}</p>
        </div>

        <!-- Submission Status/Form -->
        <div style="border-top: 1px solid var(--border-color); padding-top: 2rem;">
            @if($submission)
                <div class="grading-card" style="padding: 2rem; border-color: var(--border-hover); background: linear-gradient(135deg, var(--bg-card) 0%, rgba(99, 102, 241, 0.03) 100%);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                        <h3 style="font-size: 1.2rem;">Your Submission</h3>
                        <span class="badge badge-{{ $submission->status }}">{{ $submission->status }}</span>
                    </div>

                    @if($assignment->type === 'quiz')
                        <div class="stat-card" style="margin-bottom: 1.5rem;">
                            <div>
                                <span class="stat-label">Quiz score</span>
                                <div class="stat-value" style="color: var(--success); font-size: 1.8rem;">{{ round($submission->score) }} / {{ $assignment->max_points }}</div>
                            </div>
                            <span class="badge badge-graded">Auto-graded</span>
                        </div>

                        <!-- Dynamic Quiz Answers Breakdown -->
                        <div style="margin-top: 1.5rem; border-top: 1px solid var(--border-color); padding-top: 1.5rem;">
                            <h4 style="font-size: 1.1rem; margin-bottom: 1rem; color: var(--primary);">Quiz Review Breakdown</h4>
                            @php
                                $questions = $assignment->config['questions'] ?? [];
                                $answers = json_decode($submission->content, true) ?: [];
                            @endphp
                            <div style="display: flex; flex-direction: column; gap: 1rem;">
                                @foreach($questions as $index => $q)
                                    @php
                                        $studentAns = $answers[$index] ?? null;
                                        $correctAns = $q['correct_option'] ?? null;
                                        $isCorrect = $studentAns !== null && (int)$studentAns === (int)$correctAns;
                                    @endphp
                                    <div class="quiz-review-item" style="padding: 1rem; border: 1px solid {{ $isCorrect ? 'rgba(16,185,129,0.2)' : 'rgba(239,68,68,0.2)' }}; border-radius: var(--radius-md); background-color: rgba(255,255,255,0.01);">
                                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                            <strong>Question {{ $index + 1 }}</strong>
                                            <span style="{{ $isCorrect ? 'color: var(--success);' : 'color: var(--danger);' }} font-weight:700; font-size: 0.9rem;">
                                                {{ $isCorrect ? '✅ Correct' : '❌ Incorrect' }}
                                            </span>
                                        </div>
                                        <p style="font-size: 0.9rem; margin-bottom: 0.5rem; color: var(--text-primary);">{{ $q['question'] }}</p>
                                        <div style="font-size: 0.8rem; color: var(--text-secondary); display: flex; flex-direction: column; gap: 0.25rem;">
                                            <div>You selected: <span style="{{ $isCorrect ? 'color: var(--success);' : 'color: var(--danger);' }} font-weight:600;">{{ $q['options'][$studentAns] ?? 'Unanswered' }}</span></div>
                                            @if(!$isCorrect)
                                                <div>Correct answer: <span style="color: var(--success); font-weight:600;">{{ $q['options'][$correctAns] ?? 'N/A' }}</span></div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <a href="{{ route('submission.show', $submission->id) }}" class="btn btn-secondary" style="width: 100%; margin-top: 1.5rem;">View Discussion & Comments</a>
                    @else
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            @if($submission->file_path)
                                <div class="stat-card" style="padding: 1rem; justify-content: flex-start; gap: 1rem;">
                                    <svg style="width: 24px; height: 24px; color: var(--primary);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                    <div>
                                        <div style="font-size: 0.9rem; font-weight: 600;">Uploaded Homework File</div>
                                        <a href="{{ asset('storage/' . $submission->file_path) }}" target="_blank" style="font-size: 0.75rem; color: var(--primary); text-decoration: none;">Download File</a>
                                    </div>
                                </div>
                            @endif

                            @if($submission->content)
                                <div>
                                    <div style="font-size: 0.85rem; font-weight: 600; color: var(--text-secondary); margin-bottom: 0.5rem;">Submitted Content:</div>
                                    @if($assignment->type === 'code')
                                        <pre class="code-editor">{{ $submission->content }}</pre>
                                    @else
                                        <div style="background-color: rgba(0,0,0,0.15); padding: 1.25rem; border-radius: var(--radius-md); border:1px solid var(--border-color); font-size: 0.95rem; white-space: pre-wrap;">{{ $submission->content }}</div>
                                    @endif
                                </div>
                            @endif

                            <!-- Dynamic Autograder Evaluation Breakdown -->
                            @if($submission->auto_graded_feedback)
                                <div style="margin-top: 1rem; border-top: 1px solid var(--border-color); padding-top: 1rem;">
                                    <div style="font-size: 0.85rem; font-weight: 600; color: var(--text-secondary); margin-bottom: 0.5rem;">🤖 Automated Grading Breakdown:</div>
                                    <pre style="font-family: 'Courier New', Courier, monospace; font-size: 0.8rem; color: var(--text-secondary); white-space: pre-wrap; line-height: 1.4; background-color: rgba(0,0,0,0.2); padding: 1rem; border-radius: var(--radius-md); border: 1px solid var(--border-color);">{{ $submission->auto_graded_feedback }}</pre>
                                </div>
                            @endif

                            <div style="margin-top: 1.5rem; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border-color); padding-top: 1.5rem;">
                                @if($submission->status === 'graded')
                                    <div>
                                        <span style="font-size: 0.8rem; color: var(--text-muted); display: block;">Final Grade</span>
                                        <strong style="color: var(--success); font-size: 1.6rem;">{{ round($submission->score) }} / {{ $assignment->max_points }}</strong>
                                    </div>
                                    <a href="{{ route('submission.show', $submission->id) }}" class="btn btn-primary">Review Feedback & Chat</a>
                                @else
                                    <div>
                                        <span style="font-size: 0.8rem; color: var(--text-muted); display: block;">Tentative Score</span>
                                        <strong style="color: var(--warning); font-size: 1.6rem;">{{ round($submission->auto_graded_score) }} / {{ $assignment->max_points }}</strong>
                                        <span style="font-size: 0.75rem; color: var(--text-muted); display: block;">Pending teacher review</span>
                                    </div>
                                    <a href="{{ route('submission.show', $submission->id) }}" class="btn btn-secondary">Feedback & Discussion</a>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            @else
                <h3 style="font-size: 1.2rem; margin-bottom: 1.25rem;">Submit Homework</h3>
                
                <form action="{{ route('submission.store', $assignment->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    @if($assignment->type === 'quiz')
                        <!-- Quiz Submission -->
                        <div style="display: flex; flex-direction: column; gap: 1.75rem;">
                            @foreach($assignment->config['questions'] ?? [] as $qIndex => $question)
                                <div class="grading-card" style="padding: 1.5rem; border-color: var(--border-color); background-color: rgba(255,255,255,0.01);">
                                    <h4 style="font-size: 1.05rem; margin-bottom: 1rem;">
                                        <span style="color: var(--primary);">Q{{ $qIndex + 1 }}.</span> {{ $question['question'] }}
                                    </h4>
                                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                                        @foreach($question['options'] ?? [] as $oIndex => $option)
                                            <label style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-md); cursor: pointer; transition: var(--transition-smooth);">
                                                <input type="radio" name="quiz_answers[{{ $qIndex }}]" value="{{ $oIndex }}" required style="accent-color: var(--primary);">
                                                <span style="font-size: 0.95rem;">{{ $option }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @elseif($assignment->type === 'code')
                        <!-- Code Submission -->
                        <div class="form-group">
                            <label for="code-content" class="form-label">PHP Source Code</label>
                            <textarea name="content" id="code-content" rows="12" class="form-control" style="font-family: 'Courier New', Courier, monospace; font-size: 0.9rem;" placeholder="<?php&#10;&#10;function {{ $assignment->config['required_function'] ?? 'myFunction' }}() {&#10;    // Write your code here&#10;}" required></textarea>
                            <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.4rem;">Make sure your script includes the required function <strong>{{ $assignment->config['required_function'] ?? '' }}</strong>.</p>
                        </div>
                    @elseif($assignment->type === 'written')
                        <!-- Written Submission -->
                        <div class="form-group">
                            <label for="written-content" class="form-label">Write your Response</label>
                            <textarea name="content" id="written-content" rows="10" class="form-control" placeholder="Type your homework answer or report content here..." required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="file" class="form-label">Upload Document (Optional)</label>
                            <input type="file" name="file" id="file" class="form-control" style="padding: 0.6rem;">
                            <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.4rem;">Allowed formats: pdf, doc, docx, txt, zip, png, jpg. Max size 10MB.</p>
                        </div>
                    @endif

                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 2rem; padding: 0.9rem;">
                        Submit Assignment
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Assignment Status Sidebar -->
    <div class="grading-form-panel">
        <div class="grading-card" style="padding: 2rem;">
            <h3 style="font-size: 1.2rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.75rem; margin-bottom: 1.25rem;">Details</h3>
            
            <div style="display: flex; flex-direction: column; gap: 1rem; font-size: 0.9rem; color: var(--text-secondary);">
                <div>
                    <strong>Due Date:</strong><br>
                    <span style="color: var(--text-primary);">{{ $assignment->due_date->format('M d, Y \a\t h:i A') }}</span>
                </div>
                <div>
                    <strong>Maximum Points:</strong><br>
                    <span style="color: var(--text-primary);">{{ $assignment->max_points }} Points</span>
                </div>
                <div>
                    <strong>Status:</strong><br>
                    @if($submission)
                        <span class="badge badge-{{ $submission->status }}">{{ $submission->status }}</span>
                    @else
                        @if(now() > $assignment->due_date)
                            <span class="badge badge-late">Overdue</span>
                        @else
                            <span class="badge badge-submitted" style="background-color:rgba(255,255,255,0.05); color:var(--text-muted);">Assigned</span>
                        @endif
                    @endif
                </div>
                
                @if($assignment->type === 'code')
                    <div style="border-top:1px solid var(--border-color); padding-top:1rem; margin-top:0.5rem;">
                        <strong>Autograder Specs:</strong>
                        <div style="margin-top:0.4rem; font-size:0.8rem; line-height:1.4;">
                            Must define: <code style="color:var(--primary); background-color:rgba(0,0,0,0.2); padding:0.1rem 0.3rem; border-radius:3px;">{{ $assignment->config['required_function'] ?? 'N/A' }}()</code><br>
                            Will run against: {{ count($assignment->config['test_cases'] ?? []) }} test cases.
                        </div>
                    </div>
                @elseif($assignment->type === 'written')
                    <div style="border-top:1px solid var(--border-color); padding-top:1rem; margin-top:0.5rem;">
                        <strong>Grading Rubric:</strong>
                        <div style="margin-top:0.4rem; display:flex; flex-direction:column; gap:0.4rem;">
                            @foreach($assignment->config['rubrics'] ?? [] as $r)
                                <div style="font-size:0.8rem; line-height:1.3;">
                                    <strong>{{ $r['name'] }} ({{ $r['points'] }} pts):</strong><br>
                                    <span style="color:var(--text-muted);">Keywords: {{ implode(', ', $r['keywords']) }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
