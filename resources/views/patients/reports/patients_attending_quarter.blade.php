@extends('layouts.app')

@section('title', 'Quarter Attendance')

@section('left_content')
    @include('patients.reports.partials.reports-sidebar')
@endsection

@section('content')

@php
    $quarterMonths = [
        1 => 'Jan – Mar',
        2 => 'Apr – Jun',
        3 => 'Jul – Sep',
        4 => 'Oct – Dec',
    ];
@endphp

{{-- ── Filter bar ───────────────────────────────────────────────────────────── --}}
<div class="flex items-center justify-between mb-6 gap-4 flex-wrap">

    <form method="GET" action="{{ route('reports.patients_attending.quarter_attendance') }}"
          class="flex items-center gap-3 bg-gray-50 border border-gray-200 rounded-xl px-5 py-3">

        <label class="flex items-center gap-2 text-sm text-gray-700 font-medium">
            Quarter
            <select name="quarter"
                    class="border border-gray-300 rounded-lg px-2 py-1 text-sm
                           focus:outline-none focus:ring-2 focus:ring-clinic focus:ring-offset-1">
                @foreach ($quarterMonths as $q => $label)
                    <option value="{{ $q }}" @selected($q === $quarter)>Q{{ $q }} — {{ $label }}</option>
                @endforeach
            </select>
        </label>

        <label class="flex items-center gap-2 text-sm text-gray-700 font-medium">
            Year
            <input type="number" name="year" min="2020" max="2099" value="{{ $year }}"
                   class="w-20 border border-gray-300 rounded-lg px-2 py-1 text-sm text-center
                          focus:outline-none focus:ring-2 focus:ring-clinic focus:ring-offset-1">
        </label>

        <button type="submit"
                class="btn-clinic-primary text-sm font-medium px-4 py-1.5 rounded-lg
                       focus:outline-none focus:ring-2 focus:ring-offset-1 transition shadow-sm">
            Refresh
        </button>

    </form>

    {{-- Export to Excel --}}
    <button type="button"
            onclick="(function(){
                var k = prompt('Warning: Exported file is CSV type.\nEnter access key for full export, or leave empty for clinical records only.');
                if (k !== null) {
                    document.getElementById('quarter-export-key').value = k;
                    document.getElementById('quarter-export-form').submit();
                }
            })();"
            class="flex items-center gap-2 text-sm font-medium px-4 py-2 rounded-lg border
                   border-green-400 bg-white text-green-700 hover:bg-green-50 shadow-sm transition">
        ⬇ Export to Excel
    </button>

</div>

{{-- ── Heading ─────────────────────────────────────────────────────────────── --}}
<div class="mb-4">
    <h2 class="text-xl font-bold text-gray-800">
        Quarter Attendance — Q{{ $quarter }} {{ $year }}
        <span class="text-base font-normal text-gray-500">({{ $quarterMonths[$quarter] }})</span>
        @if ($showAll)
            <span class="ml-2 text-sm font-normal text-orange-600 bg-orange-50 border border-orange-200 px-2 py-0.5 rounded-full">
                All Records
            </span>
        @endif
    </h2>
    @php
        $rowCount = 0;
        foreach ($records as $r) {
            $hasClinical = trim($r->description ?? '') !== '' || trim($r->amount ?? '') !== '' || trim($r->tooth ?? '') !== '';
            $hasEstimate = trim($r->estimate_description ?? '') !== '' || trim($r->estimate_cost ?? '') !== '';
            if ($showAll) {
                if ($hasClinical) $rowCount++;
                if ($hasEstimate) $rowCount++;
            } else {
                $rowCount++;
            }
        }
    @endphp
    <p class="text-sm text-gray-500 mt-1">{{ $rowCount }} record(s) found.</p>
    @if (! $showAll)
        <p class="text-sm text-gray-500 mt-0.5">Clinical records</p>
        <p class="mt-0.5">
            <a href="#"
               onclick="(function(){
                   var k = prompt('Not implemented in Freeware version.');
                   if (k !== null && k.trim() !== '') {
                       document.getElementById('quarter-all-key').value = k;
                       document.getElementById('quarter-all-form').submit();
                   }
               })(); return false;"
               class="text-gray-400 hover:text-gray-500 text-xs transition">
                all records
            </a>
        </p>
    @endif
</div>

{{-- ── Error flash ──────────────────────────────────────────────────────────── --}}
@if (session('error'))
    <div class="mb-4 bg-red-50 border border-red-300 text-red-700 text-sm px-4 py-3 rounded-lg">
        {{ session('error') }}
    </div>
@endif

{{-- ── Table ────────────────────────────────────────────────────────────────── --}}
@if ($rowCount === 0)

    <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 p-4 rounded-lg text-sm">
        No records found for Q{{ $quarter }} {{ $year }}.
    </div>

@else

    <div class="overflow-x-auto rounded-xl shadow-sm border border-gray-200">
        <table class="w-full text-sm border-collapse">

            <thead>
                <tr class="bg-gray-100 text-gray-600 uppercase text-xs tracking-wide">
                    <th class="px-3 py-3 text-center w-10">#</th>
                    @if ($showAll)
                        <th class="px-3 py-3 text-center w-20">Type</th>
                    @endif
                    <th class="px-3 py-3 text-center w-24">Date</th>
                    <th class="px-3 py-3 text-left min-w-[9rem]">Patient</th>
                    <th class="px-3 py-3 text-left min-w-[7rem]">Diagnostic</th>
                    <th class="px-3 py-3 text-center w-12">Tooth</th>
                    <th class="px-3 py-3 text-left">Description</th>
                    <th class="px-3 py-3 text-right w-24">Amount</th>
                    <th class="px-3 py-3 text-right w-24">Paid</th>
                    <th class="px-3 py-3 text-right w-24">Balance</th>
                    <th class="px-3 py-3 text-left min-w-[8rem]">Remarks</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-100">
                @php $row = 0; @endphp

                @foreach ($records as $record)
                    @php
                        $hasClinical = trim($record->description ?? '') !== ''
                                    || trim($record->amount      ?? '') !== ''
                                    || trim($record->tooth       ?? '') !== '';
                        $hasEstimate = trim($record->estimate_description ?? '') !== ''
                                    || trim($record->estimate_cost        ?? '') !== '';
                        $patientName   = $record->patient->name ?? '—';
                        $dateFormatted = \Carbon\Carbon::parse($record->date)->format('d/m/Y');
                    @endphp

                    {{-- Clinical row --}}
                    @if ($hasClinical)
                        @php $row++; @endphp
                        <tr class="bg-white hover:brightness-95 transition-all align-top">
                            <td class="px-3 py-2 text-center text-gray-400">{{ $row }}</td>
                            @if ($showAll)
                                <td class="px-3 py-2 text-center">
                                    <span class="inline-block bg-blue-50 text-blue-700 text-xs font-medium px-2 py-0.5 rounded-full border border-blue-200">
                                        Clinical
                                    </span>
                                </td>
                            @endif
                            <td class="px-3 py-2 text-center whitespace-nowrap text-gray-700">{{ $dateFormatted }}</td>
                            <td class="px-3 py-2 font-medium text-gray-800">
                                @if ($record->patient)
                                    <a href="{{ route('patients.show', ['patient_id' => $record->patient->patient_id]) }}"
                                       onclick="window.open(this.href,'pt_{{ $record->patient->patient_id }}','width=1100,height=750,menubar=no,toolbar=no,location=no,status=no,scrollbars=yes,resizable=yes'); return false;"
                                       class="text-clinic hover:underline">{{ $patientName }}</a>
                                @else
                                    {{ $patientName }}
                                @endif
                            </td>
                            <td class="px-3 py-2 text-gray-700">{{ $record->diagnostic ?? '—' }}</td>
                            <td class="px-3 py-2 text-center text-gray-700">{{ $record->tooth ?: '—' }}</td>
                            <td class="px-3 py-2 text-gray-700">{{ $record->description ?: '—' }}</td>
                            <td class="px-3 py-2 text-right text-gray-700">{{ $record->amount ?: '—' }}</td>
                            <td class="px-3 py-2 text-right text-gray-700">{{ $record->paid ?: '—' }}</td>
                            <td class="px-3 py-2 text-right text-gray-700">{{ $record->balance ?: '—' }}</td>
                            <td class="px-3 py-2 text-gray-600 text-xs">{{ $record->remarks ?: '' }}</td>
                        </tr>
                    @endif

                    {{-- Estimate row (all-records mode only) --}}
                    @if ($showAll && $hasEstimate)
                        @php $row++; @endphp
                        <tr class="bg-gray-50 hover:brightness-95 transition-all align-top">
                            <td class="px-3 py-2 text-center text-gray-400">{{ $row }}</td>
                            <td class="px-3 py-2 text-center">
                                <span class="inline-block bg-orange-50 text-orange-700 text-xs font-medium px-2 py-0.5 rounded-full border border-orange-200">
                                    Estimate
                                </span>
                            </td>
                            <td class="px-3 py-2 text-center whitespace-nowrap text-gray-700">{{ $dateFormatted }}</td>
                            <td class="px-3 py-2 font-medium text-gray-800">
                                @if ($record->patient)
                                    <a href="{{ route('patients.show', ['patient_id' => $record->patient->patient_id]) }}"
                                       onclick="window.open(this.href,'pt_{{ $record->patient->patient_id }}','width=1100,height=750,menubar=no,toolbar=no,location=no,status=no,scrollbars=yes,resizable=yes'); return false;"
                                       class="text-clinic hover:underline">{{ $patientName }}</a>
                                @else
                                    {{ $patientName }}
                                @endif
                            </td>
                            <td class="px-3 py-2 text-gray-700">{{ $record->diagnostic ?? '—' }}</td>
                            <td class="px-3 py-2 text-center text-gray-400">—</td>
                            <td class="px-3 py-2 text-gray-700 italic">{{ $record->estimate_description ?: '—' }}</td>
                            <td class="px-3 py-2 text-right text-gray-700">{{ $record->estimate_cost ?: '—' }}</td>
                            <td class="px-3 py-2 text-right text-gray-700">{{ $record->estimate_paid ?: '—' }}</td>
                            <td class="px-3 py-2 text-right text-gray-700">{{ $record->estimate_balance ?: '—' }}</td>
                            <td class="px-3 py-2 text-gray-600 text-xs">{{ $record->remarks ?: '' }}</td>
                        </tr>
                    @endif

                @endforeach
            </tbody>

        </table>
    </div>

@endif

{{-- ── Hidden form for all-records key submission ──────────────────────────── --}}
@if (! $showAll)
<form id="quarter-all-form" method="POST"
      action="{{ route('reports.patients_attending.quarter_attendance.all') }}"
      style="display:none;">
    @csrf
    <input type="hidden" name="quarter" value="{{ $quarter }}">
    <input type="hidden" name="year"    value="{{ $year }}">
    <input type="hidden" name="access_key" id="quarter-all-key">
</form>
@endif

{{-- ── Hidden form: export ──────────────────────────────────────────────────── --}}
<form id="quarter-export-form" method="POST"
      action="{{ route('reports.patients_attending.quarter_attendance.export') }}"
      style="display:none;">
    @csrf
    <input type="hidden" name="quarter" value="{{ $quarter }}">
    <input type="hidden" name="year"    value="{{ $year }}">
    <input type="hidden" name="access_key" id="quarter-export-key">
</form>

@endsection
