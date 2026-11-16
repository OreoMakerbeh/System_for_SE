<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', 'Overview') · Albay Water Refilling Station</title>

    @include('layouts.partials.assets')
</head>

@php
    $user = auth()->user();
    $isAdmin = $user?->role === 'admin';
    $isRider = $user?->role === 'rider';
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
                <span class="brand-copy">
                    <span class="brand-name">Albay Water<br>Refilling Station</span>
                    <small>REFILL SERVICE</small>
                </span>
            </a>

            <div class="side-label">WORKSPACE</div>

            <nav class="side-nav" aria-label="Main navigation">
                @if ($isAdmin)
                    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                        Overview
                    </a>
                    <a class="nav-link {{ request()->routeIs('admin.customers') ? 'active' : '' }}" href="{{ route('admin.customers') }}">
                        Customers
                    </a>
                    <a class="nav-link {{ request()->routeIs('admin.approvals*') ? 'active' : '' }}" href="{{ route('admin.approvals') }}">
                        Approvals
                    </a>
                    <a class="nav-link {{ request()->routeIs('admin.orders*') ? 'active' : '' }}" href="{{ route('admin.orders') }}">
                        Orders
                    </a>
                    <a class="nav-link {{ request()->routeIs('admin.feedback.*') ? 'active' : '' }}" href="{{ route('admin.feedback.index') }}">
                        Customer feedback
                    </a>
                    <a class="nav-link {{ request()->routeIs('admin.riders*') ? 'active' : '' }}" href="{{ route('admin.riders') }}">
                        Riders
                    </a>
                    <a class="nav-link {{ request()->routeIs('admin.archive*') ? 'active' : '' }}" href="{{ route('admin.archive') }}">
                        Customer Data
                    </a>
                    <a class="nav-link {{ request()->routeIs('admin.administrators*') ? 'active' : '' }}" href="{{ route('admin.administrators') }}">
                        Administrators
                    </a>
                @elseif ($isRider)
                    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                        My deliveries
                    </a>
                @else
                    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                        Overview
                    </a>
                    <a class="nav-link {{ request()->routeIs('orders.create') ? 'active' : '' }}" href="{{ route('orders.create') }}">
                        Place an order
                    </a>
                    <a class="nav-link {{ request()->routeIs('feedback.*') ? 'active' : '' }}" href="{{ route('feedback.index') }}">
                        Feedback
                    </a>
                @endif

                <a class="nav-link {{ request()->routeIs('password.edit') ? 'active' : '' }}" href="{{ route('password.edit') }}">
                    Password &amp; security
                </a>
            </nav>

            <div class="sidebar-bottom">
                <div class="service-note">
                    <div>
                        <strong>Service is active</strong>
                        <small>Refills, delivered simply.</small>
                    </div>
                </div>

                <div class="profile-row">
                    <span class="profile-copy">
                        <strong>{{ $user->name }}</strong>
                        <small>{{ $userRoleLabel }}</small>
                    </span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="signout" type="submit">Sign out</button>
                    </form>
                </div>
            </div>
        </aside>

        <main class="main-area">
            <header class="topbar">
                <span>{{ $accountLabel }}</span>
                <div class="topbar-right">
                    <span class="today">{{ now()->format('l, F j') }}</span>
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
