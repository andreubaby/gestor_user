<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimeguardWorker extends Model
{
    protected $table = 'timeguard_workers';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['id', 'name', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function entries()
    {
        return $this->hasMany(TimeguardTimeEntry::class, 'worker_id');
    }

    public function compensations()
    {
        return $this->hasMany(TimeguardCompensation::class, 'worker_id');
    }
}

