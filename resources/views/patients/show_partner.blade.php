@extends('layouts.app')





@section('left_content')
    @if ($patient)
        <div class="bg-white p-6 rounded-xl shadow-lg space-y-5">
            <h2 class="text-xl font-bold text-gray-800 border-b pb-2">Partner Patient Overview</h2>
            
            <div class="space-y-3">
                {{-- Patient Name (Remains bold as it is the primary identifier) --}}
                <p class="text-lg font-bold uppercase text-green-700 block">{{ $patient->name }}</p> 
                
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
            

            {{-- 🏥 PARTNER VIEW: Insurance Information Block 🏥 --}}
            @if ($insuranceProvider && !empty($insuranceProvider->insurance_no))
                <div class="p-3 bg-gray-50 rounded-lg border border-orange-400">
                    <h3 class="text-sm font-semibold text-orange-700 mb-1">Insurance Policy Present</h3>
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
                    <p class="text-xs text-gray-500 italic">
                        No active policy recorded in partner database.
                    </p>
                </div>
            @endif






            {{-- Consolidated Action Buttons --}}
            <div class="pt-2 space-y-2">
                <div class="p-3 bg-blue-100 text-blue-800 rounded-lg text-sm font-bold text-center border border-blue-300">
                    VIEWING PARTNER RECORD
                </div>

                {{-- The Import Button - Using a distinct color to show it's a special action --}}
                <form action="{{ route('patients.partner.import', ['patient_id' => $patient->patient_id]) }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full bg-orange-600 hover:bg-orange-700 text-white font-bold py-2 rounded-lg shadow-md transition duration-150">
                        Import to This Clinic
                    </button>
                </form>
                
                <a href="{{ route('patients.partner.search') }}" class="w-full text-center block bg-gray-200 text-gray-700 font-semibold py-2 rounded-lg hover:bg-gray-300 transition duration-300">
                    Back to Partner Search
                </a>
            </div>
        </div>
    @else
        <div class="bg-red-100 text-red-800 p-4 rounded-lg shadow-md">Patient Data Unavailable.</div>
    @endif
@endsection


@section('content')
    <div class="container mx-auto p-4">
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
            return !empty(trim($record->estimate_description ?? '')) || 
                   !empty(trim($record->estimate_cost ?? '')) || 
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
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tooth</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Diagnostic</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Paid</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Balance</th>
                                <!-- <th class="px-4 py-3"></th> -->
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($allRecords as $record)
                                @php
                                    // RE-INTEGRATED LOGIC START
                                    $hasEstimateData = !empty(trim($record->estimate_description)) || !empty(trim($record->estimate_cost)) || !empty(trim($record->estimate_paid)) || !empty(trim($record->estimate_balance));

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
                                        @if ($record->date)
                                            {{ \Carbon\Carbon::parse($record->date)->format('d/m/Y') }}
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $record->tooth }}@if ($hasExtraNotes) <span class="{{ $dotColorClass }}"></span> @endif
                                        @if ($hasExtraNotes)
                                            <span class="tooltiptext">{!! $tooltipContent !!}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">{{ $record->diagnostic }}</td>
                                    
                                    {{-- Update this specific cell in your @foreach loop --}}
                                    <td class="px-4 py-4 text-sm text-gray-900 max-w-md">
                                        <div class="break-words whitespace-normal overflow-hidden">
                                            {{ !empty($record->estimate_description) ? trim($record->estimate_description) : trim($record->description) }}
                                        </div>
                                    </td>

                                    {{-- Amount Column: Shows estimate_cost if estimate, otherwise clinical amount --}}
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
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
    <form id="access-form" method="POST" action="{{ route('patients.partner.all_records', ['patient_id' => $patient->patient_id]) }}" style="display:none;">
        @csrf
        <input type="hidden" name="access_key" id="access-key-input">
    </form>
@endsection
@push('styles')
<style>
    /* 1. The Container - This stays inside the TD so it doesn't push the table layout */
    .tooltip-wrapper {
        position: relative;
        display: inline-flex;
        align-items: center;
        cursor: help;
    }

    /* 2. The Enhanced Tooltip Box */
    .tooltip-wrapper .tooltiptext {
        visibility: hidden;
        width: 380px; 
        background-color: #1f2937; /* Dark gray for high contrast */
        color: #fff;
        text-align: left;
        border-radius: 8px;
        padding: 12px;
        position: absolute;
        z-index: 100;
        bottom: 140%; /* Moved up slightly to clear the row */
        left: 0;
        opacity: 0;
        transition: opacity 0.3s;
        font-size: 0.85rem;
        line-height: 1.4;
        white-space: normal; /* Essential for long Remarks to wrap */
        box-shadow: 0 10px 15px rgba(0,0,0,0.3);
        border: 1px solid #4b5563;
    }

    /* 3. Show on Hover */
    .tooltip-wrapper:hover .tooltiptext {
        visibility: visible;
        opacity: 1;
    }

    /* 4. The "Status Dots" - Ensuring they don't shift the text */
    .orange-dot { height: 10px; width: 10px; background-color: #f97316; border-radius: 50%; display: inline-block; margin-left: 6px; flex-shrink: 0; }
    .green-dot { height: 10px; width: 10px; background-color: #22c55e; border-radius: 50%; display: inline-block; margin-left: 6px; flex-shrink: 0; }
</style>
@endpush
