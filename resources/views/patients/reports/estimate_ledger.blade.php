@extends('layouts.app')

@section('title', 'Estimate Report')

@section('left_content')
    @include('patients.reports.partials.reports-sidebar')
@endsection

@section('content')
<div class="p-6 bg-white rounded-xl shadow-lg">
    <h1 class="text-3xl font-bold mb-6 text-gray-800">Estimate Report</h1>
    
    <p class="text-gray-600 mb-6">
        This report will detail the status of treatment estimates: outstanding, accepted, or rejected.
    </p>

    <div class="bg-indigo-50 border border-indigo-200 p-4 rounded-lg">
        <p class="text-indigo-700">Report content implementation pending.</p>
    </div>
</div>
@endsection