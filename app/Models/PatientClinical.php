<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientClinical extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'patients_clinical';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'patient_clinic_id';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    protected $casts = [
        'diagnostic'           => 'encrypted',
        'description'          => 'encrypted',
        'tooth'                => 'encrypted',
        'amount'               => 'encrypted',
        'paid'                 => 'encrypted',
        'balance'              => 'encrypted',
        'estimate_description' => 'encrypted',
        'estimate'             => 'encrypted',
        'estimate_cost'        => 'encrypted',
        'estimate_paid'        => 'encrypted',
        'estimate_balance'     => 'encrypted',
        'notes'                => 'encrypted',
        'remarks'              => 'encrypted',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'patient_id',
        'patient_id_ver',
        'acc_no',
        'date',
        'diagnostic',
        'description',
        'tooth',
        'amount',
        'paid',
        'balance',
        'estimate_description',
        'estimate',
        'estimate_cost',
        'estimate_paid',
        'estimate_balance',
        'notes',
        'remarks',
        'time_stamp',
        'is_insurance_claim',
        'insurance_provider_id',
    ];

    /**
     * The patient this clinical record belongs to.
     * This defines the inverse relationship.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }
}
