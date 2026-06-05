<?php

namespace App\Console\Commands;

use App\Services\MissingPunchReminderService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendMissingPunchReminders extends Command
{
    protected $signature = 'app:send-missing-punch-reminders {--date=}';

    protected $description = 'Envia un mensaje automatico por WhatsApp a quien no ficho el dia indicado (por defecto: ayer).';

    public function handle(MissingPunchReminderService $service): int
    {
        try {
            $dateOption = $this->option('date');
            $targetDate = null;

            if (is_string($dateOption) && trim($dateOption) !== '') {
                try {
                    $targetDate = Carbon::createFromFormat('Y-m-d', trim($dateOption))->startOfDay();
                } catch (\Throwable $e) {
                    $this->error('Formato de --date invalido. Usa YYYY-MM-DD.');
                    return self::FAILURE;
                }
            }

            Log::channel('openwa')->info('Missing punch reminder command started', [
                'target_date' => $targetDate ? $targetDate->toDateString() : null,
            ]);

            $result = $service->sendForDate($targetDate);

            $this->info('Recordatorios de fichaje procesados.');
            $this->line('Fecha: ' . ($result['date'] ?? '-'));
            $this->line('Estado: ' . ($result['status'] ?? '-'));

            if (!empty($result['reason'])) {
                $this->line('Motivo: ' . $result['reason']);
            }

            if (array_key_exists('total_candidates', $result)) {
                $this->line('Candidatos: ' . (int) $result['total_candidates']);
                $this->line('Enviados: ' . (int) ($result['sent'] ?? 0));
                $this->line('Sin telefono: ' . (int) ($result['skipped_no_phone'] ?? 0));
                $this->line('Duplicados: ' . (int) ($result['skipped_duplicate'] ?? 0));
            }

            Log::channel('openwa')->info('Missing punch reminder command finished', [
                'date' => $result['date'] ?? null,
                'status' => $result['status'] ?? null,
                'reason' => $result['reason'] ?? null,
                'total_candidates' => $result['total_candidates'] ?? null,
                'sent' => $result['sent'] ?? null,
                'skipped_no_phone' => $result['skipped_no_phone'] ?? null,
                'skipped_duplicate' => $result['skipped_duplicate'] ?? null,
            ]);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            Log::channel('openwa')->error('Missing punch reminder command failed', [
                'error' => $e->getMessage(),
            ]);

            $this->error('Error al procesar recordatorios: ' . $e->getMessage());

            return self::FAILURE;
        }
    }
}

