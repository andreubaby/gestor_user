<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class UserTrabajador extends Authenticatable
{
    use Notifiable;

    protected $connection = 'mysql_trabajadores'; // BD remota
    protected $table = 'users';
    protected $primaryKey = 'id';

    // 👉 SÍ tienes timestamps según la captura
    public $timestamps = true;

    protected $fillable = [
        'name',
        'email',
        'password',
        'imei',
        'tfno',
        'horario',
        'tipo',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
}
