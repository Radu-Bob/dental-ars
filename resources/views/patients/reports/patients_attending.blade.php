@extends('layouts.app')

@section('title', 'Patients Attending Report')

@section('left_content')
    @include('patients.reports.partials.reports-sidebar')
@endsection

@section('content')
<div class="p-6 bg-white rounded-xl shadow-lg">
    <h1 class="text-3xl font-bold mb-6 text-gray-800">Patients Attending Report</h1>
    
    <p class="text-gray-600 mb-6">
        This page will display data about active patient attendance and appointment statuses.
    </p>

    <div class="bg-indigo-50 border border-indigo-200 p-4 rounded-lg">
        <p class="text-indigo-700">Report content implementation pending.</p>
    </div>
</div>
@endsection