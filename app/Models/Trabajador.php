<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trabajador extends Model
{
    protected $connection = 'mysql_polifonia';
    protected $table = 'trabajadores';
}

