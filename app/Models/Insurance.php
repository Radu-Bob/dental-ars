<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Insurance extends Model
{
    use HasFactory;

    // 💡 ESSENTIAL: Specify the correct table name
    protected $table = 'insurance'; 

    // 💡 ESSENTIAL: Specify the primary key if it's not the default 'id'
    protected $primaryKey = 'InsuranceID'; 
    
    // Disable timestamps if your table doesn't have `created_at` and `updated_at`
    public $timestamps = false; // Assuming your table lacks timestamps

    protected $casts = [
        'insurance_no'        => 'encrypted',
        'insurance_id_no'     => 'encrypted',
        'insurance_provider'  => 'encrypted',
        'insurance_remarks'   => 'encrypted',
        'invalidation_reason' => 'encrypted',
    ];

    protected $fillable = [
        'ver_patient_id',
        'ver_acc_no',
        'insurance_no',
        'insurance_id_no',
        'insurance_provider',
        'insurance_remarks',
        'invalidation_reason',
        'provider_id',
        'policy_status',
    ];

    /**
     * Get the patient that owns the insurance record.
     */
    public function patient()
    {
        return $this->belongsTo(Patient::class, 'ver_patient_id', 'patient_id');
    }
}