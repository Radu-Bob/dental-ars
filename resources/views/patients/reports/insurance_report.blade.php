@extends('layouts.app')

@section('title', 'Patients With Insurance')

@section('left_content')
    @include('patients.reports.partials.reports-sidebar')
@endsection

@section('content')

<h2 class="text-2xl font-bold text-gray-800 mb-6">Patients with Insurance (Report)</h2>
<p class="text-gray-600 mb-6">
    This report lists patients who have an **active insurance record**, ordered by the **date of their last visit**.
    <!-- <a href="{{ route('reports.index') }}" class="text-blue-500 hover:text-blue-700 font-medium ml-2">&larr; Back to Reports Dashboard</a>
    -->
</p>
{{-- ***************************************************************** --}}
            {{-- THE NEW EXPORT BUTTON --}}
            {{-- ***************************************************************** --}}
        @if (Auth::check() && (Auth::user()->is_admin || Auth::user()->is_doctor))
            <div class="mb-6 flex justify-end">
                <a href="{{ route('reports.insurance.export') }}" 
                   class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm btn-clinic-primary focus:outline-none focus:ring-2 focus:ring-offset-2">
                    <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    Export to CSV
                </a>
            </div>
        @endif
        {{-- End of Admin check --}}

@if(isset($patients) && $patients->isNotEmpty())
    <div class="overflow-x-auto bg-white rounded-lg shadow-sm">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <!--
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
-->
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Telephone</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Clinic</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">INS Balance</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-24">Last Visit</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($patients as $patient)
                    <tr>
                        <!--
                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">{{ $patient->patient_id }}</td>
-->
                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900"><a href="{{ route('patients.show', ['patient_id' => $patient->patient_id]) }}" class="text-clinic hover:text-clinic-bold"><span class="font-bold uppercase">{{ $patient->name }}</span></a></td>
                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">{{ $patient->tel }}</td>
                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                            @if ($patient->active == 2)
                                DSM
                            @elseif ($patient->active == 1)
                                ARS
                            @endif
                        </td>
                        {{-- INS Balance: sum of balance on insurance-flagged clinical records --}}
                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-semibold">
                            {{ $patient->insurance_balance > 0 ? number_format($patient->insurance_balance) : '—' }}
                        </td>
                        {{-- Last visit: date only --}}
                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 w-24">
                            @if ($patient->latest_record_timestamp)
                                {{ \Carbon\Carbon::parse($patient->latest_record_timestamp)->format('d/m/Y') }}
                            @else
                                <span class="text-red-500 italic">N/A</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    {{-- Pagination Links --}}
    <div class="mt-6 flex justify-center bg-gray-100 rounded-lg p-2 text-sm text-gray-700">
        {{ $patients->appends(request()->input())->links('pagination.custom-pagination') }}
    </div>
@else
    <div class="bg-yellow-100 text-yellow-800 p-4 rounded-lg">
        <p>No patients with insurance records found in the system. Time to encourage more sign-ups!</p>
    </div>
@endif


@endsection