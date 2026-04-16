<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimeguardCompensation extends Model
{
    protected $table = 'timeguard_compensations';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['id', 'worker_id', 'date', 'type', 'minutes', 'notes'];

    protected $casts = [
        'minutes' => 'integer',
    ];
}

