@extends('layouts.app')

@section('title', 'Appointment Daily Notes')

@section('content')

<div class="flex items-center justify-between mb-6 gap-4 flex-wrap">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Daily Notes</h2>
        <p class="text-sm text-gray-500 mt-1">
            {{ \Carbon\Carbon::parse($date)->format('l, j F Y') }} — appointment activity trail.
        </p>
    </div>

    <form method="GET" action="{{ route('appointments.daily_notes') }}"
          class="flex items-center gap-3 bg-gray-50 border border-gray-200 rounded-xl px-5 py-3">
        <label class="flex items-center gap-2 text-sm text-gray-700 font-medium">
            Date
            <input type="date" name="date" value="{{ $date }}"
                   class="border border-gray-300 rounded-lg px-2 py-1 text-sm
                          focus:outline-none focus:ring-2 focus:ring-clinic focus:ring-offset-1">
        </label>
        <button type="submit"
                class="btn-clinic-primary text-sm font-medium px-4 py-1.5 rounded-lg
                       focus:outline-none focus:ring-2 focus:ring-offset-1 transition shadow-sm">
            View
        </button>
    </form>

    <a href="{{ route('appointments.index', ['date' => $date]) }}"
       class="text-sm text-clinic hover:underline">← Back to day view</a>
</div>

@if ($logs->isEmpty())
    <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 p-4 rounded-lg text-sm">
        No appointment activity recorded for this day.
    </div>
@else

    @php
        $labels = [
            'patient_id'        => 'Patient',
            'appointment_date'  => 'Date',
            'start_time'        => 'Start Time',
            'end_time'          => 'End Time',
            'status'            => 'Status',
            'reason'            => 'Reason',
            'notes'             => 'Notes',
        ];
        $actionColor = fn ($action) => match ($action) {
            'created' => ['bg' => 'bg-green-100', 'text' => 'text-green-700', 'border' => 'border-green-300'],
            'updated' => ['bg' => 'bg-blue-100',  'text' => 'text-blue-700',  'border' => 'border-blue-300'],
            'deleted' => ['bg' => 'bg-red-100',   'text' => 'text-red-700',   'border' => 'border-red-300'],
            default   => ['bg' => 'bg-gray-100',  'text' => 'text-gray-700',  'border' => 'border-gray-300'],
        };
    @endphp

    <div class="space-y-3">
        @foreach ($logs as $entry)
            @php $color = $actionColor($entry->action); @endphp
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <div class="flex flex-wrap items-center gap-3 mb-3">
                    <span class="inline-block text-xs font-bold uppercase tracking-wider px-2 py-0.5 rounded border
                                 {{ $color['bg'] }} {{ $color['text'] }} {{ $color['border'] }}">
                        {{ $entry->action }}
                    </span>
                    <span class="text-sm font-medium text-gray-700">
                        Appointment <span class="text-gray-500">#{{ $entry->model_id }}</span>
                    </span>
                    <span class="text-gray-300 hidden sm:inline">|</span>
                    <span class="text-sm text-gray-600">
                        <span class="font-medium text-gray-800">{{ $entry->user_name ?? 'System' }}</span>
                    </span>
                    <span class="text-xs text-gray-400 ml-auto">
                        {{ \Carbon\Carbon::parse($entry->created_at)->setTimezone(config('app.clinic_timezone'))->format('H:i:s') }}
                    </span>
                </div>

                @php
                    $before = $entry->before ?? [];
                    $after  = $entry->after  ?? [];
                    $keys   = array_unique(array_merge(array_keys($before), array_keys($after)));
                @endphp

                @if (!empty($keys))
                    <div class="mt-1 divide-y divide-gray-100 text-sm">
                        @foreach ($keys as $key)
                            @php
                                $label  = $labels[$key] ?? ucfirst(str_replace('_', ' ', $key));
                                $oldVal = $before[$key] ?? null;
                                $newVal = $after[$key]  ?? null;
                            @endphp
                            <div class="py-1.5 grid grid-cols-[8rem_1fr] gap-2 items-start">
                                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide pt-0.5">{{ $label }}</span>
                                <span>
                                    @if ($entry->action === 'updated')
                                        @if ($oldVal !== null)<span class="line-through text-red-500 mr-2">{{ $oldVal }}</span>@endif
                                        @if ($newVal !== null)<span class="text-green-700 font-medium">{{ $newVal }}</span>@endif
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
            </div>
        @endforeach
    </div>

@endif

@endsection
