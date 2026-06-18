@extends('layouts.app')

@section('title', 'Appointments')

@section('content')

<div class="flex items-center justify-between mb-6 gap-4 flex-wrap">

    <form method="GET" action="{{ route('appointments.index') }}"
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

    <div class="flex items-center gap-3">
        <a href="{{ route('appointments.daily_notes', ['date' => $date]) }}"
           class="text-sm font-medium px-4 py-2 rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 shadow-sm transition">
            Daily Notes
        </a>
        <a href="{{ route('appointments.export_ics') }}"
           class="text-sm font-medium px-4 py-2 rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 shadow-sm transition">
            ⬇ Export .ics
        </a>
        <a href="{{ route('appointments.create', ['date' => $date]) }}"
           class="btn-clinic-primary text-sm font-medium px-4 py-2 rounded-lg shadow-sm transition">
            + New Appointment
        </a>
    </div>

</div>

<h2 class="text-xl font-bold text-gray-800 mb-1">
    Appointments — {{ \Carbon\Carbon::parse($date)->format('l, j F Y') }}
</h2>
<p class="text-sm text-gray-500 mb-4">{{ $appointments->count() }} appointment(s).</p>

@if (session('success'))
    <div class="mb-4 bg-green-50 border border-green-300 text-green-700 text-sm px-4 py-3 rounded-lg">
        {{ session('success') }}
    </div>
@endif

@if ($appointments->isEmpty())

    <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 p-4 rounded-lg text-sm">
        No appointments booked for this day.
    </div>

@else

    <div class="overflow-x-auto rounded-xl shadow-sm border border-gray-200">
        <table class="w-full text-sm border-collapse">
            <thead>
                <tr class="bg-gray-100 text-gray-600 uppercase text-xs tracking-wide">
                    <th class="px-3 py-3 text-left w-32">Time</th>
                    <th class="px-3 py-3 text-left">Patient</th>
                    <th class="px-3 py-3 text-left">Reason</th>
                    <th class="px-3 py-3 text-left w-28">Status</th>
                    <th class="px-3 py-3 text-left min-w-[8rem]">Booked by</th>
                    <th class="px-3 py-3 text-right w-40">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($appointments as $appointment)
                    @php
                        $isOverlapping = in_array($appointment->id, $overlapIds);
                        $patientLabel = $appointment->patient->name ?? $appointment->patient_name_freetext ?? '—';
                    @endphp
                    <tr class="{{ $isOverlapping ? 'bg-orange-50 border-l-4 border-orange-400' : 'bg-white' }} hover:brightness-95 transition-all align-top">
                        <td class="px-3 py-2 whitespace-nowrap text-gray-700">
                            {{ $appointment->start_time }} – {{ $appointment->end_time }}
                            @if ($isOverlapping)
                                <span class="inline-block mt-1 text-xs font-bold uppercase text-orange-700 bg-orange-100 border border-orange-300 px-1.5 py-0.5 rounded">
                                    Overlap
                                </span>
                            @endif
                        </td>
                        <td class="px-3 py-2 font-medium text-gray-800">
                            @if ($appointment->patient)
                                <a href="{{ route('patients.show', ['patient_id' => $appointment->patient->patient_id]) }}"
                                   class="text-clinic hover:underline">{{ $patientLabel }}</a>
                            @else
                                {{ $patientLabel }}
                            @endif
                        </td>
                        <td class="px-3 py-2 text-gray-700">{{ $appointment->reason ?: '—' }}</td>
                        <td class="px-3 py-2">
                            <span class="inline-block text-xs font-medium px-2 py-0.5 rounded-full border
                                @if($appointment->status === 'cancelled') bg-red-50 text-red-700 border-red-200
                                @elseif($appointment->status === 'completed') bg-green-50 text-green-700 border-green-200
                                @elseif($appointment->status === 'no_show') bg-gray-100 text-gray-600 border-gray-300
                                @else bg-blue-50 text-blue-700 border-blue-200
                                @endif">
                                {{ ucfirst(str_replace('_', ' ', $appointment->status)) }}
                            </span>
                        </td>
                        <td class="px-3 py-2 text-gray-600 text-xs">{{ $appointment->creator->name ?? '—' }}</td>
                        <td class="px-3 py-2 text-right">
                            @if (Auth::user()->is_doctor || Auth::user()->is_nurse)
                                <a href="{{ route('appointments.edit', $appointment) }}"
                                   class="text-clinic hover:underline text-xs font-medium">Edit</a>
                                @if ($appointment->status !== 'cancelled')
                                    <form action="{{ route('appointments.cancel', $appointment) }}" method="POST" class="inline"
                                          onsubmit="return confirm('Cancel this appointment?');">
                                        @csrf
                                        <button type="submit" class="text-red-600 hover:underline text-xs font-medium ml-2">Cancel</button>
                                    </form>
                                @endif
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

@endif

@endsection
