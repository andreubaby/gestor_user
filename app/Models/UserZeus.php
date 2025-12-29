<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserZeus extends Model
{
    protected $connection = 'mysql_zeus'; // Nombre de la conexión
    protected $table = 'users'; // Tabla en la base de datos
    protected $fillable = ['name', 'email', 'password']; // Ajusta según tus columnas
}
