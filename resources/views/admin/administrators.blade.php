@extends('layouts.app')

@section('title', 'Administrators')

@section('content')
    <div class="page-heading">
        <span class="eyebrow">ACCESS CONTROL</span>
        <h1>Administrator accounts</h1>
        <p>Create and update staff access. Revoking access keeps the account and its order history.</p>
    </div>

    <section class="content-section">
        <div class="section-heading">
            <div>
                <span class="eyebrow">NEW ACCOUNT</span>
                <h2>Add an administrator</h2>
            </div>
        </div>

        <div class="form-panel administrator-create">
            <form class="form-stack" method="POST" action="{{ route('admin.administrators.store') }}">
                @csrf

                <div class="form-name-fields">
                    <label>
                        First name
                        <input name="first_name" value="{{ old('first_name') }}" maxlength="80" required autocomplete="given-name">
                        @error('first_name')
                            <span class="field-error">{{ $message }}</span>
                        @enderror
                    </label>

                    <label>
                        <span class="label-title">Middle name <span class="optional">OPTIONAL</span></span>
                        <input name="middle_name" value="{{ old('middle_name') }}" maxlength="80" autocomplete="additional-name">
                        @error('middle_name')
                            <span class="field-error">{{ $message }}</span>
                        @enderror
                    </label>

                    <label>
                        Last name
                        <input name="last_name" value="{{ old('last_name') }}" maxlength="80" required autocomplete="family-name">
                        @error('last_name')
                            <span class="field-error">{{ $message }}</span>
                        @enderror
                    </label>
                </div>

                <div class="administrator-fields">
                    <label>
                        Email
                        <input type="email" name="email" value="{{ old('email') }}" maxlength="255" required autocomplete="email">
                        @error('email')
                            <span class="field-error">{{ $message }}</span>
                        @enderror
                    </label>

                    <label>
                        Password
                        <input type="password" name="password" minlength="8" required autocomplete="new-password">
                        @error('password')
                            <span class="field-error">{{ $message }}</span>
                        @enderror
                    </label>

                    <label>
                        Confirm password
                        <input type="password" name="password_confirmation" minlength="8" required autocomplete="new-password">
                    </label>
                </div>

                <div class="form-actions">
                    <button class="button button-primary" type="submit">Create administrator</button>
                </div>
            </form>
        </div>
    </section>

    <section class="content-section customer-directory">
        <div class="section-heading">
            <div>
                <span class="eyebrow">CURRENT ACCESS</span>
                <h2>Administrators</h2>
            </div>
            <span class="section-count">{{ $administrators->total() }} total</span>
        </div>

        @if ($administrators->isEmpty())
            <div class="empty-state compact-empty">
                <h3>No administrators found.</h3>
                <p>Create an administrator account to get started.</p>
            </div>
        @else
            <div class="customer-list">
                @foreach ($administrators as $administrator)
                    <article class="customer-row">
                        <div class="customer-summary">
                            <span class="avatar">{{ strtoupper(substr($administrator->name, 0, 1)) }}</span>
                            <div>
                                <strong>
                                    {{ $administrator->name }}
                                    @if (auth()->user()->is($administrator))
                                        <span class="administrator-you">(You)</span>
                                    @endif
                                </strong>
                                <small>{{ $administrator->email }}</small>
                            </div>

                            @if (! auth()->user()->is($administrator) && $administrators->total() > 1)
                                <form
                                    method="POST"
                                    action="{{ route('admin.administrators.revoke', $administrator) }}"
                                    onsubmit="return confirm('Revoke administrator access for {{ $administrator->name }}? Their account and order history will be kept.')"
                                >
                                    @csrf
                                    @method('PATCH')
                                    <button class="delete-button" type="submit">Revoke access</button>
                                </form>
                            @endif
                        </div>

                        <form
                            class="customer-edit administrator-edit"
                            method="POST"
                            action="{{ route('admin.administrators.update', $administrator) }}"
                        >
                            @csrf
                            @method('PATCH')

                            <div class="form-name-fields">
                                <label>
                                    First name
                                    <input name="first_name" value="{{ $administrator->first_name }}" maxlength="80" required autocomplete="given-name">
                                    @error('first_name')
                                        <span class="field-error">{{ $message }}</span>
                                    @enderror
                                </label>

                                <label>
                                    <span class="label-title">Middle name <span class="optional">OPTIONAL</span></span>
                                    <input name="middle_name" value="{{ $administrator->middle_name }}" maxlength="80" autocomplete="additional-name">
                                    @error('middle_name')
                                        <span class="field-error">{{ $message }}</span>
                                    @enderror
                                </label>

                                <label>
                                    Last name
                                    <input name="last_name" value="{{ $administrator->last_name }}" maxlength="80" required autocomplete="family-name">
                                    @error('last_name')
                                        <span class="field-error">{{ $message }}</span>
                                    @enderror
                                </label>
                            </div>

                            <label>
                                Email
                                <input type="email" name="email" value="{{ $administrator->email }}" maxlength="255" required>
                            </label>

                            <label>
                                <span class="label-title">
                                    New password <span class="optional">LEAVE BLANK TO KEEP CURRENT</span>
                                </span>
                                <input type="password" name="password" minlength="8" autocomplete="new-password">
                            </label>

                            <label>
                                Confirm password
                                <input type="password" name="password_confirmation" minlength="8" autocomplete="new-password">
                            </label>

                            <button class="button button-small button-outline" type="submit">Save changes</button>
                        </form>
                    </article>
                @endforeach
            </div>

            <div class="pagination-wrap">{{ $administrators->links() }}</div>
        @endif
    </section>
@endsection
