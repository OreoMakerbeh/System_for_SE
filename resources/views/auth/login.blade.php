<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in · Albay Water Refilling Station</title>

    @include('layouts.partials.assets')
</head>
<body class="auth-body auth-photo">
    <main class="auth-layout">
        <section class="auth-aside">
            <a class="brand auth-brand" href="{{ route('login') }}">
                <span class="brand-copy">
                    <span class="brand-name">Albay Water Refilling Station</span>
                    <small>REFILL SERVICE</small>
                </span>
            </a>

            <div class="auth-message">
                <span class="eyebrow">GOOD WATER, ON YOUR TERMS</span>
                <h1>Refills that fit<br>your everyday.</h1>
                <p>Place an order in minutes. We’ll take care of the rest.</p>
            </div>

            <span class="auth-aside-foot">A little more ease, delivered.</span>
        </section>

        <section class="auth-panel">
            <div class="auth-form-wrap">
                <span class="eyebrow">WELCOME BACK</span>
                <h2>Sign in</h2>
                <p class="auth-subtitle">Pick up right where you left off.</p>

                @if (session('success'))
                    <div class="flash-success" role="status">{{ session('success') }}</div>
                @endif

                @if ($errors->any())
                    <div class="flash-error" role="alert">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('login.store') }}" class="form-stack" data-login-form data-captcha-url="{{ route('login.captcha.verify') }}">
                    @csrf

                    @php($isCustomerLogin = old('role', 'customer') === 'customer')
                    <label>
                        <span id="loginLabel">{{ $isCustomerLogin ? 'Mobile number' : 'Email or mobile number' }}</span>
                        <input
                            id="loginInput"
                            type="{{ $isCustomerLogin ? 'tel' : 'text' }}"
                            inputmode="{{ $isCustomerLogin ? 'tel' : 'text' }}"
                            name="login"
                            value="{{ is_string(old('login')) ? old('login') : '' }}"
                            autocomplete="username"
                            maxlength="255"
                            required
                            autofocus
                        >
                        @error('login')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </label>
                    <label>
                        Sign in as
                        <select name="role" required>
                            <option
                                value="customer"
                                @selected(old('role', 'customer') === 'customer')
                            >Customer</option>
                            <option value="rider" @selected(old('role') === 'rider')>
                                Delivery rider
                            </option>
                            <option value="admin" @selected(old('role') === 'admin')>
                                Administrator
                            </option>
                        </select>
                        @error('role')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </label>

                    <x-password-field
                        name="password"
                        id="login-password"
                        label="Password"
                        autocomplete="current-password"
                        required
                    />

                    <div class="human-check-widget">
                        <label class="human-check-row" for="humanCheck">
                            <input id="humanCheck" class="human-check-input" type="checkbox">
                            <span class="human-check-box" aria-hidden="true"></span>
                            <span class="human-check-label">I'm not a robot</span>
                            <span class="human-check-brand">SECURITY CHECK</span>
                        </label>
                        <p id="humanCheckStatus" class="human-check-status" role="status" aria-live="polite"></p>
                        @error('captcha')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <dialog id="captchaDialog" class="captcha-dialog" aria-labelledby="captchaTitle">
                        <div class="captcha-dialog-heading">
                            <div>
                                <span class="eyebrow">SECURITY CHECK</span>
                                <h3 id="captchaTitle">Select the matching photo</h3>
                                <p>Click every tile that matches this photo.</p>
                            </div>
                            <img src="{{ $captchaTarget }}" alt="Example water bottle" width="72" height="72">
                        </div>

                        <div class="captcha-tile-grid">
                            @foreach ($captchaChoices as $index => $choice)
                                <button
                                    class="captcha-tile"
                                    type="button"
                                    data-choice="{{ $choice['token'] }}"
                                    aria-label="Select photo {{ $index + 1 }}"
                                    aria-pressed="false"
                                >
                                    <img src="{{ $choice['url'] }}" alt="" width="140" height="140">
                                    <span class="captcha-selection-label" aria-hidden="true">Selected</span>
                                </button>
                            @endforeach
                        </div>

                        <p id="captchaDialogError" class="captcha-dialog-error" role="alert" hidden></p>

                        <div class="captcha-dialog-footer">
                            <button id="captchaCancel" class="button button-outline" type="button">Cancel</button>
                            <button id="captchaVerify" class="button button-primary" type="button">Verify</button>
                        </div>
                    </dialog>

                    <button class="button button-primary button-wide" type="submit">
                        Sign in
                    </button>
                </form>

                <p class="auth-switch">
                    New to Albay Water Refilling Station?
                    <a href="{{ route('register') }}">Create an account</a>
                </p>
            </div>
        </section>
    </main>


</body>
</html>
