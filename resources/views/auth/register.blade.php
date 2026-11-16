<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create account · Albay Water Refilling Station</title>

    @include('layouts.partials.assets')
</head>
<body class="auth-body">
    <main class="auth-layout">
        <section class="auth-aside">
            <a class="brand auth-brand" href="{{ route('register') }}">
                <span class="brand-copy">
                    <span class="brand-name">Albay Water Refilling Station</span>
                    <small>REFILL SERVICE</small>
                </span>
            </a>

            <div class="auth-message">
                <span class="eyebrow">A FRESH START</span>
                <h1>One less thing<br>to think about.</h1>
                <p>Your next refill is only a few taps away.</p>
            </div>

            <span class="auth-aside-foot">A little more ease, delivered.</span>
        </section>

        <section class="auth-panel">
            <div class="auth-form-wrap register-wrap">
                <span class="eyebrow">GET STARTED</span>
                <h2>Create your account</h2>
                <p class="auth-subtitle">Register with your mobile number. You’ll use it with your password to sign in after approval.</p>

                @if ($errors->any())
                    <div class="flash-error" role="alert">
                        Please check the highlighted fields and try again.
                    </div>
                @endif

                <form method="POST" action="{{ route('register.store') }}" class="form-stack">
                    @csrf

                    <div class="form-name-fields">
                        <label>
                            First name
                            <input type="text" name="first_name" value="{{ old('first_name') }}" autocomplete="given-name" maxlength="80" required>
                            @error('first_name')
                                <small class="field-error">{{ $message }}</small>
                            @enderror
                        </label>

                        <label>
                            <span class="label-title">Middle name </span>
                            <input type="text" name="middle_name" placeholder="Optional" value="{{ old('middle_name') }}" autocomplete="additional-name" maxlength="80">
                            @error('middle_name')
                                <small class="field-error">{{ $message }}</small>
                            @enderror
                        </label>

                        <label>
                            Last name
                            <input type="text" name="last_name" value="{{ old('last_name') }}" autocomplete="family-name" maxlength="80" required>
                            @error('last_name')
                                <small class="field-error">{{ $message }}</small>
                            @enderror
                        </label>
                    </div>

                    <div class="form-pair">
                        <label>
                            Mobile number
                            <input type="tel" name="phone" value="{{ is_string(old('phone')) ? old('phone') : '' }}" autocomplete="tel" maxlength="40" required>
                            @error('phone')
                                <small class="field-error">{{ $message }}</small>
                            @enderror
                        </label>

                        <label>
                            Email address (optional)
                            <input type="email" name="email" value="{{ is_string(old('email')) ? old('email') : '' }}" autocomplete="email" maxlength="255">
                            @error('email')
                                <small class="field-error">{{ $message }}</small>
                            @enderror
                        </label>
                    </div>

                    <label>
                        Delivery address
                        <textarea name="address" rows="2" autocomplete="street-address" required>{{ old('address') }}</textarea>
                        @error('address')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </label>

                    <div class="form-pair">
                        <x-password-field
                            name="password"
                            id="register-password"
                            label="Password"
                            autocomplete="new-password"
                            required
                        />
                        <x-password-field
                            name="password_confirmation"
                            id="register-password-confirmation"
                            label="Confirm password"
                            toggle-label="password confirmation"
                            autocomplete="new-password"
                            required
                        />
                    </div>

                    <button class="button button-primary button-wide" type="submit">
                        Create account
                    </button>
                </form>

                <p class="auth-switch">
                    Already have an account?
                    <a href="{{ route('login') }}">Sign in</a>
                </p>
            </div>
        </section>
    </main>
</body>
</html>
