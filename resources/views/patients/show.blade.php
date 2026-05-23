@extends('layouts.app')


@php
    $themeColor = env('THEME_COLOR', 'gray'); // or config('app.theme_color') if cached
@endphp


@section('left_content')
    @if ($patient)
        <div class="bg-white p-6 rounded-xl shadow-lg space-y-5">
            <h2 class="text-xl font-bold text-gray-800 border-b pb-2">Patient Overview</h2>
            
            <div class="space-y-3">
                {{-- Patient Name (Remains bold as it is the primary identifier) --}}
                <p class="text-lg font-bold uppercase text-[--theme-primary-text] block">{{ $patient->name }}</p> 
                
                <hr class="border-gray-100">

                <div class="grid grid-cols-2 gap-x-4 gap-y-1 text-sm">
                    {{-- Patient ID: All font classes removed for standard weight --}}
                    <div>
                        <span class="text-gray-900 block">ID: {{ $patient->patient_id }}</span> 
                    </div>
                    
                    {{-- Age: All font classes removed for standard weight --}}
                    <div>
                        <span class="text-gray-900 block">Age: {{ $patient->age ?? 'N/A' }}</span>
                    </div>

                    {{-- Telephone: All font classes removed for standard weight --}}
                    <div class="col-span-2 mt-2">
                        <span class="text-gray-900 block">Tel: {{ $patient->tel ?? 'N/A' }}</span>
                    </div>

                    {{-- Email: All font classes removed for standard weight --}}
                    <div class="col-span-2">
                        <span class="text-gray-900 block truncate" title="{{ $patient->email }}">Email: {{ $patient->email ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>
            
            <hr class="border-gray-200">

            {{-- Remarks Display (No change) --}}
            <div class="p-3 bg-gray-50 rounded-lg border border-gray-100">
                <h3 class="text-sm font-semibold text-red-600 mb-1">Patient Remarks</h3>
                <p class="text-xs text-gray-700 italic">
                    {{ $patient->remarks ?? 'No special remarks recorded.' }}
                </p>
            </div>
            

            {{-- 🏥 RELOCATED: Insurance Information Block (styled to match remarks block) 🏥 --}}
            @if ($patient->insurance && !empty($patient->insurance->insurance_no))
                {{-- Use orange border/text for a clear warning/info distinction --}}
                <div class="p-3 bg-gray-50 rounded-lg border border-orange-400">  <!-- keep border-orange-400 or change to border-[--theme-accent] -->
                    <h3 class="text-sm font-semibold text-orange-700 mb-1">Insurance Policy</h3>
                    <p class="text-xs text-gray-700">
                        <strong class="font-medium">Provider:</strong> {{ $patient->insurance->insurance_provider ?? 'N/A' }}
                    </p>
                    <p class="text-xs text-gray-700">
                        <strong class="font-medium">Policy No:</strong> {{ $patient->insurance->insurance_no ?? 'N/A' }}
                    </p>
                </div>
            @else
                {{-- Add a muted placeholder if no insurance exists --}}
                 <div class="p-3 bg-gray-50 rounded-lg border border-gray-300">
                    <h3 class="text-sm font-semibold text-gray-500 mb-1">Insurance Policy Status</h3>
                    <p class="text-xs text-gray-500 italic">
                        No active policy recorded.
                        {{-- Include link to edit patient to add policy --}}
                        <a href="{{ route('patient.edit', ['patient' => $patient->patient_id]) }}" class="text-blue-500 hover:text-blue-700 underline ml-1">Add now</a>
                    </p>
                </div>
            @endif






            {{-- Consolidated Action Buttons --}}
            <div class="pt-2 space-y-2">
                <a href="{{ route('patients.create', ['patient_id' => $patient->patient_id]) }}" 
                    class="w-full text-center block bg-green-600 hover:bg-green-700 text-white font-bold py-2 rounded-lg transition duration-150 shadow-md">
                    <i class="fas fa-plus-circle mr-1"></i> Add New Record
                </a>
                
                {{-- MODIFICATION 1: Use 'patient' key for the route parameter, which the route expects for binding --}}
                <a href="{{ route('patient.edit', ['patient' => $patient->patient_id]) }}" 
                    class="w-full text-center block bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 rounded-lg transition duration-150 shadow-md">
                    <i class="fas fa-pencil-alt mr-1"></i> Edit Patient Details
                </a>
                
                <a href="{{ route('patients.index') }}" 
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
    <div class="container mx-auto p-4">
        @if (session('error'))
            <div class="mb-4 p-3 bg-red-100 border border-red-400 text-red-800 rounded-lg text-sm">
                {{ session('error') }}
            </div>
        @endif

        @if ($patient)
            

{{-- Tabs for Clinical vs. All Records --}}
<div class="flex border-b border-gray-200 mb-4">
    {{-- Clinical Records Tab --}}
    <a href="{{ route('patients.show', ['patient_id' => $patient->patient_id, 'showAllRecords' => 'false']) }}" 
       class="py-2 px-4 text-sm font-medium transition duration-150 {{ !$showAllRecords ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700' }}">
        Clinical Records
    </a>

    @php
        // Check if there is at least one record that qualifies as an estimate
        $hasAnyEstimates = $allRecords->contains(function ($record) {
            return !empty(trim($record->estimate ?? '')) ||
                   !empty(trim($record->estimate_description ?? '')) ||
                   !empty(trim($record->estimate_cost ?? '')) ||
                   !empty(trim($record->estimate_paid ?? '')) ||
                   !empty(trim($record->estimate_balance ?? ''));
        });
    @endphp

             @if($hasAnyEstimates)
        {{-- All Records Tab (Including Estimates) --}}
        <a href="#" 
           class="py-2 px-4 text-sm font-medium transition duration-150 {{ $showAllRecords ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700' }}" 
           onclick="promptForAccessKey({{ $patient->patient_id }}); return false;">
            All Records (incl. Estimates)
        </a>
    @endif
</div>

            @if ($allRecords->isEmpty())
                <div class="bg-yellow-100 text-yellow-800 p-4 rounded-lg">No records found for this patient.</div>
            @else
                <div class="overflow-x-auto bg-white rounded-lg shadow-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-24">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-20">Tooth</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-36">Diagnostic</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Paid</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Balance</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($allRecords as $record)
                                @php
                                    // RE-INTEGRATED LOGIC START
                                    $hasEstimateData = !empty(trim($record->estimate ?? '')) || !empty(trim($record->estimate_description)) || !empty(trim($record->estimate_cost)) || !empty(trim($record->estimate_paid)) || !empty(trim($record->estimate_balance));

                                    $hasClinicalData = !empty(trim($record->description)) ||
                                                       (!empty(trim($record->amount)) && is_numeric(trim($record->amount))) ||
                                                       (!empty(trim($record->paid)) && is_numeric(trim($record->paid))) ||
                                                       (!empty(trim($record->balance)) && is_numeric(trim($record->balance)));

                                    $isEstimate = $hasEstimateData && !$hasClinicalData;
                                    $isFree = (strtolower(trim($record->amount)) === 'free') || (strtolower(trim($record->paid)) === 'free');
                                    $isClinical = !$isEstimate || $isFree;

                                    if ($showAllRecords || $isClinical) {
                                        $hasExtraNotes = !empty($record->estimate_description) || !empty($record->remarks);
                                        $tooltipParts = [];
                                        if ($hasExtraNotes) {
                                            if (!empty($record->remarks)) $tooltipParts[] = $record->remarks;
                                        }
                                        $tooltipContent = implode('<br>', $tooltipParts);

                                        $dotColorClass = '';
                                        if ($isClinical && $hasExtraNotes) {
                                            $dotColorClass = 'orange-dot';
                                        } elseif ($isEstimate && $hasExtraNotes) {
                                            $dotColorClass = 'green-dot';
                                        }
                                    // RE-INTEGRATED LOGIC END
                                @endphp
                                
                                <tr class="@if ($showAllRecords && $isEstimate) bg-gray-100 hover:bg-gray-200 @else hover:bg-gray-50 @endif transition duration-100 tooltip">
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                        @if ($record->date && $record->date !== '0000-00-00')
                                            {{ \Carbon\Carbon::parse($record->date)->format('d/m/Y') }}
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 whitespace-wrap text-sm text-gray-900">
                                        <div class="tooltip">
                                            {{ $record->tooth }}
                                            @if ($hasExtraNotes) 
                                                <span class="{{ $dotColorClass }}"></span> 
                                                <span class="tooltiptext">{!! $tooltipContent !!}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 text-sm text-gray-900 max-w-[9rem] break-words whitespace-normal">{{ $record->diagnostic }}</td>
                                    
                                    {{-- Update this specific cell in your @foreach loop --}}
                                    <td class="px-4 py-4 text-sm text-gray-900 max-w-md">
                                        <div class="break-words whitespace-normal overflow-hidden">
                                            {{ !empty($record->estimate_description) ? trim($record->estimate_description) : trim($record->description) }}
                                        </div>
                                    </td>

                                    {{-- Amount Column: Shows estimate_cost if estimate, otherwise clinical amount --}}
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                        @if ($isClinical && $record->is_insurance_claim)
                                            <span class="mr-1 inline-block text-xs font-semibold text-orange-700 bg-orange-100 border border-orange-300 rounded px-1 py-0.5 leading-none">INS</span>
                                        @endif
                                        {{ !empty($record->estimate_cost) && is_numeric($record->estimate_cost) ? number_format($record->estimate_cost) : (is_numeric($record->amount) ? number_format($record->amount) : $record->amount) }}
                                    </td>

                                    {{-- Paid Column: Shows estimate_paid if estimate, otherwise clinical paid --}}
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ !empty($record->estimate_paid) && is_numeric($record->estimate_paid) ? number_format($record->estimate_paid) : (is_numeric($record->paid) ? number_format($record->paid) : $record->paid)}}
                                    </td>

                                    {{-- Balance Column: Shows estimate_balance if estimate, otherwise clinical balance --}}
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ !empty($record->estimate_balance) && is_numeric($record->estimate_balance) ? number_format($record->estimate_balance) : (is_numeric($record->balance) ? number_format($record->balance) : $record->balance) }}
                                    </td>
                                    
                                    {{-- Edit Link --}}
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                        
                                    @php
                                        // Use patient_clinic_id as the unique identifier for the record edit route.
                                        // We assume it's always present for a saved record.
                                        $recordIdToUse = $record->patient_clinic_id;
                                    @endphp

                                    @if (!empty($recordIdToUse))
                                        <a href="{{ route('records.edit', [
                                            'record' => $recordIdToUse, // Uses the 'record' wildcard defined in web.php
                                            'showAllRecords' => $showAllRecords ? 'true' : 'false'
                                        ]) }}" class="text-blue-600 hover:text-blue-800 font-medium">Edit</a>
                                    @else
                                        <span class="text-gray-400">N/A</span>
                                    @endif
                                    
                                    </td>
                                </tr>
                                @php
                                    } // Closing the if statement
                                @endphp
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        @else
            <div class="bg-red-100 text-red-800 p-4 rounded-lg">No patient found. Please go back and try a different search.</div>
        @endif
    </div>
    
    {{-- Your existing script and form for access key --}}
    <script>
        function promptForAccessKey(patientId) {
            let key = prompt("Not implemented in Freeware version.");
            if (key !== null && key.trim() !== '') {
                document.getElementById('access-key-input').value = key;
                document.getElementById('access-form').submit();
            }
        }
    </script>
    <form id="access-form" method="POST" action="{{ route('patients.all_records', ['patient_id' => $patient->patient_id]) }}" style="display:none;">
        @csrf
        <input type="hidden" name="access_key" id="access-key-input">
    </form>

    <script>
        document.querySelectorAll('.tooltiptext').forEach(function (box) {
            const trigger = box.closest('tr');

            trigger.addEventListener('mouseenter', function () {
                const r = trigger.getBoundingClientRect();
                box.style.top        = (r.bottom - 34) + 'px';
                box.style.left       = (r.left + 30) + 'px';
                box.style.visibility = 'visible';
                box.style.opacity    = '1';
            });

            trigger.addEventListener('mouseleave', function () {
                box.style.visibility = 'hidden';
                box.style.opacity    = '0';
                box.style.top        = '-9999px';
                box.style.left       = '-9999px';
            });
        });
    </script>
@endsection