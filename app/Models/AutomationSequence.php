<?php

namespace App\Models;

use App\Services\WhatsApp\AutomaticMessageChainService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AutomationSequence extends Model
{
    protected $fillable = [
        'name',
        'description',
        'actions',
        'status',
        'is_template',
        'template_source_id',
    ];

    protected $casts = [
        'actions' => 'array',
        'is_template' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function templateSource(): BelongsTo
    {
        return $this->belongsTo(self::class, 'template_source_id');
    }

    /**
     * Get the scheduled automations for this sequence
     */
    public function scheduledAutomations(): HasMany
    {
        return $this->hasMany(ScheduledAutomation::class);
    }

    /**
     * Historial de ejecuciones de esta secuencia.
     */
    public function executionLogs(): HasMany
    {
        return $this->hasMany(AutomationSequenceExecutionLog::class)->latest('happened_at');
    }

    /**
     * Check if the sequence is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Encola la secuencia usando el servicio real de OpenWA.
     */
    public function execute(?int $userId = null): bool
    {
        try {
            if (!$this->isActive()) {
                return false;
            }

            $steps = is_array($this->actions) ? $this->actions : [];

            if (empty($steps)) {
                return false;
            }

            /** @var AutomaticMessageChainService $service */
            $service = app(AutomaticMessageChainService::class);
            $service->dispatchChain($steps, $userId, $this->name, $this->id);

            return true;
        } catch (\Exception $e) {
            \Log::error('Error executing automation sequence: ' . $e->getMessage());
            return false;
        }
    }
}


