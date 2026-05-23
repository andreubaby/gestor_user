<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappGroupMember extends Model
{
    protected $table = 'whatsapp_group_members';

    protected $fillable = [
        'group_id',
        'trabajador_id',
        'phone',
        'chat_id',
    ];

    /**
     * Grupo al que pertenece
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(WhatsappGroup::class, 'group_id');
    }
}

