@extends($layout ?? 'layouts.app')

@section('title', 'Appointments')

@section('left_content')
    <div class="bg-white p-5 rounded-2xl shadow-xl">
        <h2 class="text-base font-bold text-gray-800 mb-3">Appointments</h2>

        <div class="flex justify-between items-center mb-3">
            <button type="button" onclick="apptChangeMonth(-1)" class="text-lg font-semibold px-1 hover:text-clinic">&lt;</button>
            <span id="apptCalLabel" class="text-sm font-bold text-gray-800"></span>
            <button type="button" onclick="apptChangeMonth(1)" class="text-lg font-semibold px-1 hover:text-clinic">&gt;</button>
        </div>

        <div class="grid grid-cols-7 text-center text-xs font-medium text-gray-500 mb-1">
            <div>Mo</div><div>Tu</div><div>We</div><div>Th</div><div>Fr</div><div>Sa</div><div>Su</div>
        </div>

        <div id="apptCalGrid" class="grid grid-cols-7 gap-1"></div>

        <p class="text-xs text-gray-400 mt-3 flex items-center gap-1">
            <span class="w-1.5 h-1.5 rounded-full bg-orange-500 inline-block"></span>
            Day has appointment(s)
        </p>

        <button type="button" onclick="apptOpenWeekView()"
                class="w-full mt-3 text-sm font-medium px-3 py-2 rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 shadow-sm transition">
            📅 Whole Week View
        </button>
    </div>

    <div class="grid grid-cols-3 gap-2 mt-4">
        @foreach ($quickCounts as $label => $info)
            @php $minRows = 10; @endphp
            <div class="rounded-xl border overflow-hidden {{ $info['date'] === $date ? 'border-clinic' : 'border-gray-200' }}">
                <a href="{{ route('appointments.index', ['date' => $info['date']]) }}"
                   title="{{ \Carbon\Carbon::parse($info['date'])->format('l, j F Y') }}"
                   class="block text-center px-2 py-2 transition
                          {{ $info['date'] === $date ? 'bg-clinic text-white' : 'bg-white hover:bg-gray-50 text-gray-700' }}">
                    <span class="block text-xs font-semibold uppercase tracking-wide {{ $info['date'] === $date ? 'text-white/80' : 'text-gray-400' }}">{{ $label }}</span>
                    <span class="block text-lg font-bold mt-0.5">{{ $info['count'] }}</span>
                </a>
                <div class="bg-white border-t border-gray-100 divide-y divide-gray-50 text-[11px] leading-tight">
                    @for ($i = 0; $i < max($info['list']->count(), $minRows); $i++)
                        <div class="px-1.5 py-1 truncate">
                            @if ($i < $info['list']->count())
                                @php $appt = $info['list'][$i]; @endphp
                                <span class="text-gray-400">{{ \Carbon\Carbon::parse($appt->start_time)->format('H:i') }}</span>
                                @if ($appt->patient)
                                    <a href="{{ route('patients.show', ['patient_id' => $appt->patient->patient_id]) }}" class="text-clinic hover:underline ml-1">{{ \Illuminate\Support\Str::before($appt->patient->name, ' ') }}</a>
                                @else
                                    <span class="text-gray-600 ml-1">{{ \Illuminate\Support\Str::before($appt->patient_name_freetext ?: '—', ' ') }}</span>
                                @endif
                            @else
                                &nbsp;
                            @endif
                        </div>
                    @endfor
                </div>
            </div>
        @endforeach
    </div>

    {{-- Week View Overlay --}}
    <div id="apptWeekOverlay" class="hidden fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-6" onclick="if(event.target===this) apptCloseWeekView()">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-6xl max-h-[85vh] flex flex-col">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200">
                <h3 id="apptWeekLabel" class="text-lg font-bold text-gray-800">Week View</h3>
                <button type="button" onclick="apptCloseWeekView()" class="text-gray-400 hover:text-gray-700 text-2xl leading-none">&times;</button>
            </div>
            <div id="apptWeekGrid" class="grid grid-cols-7 gap-2 p-5 overflow-y-auto flex-1"></div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        const apptSelectedDate = '{{ $date }}';
        const apptMarkedDates  = @json($monthDatesWithAppointments);
        const apptCanEdit      = @json(Auth::user()->is_doctor || Auth::user()->is_nurse);
        let apptCalCursor      = new Date('{{ $date }}T00:00:00');

        // Use the browser's local calendar date, not toISOString() (which is always UTC
        // and would misjudge "today" during the EAT/UTC offset window near midnight).
        function apptLocalDateStr(d) {
            return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
        }

        function apptRenderCalendar() {
            const year  = apptCalCursor.getFullYear();
            const month = apptCalCursor.getMonth();

            document.getElementById('apptCalLabel').textContent =
                apptCalCursor.toLocaleString('default', { month: 'long', year: 'numeric' });

            const firstOfMonth = new Date(year, month, 1);
            let startOffset = firstOfMonth.getDay() - 1; // Monday-first grid
            if (startOffset < 0) startOffset = 6;
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const todayStr = apptLocalDateStr(new Date());

            let html = '';
            for (let i = 0; i < startOffset; i++) html += '<div></div>';

            for (let day = 1; day <= daysInMonth; day++) {
                const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                const isSelected = dateStr === apptSelectedDate;
                const isToday = dateStr === todayStr;
                const hasAppt = apptMarkedDates.includes(dateStr);

                let classes = 'w-8 h-8 flex items-center justify-center mx-auto text-xs rounded-full cursor-pointer relative';
                if (isSelected) classes += ' bg-clinic text-white font-bold';
                else if (isToday) classes += ' border border-clinic text-clinic font-semibold';
                else classes += ' hover:bg-gray-100 text-gray-700';

                const dot = hasAppt
                    ? `<span class="absolute bottom-0.5 w-1.5 h-1.5 rounded-full ${isSelected ? 'bg-white' : 'bg-orange-500'}"></span>`
                    : '';

                html += `<div class="${classes}" onclick="apptGoToDate('${dateStr}')">${day}${dot}</div>`;
            }

            document.getElementById('apptCalGrid').innerHTML = html;
        }

        function apptChangeMonth(delta) {
            apptCalCursor.setMonth(apptCalCursor.getMonth() + delta);
            const y = apptCalCursor.getFullYear();
            const m = String(apptCalCursor.getMonth() + 1).padStart(2, '0');
            window.location.href = '{{ route("appointments.index") }}?date=' + y + '-' + m + '-01';
        }

        function apptGoToDate(dateStr) {
            window.location.href = '{{ route("appointments.index") }}?date=' + dateStr;
        }

        function apptOpenWeekView() {
            fetch('{{ route("appointments.week_summary") }}?date=' + apptSelectedDate)
                .then(r => r.json())
                .then(data => {
                    document.getElementById('apptWeekLabel').textContent = 'Week of ' + data.week_label;

                    let html = '';
                    data.days.forEach(function (day) {
                        const isToday = day.date === apptLocalDateStr(new Date());
                        html += `<div class="border rounded-lg flex flex-col ${isToday ? 'border-clinic' : 'border-gray-200'}">`;
                        html += `<div class="text-center text-xs font-bold py-2 border-b ${isToday ? 'bg-clinic text-white' : 'bg-gray-50 text-gray-600'}">${day.label}</div>`;
                        html += '<div class="p-1.5 space-y-1 text-[11px] flex-1 min-h-[28rem]">';

                        if (!day.items.length) {
                            html += '<p class="text-gray-300 text-center mt-2">—</p>';
                        } else {
                            day.items.forEach(function (item) {
                                const nameHtml = item.patient_id
                                    ? `<a href="/patients/${item.patient_id}" class="text-clinic hover:underline">${item.name}</a>`
                                    : `<span class="text-gray-600">${item.name}</span>`;
                                const timeHtml = apptCanEdit
                                    ? `<a href="/appointments/${item.id}/edit" class="text-gray-400 hover:text-clinic hover:underline" title="Edit this appointment">${item.time}</a>`
                                    : `<span class="text-gray-400">${item.time}</span>`;
                                html += `<div class="px-1 py-0.5 rounded bg-gray-50 truncate">${timeHtml} ${nameHtml}</div>`;
                            });
                        }

                        html += '</div></div>';
                    });

                    document.getElementById('apptWeekGrid').innerHTML = html;
                    document.getElementById('apptWeekOverlay').classList.remove('hidden');
                });
        }

        function apptCloseWeekView() {
            document.getElementById('apptWeekOverlay').classList.add('hidden');
        }

        document.addEventListener('DOMContentLoaded', apptRenderCalendar);

        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.tooltiptext').forEach(function (box) {
                const trigger = box.closest('tr');
                if (!trigger) return;

                trigger.addEventListener('mouseenter', function () {
                    const r = trigger.getBoundingClientRect();
                    box.style.top        = (r.bottom - 10) + 'px';
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
        });
    </script>
@endsection

@section('content')

<div class="flex items-center justify-between mb-1 gap-4 flex-wrap">

    <h2 class="text-xl font-bold text-gray-800">
        Appointments — {{ \Carbon\Carbon::parse($date)->format('l, j F Y') }}
    </h2>

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
                        $needsReview = $appointment->needsReview();
                        $patientLabel = $appointment->patient->name ?? $appointment->patient_name_freetext ?? '—';
                        $startFmt = \Carbon\Carbon::parse($appointment->start_time)->format('H:i');
                        $endFmt   = \Carbon\Carbon::parse($appointment->end_time)->format('H:i');
                    @endphp
                    <tr class="{{ $isOverlapping ? 'bg-orange-50 border-l-4 border-orange-400' : 'bg-white' }} hover:bg-gray-50 transition-colors align-top">
                        <td class="px-3 py-2 whitespace-nowrap text-gray-700">
                            <div class="tooltip inline-block">
                                {{ $startFmt }} – {{ $endFmt }}
                                @if ($appointment->notes)
                                    <span class="orange-dot" title="History"></span>
                                    <span class="tooltiptext">{!! nl2br(e($appointment->notes)) !!}</span>
                                @endif
                            </div>
                            @if ($isOverlapping)
                                <span class="inline-block mt-1 text-xs font-bold uppercase text-orange-700 bg-orange-100 border border-orange-300 px-1.5 py-0.5 rounded">
                                    Overlap
                                </span>
                            @endif
                        </td>
                        <td class="px-3 py-2 font-medium text-gray-800">
                            <div class="tooltip inline-block">
                            @if ($appointment->patient)
                                <a href="{{ route('patients.show', ['patient_id' => $appointment->patient->patient_id]) }}"
                                   class="text-clinic hover:underline">{{ $patientLabel }}</a>
                            @else
                                {{ $patientLabel }}
                            @endif
                            @if ($needsReview)
                                <span class="ml-1 inline-block text-xs font-bold uppercase text-purple-700 bg-purple-100 border border-purple-300 px-1.5 py-0.5 rounded">
                                    Needs Review
                                </span>
                                <span class="tooltiptext">{{ $appointment->review_remarks ?: 'Patient identity not confirmed — clarify, link to a registered patient, or amend the appointment outcome.' }}</span>
                            @elseif ($appointment->previously_unconfirmed)
                                <span class="ml-1 inline-block text-xs font-medium uppercase text-gray-500 bg-gray-100 border border-gray-300 px-1.5 py-0.5 rounded">
                                    Previously unconfirmed
                                </span>
                                @if ($appointment->review_remarks)
                                    <span class="tooltiptext">{{ $appointment->review_remarks }}</span>
                                @endif
                            @endif
                            </div>
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
                                <form action="{{ route('appointments.destroy', $appointment) }}" method="POST" class="inline"
                                      onsubmit="return confirm('Permanently delete this appointment? This cannot be undone (use Cancel instead if you just want to mark it as not happening).');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-gray-500 hover:underline text-xs font-medium ml-2">Delete</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

@endif

@endsection
