@extends('layouts.app')

@section('title', $appointment->exists ? 'Edit Appointment' : 'New Appointment')

@section('content')

<div class="bg-white p-8 rounded-xl shadow-lg max-w-2xl">
    <h1 class="text-2xl font-bold mb-6">{{ $appointment->exists ? 'Edit Appointment' : 'New Appointment' }}</h1>

    <form action="{{ $appointment->exists ? route('appointments.update', $appointment) : route('appointments.store') }}"
          method="POST">
        @csrf
        @if ($appointment->exists)
            @method('PUT')
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
                       value="{{ $appointment->start_time }}">
            </div>
            <div class="form-group">
                <label>End time:</label>
                <input type="time" name="end_time" class="form-control" required
                       value="{{ $appointment->end_time }}">
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

        <div class="form-group mb-6">
            <label>Notes:</label>
            <textarea name="notes" rows="3" class="form-control">{{ $appointment->notes }}</textarea>
        </div>

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
document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('patient_search');
    const hidden = document.getElementById('patient_id');
    const results = document.getElementById('patient_results');
    let timer = null;

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
