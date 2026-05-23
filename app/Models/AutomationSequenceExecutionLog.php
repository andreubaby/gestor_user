<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationSequenceExecutionLog extends Model
{
    protected $fillable = [
        'automation_sequence_id',
        'scheduled_automation_id',
        'step_number',
        'execution_key',
        'status',
        'target_type',
        'target_label',
        'message',
        'details',
        'happened_at',
    ];

    protected $casts = [
        'details' => 'array',
        'happened_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function automationSequence(): BelongsTo
    {
        return $this->belongsTo(AutomationSequence::class);
    }

    public function scheduledAutomation(): BelongsTo
    {
        return $this->belongsTo(ScheduledAutomation::class);
    }
}

