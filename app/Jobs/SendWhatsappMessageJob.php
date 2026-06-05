<?php

namespace App\Jobs;

use App\Exceptions\OpenWAException;
use App\Models\WhatsappMessage;
use App\Services\OpenWA\OpenWAClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job para enviar mensajes de WhatsApp
 *
 * Uso:
 *   SendWhatsappMessageJob::dispatch($whatsappMessage);
 *   SendWhatsappMessageJob::dispatch($chatId, $text, $userId);
 */
class SendWhatsappMessageJob implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Número de intentos antes de fallar
     */
    public int $tries = 3;

    /**
     * Segundos de espera entre intentos
     */
    public int $backoff = 60;

    /**
     * Mensaje a enviar (si se crea desde DB)
     */
    protected ?WhatsappMessage $message = null;

    /**
     * Chat ID (si se crea desde parámetros)
     */
    protected ?string $chatId = null;

    /**
     * Texto del mensaje
     */
    protected ?string $text = null;

    /**
     * User ID asociado
     */
    protected ?int $userId = null;

    /**
     * Crear un nuevo job
     *
     * @param WhatsappMessage|string $messageOrChatId
     * @param string|null $text
     * @param int|null $userId
     */
    public function __construct(
        $messageOrChatId = null,
        ?string $text = null,
        ?int $userId = null
    ) {
        if ($messageOrChatId instanceof WhatsappMessage) {
            $this->message = $messageOrChatId;
        } else {
            $this->chatId = $messageOrChatId;
            $this->text = $text;
            $this->userId = $userId;
        }
    }

    /**
     * Ejecutar el job
     */
    public function handle(OpenWAClient $client): void
    {
        if ($this->message) {
            $this->sendSavedMessage($client);
        } else {
            $this->sendNewMessage($client);
        }
    }

    /**
     * Enviar mensaje guardado en DB
     */
    protected function sendSavedMessage(OpenWAClient $client): void
    {
        try {
            $response = $client->sendTextToChatId(
                $this->message->chat_id,
                $this->message->text
            );

            $messageId = $response['id'] ?? null;
            $this->message->markAsSent($messageId);

            Log::channel('openwa')->info(
                "WhatsApp message sent successfully",
                [
                    'message_id' => $this->message->id,
                    'openwa_message_id' => $messageId,
                    'chat_id' => $this->message->chat_id,
                ]
            );
        } catch (OpenWAException $e) {
            Log::channel('openwa')->error(
                "Failed to send WhatsApp message",
                [
                    'message_id' => $this->message->id,
                    'error' => $e->getMessage(),
                    'response' => $e->getResponse(),
                ]
            );

            $this->message->markAsFailed($e->getMessage());

            // Re-lanzar para que Laravel reintente
            throw $e;
        }
    }

    /**
     * Enviar nuevo mensaje
     */
     protected function sendNewMessage(OpenWAClient $client): void
     {
         // Crear mensaje como 'pending' primero
         $message = WhatsappMessage::create([
             'user_id' => $this->userId,
             'chat_id' => $this->chatId,
             'text' => $this->text,
             'direction' => 'outbound',
             'status' => 'pending',
             'created_at' => now(),
         ]);

         try {
             $response = $client->sendTextToChatId($this->chatId, $this->text);
             $messageId = $response['id'] ?? null;

             // Marcar como enviado
             $message->update([
                 'message_id' => $messageId,
                 'status' => 'sent',
                 'payload' => $response,
                 'sent_at' => now(),
             ]);

             Log::channel('openwa')->info(
                 "WhatsApp message sent successfully",
                 [
                     'message_id' => $message->id,
                     'chat_id' => $this->chatId,
                     'openwa_message_id' => $messageId,
                 ]
             );
         } catch (OpenWAException $e) {
             // Marcar como fallido
             $message->update([
                 'status' => 'failed',
                 'error_message' => $e->getMessage(),
                 'payload' => $e->getResponse(),
             ]);

             Log::channel('openwa')->error(
                 "Failed to send WhatsApp message",
                 [
                     'message_id' => $message->id,
                     'chat_id' => $this->chatId,
                     'error' => $e->getMessage(),
                 ]
             );

             throw $e;
         }
     }

    /**
     * Manejar fallos del job
     */
    public function failed(?\Throwable $exception = null): void
    {
        Log::channel('openwa')->critical(
            "SendWhatsappMessageJob failed after {$this->tries} attempts",
            [
                'exception' => $exception?->getMessage(),
            ]
        );

        if ($this->message) {
            $this->message->markAsFailed('Job failed after ' . $this->tries . ' attempts');
        }
    }
}

