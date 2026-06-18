<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    protected $attributes = [
        'status' => 'scheduled',
    ];

    protected $fillable = [
        'patient_id',
        'patient_name_freetext',
        'appointment_date',
        'start_time',
        'end_time',
        'status',
        'reason',
        'notes',
        'review_remarks',
        'previously_unconfirmed',
        'created_by',
    ];

    protected $casts = [
        'reason'         => 'encrypted',
        'notes'          => 'encrypted',
        'review_remarks' => 'encrypted',
    ];

    public function needsReview(): bool
    {
        if ($this->patient_id !== null || $this->status === 'cancelled') {
            return false;
        }

        return $this->status === 'completed'
            || \Carbon\Carbon::parse($this->appointment_date)->startOfDay()->lt(today(config('app.clinic_timezone')));
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function overlapping(string $date, string $start, string $end, ?int $excludeId = null)
    {
        return static::where('appointment_date', $date)
            ->where('status', '!=', 'cancelled')
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->when($excludeId, fn ($query) => $query->where('id', '!=', $excludeId))
            ->get();
    }
}
