<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Usuario extends Model
{
    protected $fillable = ['nombre', 'email', 'password'];

    // 🔐 Encriptar al guardar
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = Crypt::encryptString($value);
    }
}
