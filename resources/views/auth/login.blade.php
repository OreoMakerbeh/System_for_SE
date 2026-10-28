<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in · Albay Water Refilling Station</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Fraunces:opsz,wght@9..144,500;9..144,600&display=swap"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="auth-body">
    <main class="auth-layout">
        <section class="auth-aside">
            <a class="brand auth-brand" href="{{ route('login') }}">
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
                <span class="eyebrow">GOOD WATER, ON YOUR TERMS</span>
                <h1>Refills that fit<br>your everyday.</h1>
                <p>Place an order in minutes. We’ll take care of the rest.</p>
                <div class="water-lines">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
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

                <form method="POST" action="{{ route('login.store') }}" class="form-stack">
                    @csrf

                    <label>
                        Email or mobile number
                        <input type="text" name="email" value="{{ old('email') }}" autocomplete="username" required autofocus>
                        @error('email')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </label>

                    <label>
                        Sign in as
                        <select name="role" required>
                            <option value="customer" @selected(old('role', 'customer') === 'customer')>Customer</option>
                            <option value="rider" @selected(old('role') === 'rider')>Delivery rider</option>
                            <option value="admin" @selected(old('role') === 'admin')>Administrator</option>
                        </select>
                        @error('role')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </label>

                    <label>
                        Password
                        <input id="passwordInput" type="password" name="password" autocomplete="current-password" required>
                        @error('password')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </label>

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
                                    <span class="captcha-tile-check" aria-hidden="true">&#10003;</span>
                                </button>
                            @endforeach
                        </div>

                        <p id="captchaDialogError" class="captcha-dialog-error" role="alert" hidden></p>

                        <div class="captcha-dialog-footer">
                            <button id="captchaCancel" class="button button-outline" type="button">Cancel</button>
                            <button id="captchaVerify" class="button button-primary" type="button">Verify</button>
                        </div>
                    </dialog>

                    <label class="check-label">
                        <input type="checkbox" onclick="togglePasswordVisibility()">
                        Show password
                    </label>

                    <script>
                        const humanCheck = document.getElementById('humanCheck');
                        const humanCheckStatus = document.getElementById('humanCheckStatus');
                        const captchaDialog = document.getElementById('captchaDialog');
                        const captchaDialogError = document.getElementById('captchaDialogError');
                        const captchaVerify = document.getElementById('captchaVerify');
                        let captchaVerified = false;

                        humanCheck.addEventListener('change', () => {
                            if (humanCheck.checked && !captchaVerified) {
                                humanCheck.checked = false;
                                captchaDialog.showModal();
                            }
                        });

                        document.querySelectorAll('.captcha-tile').forEach((tile) => {
                            tile.addEventListener('click', () => {
                                const selected = tile.getAttribute('aria-pressed') === 'true';
                                tile.setAttribute('aria-pressed', String(!selected));
                                tile.classList.toggle('is-selected', !selected);
                                captchaDialogError.hidden = true;
                            });
                        });

                        document.getElementById('captchaCancel').addEventListener('click', () => {
                            captchaDialog.close();
                        });

                        captchaVerify.addEventListener('click', async () => {
                            const selectedTiles = Array.from(document.querySelectorAll('.captcha-tile.is-selected'));

                            if (selectedTiles.length === 0) {
                                captchaDialogError.textContent = 'Select the matching photos first.';
                                captchaDialogError.hidden = false;
                                return;
                            }

                            captchaVerify.disabled = true;
                            captchaVerify.textContent = 'Checking...';

                            const formData = new FormData();
                            selectedTiles.forEach((tile) => formData.append('captcha_choices[]', tile.dataset.choice));

                            try {
                                const response = await fetch(@json(route('login.captcha.verify')), {
                                    method: 'POST',
                                    headers: {
                                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                                        'Accept': 'application/json',
                                    },
                                    body: formData,
                                });
                                const result = await response.json();

                                if (!response.ok) {
                                    throw new Error(result.message || 'That selection does not match. Try again.');
                                }

                                captchaVerified = true;
                                humanCheck.checked = true;
                                humanCheck.disabled = true;
                                humanCheckStatus.textContent = 'Verified';
                                captchaDialog.close();
                            } catch (error) {
                                captchaDialogError.textContent = error.message;
                                captchaDialogError.hidden = false;
                            } finally {
                                captchaVerify.disabled = false;
                                captchaVerify.textContent = 'Verify';
                            }
                        });

                        document.querySelector('.form-stack').addEventListener('submit', (event) => {
                            if (!captchaVerified) {
                                event.preventDefault();
                                humanCheckStatus.textContent = 'Complete the human check before signing in.';
                                captchaDialog.showModal();
                            }
                        });

                        function togglePasswordVisibility() {
                            const passwordInput = document.getElementById('passwordInput');

                            if (!passwordInput) return;

                            passwordInput.type = passwordInput.type === 'password' ? 'text' : 'password';
                        }
                    </script>

                    <button class="button button-primary button-wide" type="submit">
                        Sign in <span>→</span>
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
