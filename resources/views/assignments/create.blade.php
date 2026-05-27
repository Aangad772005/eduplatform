@extends('layouts.app')

@section('title', 'Create Assignment')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Create Assignment</h1>
        <p class="page-subtitle">Publish new task to classroom: {{ $classroom->name }}</p>
    </div>
    <a href="{{ route('classroom.show', $classroom->id) }}" class="btn btn-secondary">Cancel</a>
</div>

<form action="{{ route('assignment.store', $classroom->id) }}" method="POST" id="assignmentForm">
    @csrf

    <div class="grading-panel">
        <!-- Main Form Information -->
        <div class="submission-content-panel" style="gap: 1.5rem;">
            <div class="form-group">
                <label for="title" class="form-label">Assignment Title</label>
                <input type="text" name="title" id="title" class="form-control" placeholder="e.g. Functions & Recursion Practice" required value="{{ old('title') }}">
            </div>

            <div class="form-group">
                <label for="description" class="form-label">Instructions / Description</label>
                <textarea name="description" id="description" rows="8" class="form-control" placeholder="Provide detailed instructions for the homework submission..." required>{{ old('description') }}</textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="type" class="form-label">Assignment Type</label>
                    <select name="type" id="type" class="form-control" onchange="toggleConfigSection()" required style="cursor: pointer;">
                        <option value="quiz" {{ old('type') === 'quiz' ? 'selected' : '' }}>Quiz / Multiple Choice</option>
                        <option value="code" {{ old('type') === 'code' ? 'selected' : '' }}>Coding / Programming Task</option>
                        <option value="written" {{ old('type') === 'written' ? 'selected' : '' }}>Written Report / Short Answer</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="max_points" class="form-label">Maximum Score (Points)</label>
                    <input type="number" name="max_points" id="max_points" class="form-control" value="100" min="1" required>
                </div>
            </div>

            <div class="form-group">
                <label for="due_date" class="form-label">Due Date & Time</label>
                <input type="datetime-local" name="due_date" id="due_date" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary" style="margin-top: 1rem; padding: 0.9rem;">
                Publish Assignment
            </button>
        </div>

        <!-- Dynamic Configuration Panel -->
        <div class="grading-form-panel">
            <!-- Quiz Configuration -->
            <div class="grading-card" id="config-quiz">
                <h3 style="font-size: 1.2rem; margin-bottom: 1.5rem; color: var(--primary);">Configure Quiz Questions</h3>
                <div id="quiz-container" style="display: flex; flex-direction: column; gap: 1.5rem;">
                    <!-- Question Item Block -->
                </div>
                <button type="button" class="btn btn-secondary" onclick="addQuizQuestion()" style="width: 100%; margin-top: 1.5rem; font-size: 0.9rem;">
                    + Add MCQ Question
                </button>
            </div>

            <!-- Coding Configuration -->
            <div class="grading-card" id="config-code" style="display: none;">
                <h3 style="font-size: 1.2rem; margin-bottom: 1rem; color: var(--primary);">Coding Tests Configurations</h3>
                
                <div class="form-group">
                    <label for="required_function" class="form-label">Required Function Name</label>
                    <input type="text" name="required_function" id="required_function" class="form-control" placeholder="e.g. calculateFactorial">
                    <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.4rem;">The exact function name the student must define in their PHP code.</p>
                </div>

                <h4 style="font-size: 1rem; margin-top: 1.5rem; margin-bottom: 0.75rem;">Input/Output Test Cases</h4>
                <div id="test-cases-container" style="display: flex; flex-direction: column; gap: 1rem;">
                    <!-- Test Case Block -->
                </div>
                <button type="button" class="btn btn-secondary" onclick="addTestCase()" style="width: 100%; margin-top: 1rem; font-size: 0.9rem;">
                    + Add Test Case
                </button>
            </div>

            <!-- Written Configuration -->
            <div class="grading-card" id="config-written" style="display: none;">
                <h3 style="font-size: 1.2rem; margin-bottom: 1rem; color: var(--primary);">Auto-grading Keywords Rubric</h3>
                <p style="font-size: 0.8rem; color: var(--text-secondary); margin-bottom: 1.5rem;">Configure keywords to automatically verify coverage of key parts in submissions.</p>

                <div id="rubrics-container" style="display: flex; flex-direction: column; gap: 1.25rem;">
                    <!-- Rubric Block -->
                </div>
                <button type="button" class="btn btn-secondary" onclick="addRubricItem()" style="width: 100%; margin-top: 1.5rem; font-size: 0.9rem;">
                    + Add Rubric Criteria
                </button>
            </div>
        </div>
    </div>
</form>
@endsection

@section('scripts')
<script>
    // Set default due date to 7 days from now
    const now = new Date();
    now.setDate(now.getDate() + 7);
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    document.getElementById('due_date').value = now.toISOString().slice(0, 16);

    let questionCount = 0;
    let testCaseCount = 0;
    let rubricCount = 0;

    function toggleConfigSection() {
        const type = document.getElementById('type').value;
        const quizCard = document.getElementById('config-quiz');
        const codeCard = document.getElementById('config-code');
        const writtenCard = document.getElementById('config-written');

        quizCard.style.display = type === 'quiz' ? 'block' : 'none';
        codeCard.style.display = type === 'code' ? 'block' : 'none';
        writtenCard.style.display = type === 'written' ? 'block' : 'none';

        toggleInputs(quizCard, type !== 'quiz');
        toggleInputs(codeCard, type !== 'code');
        toggleInputs(writtenCard, type !== 'written');
    }

    function toggleInputs(container, disable) {
        const inputs = container.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.disabled = disable;
        });
    }

    // Dynamic Quiz Questions
    function addQuizQuestion() {
        const id = questionCount++;
        const html = `
            <div class="grading-card" id="quiz-question-${id}" style="padding: 1.25rem; border-color: var(--border-color); background-color: rgba(255,255,255,0.01);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <span style="font-weight: 700; font-size: 0.9rem; color: var(--text-secondary);">Question #${id + 1}</span>
                    <button type="button" onclick="removeElement('quiz-question-${id}')" style="background:none; border:none; color:var(--danger); cursor:pointer; font-weight:600; font-size: 0.85rem;">Remove</button>
                </div>
                <div class="form-group">
                    <input type="text" name="quiz_questions[${id}]" class="form-control" placeholder="Enter quiz question" required>
                </div>
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <input type="radio" name="quiz_correct[${id}]" value="0" checked style="accent-color: var(--primary);">
                        <input type="text" name="quiz_options[${id}][0]" class="form-control" placeholder="Option A" required style="padding: 0.6rem 1rem;">
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <input type="radio" name="quiz_correct[${id}]" value="1" style="accent-color: var(--primary);">
                        <input type="text" name="quiz_options[${id}][1]" class="form-control" placeholder="Option B" required style="padding: 0.6rem 1rem;">
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <input type="radio" name="quiz_correct[${id}]" value="2" style="accent-color: var(--primary);">
                        <input type="text" name="quiz_options[${id}][2]" class="form-control" placeholder="Option C" style="padding: 0.6rem 1rem;">
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <input type="radio" name="quiz_correct[${id}]" value="3" style="accent-color: var(--primary);">
                        <input type="text" name="quiz_options[${id}][3]" class="form-control" placeholder="Option D" style="padding: 0.6rem 1rem;">
                    </div>
                </div>
            </div>
        `;
        document.getElementById('quiz-container').insertAdjacentHTML('beforeend', html);
    }

    // Dynamic Test Cases for coding
    function addTestCase() {
        const id = testCaseCount++;
        const html = `
            <div id="test-case-${id}" style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 1rem; align-items: center;">
                <input type="text" name="test_input[${id}]" class="form-control" placeholder="Input (e.g. 5, 10)" required style="padding: 0.6rem 1rem;">
                <input type="text" name="test_output[${id}]" class="form-control" placeholder="Expected Output" required style="padding: 0.6rem 1rem;">
                <button type="button" onclick="removeElement('test-case-${id}')" style="background:none; border:none; color:var(--danger); cursor:pointer; font-weight:bold; font-size:1.2rem; padding: 0.5rem;">&times;</button>
            </div>
        `;
        document.getElementById('test-cases-container').insertAdjacentHTML('beforeend', html);
    }

    // Dynamic Rubric keywords check
    function addRubricItem() {
        const id = rubricCount++;
        const html = `
            <div class="grading-card" id="rubric-item-${id}" style="padding: 1.25rem; border-color: var(--border-color); background-color: rgba(255,255,255,0.01);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                    <span style="font-weight: 700; font-size: 0.9rem; color: var(--text-secondary);">Rubric Criteria #${id + 1}</span>
                    <button type="button" onclick="removeElement('rubric-item-${id}')" style="background:none; border:none; color:var(--danger); cursor:pointer; font-weight:600; font-size: 0.85rem;">Remove</button>
                </div>
                <div style="display: grid; grid-template-columns: 1.5fr 0.5fr; gap: 1rem; margin-bottom: 0.75rem;">
                    <input type="text" name="rubric_name[${id}]" class="form-control" placeholder="Criteria Name (e.g. Introduction)" required style="padding: 0.6rem 1rem;">
                    <input type="number" name="rubric_points[${id}]" class="form-control" placeholder="Points" required min="1" value="10" style="padding: 0.6rem 1rem;">
                </div>
                <div>
                    <input type="text" name="rubric_keywords[${id}]" class="form-control" placeholder="Required keywords (comma separated, e.g. hello, intro)" required style="padding: 0.6rem 1rem;">
                </div>
            </div>
        `;
        document.getElementById('rubrics-container').insertAdjacentHTML('beforeend', html);
    }

    function removeElement(id) {
        document.getElementById(id).remove();
    }

    // Initialize with first elements
    window.onload = function() {
        addQuizQuestion();
        addTestCase();
        addRubricItem();
        toggleConfigSection();
    };
</script>
@endsection
