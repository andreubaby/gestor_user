<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Daily extends Model
{
    protected $connection = 'mysql_fichajes';
    protected $table = 'daily_summaries';

    protected $fillable = [
        'user_id',
        'work_date',
        'work_mode',
        'raw_minutes',
        'deducted_minutes',
        'worked_minutes',
        'expected_minutes',
        'excess_minutes',
        'adjust_minutes',
        'is_open',
        'computed_at',
    ];

    protected $casts = [
        'work_date' => 'date',
        'computed_at' => 'datetime',
        'is_open' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(UserFichaje::class, 'user_id');
    }
}
