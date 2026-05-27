<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Classroom;
use App\Models\Submission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user->isTeacher()) {
            return $this->teacherDashboard($user);
        } else {
            return $this->studentDashboard($user);
        }
    }

    private function teacherDashboard($user)
    {
        $classrooms = Classroom::where('teacher_id', $user->id)->withCount('students')->get();
        $classroomIds = $classrooms->pluck('id');

        $totalStudents = DB::table('classroom_student')
            ->whereIn('classroom_id', $classroomIds)
            ->distinct('student_id')
            ->count('student_id');

        $totalAssignments = Assignment::whereIn('classroom_id', $classroomIds)->count();

        $pendingGrading = Submission::whereIn('assignment_id', function ($query) use ($classroomIds) {
                $query->select('id')->from('assignments')->whereIn('classroom_id', $classroomIds);
            })
            ->where('status', 'submitted')
            ->count();

        $recentSubmissions = Submission::whereIn('assignment_id', function ($query) use ($classroomIds) {
                $query->select('id')->from('assignments')->whereIn('classroom_id', $classroomIds);
            })
            ->with(['student', 'assignment.classroom'])
            ->latest()
            ->take(5)
            ->get();

        return view('dashboard.teacher', compact(
            'classrooms',
            'totalStudents',
            'totalAssignments',
            'pendingGrading',
            'recentSubmissions'
        ));
    }

    private function studentDashboard($user)
    {
        $classrooms = $user->enrolledClassrooms()->with('teacher')->get();
        $classroomIds = $classrooms->pluck('id');

        // Recent graded submissions
        $recentGrades = Submission::where('student_id', $user->id)
            ->where('status', 'graded')
            ->with(['assignment.classroom'])
            ->latest()
            ->take(5)
            ->get();

        // Pending assignments
        $submittedAssignmentIds = Submission::where('student_id', $user->id)
            ->pluck('assignment_id')
            ->toArray();

        $pendingAssignments = Assignment::whereIn('classroom_id', $classroomIds)
            ->whereNotIn('id', $submittedAssignmentIds)
            ->where('due_date', '>=', now())
            ->with('classroom')
            ->orderBy('due_date', 'asc')
            ->get();

        return view('dashboard.student', compact(
            'classrooms',
            'recentGrades',
            'pendingAssignments'
        ));
    }
}
