<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fichar extends Model
{
    protected $connection = 'mysql_trabajadores';
    protected $table = 'fichar';
    public $timestamps = false; // si no tienes created_at/updated_at como timestamps de Eloquent

    protected $fillable = [
        'user_id',
        'bienestar',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'fecha_hora' => 'datetime',
        'fecha'      => 'date',
    ];
}
