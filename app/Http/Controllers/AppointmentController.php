<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class AppointmentController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->get('date', today()->format('Y-m-d'));

        $appointments = Appointment::with(['patient', 'creator'])
            ->where('appointment_date', $date)
            ->orderBy('start_time')
            ->get();

        $overlapIds = [];
        foreach ($appointments as $appointment) {
            $matches = Appointment::overlapping($date, $appointment->start_time, $appointment->end_time, $appointment->id);
            if ($matches->isNotEmpty()) {
                $overlapIds[] = $appointment->id;
            }
        }

        return view('appointments.index', compact('appointments', 'date', 'overlapIds'));
    }

    public function create()
    {
        $date = request('date', today()->format('Y-m-d'));

        return view('appointments.create', ['appointment' => new Appointment(), 'date' => $date]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'patient_id'             => 'nullable|integer|exists:patients,patient_id',
            'patient_name_freetext'  => 'nullable|string|max:255',
            'appointment_date'       => 'required|date',
            'start_time'             => 'required',
            'end_time'               => 'required|after:start_time',
            'reason'                 => 'nullable|string',
            'notes'                  => 'nullable|string',
        ]);

        $data['created_by'] = Auth::id();
        $data['notes'] = $this->withOverlapNote($data['notes'] ?? null, $data['appointment_date'], $data['start_time'], $data['end_time']);

        Appointment::create($data);

        return redirect()->route('appointments.index', ['date' => $data['appointment_date']])
            ->with('success', 'Appointment booked.');
    }

    public function edit(Appointment $appointment)
    {
        return view('appointments.create', ['appointment' => $appointment, 'date' => $appointment->appointment_date]);
    }

    public function update(Request $request, Appointment $appointment)
    {
        $data = $request->validate([
            'patient_id'             => 'nullable|integer|exists:patients,patient_id',
            'patient_name_freetext'  => 'nullable|string|max:255',
            'appointment_date'       => 'required|date',
            'start_time'             => 'required',
            'end_time'               => 'required|after:start_time',
            'status'                 => 'required|in:scheduled,completed,cancelled,no_show',
            'reason'                 => 'nullable|string',
            'notes'                  => 'nullable|string',
        ]);

        $data['notes'] = $this->withOverlapNote($data['notes'] ?? null, $data['appointment_date'], $data['start_time'], $data['end_time'], $appointment->id);

        $appointment->update($data);

        return redirect()->route('appointments.index', ['date' => $data['appointment_date']])
            ->with('success', 'Appointment updated.');
    }

    public function cancel(Appointment $appointment)
    {
        $appointment->update(['status' => 'cancelled']);

        return redirect()->route('appointments.index', ['date' => $appointment->appointment_date])
            ->with('success', 'Appointment cancelled.');
    }

    public function dailyNotes(Request $request)
    {
        $date = $request->get('date', today()->format('Y-m-d'));

        $logs = AuditLog::where('model_type', 'Appointment')
            ->whereDate('created_at', $date)
            ->orderBy('created_at')
            ->get();

        return view('appointments.daily_notes', compact('logs', 'date'));
    }

    public function exportIcs(Request $request)
    {
        $appointments = Appointment::with('patient')
            ->where('status', '!=', 'cancelled')
            ->whereBetween('appointment_date', [today(), today()->addDays(30)])
            ->orderBy('appointment_date')
            ->orderBy('start_time')
            ->get();

        $lines = ["BEGIN:VCALENDAR", "VERSION:2.0", "PRODID:-//dental-ars//Appointments//EN"];

        foreach ($appointments as $appointment) {
            $start = Carbon::parse("{$appointment->appointment_date} {$appointment->start_time}");
            $end   = Carbon::parse("{$appointment->appointment_date} {$appointment->end_time}");
            $summary = $appointment->patient->name ?? $appointment->patient_name_freetext ?? 'Appointment';

            $lines[] = "BEGIN:VEVENT";
            $lines[] = "UID:appointment-{$appointment->id}@dental-ars";
            $lines[] = "DTSTART:" . $start->format('Ymd\THis');
            $lines[] = "DTEND:" . $end->format('Ymd\THis');
            $lines[] = "SUMMARY:" . str_replace([",", ";"], ['\,', '\;'], $summary);
            if ($appointment->reason) {
                $lines[] = "DESCRIPTION:" . str_replace([",", ";", "\n"], ['\,', '\;', '\n'], $appointment->reason);
            }
            $lines[] = "END:VEVENT";
        }

        $lines[] = "END:VCALENDAR";

        return response(implode("\r\n", $lines))
            ->header('Content-Type', 'text/calendar')
            ->header('Content-Disposition', 'attachment; filename="appointments.ics"');
    }

    private function withOverlapNote(?string $notes, string $date, string $start, string $end, ?int $excludeId = null): ?string
    {
        $matches = Appointment::overlapping($date, $start, $end, $excludeId);

        if ($matches->isEmpty()) {
            return $notes;
        }

        $descriptions = $matches->map(function ($match) {
            $who = $match->patient->name ?? $match->patient_name_freetext ?? 'another appointment';
            return "{$who} ({$match->start_time}-{$match->end_time})";
        })->implode(', ');

        $overlapNote = "Overlaps with {$descriptions}.";

        return trim(($notes ? $notes . "\n" : '') . $overlapNote);
    }
}
