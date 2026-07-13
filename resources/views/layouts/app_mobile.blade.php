<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>{{ config('app.clinic_name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
    @php
        $themeColor = config('app.theme_color');
    @endphp
    <style>
        :root {
            --clinic-primary: {{ $themeColor }};
            --clinic-hover: color-mix(in srgb, var(--clinic-primary), black 40%);
            --clinic-inactive: color-mix(in srgb, var(--clinic-primary), white 10%);
            --clinic-bg-light: color-mix(in srgb, var(--clinic-primary), black 5%);
            --clinic-secondary-hover: color-mix(in srgb, var(--clinic-primary), white 50%);
            --clinic-bold: color-mix(in srgb, var(--clinic-primary), black 15%);
            --clinic-bold-hover: color-mix(in srgb, var(--clinic-primary), black 30%);
            --clinic-grey-bg: color-mix(in srgb, var(--clinic-primary), #e5e7eb 92%);
            --clinic-grey-text: color-mix(in srgb, var(--clinic-primary), #374151 85%);
            --clinic-grey-hover: color-mix(in srgb, var(--clinic-primary), #d1d5db 80%);
            --clinic-tint: color-mix(in srgb, var(--clinic-primary), white 92%);
            --theme-accent: #ea580c;
            --theme-warning: #dc2626;
        }

        /* Utility classes — identical to desktop layout */
        .bg-clinic          { background-color: var(--clinic-primary) !important; }
        .bg-clinic-tint     { background-color: var(--clinic-tint) !important; }
        .border-clinic      { border-color: var(--clinic-primary) !important; }
        .text-clinic        { color: var(--clinic-primary) !important; }
        .text-clinic-bold   { color: var(--clinic-bold) !important; }
        .text-clinic-bold:hover { color: var(--clinic-bold-hover) !important; }

        .btn-clinic-primary       { background-color: var(--clinic-bold) !important; color: white !important; transition: all 0.3s ease; }
        .btn-clinic-primary:hover { background-color: var(--clinic-bold-hover) !important; }
        .btn-clinic-standard       { background-color: var(--clinic-primary) !important; color: white !important; transition: all 0.3s ease; }
        .btn-clinic-standard:hover { background-color: var(--clinic-hover) !important; }
        .bg-clinic-secondary       { background-color: var(--clinic-secondary) !important; color: var(--clinic-primary) !important; border: 1px solid var(--clinic-primary); }
        .bg-clinic-secondary:hover { background-color: var(--clinic-secondary-hover) !important; color: var(--clinic-bold) !important; }
        .btn-clinic-grey       { background-color: var(--clinic-grey-bg) !important; color: var(--clinic-grey-text) !important; transition: all 0.3s ease; }
        .btn-clinic-grey:hover { background-color: var(--clinic-grey-hover) !important; }

        /* Flatpickr theme overrides */
        .flatpickr-day.selected, .flatpickr-day.startRange, .flatpickr-day.endRange,
        .flatpickr-day.selected.prevMonthDay, .flatpickr-day.selected.nextMonthDay,
        .flatpickr-day.selected:hover, .flatpickr-day.startRange:hover, .flatpickr-day.endRange:hover {
            background: var(--clinic-primary) !important;
            border-color: var(--clinic-primary) !important;
            color: white !important;
        }
        .flatpickr-day.today { border-color: var(--clinic-primary) !important; }
        .flatpickr-day.today:hover { background: var(--clinic-primary) !important; color: white !important; }
        .flatpickr-months .flatpickr-prev-month:hover svg,
        .flatpickr-months .flatpickr-next-month:hover svg { fill: var(--clinic-primary) !important; }
        .flatpickr-current-month .flatpickr-monthDropdown-months:hover { background: var(--clinic-bg-light) !important; }

        /* ===== MOBILE OVERRIDES ===== */
        body {
            padding: 0 !important;
            background-size: 200px 200px;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* Sticky header bar */
        .mobile-header {
            position: sticky;
            top: 0;
            z-index: 200;
            background-color: #ffffff;
            padding: 0.6rem 1rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
        }

        .mobile-header-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        /* Hamburger button — 44px min touch target */
        .hamburger-btn {
            min-width: 44px;
            min-height: 44px;
            background: none;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            transition: background 0.2s;
        }
        .hamburger-btn:hover, .hamburger-btn:focus { background: #f3f4f6; outline: none; }
        .hamburger-btn i { font-size: 1.4rem; color: var(--clinic-primary); }

        /* Slide-down nav panel */
        .mobile-nav {
            display: none;
            flex-direction: column;
            border-top: 1px solid #e5e7eb;
            margin-top: 0.5rem;
            padding-top: 0.25rem;
        }
        .mobile-nav.open { display: flex; }

        .mobile-nav-link {
            display: block;
            padding: 0.8rem 0.25rem;
            font-size: 0.95rem;
            font-weight: 500;
            color: var(--clinic-primary);
            text-decoration: none;
            border-bottom: 1px solid #f3f4f6;
        }
        .mobile-nav-link:hover { color: var(--clinic-hover); }
        .mobile-nav-link-active {
            font-weight: 700;
            border-left: 3px solid var(--clinic-primary);
            padding-left: 0.6rem;
        }
        .mobile-nav-divider { border-top: 1px solid #e5e7eb; margin: 0.4rem 0; }

        .mobile-nav-logout {
            display: block;
            width: 100%;
            text-align: left;
            padding: 0.8rem 0.25rem;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 0.95rem;
            font-weight: 600;
            color: #ef4444;
        }
        .mobile-nav-logout:hover { color: #b91c1c; }

        /* Main content: stacked columns */
        .mobile-main {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            padding: 0.75rem;
            flex: 1;
        }

        /* Right column gets its own white card on mobile */
        .mobile-right-panel {
            background-color: #ffffff;
            border-radius: 10px;
            padding: 1rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .mobile-footer {
            text-align: center;
            color: #9ca3af;
            font-size: 0.75rem;
            padding: 1rem;
            margin-top: auto;
        }

        /* Wide tables scroll horizontally rather than overflow */
        .mobile-right-panel table { min-width: 480px; }
        table { overflow-x: auto; display: block; }

        /* Tooltip fix for mobile (position relative to viewport) */
        .tooltip .tooltiptext {
            min-width: 200px;
            max-width: 280px;
            font-size: 0.8rem;
        }

        /* Larger tap targets for form controls */
        input, select, textarea, button { min-height: 40px; }
    </style>
</head>
<body class="clinic-{{ $clinic_id }}">

<header class="mobile-header">
    <div class="mobile-header-row">
        <img src="{{ asset('images/logo_' . config('app.clinic_id') . '.png') }}"
             alt="{{ config('app.clinic_name') }}"
             class="h-8 w-auto">

        @auth
        <div class="flex items-center gap-2">
            <span class="text-xs font-semibold text-gray-500 max-w-[100px] truncate">{{ Auth::user()->name }}</span>
            <button class="hamburger-btn" onclick="toggleMobileNav()" aria-label="Toggle menu">
                <i id="hamburger-icon" class="fas fa-bars"></i>
            </button>
        </div>
        @endauth
    </div>

    @auth
    <nav id="mobile-nav" class="mobile-nav">
        <a href="{{ route('dashboard') }}"
           class="mobile-nav-link {{ Route::is('dashboard') ? 'mobile-nav-link-active' : '' }}">
            Dashboard
        </a>

        @if(Auth::user()->is_nurse)
            <a href="{{ route('nurse.patients.index') }}"
               class="mobile-nav-link {{ Route::is('nurse.patients.*') ? 'mobile-nav-link-active' : '' }}">
                Search
            </a>
        @else
            <a href="{{ route('patients.index') }}"
               class="mobile-nav-link {{ (Route::is('patients.index') || Route::is('patients.show')) ? 'mobile-nav-link-active' : '' }}">
                Search
            </a>
        @endif

        <a href="{{ route('patients.register') }}"
           class="mobile-nav-link {{ (Route::is('patients.register') || Route::is('patients.create')) ? 'mobile-nav-link-active' : '' }}">
            New Patient
        </a>

        <a href="{{ route('appointments.index') }}?view=month#cal-toolbar"
           class="mobile-nav-link {{ Route::is('appointments.*') ? 'mobile-nav-link-active' : '' }}">
            Appointments
        </a>

        @if(Auth::user()->is_nurse)
            <a href="{{ route('nurse.reports') }}"
               class="mobile-nav-link {{ Route::is('nurse.reports') ? 'mobile-nav-link-active' : '' }}">
                Reports
            </a>
        @else
            <a href="{{ route('reports.index') }}"
               class="mobile-nav-link {{ Route::is('reports.index') ? 'mobile-nav-link-active' : '' }}">
                Reports
            </a>
        @endif

        <div class="mobile-nav-divider"></div>

        @if(Auth::user()->role === 'admin')
            <a href="{{ route('users.index') }}" class="mobile-nav-link">User Management</a>
        @endif
        <a href="{{ route('profile.edit') }}" class="mobile-nav-link">Edit Profile</a>

        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="mobile-nav-logout">Logout</button>
        </form>
    </nav>
    @endauth
</header>

<main class="mobile-main">
    <div class="mobile-left-panel">@yield('left_content')</div>
    <div class="mobile-right-panel">@yield('content')</div>
</main>

<footer class="mobile-footer">
    <p>&copy; {{ date('Y') }} {{ config('app.clinic_name') }}. All rights reserved.</p>
</footer>

<script>
    function toggleMobileNav() {
        const nav  = document.getElementById('mobile-nav');
        const icon = document.getElementById('hamburger-icon');
        nav.classList.toggle('open');
        icon.className = nav.classList.contains('open') ? 'fas fa-times' : 'fas fa-bars';
    }
    document.addEventListener('DOMContentLoaded', function () {
        // Close nav when a link is tapped
        document.querySelectorAll('.mobile-nav-link').forEach(function (link) {
            link.addEventListener('click', function () {
                document.getElementById('mobile-nav').classList.remove('open');
                document.getElementById('hamburger-icon').className = 'fas fa-bars';
            });
        });
    });
</script>

<script src="{{ asset('js/user-dropdown.js') }}"></script>
@yield('scripts')
@stack('scripts')
</body>
</html>
