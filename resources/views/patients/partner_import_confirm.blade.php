@extends('layouts.app')

@section('title', 'Confirm Patient Import')

@section('left_content')
<div class="bg-white p-5 rounded-xl shadow-lg space-y-4">

    <h2 class="text-base font-bold text-gray-800 border-b pb-2">Importing from Partner</h2>

    <div class="space-y-1 text-sm">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Name</p>
        <p class="font-bold text-gray-900 uppercase">{{ $remotePatient->name }}</p>
    </div>

    <div class="space-y-1 text-sm">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Partner Patient ID</p>
        <p class="text-gray-700">#{{ $remotePatient->patient_id }}</p>
    </div>

    <hr class="border-gray-100">

    {{-- Warning badge --}}
    <div class="p-3 bg-red-50 border border-red-300 rounded-lg">
        <p class="text-xs font-bold text-red-700 uppercase tracking-wide mb-1">Duplicate Warning</p>
        <p class="text-xs text-red-600">
            {{ count($similarPatients) }} similar {{ count($similarPatients) === 1 ? 'record' : 'records' }} found in local database.
        </p>
    </div>

    <a href="{{ route('patients.partner.show', ['patient_id' => $remotePatient->patient_id]) }}"
       class="w-full text-center block text-sm btn-clinic-grey font-medium py-2 px-3 rounded-lg transition duration-150">
        ← Back to Patient View
    </a>
</div>
@endsection

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex items-start gap-4">
            <div class="flex-shrink-0 w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
            </div>
            <div>
                <h1 class="text-xl font-bold text-gray-900">Possible Duplicate Detected</h1>
                <p class="text-sm text-gray-600 mt-1">
                    The patient you are importing — <strong>{{ $remotePatient->name }}</strong> —
                    has a name that is <strong>{{ $similarPatients[0]['similarity'] }}% or more similar</strong>
                    to {{ count($similarPatients) }} existing {{ count($similarPatients) === 1 ? 'patient' : 'patients' }}
                    in this clinic's database.
                </p>
                <p class="text-sm text-gray-600 mt-2">
                    Please review the records below before deciding whether to proceed.
                    If you import anyway, <strong>the action will be permanently flagged in the audit log</strong>.
                </p>
            </div>
        </div>
    </div>

    {{-- Similar patients table --}}
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h2 class="text-sm font-bold text-gray-700 uppercase tracking-wide">Similar Patients Found Locally</h2>
        </div>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Patient ID</th>
                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Similarity</th>
                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach ($similarPatients as $similar)
                <tr class="{{ $similar['similarity'] >= 90 ? 'bg-red-50' : '' }}">
                    <td class="px-5 py-3 text-sm text-gray-900">#{{ $similar['patient_id'] }}</td>
                    <td class="px-5 py-3 text-sm font-semibold text-gray-900 uppercase">{{ $similar['name'] }}</td>
                    <td class="px-5 py-3">
                        <span class="inline-block px-2 py-0.5 rounded text-xs font-bold
                            {{ $similar['similarity'] >= 90 ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700' }}">
                            {{ $similar['similarity'] }}% match
                        </span>
                    </td>
                    <td class="px-5 py-3">
                        <a href="{{ route('patients.show', ['patient_id' => $similar['patient_id']]) }}"
                           target="_blank"
                           class="text-sm text-clinic hover:text-clinic-bold underline">
                            View profile ↗
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Decision buttons --}}
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h2 class="text-sm font-bold text-gray-700 mb-4">Your Decision</h2>
        <div class="flex flex-wrap gap-4">

            {{-- Cancel --}}
            <a href="{{ route('patients.partner.show', ['patient_id' => $remotePatient->patient_id]) }}"
               class="inline-flex items-center px-5 py-2 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition">
                Cancel — do not import
            </a>

            {{-- Force import --}}
            <form action="{{ route('patients.partner.import.force', ['patient_id' => $remotePatientId]) }}" method="POST">
                @csrf
                <button type="submit"
                        class="inline-flex items-center px-5 py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white text-sm font-bold shadow transition"
                        onclick="return confirm('This will import the patient and create a permanent red-flag entry in the audit log. Are you sure?')">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01"/>
                    </svg>
                    Import Anyway — I confirm this is a different patient
                </button>
            </form>

        </div>
        <p class="text-xs text-gray-400 mt-3">
            Importing will create a new patient record and flag the event in the System Audit Log with your name, timestamp, and the reason.
        </p>
    </div>

</div>
@endsection
