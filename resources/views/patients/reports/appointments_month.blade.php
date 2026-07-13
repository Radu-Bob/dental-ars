@extends($layout ?? 'layouts.app')

@section('title', 'Appointments Month')

@section('left_content')
    @include('patients.reports.partials.reports-sidebar')
@endsection

@section('content')

{{-- ── Filter bar ───────────────────────────────────────────────────────────── --}}
<div class="flex items-center justify-between mb-6 gap-4 flex-wrap">

    <form method="GET" action="{{ route('reports.patients_attending.appointments_month') }}"
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

</div>

{{-- ── Heading ─────────────────────────────────────────────────────────────── --}}
<div class="mb-4">
    <h2 class="text-xl font-bold text-gray-800">
        Appointments — {{ DateTime::createFromFormat('!m', $month)->format('F') }} {{ $year }}
    </h2>
    <p class="text-sm text-gray-500 mt-1">{{ $appointments->count() }} appointment(s) found.</p>
</div>

{{-- ── Table ────────────────────────────────────────────────────────────────── --}}
@if ($appointments->isEmpty())

    <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 p-4 rounded-lg text-sm">
        No appointments found for {{ DateTime::createFromFormat('!m', $month)->format('F') }} {{ $year }}.
    </div>

@else

    <div class="overflow-x-auto rounded-xl shadow-sm border border-gray-200">
        <table class="w-full text-sm border-collapse">

            <thead>
                <tr class="bg-gray-100 text-gray-600 uppercase text-xs tracking-wide">
                    <th class="px-3 py-3 text-center w-10">#</th>
                    <th class="px-3 py-3 text-center w-24">Date</th>
                    <th class="px-3 py-3 text-center w-20">Time</th>
                    <th class="px-3 py-3 text-left min-w-[9rem]">Patient</th>
                    <th class="px-3 py-3 text-left w-28">Status</th>
                    <th class="px-3 py-3 text-left">Reason</th>
                    <th class="px-3 py-3 text-right w-24">History</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-100">
                @php $lastDate = null; $dateShade = false; @endphp
                @foreach ($appointments as $i => $appointment)
                    @php
                        $patientName = $appointment->patient->name ?? $appointment->patient_name_freetext ?? '—';
                        $dateFormatted = \Carbon\Carbon::parse($appointment->appointment_date)->format('d/m/Y');
                        $timeFormatted = \Carbon\Carbon::parse($appointment->start_time)->format('H:i');

                        if ($appointment->appointment_date !== $lastDate) {
                            $dateShade = ! $dateShade;
                            $lastDate = $appointment->appointment_date;
                        }
                    @endphp
                    <tr class="{{ $dateShade ? 'bg-clinic-tint' : 'bg-white' }} hover:brightness-95 transition-all align-top">
                        <td class="px-3 py-2 text-center text-gray-400">{{ $i + 1 }}</td>
                        <td class="px-3 py-2 text-center whitespace-nowrap text-gray-700">{{ $dateFormatted }}</td>
                        <td class="px-3 py-2 text-center whitespace-nowrap text-gray-700">{{ $timeFormatted }}</td>
                        <td class="px-3 py-2 font-medium text-gray-800">
                            @if ($appointment->patient)
                                <a href="{{ route('patients.show', ['patient_id' => $appointment->patient->patient_id]) }}"
                                   class="text-clinic hover:underline">{{ $patientName }}</a>
                            @else
                                {{ $patientName }}
                            @endif
                        </td>
                        <td class="px-3 py-2">
                            <span class="inline-block text-xs font-medium px-2 py-0.5 rounded-full border
                                @if($appointment->status === 'completed') bg-green-50 text-green-700 border-green-200
                                @elseif($appointment->status === 'no_show') bg-gray-100 text-gray-600 border-gray-300
                                @else bg-blue-50 text-blue-700 border-blue-200
                                @endif">
                                {{ ucfirst(str_replace('_', ' ', $appointment->status)) }}
                            </span>
                        </td>
                        <td class="px-3 py-2 text-gray-700">{{ $appointment->reason ?: '—' }}</td>
                        <td class="px-3 py-2 text-right">
                            @if ($appointment->patient)
                                <button type="button"
                                        onclick="apptShowHistory({{ $appointment->patient->patient_id }}, '{{ $patientName }}')"
                                        class="text-clinic hover:underline text-xs font-medium">
                                    History
                                </button>
                            @else
                                <span class="text-gray-300 text-xs">—</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>

        </table>
    </div>

@endif

{{-- ── History Overlay ─────────────────────────────────────────────────────── --}}
<div id="apptHistoryOverlay" class="hidden fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-6" onclick="if(event.target===this) apptCloseHistory()">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[80vh] flex flex-col">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200">
            <h3 id="apptHistoryTitle" class="text-lg font-bold text-gray-800">Appointment History</h3>
            <button type="button" onclick="apptCloseHistory()" class="text-gray-400 hover:text-gray-700 text-2xl leading-none">&times;</button>
        </div>
        <div id="apptHistoryBody" class="p-5 overflow-y-auto flex-1 text-sm"></div>
    </div>
</div>

<script>
    function apptShowHistory(patientId, patientName) {
        document.getElementById('apptHistoryTitle').textContent = 'Appointment History — ' + patientName;
        const body = document.getElementById('apptHistoryBody');
        body.innerHTML = '<p class="text-gray-400 text-center">Loading…</p>';
        document.getElementById('apptHistoryOverlay').classList.remove('hidden');

        fetch('{{ route("appointments.patient_history") }}?patient_id=' + patientId)
            .then(r => r.json())
            .then(data => {
                if (!data.items.length) {
                    body.innerHTML = '<p class="text-gray-400 text-center">No previous appointments found.</p>';
                    return;
                }

                let html = '<ul class="space-y-2">';
                data.items.forEach(function (item) {
                    html += `<li class="border-b border-gray-100 pb-1.5">
                        <div class="flex justify-between text-gray-700">
                            <span>${item.date} ${item.time}</span>
                            <span class="text-xs text-gray-400">${item.status}</span>
                        </div>
                        ${item.reason ? `<div class="text-xs text-gray-500">${item.reason}</div>` : ''}
                    </li>`;
                });
                html += '</ul>';
                body.innerHTML = html;
            });
    }

    function apptCloseHistory() {
        document.getElementById('apptHistoryOverlay').classList.add('hidden');
    }
</script>

@endsection
