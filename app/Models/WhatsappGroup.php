<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsappGroup extends Model
{
    protected $table = 'whatsapp_groups';

    protected $fillable = [
        'name',
        'description',
        'chat_id',
        'created_by',
        'member_count',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Usuario que creó el grupo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Miembros del grupo
     */
    public function members(): HasMany
    {
        return $this->hasMany(WhatsappGroupMember::class, 'group_id');
    }

    /**
     * Mensajes enviados al grupo
     */
    public function messages(): HasMany
    {
        return $this->hasMany(WhatsappMessage::class, 'chat_id', 'chat_id');
    }
}

