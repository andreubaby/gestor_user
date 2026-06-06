<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class ScheduledAutomation extends Model
{
    private const ALLOWED_WEEKDAYS = ['2', '3', '4', '5'];

    protected $fillable = [
        'automation_sequence_id',
        'scheduled_time',
        'days_of_week',
        'status',
        'last_executed_at',
        'next_execution_at',
    ];

    protected $casts = [
        'days_of_week' => 'array',
        'scheduled_time' => 'datetime:H:i:s',
        'last_executed_at' => 'datetime',
        'next_execution_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the automation sequence
     */
    public function automationSequence(): BelongsTo
    {
        return $this->belongsTo(AutomationSequence::class);
    }

    /**
     * Check if this scheduled task should run now
     */
    public function shouldRun(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $now = Carbon::now();
        $scheduledTime = Carbon::createFromTimeString($this->scheduledTimeString())->setDate(
            $now->year,
            $now->month,
            $now->day
        );

        $dayOfWeek = (string) $now->dayOfWeek;
        $daysOfWeek = $this->normalizedWeekdays();

        // Legacy guard: aunque exista 0/6 en BD, nunca ejecuta en fin de semana.
        $isScheduledDay = in_array($dayOfWeek, $daysOfWeek, true);

        if (!$isScheduledDay) {
            return false;
        }

        // Solo ejecuta en el minuto exacto programado para evitar duplicados.
        if ($now->format('Y-m-d H:i') !== $scheduledTime->format('Y-m-d H:i')) {
            return false;
        }

        // Evita re-ejecutar el mismo minuto si ya se disparó.
        if ($this->last_executed_at && $this->last_executed_at->format('Y-m-d H:i') === $scheduledTime->format('Y-m-d H:i')) {
            return false;
        }

        return true;
    }

    /**
     * Mark as executed
     */
    public function markAsExecuted(): void
    {
        $this->update([
            'last_executed_at' => now(),
            'next_execution_at' => $this->calculateNextExecution(),
        ]);
    }

    /**
     * Calculate the next execution time
     */
    public function calculateNextExecution(): Carbon
    {
        return $this->calculateNextExecutionFrom(now());
    }

    /**
     * Calculate next execution from a reference date-time.
     */
    public function calculateNextExecutionFrom(?Carbon $reference = null): Carbon
    {
        $reference = ($reference ?? now())->copy();
        $daysOfWeek = $this->normalizedWeekdays();

        $nextExecution = $reference->copy()->setTimeFromTimeString($this->scheduledTimeString());

        // If today's time has passed (or is exact now), move to next day first.
        if ($nextExecution->lessThanOrEqualTo($reference)) {
            $nextExecution->addDay();
        }

        // Sin dias validos en BD (legacy), cae a proximo dia laborable.
        if (empty($daysOfWeek)) {
            while (!in_array((string) $nextExecution->dayOfWeek, self::ALLOWED_WEEKDAYS, true)) {
                $nextExecution->addDay();
            }

            return $nextExecution;
        }

        // Find the next scheduled day.
        while (!in_array((string) $nextExecution->dayOfWeek, $daysOfWeek, true)) {
            $nextExecution->addDay();
        }

        return $nextExecution;
    }

    private function normalizedWeekdays(): array
    {
        return collect($this->days_of_week ?? [])
            ->map(fn ($day) => (string) $day)
            ->filter(fn ($day) => in_array($day, self::ALLOWED_WEEKDAYS, true))
            ->unique()
            ->values()
            ->all();
    }

    private function scheduledTimeString(): string
    {
        $raw = $this->getRawOriginal('scheduled_time') ?? ($this->attributes['scheduled_time'] ?? null);

        if ($raw instanceof \DateTimeInterface) {
            return $raw->format('H:i:s');
        }

        if (is_string($raw) && preg_match('/\d{2}:\d{2}(:\d{2})?/', $raw, $matches)) {
            return strlen($matches[0]) === 5 ? $matches[0] . ':00' : $matches[0];
        }

        return Carbon::parse($this->scheduled_time)->format('H:i:s');
    }

    /**
     * Effective next execution, recalculated if persisted value is stale.
     */
    public function getEffectiveNextExecutionAttribute(): ?Carbon
    {
        $next = $this->next_execution_at;

        if ($this->status !== 'active') {
            return $next;
        }

        if (!$next || $next->lt(now())) {
            return $this->calculateNextExecutionFrom(now());
        }

        return $next;
    }

    /**
     * Check if is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
