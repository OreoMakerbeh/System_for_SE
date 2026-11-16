@extends('layouts.app')

@section('title', 'Password & security')

@section('content')
    <div class="page-heading">
        <span class="eyebrow">ACCOUNT SECURITY</span>
        <h1>Change your password</h1>
        <p>Choose a new password to protect your account.</p>
    </div>

    <form class="form-panel form-stack" method="POST" action="{{ route('password.update') }}" style="max-width: 560px">
        @csrf
        @method('PUT')
        <x-password-field
            name="current_password"
            id="current-password"
            label="Current password"
            autocomplete="current-password"
            required
            autofocus
        />
        <x-password-field
            name="password"
            id="new-password"
            label="New password"
            autocomplete="new-password"
            required
        />
        <x-password-field
            name="password_confirmation"
            id="new-password-confirmation"
            label="Confirm new password"
            toggle-label="new password confirmation"
            autocomplete="new-password"
            required
        />
        <div class="form-actions">
            <a class="text-link" href="{{ route('dashboard') }}">Cancel</a>
            <button class="button button-primary" type="submit">Update password</button>
        </div>
    </form>
@endsection
