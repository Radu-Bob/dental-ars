@extends('layouts.app')

@section('left_content')
    @if($patient)
        <div class="bg-white p-6 rounded-xl shadow-lg space-y-5">
            <h2 class="text-xl font-bold text-gray-800 border-b pb-2">Patient Overview</h2>

            <div class="space-y-3">
                <p class="text-lg font-bold uppercase text-[--theme-primary-text] block">{{ $patient->name }}</p>
                <hr class="border-gray-100">
                <div class="grid grid-cols-2 gap-x-4 gap-y-1 text-sm">
                    <div><span class="text-gray-900 block">ID: {{ $patient->patient_id }}</span></div>
                    <div><span class="text-gray-900 block">Age: {{ $patient->age ?? 'N/A' }}</span></div>
                    <div class="col-span-2 mt-2">
                        <span class="text-gray-900 block">Tel: {{ $patient->tel ?? 'N/A' }}</span>
                    </div>
                    <div class="col-span-2">
                        <span class="text-gray-900 block truncate" title="{{ $patient->email }}">
                            Email: {{ $patient->email ?? 'N/A' }}
                        </span>
                    </div>
                </div>
            </div>

            <hr class="border-gray-200">

            <div class="p-3 bg-gray-50 rounded-lg border border-gray-100">
                <h3 class="text-sm font-semibold text-red-600 mb-1">Patient Remarks</h3>
                <p class="text-xs text-gray-700 italic">
                    {{ $patient->remarks ?? 'No special remarks recorded.' }}
                </p>
            </div>

            @if($insuranceProvider && !empty($insuranceProvider->insurance_no))
                <div class="p-3 bg-gray-50 rounded-lg border border-orange-400">
                    <h3 class="text-sm font-semibold text-orange-700 mb-1">Insurance Policy</h3>
                    <p class="text-xs text-gray-700">
                        <strong class="font-medium">Provider:</strong> {{ $insuranceProvider->insurance_provider ?? 'N/A' }}
                    </p>
                    <p class="text-xs text-gray-700">
                        <strong class="font-medium">Policy No:</strong> {{ $insuranceProvider->insurance_no ?? 'N/A' }}
                    </p>
                </div>
            @else
                <div class="p-3 bg-gray-50 rounded-lg border border-gray-300">
                    <h3 class="text-sm font-semibold text-gray-500 mb-1">Insurance Policy Status</h3>
                    <p class="text-xs text-gray-500 italic">No active policy recorded.</p>
                </div>
            @endif

            <div class="pt-2 space-y-2">
                <a href="{{ route('nurse.patients.edit', ['patient' => $patient->patient_id]) }}"
                    class="w-full text-center block bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 rounded-lg transition duration-150 shadow-md">
                    <i class="fas fa-pencil-alt mr-1"></i> Edit Patient Details
                </a>
                <a href="{{ route('nurse.patients.index') }}"
                    class="w-full text-center block bg-gray-200 text-gray-700 font-semibold py-2 rounded-lg hover:bg-gray-300 transition duration-300">
                    Back to Search
                </a>
            </div>
        </div>
    @else
        <div class="bg-red-100 text-red-800 p-4 rounded-lg shadow-md">Patient Data Unavailable.</div>
    @endif
@endsection

@section('content')
    @if(session('success'))
        <div class="bg-green-100 text-green-800 p-4 rounded-lg mb-4 shadow-sm">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-100 text-red-800 p-4 rounded-lg mb-4 shadow-sm">
            {{ session('error') }}
        </div>
    @endif

    @if($patient)
        <h2 class="text-2xl font-bold text-gray-800 mb-4">{{ $patient->name }}</h2>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 space-y-4">

            <h3 class="text-lg font-semibold text-gray-700 border-b pb-2">Patient Details</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Patient ID</span>
                    <p class="text-gray-800 mt-0.5">{{ $patient->patient_id }}</p>
                </div>
                <div>
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Account No.</span>
                    <p class="text-gray-800 mt-0.5">{{ $patient->acc_no ?? 'N/A' }}</p>
                </div>
                <div>
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Date of Birth</span>
                    <p class="text-gray-800 mt-0.5">
                        {{ $patient->date_of_birth ? \Carbon\Carbon::parse($patient->date_of_birth)->format('d/m/Y') : 'N/A' }}
                    </p>
                </div>
                <div>
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Gender</span>
                    <p class="text-gray-800 mt-0.5">{{ $patient->gender ?? 'N/A' }}</p>
                </div>
                <div>
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Telephone</span>
                    <p class="text-gray-800 mt-0.5">{{ $patient->tel ?? 'N/A' }}</p>
                </div>
                <div>
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Email</span>
                    <p class="text-gray-800 mt-0.5">{{ $patient->email ?? 'N/A' }}</p>
                </div>
                <div>
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Occupation</span>
                    <p class="text-gray-800 mt-0.5">{{ $patient->occupation ?? 'N/A' }}</p>
                </div>
                <div>
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Assigned Clinic</span>
                    <p class="text-gray-800 mt-0.5">
                        @if($patient->active == 2) Dar es Salaam
                        @elseif($patient->active == 1) Arusha
                        @else N/A
                        @endif
                    </p>
                </div>
                <div>
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Location</span>
                    <p class="text-gray-800 mt-0.5">{{ $patient->location ?? 'N/A' }}</p>
                </div>
                <div>
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Town</span>
                    <p class="text-gray-800 mt-0.5">{{ $patient->town ?? 'N/A' }}</p>
                </div>
                <div>
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">P.O. Box</span>
                    <p class="text-gray-800 mt-0.5">{{ $patient->pobox ?? 'N/A' }}</p>
                </div>
                <div>
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Registered</span>
                    <p class="text-gray-800 mt-0.5">{{ $patient->opened ?? 'N/A' }}</p>
                </div>
            </div>

            @if($patient->remarks)
                <div class="mt-2 p-3 bg-yellow-50 rounded-lg border border-yellow-200">
                    <span class="text-xs font-semibold text-yellow-700 uppercase tracking-wide">Remarks</span>
                    <p class="text-sm text-gray-700 mt-1">{{ $patient->remarks }}</p>
                </div>
            @endif

            @if($insuranceProvider && !empty($insuranceProvider->insurance_no))
                <div class="mt-2 p-3 bg-orange-50 rounded-lg border border-orange-300">
                    <span class="text-xs font-semibold text-orange-700 uppercase tracking-wide">Insurance</span>
                    <p class="text-sm text-gray-700 mt-1">
                        <strong>Provider:</strong> {{ $insuranceProvider->insurance_provider ?? 'N/A' }}<br>
                        <strong>Policy No:</strong> {{ $insuranceProvider->insurance_no ?? 'N/A' }}<br>
                        @if($insuranceProvider->insurance_id_no)
                            <strong>Insurance ID:</strong> {{ $insuranceProvider->insurance_id_no }}
                        @endif
                    </p>
                </div>
            @endif

        </div>
    @else
        <div class="bg-red-100 text-red-800 p-4 rounded-lg">
            No patient found. Please go back and try a different search.
        </div>
    @endif
@endsection
