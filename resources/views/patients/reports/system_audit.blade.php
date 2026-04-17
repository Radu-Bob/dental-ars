@extends('layouts.app')

@section('title', 'System Audit Log')

@section('left_content')
    @include('patients.reports.partials.reports-sidebar')

    {{-- Recent Red Flags panel --}}
    <div class="bg-white p-4 rounded-xl shadow-lg mt-4 space-y-3">
        <div class="flex items-center justify-between border-b pb-2">
            <h3 class="text-sm font-bold text-red-700 uppercase tracking-wide">Recent Red Flags</h3>
            <span class="text-xs text-gray-400">last 5 days</span>
        </div>

        @if ($recentFlags->isEmpty())
            <p class="text-xs text-gray-400 italic">No flagged imports in the last 5 days.</p>
        @else
            <div class="space-y-2">
                @foreach ($recentFlags as $flag)
                    <div class="p-2 bg-red-50 border border-red-200 rounded-lg">
                        <p class="text-xs font-semibold text-red-800">
                            Patient #{{ $flag->model_id }}
                        </p>
                        <p class="text-xs text-gray-600 mt-0.5 line-clamp-2">
                            {{ Str::limit($flag->flag_reason, 80) }}
                        </p>
                        <p class="text-xs text-gray-400 mt-0.5">
                            {{ \Carbon\Carbon::parse($flag->created_at)->format('d/m/Y H:i') }}
                            — {{ $flag->user_name ?? 'System' }}
                        </p>
                    </div>
                @endforeach
            </div>
            <a href="{{ route('reports.audit_flags') }}"
               class="block text-center text-xs text-clinic hover:text-clinic-bold underline pt-1">
                View all flagged imports →
            </a>
        @endif
    </div>
@endsection

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">System Audit Log</h2>
        <p class="text-sm text-gray-500 mt-1">
            Every create, update, and delete action on patient data — most recent first.
        </p>
    </div>
    <div class="text-sm text-gray-400 text-right">
        Page {{ $logs->currentPage() }} of {{ $logs->lastPage() }}<br>
        <span class="text-xs">{{ number_format($logs->total()) }} total entries</span>
    </div>
</div>

@if ($logs->isEmpty())
    <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 p-4 rounded-lg">
        No audit log entries yet. The log records all future changes made through the application.
    </div>
@else

    <div class="space-y-3">
        @foreach ($logs as $entry)
            @php
                $actionColor = match($entry->action) {
                    'created' => ['bg' => 'bg-green-100', 'text' => 'text-green-700', 'border' => 'border-green-300'],
                    'updated' => ['bg' => 'bg-blue-100',  'text' => 'text-blue-700',  'border' => 'border-blue-300'],
                    'deleted' => ['bg' => 'bg-red-100',   'text' => 'text-red-700',   'border' => 'border-red-300'],
                    default   => ['bg' => 'bg-gray-100',  'text' => 'text-gray-700',  'border' => 'border-gray-300'],
                };

                $modelLabel = match($entry->model_type) {
                    'Patient'         => 'Patient',
                    'PatientClinical' => 'Clinical Record',
                    'Insurance'       => 'Insurance',
                    default           => $entry->model_type,
                };
            @endphp

            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">

                {{-- Header row: badge | who | what | when | where --}}
                <div class="flex flex-wrap items-center gap-3 mb-3">

                    {{-- Action badge --}}
                    <span class="inline-block text-xs font-bold uppercase tracking-wider px-2 py-0.5 rounded border
                                 {{ $actionColor['bg'] }} {{ $actionColor['text'] }} {{ $actionColor['border'] }}">
                        {{ $entry->action }}
                    </span>

                    {{-- Model type + ID --}}
                    <span class="text-sm font-medium text-gray-700">
                        {{ $modelLabel }}
                        @if ($entry->model_type === 'Patient')
                            <a href="{{ route('patients.show', ['patient_id' => $entry->model_id]) }}"
                               class="text-clinic hover:text-clinic-bold underline ml-1">#{{ $entry->model_id }}</a>
                        @else
                            <span class="text-gray-500 ml-1">#{{ $entry->model_id }}</span>
                        @endif
                    </span>

                    <span class="text-gray-300 hidden sm:inline">|</span>

                    {{-- User --}}
                    <span class="text-sm text-gray-600">
                        <span class="font-medium text-gray-800">{{ $entry->user_name ?? 'System' }}</span>
                        @if ($entry->user_id)
                            <span class="text-gray-400 text-xs ml-1">(ID {{ $entry->user_id }})</span>
                        @endif
                    </span>

                    <span class="text-gray-300 hidden sm:inline">|</span>

                    {{-- Timestamp --}}
                    <span class="text-xs text-gray-400 ml-auto" title="{{ $entry->created_at }}">
                        {{ \Carbon\Carbon::parse($entry->created_at)->format('d/m/Y  H:i:s') }}
                    </span>
                </div>

                {{-- Changed fields --}}
                @php
                    $before = $entry->before ?? [];
                    $after  = $entry->after  ?? [];
                    $keys   = array_unique(array_merge(array_keys($before), array_keys($after)));

                    // Human-readable field labels
                    $labels = [
                        'date_of_birth'        => 'Date of Birth',
                        'tel'                  => 'Telephone',
                        'email'                => 'Email',
                        'gender'               => 'Gender',
                        'location'             => 'Location',
                        'pobox'                => 'P.O. Box',
                        'town'                 => 'Town',
                        'occupation'           => 'Occupation',
                        'remarks'              => 'Remarks',
                        'diagnostic'           => 'Diagnostic',
                        'description'          => 'Description',
                        'tooth'                => 'Tooth',
                        'amount'               => 'Amount',
                        'paid'                 => 'Paid',
                        'balance'              => 'Balance',
                        'estimate_description' => 'Estimate Description',
                        'estimate'             => 'Estimate',
                        'estimate_cost'        => 'Estimate Cost',
                        'estimate_paid'        => 'Estimate Paid',
                        'estimate_balance'     => 'Estimate Balance',
                        'notes'                => 'Notes',
                        'insurance_no'         => 'Policy No.',
                        'insurance_id_no'      => 'Insurance ID',
                        'insurance_provider'   => 'Insurance Provider',
                        'insurance_remarks'    => 'Insurance Remarks',
                        'invalidation_reason'  => 'Invalidation Reason',
                    ];
                @endphp

                @if (!empty($keys))
                    <div class="mt-1 divide-y divide-gray-100 text-sm">
                        @foreach ($keys as $key)
                            @php
                                $label     = $labels[$key] ?? ucfirst(str_replace('_', ' ', $key));
                                $oldVal    = $before[$key] ?? null;
                                $newVal    = $after[$key]  ?? null;
                            @endphp
                            <div class="py-1.5 grid grid-cols-[10rem_1fr] gap-2 items-start">
                                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide pt-0.5">
                                    {{ $label }}
                                </span>
                                <span>
                                    @if ($entry->action === 'updated')
                                        @if ($oldVal !== null)
                                            <span class="line-through text-red-500 mr-2">{{ $oldVal }}</span>
                                        @endif
                                        @if ($newVal !== null)
                                            <span class="text-green-700 font-medium">{{ $newVal }}</span>
                                        @endif
                                    @elseif ($entry->action === 'created')
                                        <span class="text-gray-800">{{ $newVal }}</span>
                                    @elseif ($entry->action === 'deleted')
                                        <span class="text-gray-500 italic">{{ $oldVal }}</span>
                                    @endif
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- IP / UA footer --}}
                @if ($entry->ip_address || $entry->user_agent)
                    <div class="mt-3 pt-2 border-t border-gray-100 text-xs text-gray-400 flex flex-wrap gap-x-4">
                        @if ($entry->ip_address)
                            <span>IP: {{ $entry->ip_address }}</span>
                        @endif
                        @if ($entry->user_agent)
                            <span class="truncate max-w-sm" title="{{ $entry->user_agent }}">
                                {{ Str::limit($entry->user_agent, 80) }}
                            </span>
                        @endif
                    </div>
                @endif

            </div>
        @endforeach
    </div>

    {{-- Pagination --}}
    <div class="mt-6 flex justify-center bg-gray-100 rounded-lg p-2 text-sm text-gray-700">
        {{ $logs->links('pagination.custom-pagination') }}
    </div>

@endif

@endsection
