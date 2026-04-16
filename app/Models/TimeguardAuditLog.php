<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimeguardAuditLog extends Model
{
    protected $table = 'timeguard_audit_logs';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'entry_id', 'timestamp', 'field',
        'old_value', 'new_value', 'user', 'reason',
    ];

    protected $casts = [
        'timestamp' => 'integer',
    ];
}

