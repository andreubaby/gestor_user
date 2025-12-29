<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserBuscador extends Model
{
    protected $connection = 'mysql_buscador'; // Nombre de la conexión
    protected $table = 'users'; // Tabla en la base de datos
    protected $fillable = ['name', 'email', 'password']; // Ajusta según tus columnas
}
