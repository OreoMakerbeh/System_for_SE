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
    <label>Current password
        <input type="password" name="current_password" autocomplete="current-password" required autofocus>
        @error('current_password')<small class="field-error">{{ $message }}</small>@enderror
    </label>
    <label>New password
        <input type="password" name="password" autocomplete="new-password" required>
        @error('password')<small class="field-error">{{ $message }}</small>@enderror
    </label>
    <label>Confirm new password
        <input type="password" name="password_confirmation" autocomplete="new-password" required>
    </label>
    <div class="form-actions">
        <a class="text-link" href="{{ route('dashboard') }}">Cancel</a>
        <button class="button button-primary" type="submit">Update password <span>→</span></button>
    </div>
</form>
@endsection