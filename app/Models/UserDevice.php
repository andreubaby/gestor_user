<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserDevice extends Model
{
    protected $connection = 'mysql_pluton';
    protected $table = 'users_devices';

    public $timestamps = false;

    protected $fillable = ['user_id', 'device'];

    public function user()
    {
        return $this->belongsTo(UserPluton::class, 'user_id');
    }
}
