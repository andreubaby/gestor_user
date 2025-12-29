<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPluton extends Model
{
    protected $connection = 'mysql_pluton'; // Nombre de la conexión en config/database.php
    protected $table = 'users';

    public $timestamps = false;

    protected $fillable = [
        'nombre', 'email', 'password', 'imei', 'editar', 'editar_all', 'consultar', 'consultar_all'
    ];

    public function devices()
    {
        return $this->hasMany(UserDevice::class, 'user_id');
    }
}
