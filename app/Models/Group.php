<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'active'];

    public function trabajadores()
    {
        return $this->belongsToMany(
            Trabajador::class,
            'group_trabajador'
        )->withPivot('role')->withTimestamps();
    }
}
