<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\CommentController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

// Guest Routes
Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// Auth Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Classrooms
    Route::post('/classrooms', [ClassroomController::class, 'store'])->name('classroom.store');
    Route::post('/classrooms/join', [ClassroomController::class, 'join'])->name('classroom.join');
    Route::get('/classrooms/{classroom}', [ClassroomController::class, 'show'])->name('classroom.show');

    // Assignments
    Route::get('/classrooms/{classroom}/assignments/create', [AssignmentController::class, 'create'])->name('assignment.create');
    Route::post('/classrooms/{classroom}/assignments', [AssignmentController::class, 'store'])->name('assignment.store');
    Route::get('/assignments/{assignment}', [AssignmentController::class, 'show'])->name('assignment.show');

    // Submissions
    Route::post('/assignments/{assignment}/submissions', [SubmissionController::class, 'store'])->name('submission.store');
    Route::get('/submissions/{submission}', [SubmissionController::class, 'show'])->name('submission.show');
    Route::get('/submissions/{submission}/grade', [SubmissionController::class, 'grade'])->name('submission.grade');
    Route::post('/submissions/{submission}/grade', [SubmissionController::class, 'updateGrade'])->name('submission.update_grade');

    // Comments
    Route::post('/submissions/{submission}/comments', [CommentController::class, 'store'])->name('comment.store');
});
