<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsuarioPolifonia extends Model
{
    protected $connection = 'mysql_polifonia'; // 👈 importante
    protected $table = 'users'; // o el nombre real de la tabla
    protected $fillable = ['nombre', 'email', 'password'];

    public function getPasswordAttribute($value)
    {
        return Crypt::decryptString($value);
    }
}
