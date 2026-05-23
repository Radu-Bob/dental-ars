@extends('layouts.app')

@section('left_content')
<div class="bg-white p-6 rounded-xl shadow-md space-y-4">
    <h2 class="text-xl font-semibold text-gray-800">Partner Search</h2>
    
    {{-- Search again form --}}
    <form action="{{ route('patients.partner.results') }}" method="GET">
        <div class="mb-4">
            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Name:</label>
            <input type="text" name="q" value="{{ request('q') }}" 
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2"
                   style="--tw-ring-color: {{ config('app.theme_color') == 'gray' ? '#6b7280' : config('app.theme_color') }};">
        </div>
        
        <button type="submit" class="w-full text-white font-semibold py-2 rounded-lg shadow-md hover:opacity-90 transition"
                style="background-color: {{ config('app.theme_color') == 'gray' ? '#4b5563' : config('app.theme_color') }};">
            Search Again
        </button>
    </form>

    <div class="mt-6 border-t pt-4">
        <a href="{{ route('patients.index') }}" class="w-full text-center block bg-gray-200 text-gray-700 font-semibold py-2 rounded-lg hover:bg-gray-300">
            Back to Local List
        </a>
    </div>
</div>
@endsection
@section('content')
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Partner Clinic Search Results</h2>

    @if($remotePatients->count() > 0)
        <div class="space-y-4">
            @foreach($remotePatients as $remotePatient)
                <div class="bg-white rounded-lg shadow-md overflow-hidden border-l-4" 
                     style="border-color: {{ config('app.theme_color') == 'gray' ? '#4b5563' : config('app.theme_color') }};">
                    <div class="p-6">
                        <h3 class="text-xl font-bold text-gray-900 uppercase">
                            <a href="{{ route('patients.partner.show', ['patient_id' => $remotePatient->patient_id]) }}" class="hover:underline text-blue-600">
                                {{ $remotePatient->name }}
                            </a>
                        </h3>
                        <p class="text-sm text-gray-600 mt-1">
                            <strong>ID:</strong> {{ $remotePatient->patient_id }} |
                            <strong>Acc No:</strong> {{ $remotePatient->acc_no }} |
                            <strong>Tel:</strong> {{ $remotePatient->tel }}
                        </p>
                        <p class="text-xs text-gray-400 mt-2">Click the name to view full details before importing.</p>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded shadow-sm">
            <p class="font-bold">No Records Found</p>
            <p>No matches found in the partner database for "{{ request('q') }}".</p>
        </div>
    @endif
@endsection