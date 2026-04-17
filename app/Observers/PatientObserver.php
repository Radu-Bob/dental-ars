<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\Patient;
use Illuminate\Support\Facades\Crypt;

class PatientObserver
{
    private const SENSITIVE = [
        'date_of_birth', 'tel', 'email', 'gender',
        'location', 'pobox', 'town', 'occupation', 'remarks',
    ];

    /**
     * Static so the same data survives between the updating and updated event calls.
     * (Laravel resolves a fresh observer instance for every event dispatch unless
     * explicitly bound as a singleton, so an instance property would be lost.)
     */
    private static array $pendingAudit = [];

    public function created(Patient $patient): void
    {
        $after = [];
        foreach (self::SENSITIVE as $field) {
            $after[$field] = $patient->$field; // cast decrypts transparently
        }

        $this->writeLog('created', $patient, null, $after);
    }

    public function updating(Patient $patient): void
    {
        $dirty   = array_intersect(array_keys($patient->getDirty()), self::SENSITIVE);
        $before  = [];
        $after   = [];

        foreach ($dirty as $field) {
            $before[$field] = $this->safeDecrypt($patient->getOriginal($field));
            $after[$field]  = $patient->$field; // cast decrypts transparently
        }

        self::$pendingAudit[$patient->getKey()] = compact('before', 'after');
    }

    public function updated(Patient $patient): void
    {
        $data = self::$pendingAudit[$patient->getKey()] ?? null;
        if (!$data || (empty($data['before']) && empty($data['after']))) {
            return;
        }

        $this->writeLog('updated', $patient, $data['before'], $data['after']);
        unset(self::$pendingAudit[$patient->getKey()]);
    }

    public function deleted(Patient $patient): void
    {
        $before = [];
        foreach (self::SENSITIVE as $field) {
            $before[$field] = $patient->$field;
        }

        $this->writeLog('deleted', $patient, $before, null);
    }

    private function writeLog(string $action, Patient $patient, ?array $before, ?array $after): void
    {
        AuditLog::create([
            'user_id'    => auth()->id(),
            'user_name'  => auth()->user()?->name,
            'action'     => $action,
            'model_type' => 'Patient',
            'model_id'   => (string) $patient->getKey(),
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
            return $value; // plaintext (pre-encryption data)
        }
    }
}
