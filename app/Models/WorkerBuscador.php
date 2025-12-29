<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkerBuscador extends Model
{
    protected $connection = 'mysql_buscador';
    protected $table = 'workers';
    protected $fillable = ['name', 'email', 'password']; // Ajusta si tienes más campos
}
