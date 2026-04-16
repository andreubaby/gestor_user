<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimeguardTimeEntry extends Model
{
    protected $table = 'timeguard_time_entries';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'worker_id', 'date', 'hours_brutas',
        'lunch_discount', 'is_lunch_manual', 'is_free_day', 'notes',
    ];

    protected $casts = [
        'is_lunch_manual' => 'boolean',
        'is_free_day'     => 'boolean',
        'hours_brutas'    => 'integer',
        'lunch_discount'  => 'integer',
    ];

    public function logs()
    {
        return $this->hasMany(TimeguardAuditLog::class, 'entry_id');
    }
}

