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
        $date = $request->get('date', today(config('app.clinic_timezone'))->format('Y-m-d'));
        $month = Carbon::parse($date);

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

        $monthDatesWithAppointments = Appointment::where('status', '!=', 'cancelled')
            ->whereBetween('appointment_date', [$month->copy()->startOfMonth()->format('Y-m-d'), $month->copy()->endOfMonth()->format('Y-m-d')])
            ->pluck('appointment_date')
            ->map(fn ($d) => Carbon::parse($d)->format('Y-m-d'))
            ->unique()
            ->values();

        $quickDates = [
            'Yesterday' => today(config('app.clinic_timezone'))->subDay(),
            'Today'     => today(config('app.clinic_timezone')),
            'Tomorrow'  => today(config('app.clinic_timezone'))->addDay(),
        ];
        $quickCounts = [];
        foreach ($quickDates as $label => $quickDate) {
            $list = Appointment::with('patient')
                ->where('appointment_date', $quickDate->format('Y-m-d'))
                ->where('status', '!=', 'cancelled')
                ->orderBy('start_time')
                ->get();

            $quickCounts[$label] = [
                'date'  => $quickDate->format('Y-m-d'),
                'count' => $list->count(),
                'list'  => $list,
            ];
        }

        return view('appointments.index', compact('appointments', 'date', 'overlapIds', 'monthDatesWithAppointments', 'quickCounts'));
    }

    public function weekSummary(Request $request)
    {
        $date = $request->get('date', today(config('app.clinic_timezone'))->format('Y-m-d'));
        $weekStart = Carbon::parse($date)->startOfWeek(Carbon::MONDAY);

        $appointments = Appointment::with('patient')
            ->whereBetween('appointment_date', [$weekStart->format('Y-m-d'), $weekStart->copy()->addDays(6)->format('Y-m-d')])
            ->where('status', '!=', 'cancelled')
            ->orderBy('start_time')
            ->get()
            ->groupBy(fn ($appt) => Carbon::parse($appt->appointment_date)->format('Y-m-d'));

        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $day = $weekStart->copy()->addDays($i);
            $dayKey = $day->format('Y-m-d');

            $days[] = [
                'date'  => $dayKey,
                'label' => $day->format('D j/n'),
                'items' => ($appointments->get($dayKey) ?? collect())->map(fn ($appt) => [
                    'id'         => $appt->id,
                    'time'       => Carbon::parse($appt->start_time)->format('H:i'),
                    'patient_id' => $appt->patient->patient_id ?? null,
                    'name'       => $appt->patient->name ?? $appt->patient_name_freetext ?? '—',
                ])->values(),
            ];
        }

        return response()->json([
            'week_label' => $weekStart->format('j M') . ' – ' . $weekStart->copy()->addDays(6)->format('j M Y'),
            'days'       => $days,
        ]);
    }

    public function patientHistory(Request $request)
    {
        $patientId = $request->get('patient_id');

        if (! $patientId) {
            return response()->json(['items' => []]);
        }

        $items = Appointment::where('patient_id', $patientId)
            ->where('appointment_date', '<=', today(config('app.clinic_timezone'))->format('Y-m-d'))
            ->orderByDesc('appointment_date')
            ->orderByDesc('start_time')
            ->limit(15)
            ->get()
            ->map(fn ($appt) => [
                'date'   => Carbon::parse($appt->appointment_date)->format('d/m/Y'),
                'time'   => Carbon::parse($appt->start_time)->format('H:i'),
                'status' => ucfirst(str_replace('_', ' ', $appt->status)),
                'reason' => $appt->reason,
            ]);

        return response()->json(['items' => $items]);
    }

    public function daySummary(Request $request)
    {
        $date = $request->get('date', today(config('app.clinic_timezone'))->format('Y-m-d'));

        $items = Appointment::with('patient')
            ->where('appointment_date', $date)
            ->where('status', '!=', 'cancelled')
            ->orderBy('start_time')
            ->get()
            ->map(fn ($appt) => [
                'time'       => Carbon::parse($appt->start_time)->format('H:i'),
                'status'     => ucfirst(str_replace('_', ' ', $appt->status)),
                'patient_id' => $appt->patient->patient_id ?? null,
                'name'       => $appt->patient->name ?? $appt->patient_name_freetext ?? '—',
            ]);

        return response()->json([
            'date'  => Carbon::parse($date)->format('l, j F Y'),
            'items' => $items,
        ]);
    }

    public function create()
    {
        $date = request('date', today(config('app.clinic_timezone'))->format('Y-m-d'));

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
            'review_remarks'         => 'nullable|string',
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
            'review_remarks'         => 'nullable|string',
        ]);

        $oldDate   = $appointment->appointment_date;
        $oldStart  = $appointment->start_time;
        $oldEnd    = $appointment->end_time;
        $oldStatus = $appointment->status;

        $dateChanged = Carbon::parse($oldDate)->format('Y-m-d') !== Carbon::parse($data['appointment_date'])->format('Y-m-d');
        $timeChanged = $this->formatTime($oldStart) !== $this->formatTime($data['start_time'])
            || $this->formatTime($oldEnd) !== $this->formatTime($data['end_time']);

        $notes = $data['notes'] ?? null;

        if ($dateChanged) {
            $notes = $this->appendChangeNote($notes, sprintf(
                'Rescheduled from %s %s-%s to %s %s-%s.',
                Carbon::parse($oldDate)->format('d/m'), $this->formatTime($oldStart), $this->formatTime($oldEnd),
                Carbon::parse($data['appointment_date'])->format('d/m'), $this->formatTime($data['start_time']), $this->formatTime($data['end_time'])
            ));
        } elseif ($timeChanged) {
            $notes = $this->appendChangeNote($notes, sprintf(
                'Time changed from %s-%s to %s-%s.',
                $this->formatTime($oldStart), $this->formatTime($oldEnd),
                $this->formatTime($data['start_time']), $this->formatTime($data['end_time'])
            ));
        }

        if ($oldStatus !== $data['status'] && $data['status'] === 'cancelled') {
            $notes = $this->appendChangeNote($notes, 'Cancelled.');
        }

        $data['notes'] = $this->withOverlapNote($notes, $data['appointment_date'], $data['start_time'], $data['end_time'], $appointment->id);

        $wasUnconfirmed = $appointment->needsReview();

        $appointment->update($data);

        if ($wasUnconfirmed && $appointment->patient_id !== null) {
            $appointment->update(['previously_unconfirmed' => true]);
        }

        return redirect()->route('appointments.index', ['date' => $data['appointment_date']])
            ->with('success', 'Appointment updated.');
    }

    public function cancel(Appointment $appointment)
    {
        $notes = $this->appendChangeNote($appointment->notes, 'Cancelled.');
        $appointment->update(['status' => 'cancelled', 'notes' => $notes]);

        return redirect()->route('appointments.index', ['date' => $appointment->appointment_date])
            ->with('success', 'Appointment cancelled.');
    }

    public function destroy(Appointment $appointment)
    {
        $date = $appointment->appointment_date;
        $appointment->delete();

        return redirect()->route('appointments.index', ['date' => $date])
            ->with('success', 'Appointment deleted.');
    }

    public function dailyNotes(Request $request)
    {
        $date = $request->get('date', today(config('app.clinic_timezone'))->format('Y-m-d'));

        // created_at is stored in true UTC; convert the clinic-local day's
        // boundaries to UTC before filtering, so the query reflects the
        // EAT calendar day rather than the UTC one.
        $dayStartUtc = Carbon::parse($date, config('app.clinic_timezone'))->startOfDay()->utc();
        $dayEndUtc   = Carbon::parse($date, config('app.clinic_timezone'))->endOfDay()->utc();

        $logs = AuditLog::where('model_type', 'Appointment')
            ->whereBetween('created_at', [$dayStartUtc, $dayEndUtc])
            ->orderBy('created_at')
            ->get();

        return view('appointments.daily_notes', compact('logs', 'date'));
    }

    public function exportIcs(Request $request)
    {
        $appointments = Appointment::with('patient')
            ->where('status', '!=', 'cancelled')
            ->whereBetween('appointment_date', [today(config('app.clinic_timezone')), today(config('app.clinic_timezone'))->addDays(30)])
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
            return "{$who} ({$this->formatTime($match->start_time)}-{$this->formatTime($match->end_time)})";
        })->implode(', ');

        $overlapNote = "Overlaps with {$descriptions}.";

        return trim(($notes ? $notes . "\n" : '') . $overlapNote);
    }

    private function formatTime(string $time): string
    {
        return Carbon::parse($time)->format('H:i');
    }

    private function appendChangeNote(?string $notes, string $line): string
    {
        $who = Auth::user()->name ?? 'System';
        $prefix = '[' . now(config('app.clinic_timezone'))->format('d/m H:i') . ' ' . $who . '] ';

        return trim(($notes ? $notes . "\n" : '') . $prefix . $line);
    }
}
