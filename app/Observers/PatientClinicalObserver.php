<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\PatientClinical;
use Illuminate\Support\Facades\Crypt;

class PatientClinicalObserver
{
    private const SENSITIVE = [
        'diagnostic', 'description', 'tooth',
        'amount', 'paid', 'balance',
        'estimate_description', 'estimate', 'estimate_cost',
        'estimate_paid', 'estimate_balance',
        'notes', 'remarks',
    ];

    private static array $pendingAudit = [];

    public function created(PatientClinical $record): void
    {
        $after = [];
        foreach (self::SENSITIVE as $field) {
            $after[$field] = $record->$field;
        }

        $this->writeLog('created', $record, null, $after);
    }

    public function updating(PatientClinical $record): void
    {
        $dirty  = array_intersect(array_keys($record->getDirty()), self::SENSITIVE);
        $before = [];
        $after  = [];

        foreach ($dirty as $field) {
            $before[$field] = $this->safeDecrypt($record->getOriginal($field));
            $after[$field]  = $record->$field;
        }

        self::$pendingAudit[$record->getKey()] = compact('before', 'after');
    }

    public function updated(PatientClinical $record): void
    {
        $data = self::$pendingAudit[$record->getKey()] ?? null;
        if (!$data || (empty($data['before']) && empty($data['after']))) {
            return;
        }

        $this->writeLog('updated', $record, $data['before'], $data['after']);
        unset(self::$pendingAudit[$record->getKey()]);
    }

    public function deleted(PatientClinical $record): void
    {
        $before = [];
        foreach (self::SENSITIVE as $field) {
            $before[$field] = $record->$field;
        }

        $this->writeLog('deleted', $record, $before, null);
    }

    private function writeLog(string $action, PatientClinical $record, ?array $before, ?array $after): void
    {
        AuditLog::create([
            'user_id'    => auth()->id(),
            'user_name'  => auth()->user()?->name,
            'action'     => $action,
            'model_type' => 'PatientClinical',
            'model_id'   => (string) $record->getKey(),
            'before'     => $before,
            'after'      => $after,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    private function safeDecrypt(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        try {
            return Crypt::decryptString($value);
        } catch (\Exception) {
            return $value;
        }
    }
}
