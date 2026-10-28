<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Overview') · Albay Water Refilling Station</title>

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

@php
    $user = auth()->user();
    $isAdmin = $user?->role === 'admin';
    $isRider = $user?->role === 'rider';
    $userInitial = $user ? strtoupper(substr($user->name, 0, 1)) : 'U';
    $userRoleLabel = $isAdmin ? 'Administrator' : ($isRider ? 'Delivery rider' : 'Customer');
    $accountLabel = $isAdmin ? 'OPERATIONS' : ($isRider ? 'DELIVERY SERVICE' : 'YOUR ACCOUNT');
@endphp

<body class="{{ $isAdmin ? 'is-admin' : '' }} {{ $isRider ? 'is-rider' : '' }}">
    @if (! $isAdmin && ! $isRider)
        <span id="delivery-notification-poller" data-url="{{ route('notifications.unread') }}" hidden></span>
    @endif

    <div class="app-shell">
        <aside class="sidebar">
            <a class="brand" href="{{ route('dashboard') }}">
                <span class="brand-mark" aria-hidden="true">
                    <svg viewBox="0 0 32 32" fill="none">
                        <path d="M16 3C12 9 7 14.2 7 20a9 9 0 0 0 18 0c0-5.8-5-11-9-17Z" fill="currentColor"/>
                        <path d="M11 21c.4 2.1 1.9 3.4 4 3.8" stroke="#fff" stroke-width="1.7" stroke-linecap="round"/>
                    </svg>
                </span>
                <span class="brand-copy">
                    <span class="brand-name">Albay Water<br>Refilling Station</span>
                    <small>REFILL SERVICE</small>
                </span>
            </a>

            <div class="side-label">WORKSPACE</div>

            <nav class="side-nav" aria-label="Main navigation">
                @if ($isAdmin)
                    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                        <span class="nav-icon"></span>Overview
                    </a>
                    <a class="nav-link {{ request()->routeIs('admin.customers') ? 'active' : '' }}" href="{{ route('admin.customers') }}">
                        <span class="nav-icon"></span>Customers
                    </a>
                    <a class="nav-link {{ request()->routeIs('admin.approvals*') ? 'active' : '' }}" href="{{ route('admin.approvals') }}">
                        <span class="nav-icon"></span>Approvals
                    </a>
                    <a class="nav-link {{ request()->routeIs('admin.orders*') ? 'active' : '' }}" href="{{ route('admin.orders') }}">
                        <span class="nav-icon"></span>Orders
                    </a>
                    <a class="nav-link {{ request()->routeIs('admin.riders*') ? 'active' : '' }}" href="{{ route('admin.riders') }}">
                        <span class="nav-icon"></span>Riders
                    </a>
                    <a class="nav-link {{ request()->routeIs('admin.archive*') ? 'active' : '' }}" href="{{ route('admin.archive') }}">
                        <span class="nav-icon"></span>Costumer Data
                    </a>
                    <a class="nav-link {{ request()->routeIs('admin.administrators*') ? 'active' : '' }}" href="{{ route('admin.administrators') }}">
                        <span class="nav-icon"></span>Administrators
                    </a>
                @elseif ($isRider)
                    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                        <span class="nav-icon"></span>My deliveries
                    </a>
                @else
                    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                        <span class="nav-icon"></span>Overview
                    </a>
                    <a class="nav-link {{ request()->routeIs('orders.create') ? 'active' : '' }}" href="{{ route('orders.create') }}">
                        <span class="nav-icon"></span>Place an order
                    </a>
                @endif

                <a class="nav-link {{ request()->routeIs('password.edit') ? 'active' : '' }}" href="{{ route('password.edit') }}">
                    <span class="nav-icon"></span>Password &amp; security
                </a>
            </nav>

            <div class="sidebar-bottom">
                <div class="service-note">
                    <span class="live-dot"></span>
                    <div>
                        <strong>Service is active</strong>
                        <small>Refills, delivered simply.</small>
                    </div>
                </div>

                <div class="profile-row">
                    <span class="avatar">{{ $userInitial }}</span>
                    <span class="profile-copy">
                        <strong>{{ $user->name }}</strong>
                        <small>{{ $userRoleLabel }}</small>
                    </span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="signout" type="submit" aria-label="Sign out" title="Sign out">↗</button>
                    </form>
                </div>
            </div>
        </aside>

        <main class="main-area">
            <header class="topbar">
                <span>{{ $accountLabel }}</span>
                <div class="topbar-right">
                    <span class="today">{{ now()->format('l, F j') }}</span>
                    <span class="topbar-dot"></span>
                </div>
            </header>

            <div class="page-content">
                @if (session('success'))
                    <div class="flash-success" role="status">{{ session('success') }}</div>
                @endif

                @if ($errors->any())
                    <div class="flash-error" role="alert">Please check the highlighted fields and try again.</div>
                @endif

                @yield('content')
            </div>

            <footer class="page-footer">
                Albay Water Refilling Station <span>·</span> A better way to stay stocked.
            </footer>
        </main>
    </div>
</body>
</html>
