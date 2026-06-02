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

    {{-- Export to Excel — requires password before downloading --}}
    <button type="button" onclick="document.getElementById('export-access-key').value = '';
                                   document.getElementById('export-modal').classList.remove('hidden');
                                   document.getElementById('export-access-key').focus();"
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
    </h2>
    <p class="text-sm text-gray-500 mt-1">
        {{ $records->count() }} record(s) found.
        Rows with a light-grey background are estimate-only records (no clinical description).
    </p>
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
                @foreach ($records as $record)
                    @php
                        $hasClinical  = trim($record->description          ?? '') !== '';
                        $hasEstimate  = trim($record->estimate_description ?? '') !== '';
                        $hasBoth      = $hasClinical && $hasEstimate;
                        $estimateOnly = !$hasClinical && $hasEstimate;

                        $isFree = str_contains(strtolower($record->amount        ?? ''), 'free')
                               || str_contains(strtolower($record->paid          ?? ''), 'free')
                               || str_contains(strtolower($record->estimate_cost ?? ''), 'free')
                               || str_contains(strtolower($record->estimate_paid ?? ''), 'free');

                        $rowBg = $hasEstimate ? 'bg-gray-100' : 'bg-white';
                    @endphp

                    <tr class="{{ $rowBg }} hover:brightness-95 transition-all align-top">

                        {{-- Serial # --}}
                        <td class="px-3 py-2 text-center text-gray-400">{{ $loop->iteration }}</td>

                        {{-- Date --}}
                        <td class="px-3 py-2 text-center whitespace-nowrap text-gray-700">
                            {{ \Carbon\Carbon::parse($record->date)->format('d/m/Y') }}
                        </td>

                        {{-- Name --}}
                        <td class="px-3 py-2 font-medium text-gray-800">
                            {{ $record->patient->name ?? '—' }}
                        </td>

                        {{-- Age --}}
                        <td class="px-3 py-2 text-center text-gray-700">
                            {{ $record->patient->age ?? '—' }}
                        </td>

                        {{-- Gender --}}
                        <td class="px-3 py-2 text-center text-gray-700">
                            {{ $record->patient->gender ?? '—' }}
                        </td>

                        {{-- Diagnostic --}}
                        <td class="px-3 py-2 text-gray-700">
                            {{ $record->diagnostic ?? '—' }}
                        </td>

                        {{-- Description — merged clinical + estimate --}}
                        <td class="px-3 py-2 text-gray-700 leading-relaxed">
                            @if ($hasClinical)
                                <span>{{ $record->description }}</span>
                            @endif
                            @if ($hasEstimate)
                                @if ($hasClinical)
                                    <br>
                                @endif
                                <span class="text-gray-500 italic">{{ $record->estimate_description }}</span>
                            @endif
                            @if ($hasBoth)
                                {{-- Both clinical and estimate description present on the same record --}}
                                <br><span class="text-red-500 font-bold text-xs">?</span>
                            @endif
                            @if (!$hasClinical && !$hasEstimate)
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>

                        {{-- FREE flag --}}
                        <td class="px-3 py-2 text-center">
                            @if ($isFree)
                                <span class="inline-block bg-green-100 text-green-700 font-bold text-xs
                                             px-2 py-0.5 rounded-full border border-green-300">
                                    FREE
                                </span>
                            @endif
                        </td>

                    </tr>
                @endforeach
            </tbody>

        </table>
    </div>

@endif

{{-- ── Error flash (wrong password) ───────────────────────────────────────── --}}
@if (session('error'))
    <div class="mt-4 bg-red-50 border border-red-300 text-red-700 text-sm px-4 py-3 rounded-lg">
        {{ session('error') }}
    </div>
@endif

{{-- ── Export password modal ───────────────────────────────────────────────── --}}
<div id="export-modal"
     class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40">
    <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-sm mx-4">

        <h3 class="text-lg font-bold text-gray-800 mb-1">Export to Excel</h3>
        <p class="text-sm text-gray-500 mb-5">
            Enter the records access key to download the patient list.
        </p>

        <form method="POST"
              action="{{ route('reports.patients_attending.statistics_month.export') }}"
              onsubmit="document.getElementById('export-modal').classList.add('hidden');"
>
            @csrf
            <input type="hidden" name="month" value="{{ $month }}">
            <input type="hidden" name="year"  value="{{ $year }}">

            <label class="block text-sm font-medium text-gray-700 mb-1">Access key</label>
            <input type="password" name="access_key" id="export-access-key"
                   autocomplete="off"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm mb-5
                          focus:outline-none focus:ring-2 focus:ring-clinic focus:ring-offset-1"
                   placeholder="••••••••">

            <div class="flex gap-3 justify-end">
                <button type="button"
                        onclick="document.getElementById('export-modal').classList.add('hidden');
                                 document.getElementById('export-access-key').value = '';"
                        class="btn-clinic-grey text-sm px-4 py-2 rounded-lg">
                    Cancel
                </button>
                <button type="submit"
                        class="btn-clinic-primary text-sm font-medium px-4 py-2 rounded-lg">
                    Download
                </button>
            </div>
        </form>

    </div>
</div>

@endsection
