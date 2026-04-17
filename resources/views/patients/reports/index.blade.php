@extends('layouts.app')

@section('title', 'System Reports')

{{--
NOTE TO DEVELOPER:
The $reports array is now correctly passed from the ReportController
via the 'return view('reports.index', compact('reports'))' call.
The temporary @php block has been successfully removed.
--}}

{{--
-----------------------------------------------------
1. LEFT NAVIGATION SECTION (Quick Links/Actions)
-----------------------------------------------------
--}}
@section('left_content')

<div class="bg-white p-6 rounded-xl shadow-lg space-y-4">
<h2 class="text-xl font-bold text-gray-800 border-b pb-3 mb-3">Reports Navigation</h2>

<p class="text-sm text-gray-600 mb-4">Jump directly to a specific report type.</p>

<div class="space-y-2">
    {{-- Iterate over the $reports array supplied by the Controller --}}
    @foreach ($reports as $report)
        <a href="{{ route($report['route']) }}" 
           class="w-full text-left block text-sm bg-gray-100 text-gray-700 font-medium py-2 px-3 rounded-lg hover:bg-gray-200 transition duration-150">
            {{ $report['title'] }}
        </a>
    @endforeach
</div>

{{-- Example of a separate action --}}
<!--
<div class="pt-4 border-t mt-4">
    {{-- Custom button functionality --}}
    <button onclick="alert('This feature requires controller implementation.');" 
            class="w-full text-center block bg-green-500 text-white font-semibold py-2 rounded-lg shadow-md hover:bg-green-700 transition duration-300">
        Generate Custom Report
    </button>
</div>
-->


</div>
@endsection

{{--
-----------------------------------------------------
2. MAIN CONTENT SECTION (Dashboard Grid)
-----------------------------------------------------
--}}
@section('content')

<div class="p-6 bg-white rounded-xl shadow-lg">
<h1 class="text-3xl font-bold mb-6 text-gray-800">Reports Dashboard</h1>

{{-- Check if the $reports variable exists and is not empty --}}
@if(isset($reports) && count($reports) > 0)
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @foreach ($reports as $report)
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-5 hover:shadow-xl transition duration-300 transform hover:scale-[1.02]">
                <h2 class="text-xl font-bold text-clinic-bold mb-2">{{ $report['title'] }}</h2>
                <p class="text-gray-600 mb-4 text-sm">{{ $report['description'] }}</p>
                <a href="{{ route($report['route']) }}" 
                   class="inline-block btn-clinic-primary font-semibold py-2 px-4 rounded-lg shadow-md transition duration-300">
                    View Report
                </a>
            </div>
        @endforeach
    </div>
@else
     <div class="bg-yellow-50 border border-yellow-200 p-4 rounded-lg">
         <p class="text-gray-600">This page is currently under construction. Please inform the IT department that their data is missing.</p>
     </div>
@endif


</div>
@endsection