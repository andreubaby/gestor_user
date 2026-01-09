<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Punch extends Model
{
    protected $connection = 'mysql_fichajes';
    protected $table = 'punches';

    public $timestamps = true;

    protected $fillable = [
        'user_id','type','mood','happened_at','is_manual','note'
    ];

    protected $casts = [
        'happened_at' => 'datetime',
        'is_manual' => 'boolean',
        'mood' => 'integer',
    ];
}
