<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tacografo extends Model
{
    protected $connection = 'mysql_polifonia';
    protected $table = 'tacografo';

    public $timestamps = false;

    protected $fillable = [
        'tipo',
        'valor',
        'activo',
        'observaciones',
        'fecha',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'fecha' => 'date',
    ];
}
