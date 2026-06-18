@extends('layouts.app')

@section('title', 'Statistics Month')

@section('left_content')
    @include('patients.reports.partials.reports-sidebar')
@endsection

@section('content')

{{-- ── Filter bar + Export button ─────────────────────────────────────────── --}}
<div class="flex items-center justify-between mb-6 gap-4 flex-wrap">

    <form method="GET" action="{{ route('reports.patients_attending.statistics_month') }}"
          class="flex items-center gap-3 bg-gray-50 border border-gray-200 rounded-xl px-5 py-3">

        <label class="flex items-center gap-2 text-sm text-gray-700 font-medium">
            Month
            <input type="number" name="month" min="1" max="12" value="{{ $month }}"
                   class="w-16 border border-gray-300 rounded-lg px-2 py-1 text-sm text-center
                          focus:outline-none focus:ring-2 focus:ring-clinic focus:ring-offset-1">
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
                    document.getElementById('stats-export-key').value = k;
                    document.getElementById('stats-export-form').submit();
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
        Statistics Month —
        {{ DateTime::createFromFormat('!m', $month)->format('F') }} {{ $year }}
        @if ($showAll)
            <span class="ml-2 text-sm font-normal text-orange-600 bg-orange-50 border border-orange-200 px-2 py-0.5 rounded-full">
                All Records
            </span>
        @endif
    </h2>
    <p class="text-sm text-gray-500 mt-1">{{ $records->count() }} record(s) found.</p>
    @if (! $showAll)
        <p class="text-sm text-gray-500 mt-0.5">Clinical records</p>
        <p class="mt-0.5">
            <a href="#"
               onclick="(function(){
                   var k = prompt('Not implemented in Freeware version.');
                   if (k !== null && k.trim() !== '') {
                       document.getElementById('stats-all-key').value = k;
                       document.getElementById('stats-all-form').submit();
                   }
               })(); return false;"
               class="text-gray-400 hover:text-gray-500 text-xs transition">
                all records
            </a>
        </p>
    @endif
</div>

@if ($records->isEmpty())

    <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 p-4 rounded-lg text-sm">
        No records found for {{ DateTime::createFromFormat('!m', $month)->format('F') }} {{ $year }}.
    </div>

@else

    {{--
        TODO (future): if the record count grows large, consider splitting results across
        monthly-week tabs (week 1 / week 2 / week 3 / week 4) similar to the Search results
        tab pattern, to avoid very long single-page scrolling.
    --}}

    <div class="overflow-x-auto rounded-xl shadow-sm border border-gray-200">
        <table class="w-full text-sm border-collapse">

            <thead>
                <tr class="bg-gray-100 text-gray-600 uppercase text-xs tracking-wide">
                    <th class="px-3 py-3 text-center w-10">#</th>
                    @if ($showAll)
                        <th class="px-3 py-3 text-center w-20">Type</th>
                    @endif
                    <th class="px-3 py-3 text-center w-24">Date</th>
                    <th class="px-3 py-3 text-left min-w-[9rem]">Name</th>
                    <th class="px-3 py-3 text-center w-12">Age</th>
                    <th class="px-3 py-3 text-center w-14">Gender</th>
                    <th class="px-3 py-3 text-left min-w-[8rem]">Diagnostic</th>
                    <th class="px-3 py-3 text-left">Description</th>
                    <th class="px-3 py-3 text-center w-16">Free</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-100">
                @php $row = 0; @endphp

                @foreach ($records as $record)
                    @php
                        $hasClinical   = trim($record->description          ?? '') !== '';
                        $hasEstimate   = trim($record->estimate_description ?? '') !== '';
                        $dateFormatted = \Carbon\Carbon::parse($record->date)->format('d/m/Y');
                        $patientName   = $record->patient->name   ?? '—';
                        $patientAge    = $record->patient->age    ?? '—';
                        $patientGender = $record->patient->gender ?? '—';

                        $isFree = str_contains(strtolower($record->amount        ?? ''), 'free')
                               || str_contains(strtolower($record->paid          ?? ''), 'free')
                               || str_contains(strtolower($record->estimate_cost ?? ''), 'free')
                               || str_contains(strtolower($record->estimate_paid ?? ''), 'free');
                    @endphp

                    @if (! $showAll)
                        {{-- Clinical-only mode: single row per record (original layout) --}}
                        <tr class="bg-white hover:brightness-95 transition-all align-top">
                            <td class="px-3 py-2 text-center text-gray-400">{{ ++$row }}</td>
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
                            <td class="px-3 py-2 text-center text-gray-700">{{ $patientAge }}</td>
                            <td class="px-3 py-2 text-center text-gray-700">{{ $patientGender }}</td>
                            <td class="px-3 py-2 text-gray-700">{{ $record->diagnostic ?? '—' }}</td>
                            <td class="px-3 py-2 text-gray-700">{{ $record->description ?: '—' }}</td>
                            <td class="px-3 py-2 text-center">
                                @if ($isFree)
                                    <span class="inline-block bg-green-100 text-green-700 font-bold text-xs px-2 py-0.5 rounded-full border border-green-300">FREE</span>
                                @endif
                            </td>
                        </tr>

                    @else
                        {{-- All-records mode: separate rows with type pills --}}

                        @if ($hasClinical)
                            <tr class="bg-white hover:brightness-95 transition-all align-top">
                                <td class="px-3 py-2 text-center text-gray-400">{{ ++$row }}</td>
                                <td class="px-3 py-2 text-center">
                                    <span class="inline-block bg-blue-50 text-blue-700 text-xs font-medium px-2 py-0.5 rounded-full border border-blue-200">Clinical</span>
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
                                <td class="px-3 py-2 text-center text-gray-700">{{ $patientAge }}</td>
                                <td class="px-3 py-2 text-center text-gray-700">{{ $patientGender }}</td>
                                <td class="px-3 py-2 text-gray-700">{{ $record->diagnostic ?? '—' }}</td>
                                <td class="px-3 py-2 text-gray-700">{{ $record->description ?: '—' }}</td>
                                <td class="px-3 py-2 text-center">
                                    @if ($isFree)
                                        <span class="inline-block bg-green-100 text-green-700 font-bold text-xs px-2 py-0.5 rounded-full border border-green-300">FREE</span>
                                    @endif
                                </td>
                            </tr>
                        @endif

                        @if ($hasEstimate)
                            @php
                                $estFree = str_contains(strtolower($record->estimate_cost ?? ''), 'free')
                                        || str_contains(strtolower($record->estimate_paid ?? ''), 'free');
                            @endphp
                            <tr class="bg-gray-50 hover:brightness-95 transition-all align-top">
                                <td class="px-3 py-2 text-center text-gray-400">{{ ++$row }}</td>
                                <td class="px-3 py-2 text-center">
                                    <span class="inline-block bg-orange-50 text-orange-700 text-xs font-medium px-2 py-0.5 rounded-full border border-orange-200">Estimate</span>
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
                                <td class="px-3 py-2 text-center text-gray-700">{{ $patientAge }}</td>
                                <td class="px-3 py-2 text-center text-gray-700">{{ $patientGender }}</td>
                                <td class="px-3 py-2 text-gray-700">{{ $record->diagnostic ?? '—' }}</td>
                                <td class="px-3 py-2 text-gray-700 italic">{{ $record->estimate_description ?: '—' }}</td>
                                <td class="px-3 py-2 text-center">
                                    @if ($estFree)
                                        <span class="inline-block bg-green-100 text-green-700 font-bold text-xs px-2 py-0.5 rounded-full border border-green-300">FREE</span>
                                    @endif
                                </td>
                            </tr>
                        @endif

                    @endif

                @endforeach
            </tbody>

        </table>
    </div>

@endif

{{-- ── Error flash ──────────────────────────────────────────────────────────── --}}
@if (session('error'))
    <div class="mt-4 bg-red-50 border border-red-300 text-red-700 text-sm px-4 py-3 rounded-lg">
        {{ session('error') }}
    </div>
@endif

{{-- ── Hidden form: all records ─────────────────────────────────────────────── --}}
@if (! $showAll)
<form id="stats-all-form" method="POST"
      action="{{ route('reports.patients_attending.statistics_month.all') }}"
      style="display:none;">
    @csrf
    <input type="hidden" name="month" value="{{ $month }}">
    <input type="hidden" name="year"  value="{{ $year }}">
    <input type="hidden" name="access_key" id="stats-all-key">
</form>
@endif

{{-- ── Hidden form: export ──────────────────────────────────────────────────── --}}
<form id="stats-export-form" method="POST"
      action="{{ route('reports.patients_attending.statistics_month.export') }}"
      style="display:none;">
    @csrf
    <input type="hidden" name="month" value="{{ $month }}">
    <input type="hidden" name="year"  value="{{ $year }}">
    <input type="hidden" name="access_key" id="stats-export-key">
</form>

@endsection
