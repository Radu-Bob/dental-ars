<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\Insurance;
use Illuminate\Support\Facades\Crypt;

class InsuranceObserver
{
    private const SENSITIVE = [
        'insurance_no', 'insurance_id_no', 'insurance_provider',
        'insurance_remarks', 'invalidation_reason',
    ];

    private static array $pendingAudit = [];

    public function created(Insurance $insurance): void
    {
        $after = [];
        foreach (self::SENSITIVE as $field) {
            $after[$field] = $insurance->$field;
        }

        $this->writeLog('created', $insurance, null, $after);
    }

    public function updating(Insurance $insurance): void
    {
        $dirty  = array_intersect(array_keys($insurance->getDirty()), self::SENSITIVE);
        $before = [];
        $after  = [];

        foreach ($dirty as $field) {
            $before[$field] = $this->safeDecrypt($insurance->getOriginal($field));
            $after[$field]  = $insurance->$field;
        }

        self::$pendingAudit[$insurance->getKey()] = compact('before', 'after');
    }

    public function updated(Insurance $insurance): void
    {
        $data = self::$pendingAudit[$insurance->getKey()] ?? null;
        if (!$data || (empty($data['before']) && empty($data['after']))) {
            return;
        }

        $this->writeLog('updated', $insurance, $data['before'], $data['after']);
        unset(self::$pendingAudit[$insurance->getKey()]);
    }

    public function deleted(Insurance $insurance): void
    {
        $before = [];
        foreach (self::SENSITIVE as $field) {
            $before[$field] = $insurance->$field;
        }

        $this->writeLog('deleted', $insurance, $before, null);
    }

    private function writeLog(string $action, Insurance $insurance, ?array $before, ?array $after): void
    {
        AuditLog::create([
            'user_id'    => auth()->id(),
            'user_name'  => auth()->user()?->name,
            'action'     => $action,
            'model_type' => 'Insurance',
            'model_id'   => (string) $insurance->getKey(),
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
