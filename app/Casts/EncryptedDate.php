<?php

namespace App\Casts;

use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Facades\Crypt;

class EncryptedDate implements CastsAttributes
{
    /**
     * Decrypt and return the stored Y-m-d date string.
     */
    public function get($model, string $key, $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Illuminate\Contracts\Encryption\DecryptException) {
            // Pre-encryption plaintext — return as-is so rollback is safe
            return $value;
        }
    }

    /**
     * Convert d/m/Y format if needed, then encrypt before storing.
     */
    public function set($model, string $key, $value, array $attributes): ?string
    {
        if (!$value) {
            return null;
        }

        // Flatpickr submits d/m/Y — convert to Y-m-d for storage
        if (Carbon::hasFormat($value, 'd/m/Y')) {
            $value = Carbon::createFromFormat('d/m/Y', $value)->format('Y-m-d');
        }

        return Crypt::encryptString($value);
    }
}
