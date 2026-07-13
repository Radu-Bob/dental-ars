@extends($layout ?? 'layouts.app')

@section('title', $appointment->exists ? 'Edit Appointment' : 'New Appointment')

@section('left_content')
    <div class="bg-white p-5 rounded-2xl shadow-xl mb-6">
        <h2 class="text-base font-bold text-gray-800 mb-3">Patient Details</h2>
        <div id="apptPatientPanel" class="text-sm">
            <p class="text-gray-400 text-center">Select or search a patient above to see their details.</p>
        </div>
    </div>

    <div class="bg-white p-5 rounded-2xl shadow-xl">
        <h2 class="text-base font-bold text-gray-800 mb-3">Previous Appointments</h2>
        <div id="apptHistoryPanel" class="text-sm">
            <p class="text-gray-400 text-center">Select or search a patient above to see their appointment history.</p>
        </div>
    </div>
@endsection

@section('content')

<div class="bg-white p-8 rounded-xl shadow-lg max-w-2xl">
    <h1 class="text-2xl font-bold mb-6">{{ $appointment->exists ? 'Edit Appointment' : 'New Appointment' }}</h1>

    <form action="{{ $appointment->exists ? route('appointments.update', $appointment) : route('appointments.store') }}"
          method="POST">
        @csrf
        @if ($appointment->exists)
            @method('PUT')
        @endif

        @if ($appointment->exists && $appointment->needsReview())
            <div class="mb-4 bg-purple-50 border border-purple-300 text-purple-800 p-4 rounded-lg text-sm">
                <p class="font-semibold mb-1">This appointment needs review</p>
                <p>The patient was never linked to a registered record and the appointment has either passed or been marked completed. Please do one of the following:</p>
                <ul class="list-disc list-inside mt-1 space-y-0.5">
                    <li>Search and link the correct patient below, or</li>
                    <li><a href="{{ route('patients.register', ['name' => $appointment->patient_name_freetext]) }}" target="_blank" class="underline font-medium">Register a new patient</a> if they aren't in the system yet, then link them here, or</li>
                    <li>Amend the status (e.g. No Show / Cancelled) and add a remark below if the appointment didn't happen.</li>
                </ul>
            </div>
        @endif

        <div class="form-group mb-4">
            <label>Patient:</label>
            <input type="text" id="patient_search" autocomplete="off"
                   value="{{ $appointment->patient->name ?? '' }}"
                   placeholder="Search registered patient by name…" class="form-control">
            <input type="hidden" name="patient_id" id="patient_id" value="{{ $appointment->patient_id }}">
            <div id="patient_results" class="border border-gray-200 rounded-lg mt-1 hidden bg-white shadow-sm"></div>
        </div>

        <div class="form-group mb-4">
            <label>Or patient name (not yet registered):</label>
            <input type="text" name="patient_name_freetext" class="form-control"
                   value="{{ $appointment->patient_name_freetext }}">
            <p class="text-xs text-gray-400 mt-1">It's fine to book with just a name (or partial name) — link the registered patient record later once confirmed.</p>
        </div>

        <div class="grid grid-cols-3 gap-4 mb-4">
            <div class="form-group">
                <label>Date:</label>
                <input type="date" name="appointment_date" class="form-control" required
                       value="{{ $appointment->appointment_date ?? $date }}">
            </div>
            <div class="form-group">
                <label>Start time:</label>
                <input type="time" name="start_time" class="form-control" required
                       value="{{ $appointment->start_time ? \Carbon\Carbon::parse($appointment->start_time)->format('H:i') : '' }}">
            </div>
            <div class="form-group">
                <label>End time:</label>
                <input type="time" name="end_time" class="form-control" required
                       value="{{ $appointment->end_time ? \Carbon\Carbon::parse($appointment->end_time)->format('H:i') : '' }}">
            </div>
        </div>

        @if ($appointment->exists)
            <div class="form-group mb-4">
                <label>Status:</label>
                <select name="status" class="form-control">
                    @foreach (['scheduled', 'completed', 'cancelled', 'no_show'] as $status)
                        <option value="{{ $status }}" {{ $appointment->status === $status ? 'selected' : '' }}>
                            {{ ucfirst(str_replace('_', ' ', $status)) }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endif

        <div class="form-group mb-4">
            <label>Reason:</label>
            <textarea name="reason" rows="2" class="form-control">{{ $appointment->reason }}</textarea>
        </div>

        <div class="form-group mb-4">
            <label>Notes:</label>
            <textarea name="notes" rows="3" class="form-control">{{ $appointment->notes }}</textarea>
        </div>

        @if ($appointment->exists)
            <div class="form-group mb-6">
                <label>Review / clarification remarks <span class="text-gray-400 text-xs">(visible on hover in the day list)</span>:</label>
                <textarea name="review_remarks" rows="2" class="form-control" placeholder="e.g. Could not confirm last name, called back twice, no answer.">{{ $appointment->review_remarks }}</textarea>
            </div>
        @endif

        <div class="flex gap-3">
            <button type="submit" class="btn-primary flex-1 py-3 font-bold uppercase tracking-wider">
                {{ $appointment->exists ? 'Save Changes' : 'Book Appointment' }}
            </button>
            <a href="{{ route('appointments.index', ['date' => $appointment->appointment_date ?? $date]) }}"
               class="flex-1 text-center bg-gray-200 text-gray-700 font-semibold py-3 rounded-lg hover:bg-gray-300 transition">
                Cancel
            </a>
        </div>
    </form>
</div>

<script>
function apptLoadPatientDetails(patientId) {
    const panel = document.getElementById('apptPatientPanel');
    if (!patientId) {
        panel.innerHTML = '<p class="text-gray-400 text-center">Select or search a patient above to see their details.</p>';
        return;
    }

    panel.innerHTML = '<p class="text-gray-400 text-center">Loading…</p>';

    fetch('{{ route("appointments.patient_summary") }}?id=' + patientId, {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
        .then(r => r.ok ? r.json() : Promise.reject())
        .then(d => {
            const fields = [
                { label: 'Acc. No.',    value: d.acc_no },
                { label: 'Clinic',      value: d.clinic },
                { label: 'Telephone',   value: d.tel },
                { label: 'Location',    value: d.location },
                { label: 'Town',        value: d.town },
                { label: 'Last visit',  value: d.last_visit },
                { label: 'Insurance',   value: d.insurance_provider },
                { label: 'Policy status', value: d.policy_status },
            ];

            let html = `<p class="font-semibold text-gray-800 mb-2">${d.name}</p>`;
            html += '<div class="space-y-1 mb-3">';
            fields.forEach(function (f) {
                if (!f.value) return;
                html += `<div class="flex justify-between text-xs">
                    <span class="text-gray-400">${f.label}</span>
                    <span class="text-gray-700 text-right">${f.value}</span>
                </div>`;
            });
            html += '</div>';
            html += `<a href="/patients/${patientId}" target="_blank"
                        class="block text-center text-sm font-medium px-3 py-2 rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 shadow-sm transition">
                        Open Clinical Record →
                      </a>`;
            panel.innerHTML = html;
        })
        .catch(() => {
            panel.innerHTML = '<p class="text-red-500 text-sm text-center">Could not load patient details.</p>';
        });
}

function apptLoadHistory(patientId) {
    const panel = document.getElementById('apptHistoryPanel');
    if (!patientId) {
        panel.innerHTML = '<p class="text-gray-400 text-center">Select or search a patient above to see their appointment history.</p>';
        return;
    }

    panel.innerHTML = '<p class="text-gray-400 text-center">Loading…</p>';

    fetch('{{ route("appointments.patient_history") }}?patient_id=' + patientId)
        .then(r => r.json())
        .then(data => {
            if (!data.items.length) {
                panel.innerHTML = '<p class="text-gray-400 text-center">No previous appointments found.</p>';
                return;
            }

            let html = '<ul class="space-y-2 max-h-96 overflow-y-auto">';
            data.items.forEach(function (item) {
                html += `<li class="border-b border-gray-100 pb-1.5">
                    <div class="flex justify-between text-gray-700">
                        <span>${item.date} ${item.time}</span>
                        <span class="text-xs text-gray-400">${item.status}</span>
                    </div>
                    ${item.reason ? `<div class="text-xs text-gray-500 truncate">${item.reason}</div>` : ''}
                </li>`;
            });
            html += '</ul>';
            panel.innerHTML = html;
        });
}

document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('patient_search');
    const hidden = document.getElementById('patient_id');
    const results = document.getElementById('patient_results');
    let timer = null;

    if (hidden.value) {
        apptLoadPatientDetails(hidden.value);
        apptLoadHistory(hidden.value);
    }

    input.addEventListener('input', function () {
        clearTimeout(timer);
        const q = input.value.trim();
        hidden.value = '';
        if (q.length < 2) {
            results.classList.add('hidden');
            results.innerHTML = '';
            return;
        }
        timer = setTimeout(function () {
            fetch('{{ route('appointments.patient_search') }}?q=' + encodeURIComponent(q))
                .then(r => r.json())
                .then(data => {
                    results.innerHTML = '';
                    if (!data.length) {
                        results.classList.add('hidden');
                        return;
                    }
                    data.forEach(function (p) {
                        const item = document.createElement('div');
                        item.className = 'px-3 py-2 text-sm hover:bg-gray-100 cursor-pointer';
                        item.textContent = p.name + ' (#' + p.patient_id + ')';
                        item.addEventListener('click', function () {
                            input.value = p.name;
                            hidden.value = p.patient_id;
                            results.classList.add('hidden');
                            apptLoadPatientDetails(p.patient_id);
                            apptLoadHistory(p.patient_id);
                        });
                        results.appendChild(item);
                    });
                    results.classList.remove('hidden');
                });
        }, 250);
    });
});
</script>

@endsection
