<?php

namespace App\Jobs;

use App\Exceptions\OpenWAException;
use App\Models\WhatsappMessage;
use App\Services\OpenWA\OpenWAClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendWhatsappFileJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public string $chatId,
        public string $fileUrl,
        public ?string $caption = null,
        public ?string $filename = null,
        public ?int $userId = null
    ) {
    }

    public function handle(OpenWAClient $client): void
    {
        $message = WhatsappMessage::create([
            'user_id' => $this->userId,
            'chat_id' => $this->chatId,
            'text' => $this->caption ?: '[Archivo enviado]',
            'direction' => 'outbound',
            'status' => 'pending',
            'payload' => [
                'type' => 'file',
                'file_url' => $this->fileUrl,
                'filename' => $this->filename,
            ],
            'created_at' => now(),
        ]);

        try {
            $response = $client->sendFileToChatId($this->chatId, $this->fileUrl, $this->caption, $this->filename);
            $messageId = $response['id'] ?? null;

            $message->update([
                'message_id' => $messageId,
                'status' => 'sent',
                'payload' => array_merge($message->payload ?? [], ['response' => $response]),
                'sent_at' => now(),
            ]);

            Log::channel('openwa')->info('WhatsApp file sent successfully', [
                'message_id' => $message->id,
                'chat_id' => $this->chatId,
                'file_url' => $this->fileUrl,
            ]);
        } catch (OpenWAException $e) {
            $message->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'payload' => array_merge($message->payload ?? [], ['error' => $e->getResponse()]),
            ]);

            Log::channel('openwa')->error('Failed to send WhatsApp file', [
                'message_id' => $message->id,
                'chat_id' => $this->chatId,
                'file_url' => $this->fileUrl,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}

