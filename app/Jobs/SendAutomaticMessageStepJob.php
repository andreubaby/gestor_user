<?php

namespace App\Jobs;

use App\Models\TrabajadorPolifonia;
use App\Models\WhatsappGroup;
use App\Models\AutomationSequenceExecutionLog;
use App\Services\WhatsApp\WhatsappNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SendAutomaticMessageStepJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    /**
     * @var array<int, array<string, string>>
     */
    protected array $attachmentErrors = [];

    public function __construct(
        public array $step,
        public ?int $userId = null,
        public ?string $automationName = null,
        public ?int $stepNumber = null,
        public ?string $executionKey = null,
        public ?int $automationSequenceId = null
    ) {
    }

    public function handle(WhatsappNotificationService $service): void
    {
        if ($this->executionKey) {
            $lockKey = 'automation-step-executed:' . md5($this->executionKey);

            if (!Cache::add($lockKey, now()->toDateTimeString(), now()->addDay())) {
                $this->recordExecutionLog('duplicate_blocked', [
                    'reason' => 'duplicate_execution_key',
                ]);

                Log::channel('openwa')->warning('Automatic message step skipped (duplicate execution key)', [
                    'automation_name' => $this->automationName,
                    'step_number' => $this->stepNumber,
                    'execution_key' => $this->executionKey,
                ]);

                return;
            }
        }

        $type = (string) ($this->step['type'] ?? '');
        $message = trim((string) ($this->step['message'] ?? ''));
        $attachments = $this->normalizeAttachments();
        $automationName = $this->automationName ?: 'Secuencia automática';
        $label = $this->resolveTargetLabel();

        if ($message === '' && empty($attachments)) {
            throw new \InvalidArgumentException('El paso no tiene mensaje ni adjunto.');
        }

        switch ($type) {
            case 'person':
                $this->sendToPerson($service, $message, $attachments);
                break;
            case 'local_group':
                $this->sendToLocalGroup($service, $message, $attachments);
                break;
            case 'openwa_group':
                $this->sendToOpenwaGroup($service, $message, $attachments);
                break;
            default:
                $this->recordExecutionLog('failed', [
                    'error' => 'invalid_target_type',
                ]);
                throw new \InvalidArgumentException('Tipo de destino no válido en la secuencia automática.');
        }

        $executionDetails = [];
        if (!empty($this->attachmentErrors)) {
            $executionDetails['attachment_errors'] = $this->attachmentErrors;
        }

        $this->recordExecutionLog('executed', $executionDetails);

        Log::channel('openwa')->info('Automatic message step queued', [
            'automation_name' => $automationName,
            'step_number' => $this->stepNumber,
            'target_type' => $type,
            'target_label' => $label,
            'delay_minutes' => (int) ($this->step['delay_minutes'] ?? 0),
            'user_id' => $this->userId,
        ]);
    }

    protected function sendToPerson(WhatsappNotificationService $service, string $message, array $attachments = []): void
    {
        $mode = (string) ($this->step['person_mode'] ?? 'phone');

        if ($mode === 'worker') {
            $trabajadorId = (int) ($this->step['trabajador_id'] ?? 0);
            $trabajador = TrabajadorPolifonia::query()->whereKey($trabajadorId)->first();

            if (!$trabajador || empty($trabajador->tfno)) {
                throw new \InvalidArgumentException('No se pudo resolver el trabajador seleccionado o no tiene teléfono.');
            }

            if (!empty($attachments)) {
                $this->sendAttachmentsWithFallback(
                    attachments: $attachments,
                    message: $message,
                    targetType: 'person',
                    targetLabel: (string) $trabajador->tfno,
                    sendFile: function (array $attachment, int $index) use ($service, $trabajador, $message): void {
                        $service->sendFileToPhone(
                            (string) $trabajador->tfno,
                            $attachment['url'],
                            ($message !== '' && $index === 0) ? $message : null,
                            $attachment['name'] ?: null,
                            $this->userId,
                            true
                        );
                    },
                    sendTextFallback: function () use ($service, $trabajador, $message): void {
                        $service->sendToPhone((string) $trabajador->tfno, $message, $this->userId, true);
                    }
                );
            } else {
                $service->sendToPhone((string) $trabajador->tfno, $message, $this->userId, true);
            }
            return;
        }

        $phone = trim((string) ($this->step['phone'] ?? ''));

        if ($phone === '') {
            throw new \InvalidArgumentException('Debes indicar un teléfono para este paso.');
        }

        if (!empty($attachments)) {
            $this->sendAttachmentsWithFallback(
                attachments: $attachments,
                message: $message,
                targetType: 'person',
                targetLabel: $phone,
                sendFile: function (array $attachment, int $index) use ($service, $phone, $message): void {
                    $service->sendFileToPhone(
                        $phone,
                        $attachment['url'],
                        ($message !== '' && $index === 0) ? $message : null,
                        $attachment['name'] ?: null,
                        $this->userId,
                        true
                    );
                },
                sendTextFallback: function () use ($service, $phone, $message): void {
                    $service->sendToPhone($phone, $message, $this->userId, true);
                }
            );
        } else {
            $service->sendToPhone($phone, $message, $this->userId, true);
        }
    }

    protected function sendToLocalGroup(WhatsappNotificationService $service, string $message, array $attachments = []): void
    {
        $groupId = (int) ($this->step['group_id'] ?? 0);
        $group = WhatsappGroup::query()->find($groupId);

        if (!$group) {
            throw new \InvalidArgumentException('No se encontró el grupo local seleccionado.');
        }

        if (!empty($attachments)) {
            $this->sendAttachmentsWithFallback(
                attachments: $attachments,
                message: $message,
                targetType: 'local_group',
                targetLabel: $group->name,
                sendFile: function (array $attachment, int $index) use ($service, $group, $message): void {
                    $service->sendFileToGroup(
                        $group,
                        $attachment['url'],
                        ($message !== '' && $index === 0) ? $message : null,
                        $attachment['name'] ?: null,
                        $this->userId,
                        true
                    );
                },
                sendTextFallback: function () use ($service, $group, $message): void {
                    $service->sendToGroup($group, $message, true);
                }
            );
        } else {
            $service->sendToGroup($group, $message, true);
        }
    }

    protected function sendToOpenwaGroup(WhatsappNotificationService $service, string $message, array $attachments = []): void
    {
        $chatId = trim((string) ($this->step['chat_id'] ?? ''));

        if ($chatId === '') {
            throw new \InvalidArgumentException('Debes seleccionar un chat_id de grupo OpenWA.');
        }

        if (!str_ends_with($chatId, '@g.us')) {
            throw new \InvalidArgumentException('El chat_id debe terminar en @g.us para enviar a un grupo OpenWA.');
        }

        if (!empty($attachments)) {
            $this->sendAttachmentsWithFallback(
                attachments: $attachments,
                message: $message,
                targetType: 'openwa_group',
                targetLabel: $chatId,
                sendFile: function (array $attachment, int $index) use ($service, $chatId, $message): void {
                    $service->sendFileToChatId(
                        $chatId,
                        $attachment['url'],
                        ($message !== '' && $index === 0) ? $message : null,
                        $attachment['name'] ?: null,
                        $this->userId,
                        true
                    );
                },
                sendTextFallback: function () use ($service, $chatId, $message): void {
                    $service->sendToChatId($chatId, $message, $this->userId, true);
                }
            );
        } else {
            $service->sendToChatId($chatId, $message, $this->userId, true);
        }
    }

    /**
     * Intenta enviar todos los adjuntos y, si ninguno sale, envía al menos el texto como fallback.
     *
     * @param array<int, array<string, string>> $attachments
     * @param callable(array<string, string>, int): void $sendFile
     * @param callable(): void $sendTextFallback
     */
    protected function sendAttachmentsWithFallback(
        array $attachments,
        string $message,
        string $targetType,
        string $targetLabel,
        callable $sendFile,
        callable $sendTextFallback
    ): void {
        $sentAnyAttachment = false;

        foreach ($attachments as $index => $attachment) {
            try {
                $sendFile($attachment, $index);
                $sentAnyAttachment = true;
            } catch (\Throwable $e) {
                $this->attachmentErrors[] = [
                    'url' => (string) ($attachment['url'] ?? ''),
                    'error' => $e->getMessage(),
                ];

                Log::channel('openwa')->warning('Attachment send failed, continuing with next attachment', [
                    'automation_name' => $this->automationName,
                    'step_number' => $this->stepNumber,
                    'target_type' => $targetType,
                    'target_label' => $targetLabel,
                    'attachment_url' => (string) ($attachment['url'] ?? ''),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (!$sentAnyAttachment && $message !== '') {
            $sendTextFallback();
            Log::channel('openwa')->info('Text fallback sent after attachment failures', [
                'automation_name' => $this->automationName,
                'step_number' => $this->stepNumber,
                'target_type' => $targetType,
                'target_label' => $targetLabel,
            ]);
            return;
        }

        if (!$sentAnyAttachment && $message === '') {
            throw new \RuntimeException('No se pudo enviar ningun adjunto y el paso no tiene texto de respaldo.');
        }
    }

    protected function normalizeAttachments(): array
    {
        $urls = collect($this->step['attachment_urls'] ?? [])
            ->filter(fn ($u) => is_string($u) && trim($u) !== '')
            ->map(fn ($u) => trim($u))
            ->values();

        $names = collect($this->step['attachment_names'] ?? [])->values();

        $legacyUrl = trim((string) ($this->step['attachment_url'] ?? ''));
        $legacyName = trim((string) ($this->step['attachment_name'] ?? ''));

        if ($legacyUrl !== '' && !$urls->contains($legacyUrl)) {
            $urls->push($legacyUrl);
            $names->push($legacyName);
        }

        return $urls->map(function ($url, $index) use ($names) {
            $name = $names->get($index);
            return [
                'url' => $url,
                'name' => is_string($name) ? trim($name) : '',
            ];
        })->all();
    }

    protected function resolveTargetLabel(): string
    {
        $type = (string) ($this->step['type'] ?? '');

        return match ($type) {
            'person' => $this->resolvePersonLabel(),
            'local_group' => $this->resolveLocalGroupLabel(),
            'openwa_group' => $this->resolveOpenwaGroupLabel(),
            default => 'Destino no resuelto',
        };
    }

    protected function resolvePersonLabel(): string
    {
        $mode = (string) ($this->step['person_mode'] ?? 'phone');

        if ($mode === 'worker') {
            $trabajadorId = (int) ($this->step['trabajador_id'] ?? 0);
            $trabajador = TrabajadorPolifonia::query()->whereKey($trabajadorId)->first();

            if ($trabajador) {
                return trim($trabajador->nombre . ' (' . ($trabajador->tfno ?: 'sin teléfono') . ')');
            }
        }

        $phone = trim((string) ($this->step['phone'] ?? ''));

        return $phone !== '' ? 'Teléfono ' . $phone : 'Persona';
    }

    protected function resolveLocalGroupLabel(): string
    {
        $groupId = (int) ($this->step['group_id'] ?? 0);
        $group = WhatsappGroup::query()->find($groupId);

        return $group ? $group->name : 'Grupo local';
    }

    protected function resolveOpenwaGroupLabel(): string
    {
        $chatId = trim((string) ($this->step['chat_id'] ?? ''));

        if ($chatId === '') {
            return 'Grupo OpenWA';
        }

        return $chatId;
    }

    public function failed(?\Throwable $exception = null): void
    {
        $this->recordExecutionLog('failed', [
            'error' => $exception?->getMessage(),
        ]);

        Log::channel('openwa')->critical('Automatic message step job failed', [
            'automation_name' => $this->automationName,
            'step_number' => $this->stepNumber,
            'exception' => $exception?->getMessage(),
        ]);
    }

    protected function recordExecutionLog(string $status, array $details = []): void
    {
        if (!$this->executionKey || !$this->automationSequenceId) {
            return;
        }

        AutomationSequenceExecutionLog::create([
            'automation_sequence_id' => $this->automationSequenceId,
            'step_number' => $this->stepNumber,
            'execution_key' => $this->executionKey,
            'status' => $status,
            'target_type' => (string) ($this->step['type'] ?? 'unknown'),
            'target_label' => $this->resolveTargetLabel(),
            'message' => (string) ($this->step['message'] ?? ''),
            'details' => array_filter(array_merge([
                'automation_name' => $this->automationName,
                'user_id' => $this->userId,
            ], $details)),
            'happened_at' => now(),
        ]);
    }
}

