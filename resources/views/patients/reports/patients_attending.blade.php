@extends($layout ?? 'layouts.app')

@section('title', 'Patients Attending')

@section('left_content')
    @include('patients.reports.partials.reports-sidebar')
@endsection

@section('content')

<div class="p-6 bg-white rounded-xl shadow-lg max-w-2xl mx-auto mt-6">

    <h1 class="text-2xl font-bold mb-2 text-gray-800">Patients Attending</h1>
    <p class="text-sm text-gray-500 mb-8">Choose the attendance report you want to view.</p>

    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

        @if (Auth::user()->is_admin || Auth::user()->is_doctor)
            {{-- Month Attendance --}}
            <a href="{{ route('reports.patients_attending.month_attendance') }}"
               class="group flex flex-col gap-3 p-6 border-2 border-gray-200 rounded-xl hover:border-clinic hover:shadow-md transition">
                <div class="text-3xl">📅</div>
                <div>
                    <div class="text-lg font-bold text-gray-800 group-hover:text-clinic transition">Month Attendance</div>
                    <p class="text-sm text-gray-500 mt-1 leading-relaxed">
                        All patients with clinical or estimate records within a selected month.
                    </p>
                </div>
                <div class="mt-auto text-sm font-medium text-clinic opacity-0 group-hover:opacity-100 transition">
                    Open Report →
                </div>
            </a>

            {{-- Quarter Attendance --}}
            <a href="{{ route('reports.patients_attending.quarter_attendance') }}"
               class="group flex flex-col gap-3 p-6 border-2 border-gray-200 rounded-xl hover:border-clinic hover:shadow-md transition">
                <div class="text-3xl">📆</div>
                <div>
                    <div class="text-lg font-bold text-gray-800 group-hover:text-clinic transition">Quarter Attendance</div>
                    <p class="text-sm text-gray-500 mt-1 leading-relaxed">
                        Attendance summary aggregated across a full quarter — three-month view.
                    </p>
                </div>
                <div class="mt-auto text-sm font-medium text-clinic opacity-0 group-hover:opacity-100 transition">
                    Open Report →
                </div>
            </a>

            {{-- Statistics Month --}}
            <a href="{{ route('reports.patients_attending.statistics_month') }}"
               class="group flex flex-col gap-3 p-6 border-2 border-gray-200 rounded-xl hover:border-clinic hover:shadow-md transition">
                <div class="text-3xl">📊</div>
                <div>
                    <div class="text-lg font-bold text-gray-800 group-hover:text-clinic transition">Statistics Month</div>
                    <p class="text-sm text-gray-500 mt-1 leading-relaxed">
                        All patients attending in a selected month — clinical and estimate records with diagnostic, description, and FREE flag.
                    </p>
                </div>
                <div class="mt-auto text-sm font-medium text-clinic opacity-0 group-hover:opacity-100 transition">
                    Open Report →
                </div>
            </a>
        @endif

        {{-- Appointments Month --}}
        <a href="{{ route('reports.patients_attending.appointments_month') }}"
           class="group flex flex-col gap-3 p-6 border-2 border-gray-200 rounded-xl hover:border-clinic hover:shadow-md transition">
            <div class="text-3xl">🗓️</div>
            <div>
                <div class="text-lg font-bold text-gray-800 group-hover:text-clinic transition">Appointments Month</div>
                <p class="text-sm text-gray-500 mt-1 leading-relaxed">
                    All booked appointments within a selected month, by date and time — with per-patient appointment history.
                </p>
            </div>
            <div class="mt-auto text-sm font-medium text-clinic opacity-0 group-hover:opacity-100 transition">
                Open Report →
            </div>
        </a>

    </div>

</div>

@endsection
