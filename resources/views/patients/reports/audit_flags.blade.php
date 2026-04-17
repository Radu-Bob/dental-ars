@extends('layouts.app')

@section('title', 'Import Red Flags')

@section('left_content')
    @include('patients.reports.partials.reports-sidebar')
@endsection

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
            <span class="inline-block w-3 h-3 rounded-full bg-red-500"></span>
            Import Red Flags
        </h2>
        <p class="text-sm text-gray-500 mt-1">
            Partner imports where a user confirmed despite a similar patient already existing locally.
        </p>
    </div>
    <div class="text-sm text-gray-400 text-right">
        Page {{ $flags->currentPage() }} of {{ $flags->lastPage() }}<br>
        <span class="text-xs">{{ number_format($flags->total()) }} total flagged {{ $flags->total() === 1 ? 'entry' : 'entries' }}</span>
    </div>
</div>

@if ($flags->isEmpty())
    <div class="bg-green-50 border border-green-200 text-green-800 p-5 rounded-lg flex items-center gap-3">
        <svg class="w-6 h-6 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
        <div>
            <p class="font-semibold">No red flags on record.</p>
            <p class="text-sm mt-0.5">All partner imports were clean — no similar patients existed at the time of import.</p>
        </div>
    </div>
@else

    <div class="space-y-4">
        @foreach ($flags as $entry)
            @php
                $after = $entry->after ?? [];
            @endphp

            <div class="bg-white rounded-lg shadow-sm border-l-4 border-red-500 p-5">

                {{-- Header row --}}
                <div class="flex flex-wrap items-center gap-3 mb-3">

                    <span class="inline-block text-xs font-bold uppercase tracking-wider px-2 py-0.5 rounded border bg-red-100 text-red-700 border-red-300">
                        ⚑ Flagged Import
                    </span>

                    {{-- Patient link --}}
                    <span class="text-sm font-medium text-gray-700">
                        Patient
                        <a href="{{ route('patients.show', ['patient_id' => $entry->model_id]) }}"
                           class="text-clinic hover:text-clinic-bold underline ml-1">#{{ $entry->model_id }}</a>
                    </span>

                    <span class="text-gray-300 hidden sm:inline">|</span>

                    {{-- Who --}}
                    <span class="text-sm text-gray-600">
                        Imported by
                        <span class="font-medium text-gray-800">{{ $entry->user_name ?? 'Unknown' }}</span>
                        @if ($entry->user_id)
                            <span class="text-gray-400 text-xs ml-1">(ID {{ $entry->user_id }})</span>
                        @endif
                    </span>

                    <span class="text-gray-300 hidden sm:inline">|</span>

                    {{-- When --}}
                    <span class="text-xs text-gray-400 ml-auto">
                        {{ \Carbon\Carbon::parse($entry->created_at)->format('d/m/Y  H:i:s') }}
                    </span>
                </div>

                {{-- Flag reason --}}
                @if ($entry->flag_reason)
                    <div class="bg-red-50 border border-red-200 rounded-lg px-4 py-3 mb-3">
                        <p class="text-xs font-semibold text-red-700 uppercase tracking-wide mb-1">Flag Reason</p>
                        <p class="text-sm text-red-900">{{ $entry->flag_reason }}</p>
                    </div>
                @endif

                {{-- Imported data (after values) --}}
                @if (!empty($after))
                    @php
                        $labels = [
                            'date_of_birth' => 'Date of Birth', 'tel' => 'Telephone', 'email' => 'Email',
                            'gender' => 'Gender', 'location' => 'Location', 'pobox' => 'P.O. Box',
                            'town' => 'Town', 'occupation' => 'Occupation', 'remarks' => 'Remarks',
                        ];
                    @endphp
                    <details class="mt-1">
                        <summary class="text-xs font-medium text-gray-500 cursor-pointer hover:text-gray-700 select-none">
                            Show imported field values
                        </summary>
                        <div class="mt-2 divide-y divide-gray-100 text-sm">
                            @foreach ($after as $key => $value)
                                @if ($value !== null && $value !== '')
                                    <div class="py-1.5 grid grid-cols-[10rem_1fr] gap-2">
                                        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide pt-0.5">
                                            {{ $labels[$key] ?? ucfirst(str_replace('_', ' ', $key)) }}
                                        </span>
                                        <span class="text-gray-800">{{ $value }}</span>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </details>
                @endif

                {{-- IP footer --}}
                @if ($entry->ip_address)
                    <div class="mt-3 pt-2 border-t border-gray-100 text-xs text-gray-400">
                        IP: {{ $entry->ip_address }}
                        @if ($entry->user_agent)
                            &nbsp;·&nbsp; {{ Str::limit($entry->user_agent, 80) }}
                        @endif
                    </div>
                @endif

            </div>
        @endforeach
    </div>

    {{-- Pagination --}}
    <div class="mt-6 flex justify-center bg-gray-100 rounded-lg p-2 text-sm text-gray-700">
        {{ $flags->links('pagination.custom-pagination') }}
    </div>

@endif

@endsection
