@extends('layouts.app')

@section('title', 'Login')

@section('content')
<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <h1 class="auth-title">Welcome Back</h1>
            <p class="auth-subtitle">Sign in to your EduGrade account</p>
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

        <form action="{{ route('login') }}" method="POST">
            @csrf
            
            <div class="form-group">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" name="email" id="email" class="form-control" placeholder="name@school.edu" value="{{ old('email') }}" required autofocus>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
            </div>

            <div class="form-group" style="display: flex; align-items: center; gap: 0.5rem; margin-top: 1rem;">
                <input type="checkbox" name="remember" id="remember" style="accent-color: var(--primary);">
                <label for="remember" class="form-label" style="margin-bottom: 0; cursor: pointer; font-size: 0.85rem;">Remember my session</label>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1.5rem; padding: 0.9rem;">
                Sign In
            </button>
        </form>

        <div style="text-align: center; margin-top: 2rem; font-size: 0.9rem; color: var(--text-secondary);">
            Don't have an account? 
            <a href="{{ route('register') }}" style="color: var(--primary); text-decoration: none; font-weight: 600;">Sign up</a>
        </div>
    </div>
</div>
@endsection
