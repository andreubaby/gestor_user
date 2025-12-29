<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrabajadorDia extends Model
{
    protected $connection = 'mysql_polifonia'; // 👈 base remota
    protected $table = 'trabajadores_dias';         // 👈 tabla correcta
    protected $primaryKey = 'id';              // si tu PK es diferente, cámbialo
    public $timestamps = false;                // si no tienes created_at/updated_at

    protected $fillable = ['id','trabajador_id', 'fecha','vaction_year','tipo']; // ajusta según tus campos reales
}

