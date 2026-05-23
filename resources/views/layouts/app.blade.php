<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.clinic_name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
    <!-- <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    -->
    @php
        $themeColor = config('app.theme_color');
    @endphp
    <style>
        :root {
            /* 1. Core Branding Colors */
            --clinic-primary: {{ $themeColor }};
            
            /* 2. Derived States (Calculated by the Browser) */
            /* Hover: 20% darker */
            --clinic-hover: color-mix(in srgb, var(--clinic-primary), black 40%);
            /* Inactive/Muted: 60% faded toward white */
            --clinic-inactive: color-mix(in srgb, var(--clinic-primary), white 10%);
            /* Very light background for panels/sections */
            --clinic-bg-light: color-mix(in srgb, var(--clinic-primary), black 5%);
            /* A slightly stronger version for the hover state */
            --clinic-secondary-hover: color-mix(in srgb, var(--clinic-primary), white 50%);

            /* --- NEW: Simulation of "600" Weight (like green-600) --- */
            /* Mix 15% black to get that richer, deeper color */
            --clinic-bold: color-mix(in srgb, var(--clinic-primary), black 15%);
            
            /* Mix 30% black for the button hover state (like green-700) */
            --clinic-bold-hover: color-mix(in srgb, var(--clinic-primary), black 30%);

            /* The "Cool Grey" replacement: 
            We take 92% of a light grey and mix in 8% of the clinic color.
            This creates a 'tinted' grey that feels premium.
            */
            --clinic-grey-bg: color-mix(in srgb, var(--clinic-primary), #e5e7eb 92%);
            
            /* A slightly deeper version for the text to keep it readable */
            --clinic-grey-text: color-mix(in srgb, var(--clinic-primary), #374151 85%);
            
            /* The Hover state: just a bit more of the theme color coming through */
            --clinic-grey-hover: color-mix(in srgb, var(--clinic-primary), #d1d5db 80%);
            
            /* 3. Tint — very light wash of clinic color for panel backgrounds */
            --clinic-tint: color-mix(in srgb, var(--clinic-primary), white 92%);

            /* 4. Semantic Accents (Keeping these consistent across clinics) */
            --theme-accent: #ea580c;  /* Equivalent to orange-600 */
            --theme-warning: #dc2626; /* Equivalent to red-600 */
        }
            /* UTILITIES */
                .bg-clinic { background-color: var(--clinic-primary) !important; }
                .bg-clinic-tint { background-color: var(--clinic-tint) !important; }
                .border-clinic { border-color: var(--clinic-primary) !important; }
                .text-clinic { color: var(--clinic-primary) !important; }
                
                /* NEW: Text class for the darker/bolder look */
                .text-clinic-bold { color: var(--clinic-bold) !important; }
                .text-clinic-bold:hover { color: var(--clinic-bold-hover) !important; }

                /* NEW: Main Action Button (Replaces manual styles) */
                .btn-clinic-primary {
                    background-color: var(--clinic-bold) !important; /* Starts at the "600" look */
                    color: white !important;
                    transition: all 0.3s ease;
                }
                .btn-clinic-primary:hover {
                    background-color: var(--clinic-bold-hover) !important; /* Darkens on hover */
                }

                /* 2. NEW: The "Standard" Button (Lighter - uses base Theme Color) */
                .btn-clinic-standard {
                    background-color: var(--clinic-primary) !important;
                    color: white !important;
                    transition: all 0.3s ease;
                }
                .btn-clinic-standard:hover {
                    /* On hover, it goes slightly darker (to the 20% mix) */
                    background-color: var(--clinic-hover) !important; 
                }

                .bg-clinic-secondary { background-color: var(--clinic-secondary) !important; color: var(--clinic-primary) !important; border: 1px solid var(--clinic-primary); }
                .bg-clinic-secondary:hover { background-color: var(--clinic-secondary-hover) !important; color: var(--clinic-bold) !important;}

                .btn-clinic-grey {
                    background-color: var(--clinic-grey-bg) !important;
                    color: var(--clinic-grey-text) !important;
                    transition: all 0.3s ease;
                }
                .btn-clinic-grey:hover {
                    background-color: var(--clinic-grey-hover) !important;
                }

        /* Layout Elements */
        /*
        .header, .navbar {
            background-color: var(--clinic-primary) !important;
            color: white; 
        }
        */

        /* Navigation Logic */
        .nav-link {
            color: var(--clinic-inactive) !important;
            /*color: black;*/
            transition: all 0.2s ease-in-out;
            text-decoration: none;
        }

        .nav-link:hover {
            /*color: white !important; /* Glow white on the primary background */
            color: var(--clinic-hover) !important;
            /*color: blue;*/
            /*font-weight: bold;*/
        }

        .nav-link-active {
            color: var(--clinic-inactive) !important;
            font-weight: bold;
            border-bottom: 2px solid white;
        }

        /* Flatpickr Theme Overrides */
        .flatpickr-day.selected, 
        .flatpickr-day.startRange, 
        .flatpickr-day.endRange, 
        .flatpickr-day.selected.prevMonthDay, 
        .flatpickr-day.selected.nextMonthDay, 
        .flatpickr-day.selected:hover, 
        .flatpickr-day.startRange:hover, 
        .flatpickr-day.endRange:hover, 
        .flatpickr-day.prevMonthDay.selected:hover, 
        .flatpickr-day.nextMonthDay.selected:hover {
            background: var(--clinic-primary) !important;
            border-color: var(--clinic-primary) !important;
            color: white !important;
        }

        .flatpickr-day.today {
            border-color: var(--clinic-primary) !important;
        }

        .flatpickr-day.today:hover {
            background: var(--clinic-primary) !important;
            color: white !important;
        }

        /* Arrow and Month highlight */
        .flatpickr-months .flatpickr-prev-month:hover svg, 
        .flatpickr-months .flatpickr-next-month:hover svg {
            fill: var(--clinic-primary) !important;
        }

        .flatpickr-current-month .flatpickr-monthDropdown-months:hover {
            background: var(--clinic-bg-light) !important;
        }
    </style>

</head>
<body class="clinic-{{ $clinic_id }}">

<header class="header">
    {{-- 1. Logo Section (Replaces the old <h1> title) --}}
    <div class="flex items-center">
        <img src="{{ asset('images/logo_' . config('app.clinic_id') . '.png') }}"
            alt="{{ config('app.clinic_name') }}"
            class="h-12 w-auto">
    </div>

    <nav class="flex space-x-6">
        {{-- Dashboard Link --}}
        <a href="{{ route('dashboard') }}" 
        class="nav-link {{ Route::is('dashboard') ? 'nav-link-active' : '' }}">
            Dashboard
        </a>
        
        {{-- Search / Listing Link — nurse gets their own route --}}
        @if(Auth::user()->is_nurse)
            <a href="{{ route('nurse.patients.index') }}"
            class="nav-link {{ Route::is('nurse.patients.*') ? 'nav-link-active' : '' }}">
                Search / Listing
            </a>
        @else
            <a href="{{ route('patients.index') }}"
            class="nav-link {{ (Route::is('patients.index') || Route::is('patients.show')) ? 'nav-link-active' : '' }}">
                Search / Listing
            </a>
        @endif

        {{-- New Patient Link — shared --}}
        <a href="{{ route('patients.register') }}"
        class="nav-link {{ (Route::is('patients.register') || Route::is('patients.create')) ? 'nav-link-active' : '' }}">
            New Patient
        </a>

        {{-- Reports Link — nurses get their own placeholder page --}}
        @if(Auth::user()->is_nurse)
            <a href="{{ route('nurse.reports') }}"
            class="nav-link {{ Route::is('nurse.reports') ? 'nav-link-active' : '' }}">
                Reports
            </a>
        @else
            <a href="{{ route('reports.index') }}"
            class="nav-link {{ Route::is('reports.index') ? 'nav-link-active' : '' }}">
                Reports
            </a>
        @endif
    </nav>
    {{-- 3. THE NEW DIGITAL WATCH MODULE --}}
    <div class="clock-module flex items-center space-x-3">
        <div id="digital-clock" class="digital-clock-frame" title="{{ date('D, d M Y') }}">
            <span id="clock-display" class="clock-on">00:00</span>
        </div>
        <button id="clock-toggle" class="clock-toggle-button" aria-label="Toggle digital clock">
            <i id="toggle-icon" class="fas"></i> 
        </button>
    </div>
    {{-- END OF DIGITAL WATCH MODULE --}}
    <div>
        @auth
        <div class="user-dropdown">
            <button onclick="toggleDropdown()" class="user-dropdown-button">
                {{ Auth::user()->name }} ▼
            </button>
            <div id="myDropdown" class="user-dropdown-content">
                <a href="{{ route('profile.edit') }}">Edit My Profile</a>
                
                @if(Auth::user()->role === 'admin')
                    <a href="{{ route('users.index') }}">User Management</a>
                @endif
                
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit">Logout</button>
                </form>
            </div>
        </div>
        
        @else
            <a href="{{ route('login') }}" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">Login</a>
        @endauth
    </div>
</header>

    <main class="main-content">
        <div class="left-column">@yield('left_content')</div>
        <div class="right-column">@yield('content')</div>
    </main>

    <footer class="footer">
        <p>&copy; {{ date('Y') }} {{ config('app.clinic_name') }}. All rights reserved.</p>
    </footer>
    <script src="{{ asset('js/user-dropdown.js') }}"></script>

   @yield('scripts')
    @stack('scripts')
</body>
</html>