@extends('layouts.app')

@section('left_content')
    <div class="bg-white p-6 rounded-xl shadow-md space-y-4">
        <h2 class="text-xl font-semibold text-gray-800">New Partner Search</h2>
        
        {{-- The form allows the user to try again immediately --}}
        <form action="{{ route('patients.partner.results') }}" method="GET">
            <div class="mb-4">
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Name:</label>
                <input type="text" name="q" value="{{ request('q') }}" 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2"
                       style="--tw-ring-color: {{ config('app.theme_color') }};">
            </div>
            
            <button type="submit" class="w-full text-white font-semibold py-2 rounded-lg shadow-md transition duration-300"
                    style="background-color: {{ config('app.theme_color') }};">
                Search Again
            </button>
        </form>

        <div class="mt-6">
            <a href="{{ route('patients.index') }}" class="w-full text-center block bg-gray-200 text-gray-700 font-semibold py-2 rounded-lg hover:bg-gray-300 transition duration-300">
                Back to Local Records
            </a>
        </div>
    </div>
@endsection

@section('content')
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Partner Clinic Search Results</h2>

    @if(isset($remotePatient))
        <div class="bg-white rounded-lg shadow-md overflow-hidden border-l-4" style="border-color: {{ config('app.theme_color') }};">
            <div class="p-6">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 uppercase">{{ $remotePatient->name }}</h3>
                        <p class="text-gray-600">DOB: {{ $remotePatient->date_of_birth }}</p>
                        <p class="text-gray-600">Tel: {{ $remotePatient->tel }}</p>
                        <p class="mt-2 text-sm font-medium text-blue-600 italic">
                            Currently Registered at: {{ $remotePatient->active == 1 ? 'Arusha' : 'DSM' }} 
                        </p>
                    </div>
                    
                    {{-- The Import Button --}}
                    <form action="{{ route('patients.partner.import', $remotePatient->patient_id) }}" method="POST">
                        @csrf
                        <button type="submit" 
                                class="px-6 py-3 text-white font-bold rounded-lg shadow-lg hover:opacity-90 transition transform hover:scale-105"
                                style="background-color: {{ config('app.theme_color') }};">
                            Import to This Clinic
                        </button>
                    </form>
                </div>
                
                <div class="mt-6 grid grid-cols-2 gap-4 text-sm bg-gray-50 p-4 rounded">
                    <div><span class="font-semibold">Account No:</span> {{ $remotePatient->acc_no }}</div>
                    <div><span class="font-semibold">Gender:</span> {{ $remotePatient->gender }}</div>
                    <div class="col-span-2"><span class="font-semibold">Remarks:</span> {{ $remotePatient->remarks }}</div>
                </div>
            </div>
        </div>
    @else
        <div class="bg-yellow-100 text-yellow-800 p-6 rounded-lg shadow-inner">
            <p class="font-bold">No Match Found.</p>
            <p>We couldn't find a patient with that exact name and date of birth in the partner clinic's database. Please check the spelling and try again.</p>
        </div>
    @endif
@endsection