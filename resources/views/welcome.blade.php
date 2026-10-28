<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Order water refills from Albay Water Refilling Station and manage your deliveries online.">
    <title>{{ config('app.name', 'Albay Water Refilling Station') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Fraunces:opsz,wght@9..144,500;9..144,600&display=swap" rel="stylesheet">
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @endif
    <style>
        .landing { min-height: 100vh; color: #173238; background: #f2f7f4; font-family: 'Manrope', sans-serif; }
        .landing-header { display: flex; min-height: 82px; align-items: center; justify-content: space-between; gap: 24px; width: min(1160px, calc(100% - 48px)); margin: 0 auto; }
        .landing-brand { display: inline-flex; align-items: center; gap: 11px; color: #233337; font-size: 15px; font-weight: 700; line-height: 1.15; }
        .landing-brand-mark { display: grid; width: 42px; height: 42px; flex: 0 0 auto; place-items: center; color: #fff; background: linear-gradient(145deg, #19a398, #0c5d62); border-radius: 14px 14px 14px 5px; box-shadow: 0 8px 18px rgba(17, 112, 108, .2); }
        .landing-brand-mark svg { width: 26px; height: 26px; }
        .landing-nav { display: flex; align-items: center; gap: 24px; }
        .landing-nav-link { color: #657477; font-size: 12px; font-weight: 600; }
        .landing-nav-link:hover { color: #0e5d5a; }
        .landing-button { display: inline-flex; min-height: 46px; align-items: center; justify-content: center; gap: 10px; padding: 0 19px; color: #fff; background: linear-gradient(135deg, #168f87, #0c6267); border-radius: 999px; box-shadow: 0 8px 18px rgba(17, 112, 108, .18); font-size: 12px; font-weight: 800; transition: box-shadow .2s ease, transform .2s ease; }
        .landing-button:hover { color: #fff; box-shadow: 0 12px 24px rgba(17, 112, 108, .25); transform: translateY(-2px); }
        .landing-button span { font-size: 16px; font-weight: 400; }
        .landing-hero { position: relative; overflow: hidden; background: linear-gradient(115deg, #e7f4ef, #d7eeeb 62%, #edf5df); }
        .landing-hero::before, .landing-hero::after { position: absolute; width: 380px; height: 380px; border: 1px solid rgba(20,125,121,.12); border-radius: 50%; content: ''; pointer-events: none; }
        .landing-hero::before { top: -215px; right: -90px; box-shadow: 0 0 0 28px rgba(20,125,121,.025), 0 0 0 56px rgba(20,125,121,.02); }
        .landing-hero::after { bottom: -290px; left: -120px; }
        .landing-hero-inner { position: relative; z-index: 1; display: grid; min-height: 520px; grid-template-columns: 1.1fr .9fr; align-items: center; gap: 48px; width: min(1080px, calc(100% - 48px)); padding: 70px 0; margin: 0 auto; }
        .landing-copy { max-width: 570px; }
        .landing-eyebrow { color: #147d79; font-size: 10px; font-weight: 700; letter-spacing: 1.5px; }
        .landing-copy h1 { margin: 16px 0 18px; font-family: 'Fraunces', Georgia, serif; font-size: clamp(42px, 5.6vw, 68px); font-weight: 500; letter-spacing: -.8px; line-height: 1.03; }
        .landing-copy h1 span { color: #147d79; }
        .landing-copy p { max-width: 450px; margin: 0; color: #657477; font-size: 15px; line-height: 1.75; }
        .landing-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 20px; margin-top: 28px; }
        .landing-text-link { color: #0e5d5a; font-size: 12px; font-weight: 700; }
        .landing-text-link:hover { text-decoration: underline; text-underline-offset: 4px; }
        .landing-visual { position: relative; display: grid; min-height: 330px; place-items: center; }
        .landing-water { position: relative; display: grid; width: min(300px, 74vw); aspect-ratio: 1; place-items: center; background: rgba(255,255,255,.62); border: 1px solid rgba(20,125,121,.12); border-radius: 50%; box-shadow: 0 0 0 20px rgba(255,255,255,.22), 0 30px 75px rgba(35,51,55,.12); animation: landing-float 6s ease-in-out infinite; }
        .landing-water svg { width: 52%; height: auto; color: #147d79; }
        .landing-badge { position: absolute; right: 1%; bottom: 16%; padding: 13px 16px; background: #fff; border: 1px solid #e5eae6; border-radius: 6px; box-shadow: 0 8px 26px rgba(35,51,55,.08); }
        .landing-badge strong, .landing-badge small { display: block; }
        .landing-badge strong { font-family: 'Fraunces', Georgia, serif; font-size: 17px; font-weight: 500; }
        .landing-badge small { margin-top: 3px; color: #738184; font-size: 9px; }
        .landing-section { width: min(1080px, calc(100% - 48px)); padding: 76px 0; margin: 0 auto; }
        .landing-section-heading { max-width: 510px; margin-bottom: 28px; }
        .landing-section-heading h2 { margin: 8px 0 8px; font-family: 'Fraunces', Georgia, serif; font-size: 32px; font-weight: 500; }
        .landing-section-heading p { margin: 0; color: #738184; font-size: 13px; line-height: 1.7; }
        .landing-steps { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }
        .landing-step { padding: 25px; background: #fff; border: 1px solid #e1ebe5; border-radius: 16px; box-shadow: 0 10px 30px rgba(31,67,58,.045); transition: box-shadow .2s ease, transform .2s ease; }
        .landing-step:hover { box-shadow: 0 18px 38px rgba(31,67,58,.1); transform: translateY(-4px); }
        .landing-step-number { display: grid; width: 34px; height: 34px; place-items: center; color: #0e5d5a; background: #e8f4ef; border-radius: 50%; font-size: 12px; font-weight: 700; }
        .landing-step h3 { margin: 17px 0 7px; font-family: 'Fraunces', Georgia, serif; font-size: 20px; font-weight: 500; }
        .landing-step p { margin: 0; color: #738184; font-size: 12px; line-height: 1.7; }
        @keyframes landing-float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-9px); } }
        .landing-footer { display: flex; justify-content: space-between; gap: 16px; padding: 20px max(24px, calc((100% - 1080px) / 2)); color: #96a09e; border-top: 1px solid #e5eae6; font-size: 10px; }
        .landing-footer strong { color: #657477; font-weight: 600; }
        @media (max-width: 760px) {
            .landing-header { min-height: 72px; }
            .landing-nav { gap: 14px; }
            .landing-nav-link { display: none; }
            .landing-hero-inner { min-height: auto; grid-template-columns: 1fr; gap: 16px; padding: 58px 0 46px; }
            .landing-copy { max-width: none; }
            .landing-copy h1 { font-size: clamp(42px, 10vw, 60px); }
            .landing-copy p { font-size: 14px; }
            .landing-visual { min-height: 260px; }
            .landing-water { width: 220px; }
            .landing-badge { right: 10%; bottom: 10%; }
            .landing-section { padding: 54px 0; }
            .landing-steps { grid-template-columns: 1fr; }
        }
        @media (max-width: 420px) {
            .landing-header, .landing-hero-inner, .landing-section { width: calc(100% - 32px); }
            .landing-brand { font-size: 13px; }
            .landing-brand-mark { width: 38px; height: 38px; }
            .landing-header > .landing-button { min-height: 38px; padding: 0 11px; font-size: 11px; }
            .landing-footer { flex-direction: column; padding-right: 16px; padding-left: 16px; }
        }
        @media (prefers-reduced-motion: reduce) { .landing-water { animation: none; } .landing-step, .landing-button { transition: none; } }
    </style>
</head>
<body>
    @php($signedIn = auth()->check())
    <div class="landing">
        <header class="landing-header">
            <a class="landing-brand" href="{{ route('home') }}" aria-label="Albay Water Refilling Station home">
                <span class="landing-brand-mark" aria-hidden="true">
                    <svg viewBox="0 0 32 32" fill="none">
                        <path d="M16 3C12 9 7 14.2 7 20a9 9 0 0 0 18 0c0-5.8-5-11-9-17Z" fill="currentColor"/>
                        <path d="M11 21c.4 2.1 1.9 3.4 4 3.8" stroke="#fff" stroke-width="1.7" stroke-linecap="round"/>
                    </svg>
                </span>
                <span>Albay Water<br>Refilling Station</span>
            </a>
            <nav class="landing-nav" aria-label="Main navigation">
                <a class="landing-nav-link" href="#how-it-works">How it works</a>
                @if ($signedIn)
                    <a class="landing-button" href="{{ route('dashboard') }}">Go to dashboard <span aria-hidden="true">→</span></a>
                @else
                    <a class="landing-nav-link" href="{{ route('login') }}">Sign in</a>
                    <a class="landing-button" href="{{ route('register') }}">Create an account <span aria-hidden="true">→</span></a>
                @endif
            </nav>
        </header>

        <main>
            <section class="landing-hero">
                <div class="landing-hero-inner">
                    <div class="landing-copy">
                        <span class="landing-eyebrow">REFILLS MADE SIMPLE</span>
                        <h1>Good water,<br><span>delivered with ease.</span></h1>
                        <p>Order your water refills online, keep track of every order, and let our team take care of the rest.</p>
                        <div class="landing-actions">
                            @if ($signedIn)
                                <a class="landing-button" href="{{ route('dashboard') }}">View your account <span aria-hidden="true">→</span></a>
                            @else
                                <a class="landing-button" href="{{ route('register') }}">Get started <span aria-hidden="true">→</span></a>
                                <a class="landing-text-link" href="{{ route('login') }}">Already have an account?</a>
                            @endif
                        </div>
                    </div>
                    <div class="landing-visual" aria-hidden="true">
                        <div class="landing-water">
                            <svg viewBox="0 0 160 200" fill="none">
                                <path d="M56 20h48v24H56z" fill="#147d79"/>
                                <path d="M50 44h60c4 0 7 3 8 7l18 116c2 11-6 21-17 21H41c-11 0-19-10-17-21L42 51c1-4 4-7 8-7Z" fill="currentColor" opacity=".16"/>
                                <path d="M50 44h60c4 0 7 3 8 7l18 116c2 11-6 21-17 21H41c-11 0-19-10-17-21L42 51c1-4 4-7 8-7Z" stroke="currentColor" stroke-width="5"/>
                                <path d="M34 134c25-11 67 12 101-2l5 35c2 11-6 21-17 21H41c-11 0-19-10-17-21l10-33Z" fill="currentColor" opacity=".55"/>
                                <path d="M55 77h50M52 91h56" stroke="#fff" stroke-width="5" stroke-linecap="round" opacity=".8"/>
                            </svg>
                        </div>
                        <div class="landing-badge"><strong>Order in minutes</strong><small>Simple, convenient refills</small></div>
                    </div>
                </div>
            </section>

            <section class="landing-section" id="how-it-works">
                <div class="landing-section-heading">
                    <span class="landing-eyebrow">A BETTER REFILL ROUTINE</span>
                    <h2>From order to doorstep.</h2>
                    <p>Manage your refill requests online and follow their progress from your account.</p>
                </div>
                <div class="landing-steps">
                    <article class="landing-step">
                        <span class="landing-step-number">01</span>
                        <h3>Create your account</h3>
                        <p>Add your contact and delivery details once, then place refill orders whenever you need them.</p>
                    </article>
                    <article class="landing-step">
                        <span class="landing-step-number">02</span>
                        <h3>Place an order</h3>
                        <p>Send your refill request online and include any helpful delivery notes for our team.</p>
                    </article>
                    <article class="landing-step">
                        <span class="landing-step-number">03</span>
                        <h3>Track its progress</h3>
                        <p>Check your order history and see updates as your request is reviewed and fulfilled.</p>
                    </article>
                </div>
            </section>
        </main>

        <footer class="landing-footer">
            <strong>Albay Water Refilling Station</strong>
            <span>Refills, delivered simply.</span>
        </footer>
    </div>
</body>
</html>
