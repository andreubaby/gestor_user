<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class UserFichaje extends Authenticatable
{
    use Notifiable;

    /**
     * Conexión a la nueva base de datos
     */
    protected $connection = 'mysql_fichajes'; // 👈 AJUSTA EL NOMBRE

    /**
     * Tabla
     */
    protected $table = 'users'; // 👈 AJUSTA SI ES DISTINTO

    /**
     * Campos asignables
     */
    protected $fillable = [
        'name',
        'email',
        'work_mode',
        'password',
    ];

    /**
     * Campos ocultos al serializar
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Casts
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
}
