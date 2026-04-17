<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $table = 'audit_logs';

    public $timestamps = false; // table only has created_at, no updated_at

    protected $fillable = [
        'user_id',
        'user_name',
        'action',
        'model_type',
        'model_id',
        'before',
        'after',
        'ip_address',
        'user_agent',
        'is_flagged',
        'flag_reason',
    ];

    protected $casts = [
        'before'     => 'array',
        'after'      => 'array',
        'is_flagged' => 'boolean',
    ];
}
