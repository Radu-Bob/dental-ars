@extends('layouts.app')

@section('title', 'Patient Demographics Report')


@section('left_content')
    @include('patients.reports.partials.reports-sidebar')
@endsection

@section('content')
<div class="p-6 bg-white rounded-xl shadow-lg">
    <h1 class="text-3xl font-bold mb-6 text-gray-800">Patient Demographics Report</h1>
    
    <p class="text-gray-600 mb-6">
        This page will display a comprehensive summary of patient demographic data, contact information, and insurance details.
    </p>

    <div class="bg-indigo-50 border border-indigo-200 p-4 rounded-lg">
        <p class="text-indigo-700">Report content implementation pending.</p>
    </div>
</div>
@endsection