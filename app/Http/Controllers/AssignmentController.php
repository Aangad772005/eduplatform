<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Classroom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AssignmentController extends Controller
{
    public function create(Classroom $classroom)
    {
        $user = Auth::user();
        if (!$user->isTeacher() || $classroom->teacher_id !== $user->id) {
            abort(403, 'Unauthorized.');
        }

        return view('assignments.create', compact('classroom'));
    }

    public function store(Request $request, Classroom $classroom)
    {
        $user = Auth::user();
        if (!$user->isTeacher() || $classroom->teacher_id !== $user->id) {
            abort(403, 'Unauthorized.');
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|string|in:quiz,written,code',
            'max_points' => 'required|integer|min:1',
            'due_date' => 'required|date',
        ]);

        $config = [];

        // Build configuration depending on type
        if ($request->type === 'quiz') {
            $questions = [];
            if ($request->has('quiz_questions')) {
                foreach ($request->quiz_questions as $index => $qText) {
                    $options = $request->quiz_options[$index] ?? [];
                    $questions[] = [
                        'question' => $qText,
                        'options' => array_values(array_filter($options)),
                        'correct_option' => (int)($request->quiz_correct[$index] ?? 0),
                    ];
                }
            }
            $config['questions'] = $questions;
        } elseif ($request->type === 'code') {
            $testCases = [];
            if ($request->has('test_input')) {
                foreach ($request->test_input as $index => $input) {
                    $expected = $request->test_output[$index] ?? '';
                    if (trim($input) !== '' || trim($expected) !== '') {
                        $testCases[] = [
                            'input' => $input,
                            'expected_output' => $expected,
                        ];
                    }
                }
            }
            $config['required_function'] = $request->required_function ?? '';
            $config['test_cases'] = $testCases;
        } elseif ($request->type === 'written') {
            $rubrics = [];
            if ($request->has('rubric_name')) {
                foreach ($request->rubric_name as $index => $rName) {
                    $points = $request->rubric_points[$index] ?? 0;
                    $rawKeywords = $request->rubric_keywords[$index] ?? '';
                    $keywords = array_map('trim', explode(',', $rawKeywords));
                    $keywords = array_filter($keywords);

                    if (!empty($rName)) {
                        $rubrics[] = [
                            'name' => $rName,
                            'points' => (float)$points,
                            'keywords' => array_values($keywords),
                        ];
                    }
                }
            }
            $config['rubrics'] = $rubrics;
        }

        Assignment::create([
            'classroom_id' => $classroom->id,
            'title' => $request->title,
            'description' => $request->description,
            'type' => $request->type,
            'max_points' => $request->max_points,
            'due_date' => $request->due_date,
            'config' => $config,
        ]);

        return redirect()->route('classroom.show', $classroom->id)->with('success', 'Assignment created successfully.');
    }

    public function show(Assignment $assignment)
    {
        $user = Auth::user();
        $classroom = $assignment->classroom;

        // Authorization
        if ($user->isTeacher()) {
            if ($classroom->teacher_id !== $user->id) {
                abort(403, 'Unauthorized.');
            }

            // Get all student enrollments and match them with submissions
            $students = $classroom->students()->orderBy('name')->get();
            $submissions = $assignment->submissions()->with('student')->get()->keyBy('student_id');

            // Map submissions back to students
            $studentSubmissions = $students->map(function ($student) use ($submissions) {
                $student->submission = $submissions->get($student->id);
                return $student;
            });

            return view('assignments.show_teacher', compact('assignment', 'classroom', 'studentSubmissions'));
        } else {
            if (!$classroom->students()->where('student_id', $user->id)->exists()) {
                abort(403, 'Unauthorized.');
            }

            $submission = $assignment->submissions()
                ->where('student_id', $user->id)
                ->first();

            return view('assignments.show_student', compact('assignment', 'classroom', 'submission'));
        }
    }
}
