@extends('layouts.app')

@section('title', 'Register')

@section('content')
<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <h1 class="auth-title">Create Account</h1>
            <p class="auth-subtitle">Get started with EduGrade system</p>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger" style="margin-bottom: 1.5rem; font-size: 0.9rem; padding: 0.85rem 1.2rem; display: block;">
                <ul style="margin: 0; padding-left: 1rem; text-align: left;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('register') }}" method="POST">
            @csrf
            
            <div class="form-group">
                <label for="name" class="form-label">Full Name</label>
                <input type="text" name="name" id="name" class="form-control" placeholder="John Doe" value="{{ old('name') }}" required autofocus>
            </div>

            <div class="form-group">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" name="email" id="email" class="form-control" placeholder="name@school.edu" value="{{ old('email') }}" required>
            </div>

            <div class="form-group">
                <label for="role" class="form-label">I am a</label>
                <select name="role" id="role" class="form-control" required style="cursor: pointer;">
                    <option value="student" {{ old('role') === 'student' ? 'selected' : '' }}>Student (Submit Homework)</option>
                    <option value="teacher" {{ old('role') === 'teacher' ? 'selected' : '' }}>Teacher (Grade & Manage Classes)</option>
                </select>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
                </div>
                <div class="form-group">
                    <label for="password_confirmation" class="form-label">Confirm Password</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" placeholder="••••••••" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1.5rem; padding: 0.9rem;">
                Create Account
            </button>
        </form>

        <div style="text-align: center; margin-top: 2rem; font-size: 0.9rem; color: var(--text-secondary);">
            Already have an account? 
            <a href="{{ route('login') }}" style="color: var(--primary); text-decoration: none; font-weight: 600;">Sign in</a>
        </div>
    </div>
</div>
@endsection
