<?php

namespace App\Console\Commands;

use App\Models\ScheduledAutomation;
use App\Models\AutomationSequenceExecutionLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ExecuteScheduledAutomations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:execute-scheduled-automations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ejecuta las automatizaciones programadas según su horario';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🤖 Ejecutando automatizaciones programadas...');

        $scheduledAutomations = ScheduledAutomation::where('status', 'active')
            ->with('automationSequence')
            ->get();

        $executed = 0;
        $skipped = 0;

        foreach ($scheduledAutomations as $schedule) {
            // Verificar si debe ejecutarse
            if ($schedule->shouldRun()) {
                try {
                    $minuteKey = 'automation-schedule-run:' . $schedule->id . ':' . now()->format('Y-m-d H:i');

                    if (!Cache::add($minuteKey, now()->toDateTimeString(), now()->addMinutes(10))) {
                        if ($schedule->automation_sequence_id) {
                            AutomationSequenceExecutionLog::create([
                                'automation_sequence_id' => $schedule->automation_sequence_id,
                                'scheduled_automation_id' => $schedule->id,
                                'execution_key' => $minuteKey,
                                'status' => 'duplicate_blocked',
                                'target_type' => 'schedule',
                                'target_label' => $schedule->automationSequence?->name ?? 'Secuencia programada',
                                'message' => 'Bloqueado por ejecución duplicada en el mismo minuto.',
                                'details' => [
                                    'scheduled_time' => (string) $schedule->scheduled_time,
                                    'days_of_week' => $schedule->days_of_week,
                                ],
                                'happened_at' => now(),
                            ]);
                        }

                        $skipped++;
                        continue;
                    }

                    // Verificar que la secuencia exista y esté activa
                    if (!$schedule->automationSequence || !$schedule->automationSequence->isActive()) {
                        $skipped++;
                        continue;
                    }

                    // Ejecutar la secuencia
                    if ($schedule->automationSequence->execute()) {
                        $schedule->markAsExecuted();
                        $executed++;
                        $this->info("✅ Ejecutada: {$schedule->automationSequence->name} ({$schedule->scheduled_time})");
                    } else {
                        $this->warn("⚠️ Error al ejecutar: {$schedule->automationSequence->name}");
                        $skipped++;
                    }
                } catch (\Exception $e) {
                    $this->error("❌ Excepción: {$e->getMessage()}");
                    $skipped++;
                }
            }
        }

        $this->info("✅ Proceso completado. Ejecutadas: {$executed}, Omitidas: {$skipped}");

        return Command::SUCCESS;
    }
}

