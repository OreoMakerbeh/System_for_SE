<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create account · Albay Water Refilling Station</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Fraunces:opsz,wght@9..144,500;9..144,600&display=swap"
        rel="stylesheet"
    >

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @endif
</head>
<body class="auth-body">
    <main class="auth-layout">
        <section class="auth-aside">
            <a class="brand auth-brand" href="{{ route('register') }}">
                <span class="brand-mark">
                    <svg viewBox="0 0 32 32" fill="none">
                        <path d="M16 3C12 9 7 14.2 7 20a9 9 0 0 0 18 0c0-5.8-5-11-9-17Z" fill="currentColor"/>
                        <path d="M11 21c.4 2.1 1.9 3.4 4 3.8" stroke="#fff" stroke-width="1.7" stroke-linecap="round"/>
                    </svg>
                </span>
                <span class="brand-copy">
                    <span class="brand-name">Albay Water Refilling Station</span>
                    <small>REFILL SERVICE</small>
                </span>
            </a>

            <div class="auth-message">
                <span class="eyebrow">A FRESH START</span>
                <h1>One less thing<br>to think about.</h1>
                <p>Your next refill is only a few taps away.</p>
                <div class="water-lines">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </div>

            <span class="auth-aside-foot">A little more ease, delivered.</span>
        </section>

        <section class="auth-panel">
            <div class="auth-form-wrap register-wrap">
                <span class="eyebrow">GET STARTED</span>
                <h2>Create your account</h2>
                <p class="auth-subtitle">Tell us where to bring your next refill.</p>

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
                            Email address
                            <input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required>
                            @error('email')
                                <small class="field-error">{{ $message }}</small>
                            @enderror
                        </label>

                        <label>
                            Phone number
                            <input type="tel" name="phone" value="{{ old('phone') }}" autocomplete="tel" required>
                            @error('phone')
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
                        <label>
                            Password
                            <input type="password" name="password" autocomplete="new-password" required>
                            @error('password')
                                <small class="field-error">{{ $message }}</small>
                            @enderror
                        </label>

                        <label>
                            Confirm password
                            <input type="password" name="password_confirmation" autocomplete="new-password" required>
                        </label>
                    </div>

                    <button class="button button-primary button-wide" type="submit">
                        Create account <span>→</span>
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
