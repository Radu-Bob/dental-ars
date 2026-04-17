<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;
use Carbon\Carbon;



class Patient extends Model
{
    use HasFactory;

    protected $table = 'patients';
    protected $primaryKey = 'patient_id';

    public $timestamps = true; // <-- Corrects the model to use the table columns

    // date_of_birth is handled by EncryptedDate cast below (replaces old mutator)



    protected $fillable = [
        'name',
        'acc_no',
        // Use the actual database column name for the birth date
        'date_of_birth',
        'gender',
        'location',
        'pobox',
        'town',
        'tel',
        'active', // Assuming this is the clinic location field
        'email',
        'occupation',
        'remarks',
        'opened',
        // NOTE: The insurance fields MUST NOT be in this list!
    ];
    
    protected $casts = [
        'date_of_birth' => \App\Casts\EncryptedDate::class,
        'tel'           => 'encrypted',
        'email'         => 'encrypted',
        'location'      => 'encrypted',
        'pobox'         => 'encrypted',
        'town'          => 'encrypted',
        'occupation'    => 'encrypted',
        'remarks'       => 'encrypted',
        'active'        => 'integer',
        'opened'        => 'date:Y-m-d',
        // gender is encrypted inside the gender() Attribute below (new-style Attribute overrides $casts)
    ];

    /**
     * The "booted" method of the model.
     * Use 'creating' event to generate a UUID for acc_no before insertion.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($patient) {
            // Check if acc_no is not already set (it shouldn't be for a new record)
            if (empty($patient->acc_no)) {
                // Generates a random, unique UUID (v4) and sets it
                $patient->acc_no = (string) Str::uuid(); 
            }
        });
    }


    protected function gender(): Attribute
    {
        return Attribute::make(
            // Decrypt on read; truncate to M/F then encrypt on write
            get: fn (?string $value) => $value ? $this->safeDecrypt($value) : null,
            set: fn (?string $value) => $value
                ? \Illuminate\Support\Facades\Crypt::encryptString(strtoupper(substr($value, 0, 1)))
                : null,
        );
    }

    protected function genderLabel(): Attribute
    {
        return Attribute::make(
            // Read through the getter (which decrypts) instead of raw $attributes
            get: fn () => match ($this->gender) {
                'M' => 'Male',
                'F' => 'Female',
                default => 'Unknown',
            },
        );
    }

    private function safeDecrypt(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        try {
            return \Illuminate\Support\Facades\Crypt::decryptString($value);
        } catch (\Exception) {
            return $value; // plaintext (pre-encryption data or rollback state)
        }
    }

    /**
     * Get the insurance provider associated with the patient.
     * This is a one-to-one relationship.
     */
    public function insurance(): HasOne
    {
        return $this->hasOne(Insurance::class, 'ver_patient_id', 'patient_id');
    }

    

    /**
     * Get the clinical records for the patient.
     * This is a one-to-many relationship.
     */
    public function clinicalRecords(): HasMany
    {
        return $this->hasMany(PatientClinical::class, 'patient_id', 'patient_id');
    }

    public function getAgeAttribute(): ?int
    {
        if (empty($this->date_of_birth)) {
            return null;
        }
        try {
            return Carbon::parse($this->date_of_birth)->age;
        } catch (\Exception $e) {
            return null; // Handle invalid date format gracefully
        }
    }
}
