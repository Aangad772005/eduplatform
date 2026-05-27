<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Classroom;
use App\Models\Submission;
use App\Models\User;
use App\Services\AutograderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class HomeworkSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_as_teacher_or_student()
    {
        $response = $this->post('/register', [
            'name' => 'Dr. Jones',
            'email' => 'jones@university.edu',
            'role' => 'teacher',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertDatabaseHas('users', [
            'email' => 'jones@university.edu',
            'role' => 'teacher',
        ]);
    }

    public function test_teacher_can_create_classroom_and_get_unique_code()
    {
        $teacher = User::create([
            'name' => 'Dr. Jones',
            'email' => 'jones@university.edu',
            'role' => 'teacher',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->actingAs($teacher)->post('/classrooms', [
            'name' => 'CS 101',
            'subject' => 'Computer Science',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertDatabaseHas('classrooms', [
            'name' => 'CS 101',
            'teacher_id' => $teacher->id,
        ]);

        $classroom = Classroom::first();
        $this->assertNotEmpty($classroom->code);
    }

    public function test_student_can_join_classroom_with_code()
    {
        $teacher = User::create([
            'name' => 'Dr. Jones',
            'email' => 'jones@university.edu',
            'role' => 'teacher',
            'password' => bcrypt('password123'),
        ]);

        $classroom = Classroom::create([
            'name' => 'CS 101',
            'subject' => 'Computer Science',
            'teacher_id' => $teacher->id,
        ]);

        $student = User::create([
            'name' => 'Alice Student',
            'email' => 'alice@student.edu',
            'role' => 'student',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->actingAs($student)->post('/classrooms/join', [
            'code' => $classroom->code,
        ]);

        $response->assertRedirect('/classrooms/' . $classroom->id);
        $this->assertTrue($classroom->students->contains($student));
    }

    public function test_quiz_autograding()
    {
        $teacher = User::create(['name' => 'Teacher', 'email' => 't@t.com', 'role' => 'teacher', 'password' => '123']);
        $classroom = Classroom::create(['name' => 'CS101', 'teacher_id' => $teacher->id]);
        $student = User::create(['name' => 'Student', 'email' => 's@s.com', 'role' => 'student', 'password' => '123']);
        $classroom->students()->attach($student->id);

        $assignment = Assignment::create([
            'classroom_id' => $classroom->id,
            'title' => 'Sample Quiz',
            'type' => 'quiz',
            'max_points' => 100,
            'due_date' => now()->addDays(2),
            'config' => [
                'questions' => [
                    [
                        'question' => 'What is 2+2?',
                        'options' => ['3', '4', '5'],
                        'correct_option' => 1 // index of '4'
                    ],
                    [
                        'question' => 'What is 5*2?',
                        'options' => ['10', '15', '20'],
                        'correct_option' => 0 // index of '10'
                    ]
                ]
            ]
        ]);

        $submission = Submission::create([
            'assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'content' => json_encode([1, 1]), // first option correct, second option incorrect
            'status' => 'submitted',
        ]);

        $service = new AutograderService();
        $result = $service->grade($submission);

        $this->assertEquals(50.0, $result['score']);
        $this->assertStringContainsString('Passed 1 out of 2 questions', $result['feedback']);
    }

    public function test_code_autograding_valid()
    {
        $teacher = User::create(['name' => 'Teacher', 'email' => 't@t.com', 'role' => 'teacher', 'password' => '123']);
        $classroom = Classroom::create(['name' => 'CS101', 'teacher_id' => $teacher->id]);
        $student = User::create(['name' => 'Student', 'email' => 's@s.com', 'role' => 'student', 'password' => '123']);

        $assignment = Assignment::create([
            'classroom_id' => $classroom->id,
            'title' => 'Math Function',
            'type' => 'code',
            'max_points' => 100,
            'due_date' => now()->addDays(2),
            'config' => [
                'required_function' => 'add',
                'test_cases' => [
                    ['input' => '2, 3', 'expected_output' => '5'],
                    ['input' => '10, -5', 'expected_output' => '5']
                ]
            ]
        ]);

        $studentCode = "<?php\nfunction add(\$a, \$b) {\n    return \$a + \$b;\n}";

        $submission = Submission::create([
            'assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'content' => $studentCode,
            'status' => 'submitted',
        ]);

        $service = new AutograderService();
        $result = $service->grade($submission);

        $this->assertEquals(100.0, $result['score']);
        $this->assertStringContainsString('Passed 2 out of 2 test cases', $result['feedback']);
    }

    public function test_code_autograding_unsafe_rejected()
    {
        $teacher = User::create(['name' => 'Teacher', 'email' => 't@t.com', 'role' => 'teacher', 'password' => '123']);
        $classroom = Classroom::create(['name' => 'CS101', 'teacher_id' => $teacher->id]);
        $student = User::create(['name' => 'Student', 'email' => 's@s.com', 'role' => 'student', 'password' => '123']);

        $assignment = Assignment::create([
            'classroom_id' => $classroom->id,
            'title' => 'Math Function',
            'type' => 'code',
            'max_points' => 100,
            'due_date' => now()->addDays(2),
            'config' => [
                'required_function' => 'add',
                'test_cases' => [
                    ['input' => '2, 3', 'expected_output' => '5']
                ]
            ]
        ]);

        $studentCode = "<?php\nfunction add(\$a, \$b) {\n    system('dir');\n    return \$a + \$b;\n}";

        $submission = Submission::create([
            'assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'content' => $studentCode,
            'status' => 'submitted',
        ]);

        $service = new AutograderService();
        $result = $service->grade($submission);

        $this->assertEquals(0.0, $result['score']);
        $this->assertStringContainsString('Unsafe code detected', $result['feedback']);
    }

    public function test_written_rubric_autograding()
    {
        $teacher = User::create(['name' => 'Teacher', 'email' => 't@t.com', 'role' => 'teacher', 'password' => '123']);
        $classroom = Classroom::create(['name' => 'CS101', 'teacher_id' => $teacher->id]);
        $student = User::create(['name' => 'Student', 'email' => 's@s.com', 'role' => 'student', 'password' => '123']);

        $assignment = Assignment::create([
            'classroom_id' => $classroom->id,
            'title' => 'Written Report',
            'type' => 'written',
            'max_points' => 100,
            'due_date' => now()->addDays(2),
            'config' => [
                'rubrics' => [
                    [
                        'name' => 'Introduction section',
                        'points' => 40,
                        'keywords' => ['introduction', 'background', 'context']
                    ],
                    [
                        'name' => 'Analysis section',
                        'points' => 60,
                        'keywords' => ['results', 'evaluation', 'chart']
                    ]
                ]
            ]
        ]);

        // Student text has all introduction keywords but only 1 analysis keyword ('results')
        $studentText = "In the introduction, we discuss the background context of this research. Our results were quite interesting.";

        $submission = Submission::create([
            'assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'content' => $studentText,
            'status' => 'submitted',
        ]);

        $service = new AutograderService();
        $result = $service->grade($submission);

        // Intro: 3/3 keywords -> 40 points
        // Analysis: 1/3 keywords ('results') -> 20 points
        // Total points earned: 60 / 100
        $this->assertEquals(60.0, $result['score']);
        $this->assertStringContainsString('Matched terms: [introduction, background, context] (3/3 matched)', $result['feedback']);
        $this->assertStringContainsString('Matched terms: [results] (1/3 matched)', $result['feedback']);
    }

    public function test_student_can_view_submission_feedback_page_without_errors()
    {
        $teacher = User::create(['name' => 'Teacher', 'email' => 't@t.com', 'role' => 'teacher', 'password' => '123']);
        $classroom = Classroom::create(['name' => 'CS101', 'teacher_id' => $teacher->id]);
        $student = User::create(['name' => 'Student', 'email' => 's@s.com', 'role' => 'student', 'password' => '123']);
        $classroom->students()->attach($student->id);

        $assignment = Assignment::create([
            'classroom_id' => $classroom->id,
            'title' => 'Sample Quiz',
            'type' => 'quiz',
            'max_points' => 100,
            'due_date' => now()->addDays(2),
            'config' => [
                'questions' => [
                    [
                        'question' => 'What is 2+2?',
                        'options' => ['3', '4', '5'],
                        'correct_option' => 1
                    ]
                ]
            ]
        ]);

        $submission = Submission::create([
            'assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'content' => json_encode([1]),
            'status' => 'graded',
            'score' => 100,
            'graded_at' => now(),
        ]);

        $response = $this->actingAs($student)->get('/submissions/' . $submission->id);

        $response->assertStatus(200);
        $response->assertSee('System Auto-grader');
    }
}
