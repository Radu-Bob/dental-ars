@extends('layouts.app')

@section('title', 'Patients Attending')

@section('left_content')
    @include('patients.reports.partials.reports-sidebar')
@endsection

@section('content')

<div class="p-6 bg-white rounded-xl shadow-lg max-w-2xl mx-auto mt-6">

    <h1 class="text-2xl font-bold mb-2 text-gray-800">Patients Attending</h1>
    <p class="text-sm text-gray-500 mb-8">Choose the attendance report you want to view.</p>

    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

        {{-- Month Attendance --}}
        <div class="flex flex-col gap-3 p-6 border-2 border-gray-200 rounded-xl opacity-60 cursor-not-allowed">
            <div class="text-3xl">📅</div>
            <div>
                <div class="flex items-center gap-2">
                    <div class="text-lg font-bold text-gray-800">Month Attendance</div>
                    <span class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full">Coming soon</span>
                </div>
                <p class="text-sm text-gray-500 mt-1 leading-relaxed">
                    All patients with clinical or estimate records within a selected month.
                </p>
            </div>
        </div>

        {{-- Quarter Attendance --}}
        <div class="flex flex-col gap-3 p-6 border-2 border-gray-200 rounded-xl opacity-60 cursor-not-allowed">
            <div class="text-3xl">📆</div>
            <div>
                <div class="flex items-center gap-2">
                    <div class="text-lg font-bold text-gray-800">Quarter Attendance</div>
                    <span class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full">Coming soon</span>
                </div>
                <p class="text-sm text-gray-500 mt-1 leading-relaxed">
                    Attendance summary aggregated across a full quarter — three-month view.
                </p>
            </div>
        </div>

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

    </div>

</div>

@endsection
