<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrabajadorPolifonia extends Model
{
    protected $connection = 'mysql_polifonia'; // 👈 base remota
    protected $table = 'trabajadores';         // 👈 tabla correcta
    protected $primaryKey = 'id';              // si tu PK es diferente, cámbialo
    public $timestamps = false;                // si no tienes created_at/updated_at

    protected $fillable = ['id','nombre', 'email','nif','empresa']; // ajusta según tus campos reales
}
