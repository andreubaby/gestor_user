<?php

namespace App\Services\WhatsApp;

use App\Jobs\SendAutomaticMessageStepJob;
use App\Models\AutomationSequenceExecutionLog;
use App\Models\TrabajadorPolifonia;
use App\Models\WhatsappGroup;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class AutomaticMessageChainService
{
    /**
     * Encola una secuencia de mensajes con retrasos acumulados.
     *
     * @param array<int, array<string, mixed>> $steps
     * @return array<string, mixed>
     */
    public function dispatchChain(array $steps, ?int $userId = null, ?string $automationName = null, ?int $automationSequenceId = null): array
    {
        if (empty($steps)) {
            throw new \InvalidArgumentException('La secuencia debe contener al menos un paso.');
        }

        $automationName = trim((string) $automationName) ?: null;
        $totalDelaySeconds = 0;
        $scheduledSteps = [];
        $attachmentValidationCache = [];

        foreach (array_values($steps) as $index => $step) {
            $this->assertValidStep($step, $index + 1, $attachmentValidationCache);
        }

        foreach (array_values($steps) as $index => $step) {
            $stepNumber = $index + 1;
            $delayMinutes = max(0, (int) ($step['delay_minutes'] ?? 0));
            $totalDelaySeconds += $delayMinutes * 60;
            $dispatchAt = now()->addSeconds($totalDelaySeconds + $index);
            $targetLabel = $this->resolveTargetLabel($step);
            $executionKey = implode('|', [
                $automationName ?: 'automation',
                $stepNumber,
                (string) ($step['type'] ?? 'unknown'),
                $targetLabel,
                $dispatchAt->format('Y-m-d H:i'),
            ]);

            SendAutomaticMessageStepJob::dispatch($step, $userId, $automationName, $stepNumber, $executionKey, $automationSequenceId)
                ->delay($dispatchAt);

            if ($automationSequenceId) {
                AutomationSequenceExecutionLog::create([
                    'automation_sequence_id' => $automationSequenceId,
                    'step_number' => $stepNumber,
                    'execution_key' => $executionKey,
                    'status' => 'queued',
                    'target_type' => (string) ($step['type'] ?? 'unknown'),
                    'target_label' => $targetLabel,
                    'message' => (string) ($step['message'] ?? ''),
                    'details' => [
                        'dispatch_at' => $dispatchAt->format('Y-m-d H:i:s'),
                        'delay_minutes' => $delayMinutes,
                    ],
                    'happened_at' => now(),
                ]);
            }

            $scheduledSteps[] = [
                'step_number' => $stepNumber,
                'type' => (string) ($step['type'] ?? 'unknown'),
                'target_label' => $targetLabel,
                'delay_minutes' => $delayMinutes,
                'dispatch_at' => $dispatchAt->format('Y-m-d H:i:s'),
            ];
        }

        Log::channel('openwa')->info('Automatic message chain queued', [
            'automation_name' => $automationName,
            'step_count' => count($steps),
            'total_delay_seconds' => $totalDelaySeconds,
            'user_id' => $userId,
        ]);

        return [
            'automation_name' => $automationName,
            'step_count' => count($steps),
            'total_delay_minutes' => (int) ceil($totalDelaySeconds / 60),
            'scheduled_steps' => $scheduledSteps,
        ];
    }

    protected function assertValidStep(array $step, int $stepNumber, array &$attachmentValidationCache = []): void
    {
        $type = (string) ($step['type'] ?? '');

        if (!in_array($type, ['person', 'local_group', 'openwa_group'], true)) {
            throw new \InvalidArgumentException("El paso {$stepNumber} tiene un tipo de destino no válido.");
        }

        $message = trim((string) ($step['message'] ?? ''));
        $attachmentUrl = trim((string) ($step['attachment_url'] ?? ''));
        $attachmentUrls = collect($step['attachment_urls'] ?? [])
            ->filter(fn ($u) => is_string($u) && trim($u) !== '')
            ->map(fn ($u) => trim($u))
            ->values()
            ->all();

        if ($attachmentUrl !== '' && !in_array($attachmentUrl, $attachmentUrls, true)) {
            $attachmentUrls[] = $attachmentUrl;
        }

        if ($message === '' && empty($attachmentUrls)) {
            throw new \InvalidArgumentException("El paso {$stepNumber} debe tener mensaje o adjunto.");
        }

        foreach ($attachmentUrls as $url) {
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                throw new \InvalidArgumentException("El paso {$stepNumber} tiene un adjunto con URL no válida.");
            }

            if ((bool) config('openwa.attachment_validation.enabled', true)) {
                if (!array_key_exists($url, $attachmentValidationCache)) {
                    /** @var AttachmentUrlValidatorService $validator */
                    $validator = App::make(AttachmentUrlValidatorService::class);
                    $attachmentValidationCache[$url] = $validator->validate($url, $stepNumber);
                }
            }
        }

        if ($type === 'person') {
            $mode = (string) ($step['person_mode'] ?? 'phone');

            if ($mode === 'worker') {
                $trabajadorId = (int) ($step['trabajador_id'] ?? 0);
                $trabajador = TrabajadorPolifonia::query()->whereKey($trabajadorId)->first();

                if (!$trabajador || empty($trabajador->tfno)) {
                    throw new \InvalidArgumentException("El paso {$stepNumber} no puede resolverse porque el trabajador seleccionado no existe o no tiene teléfono.");
                }

                return;
            }

            $phone = trim((string) ($step['phone'] ?? ''));

            if ($phone === '') {
                throw new \InvalidArgumentException("El paso {$stepNumber} necesita un teléfono.");
            }

            return;
        }

        if ($type === 'local_group') {
            $groupId = (int) ($step['group_id'] ?? 0);

            if (!$groupId || !WhatsappGroup::query()->whereKey($groupId)->exists()) {
                throw new \InvalidArgumentException("El paso {$stepNumber} necesita un grupo local válido.");
            }

            return;
        }

        $chatId = trim((string) ($step['chat_id'] ?? ''));

        if ($chatId === '' || !str_ends_with($chatId, '@g.us')) {
            throw new \InvalidArgumentException("El paso {$stepNumber} necesita un chat_id de grupo OpenWA válido.");
        }
    }

    protected function resolveTargetLabel(array $step): string
    {
        $type = (string) ($step['type'] ?? '');

        return match ($type) {
            'person' => $this->resolvePersonLabel($step),
            'local_group' => $this->resolveLocalGroupLabel($step),
            'openwa_group' => $this->resolveOpenwaGroupLabel($step),
            default => 'Destino no resuelto',
        };
    }

    protected function resolvePersonLabel(array $step): string
    {
        $mode = (string) ($step['person_mode'] ?? 'phone');

        if ($mode === 'worker') {
            $trabajadorId = (int) ($step['trabajador_id'] ?? 0);
            $trabajador = TrabajadorPolifonia::query()->whereKey($trabajadorId)->first();

            if ($trabajador) {
                return trim($trabajador->nombre . ' (' . ($trabajador->tfno ?: 'sin teléfono') . ')');
            }
        }

        $phone = trim((string) ($step['phone'] ?? ''));

        return $phone !== '' ? 'Teléfono ' . $phone : 'Persona';
    }

    protected function resolveLocalGroupLabel(array $step): string
    {
        $groupId = (int) ($step['group_id'] ?? 0);
        $group = WhatsappGroup::query()->find($groupId);

        return $group ? $group->name : 'Grupo local';
    }

    protected function resolveOpenwaGroupLabel(array $step): string
    {
        $chatId = trim((string) ($step['chat_id'] ?? ''));

        return $chatId !== '' ? $chatId : 'Grupo OpenWA';
    }
}



