<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WhatsappMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Controlador para recibir webhooks de OpenWA
 *
 * Ruta: POST /webhooks/openwa
 */
class OpenWAWebhookController extends Controller
{
    /**
     * Eventos procesados para idempotencia
     */
    protected static array $processedEvents = [];

    /**
     * Manejar webhook de OpenWA
     */
    public function handle(Request $request): JsonResponse
    {
        try {
            // Validar firma HMAC si está configurada
            if (!$this->validateHmac($request)) {
                Log::channel('openwa')->warning('Invalid HMAC signature for webhook');
                return response()->json(['error' => 'Invalid signature'], 401);
            }

            $event = $request->all();
            $eventId = $event['idempotencyKey'] ?? $event['deliveryId'] ?? sha1(json_encode($event));

            // Verificar idempotencia
            if ($this->isProcessed($eventId)) {
                Log::channel('openwa')->debug('Webhook already processed', ['event_id' => $eventId]);
                return response()->json(['ok' => true, 'message' => 'Already processed']);
            }

            // Marcar como procesado
            $this->markAsProcessed($eventId);

            Log::channel('openwa')->info('Webhook received', [
                'type' => $event['event'] ?? $event['type'] ?? 'unknown',
                'event_id' => $eventId,
            ]);

            // Procesar según tipo de evento
            $type = $event['event'] ?? $event['type'] ?? null;

            match ($type) {
                'message.received' => $this->handleMessageReceived($event),
                'session.status' => $this->handleSessionStatus($event),
                'session.qr' => $this->handleSessionQr($event),
                'session.disconnected' => $this->handleSessionDisconnected($event),
                'message.status' => $this->handleMessageStatus($event),
                default => $this->handleUnknownEvent($event),
            };

            return response()->json(['ok' => true]);
        } catch (\Exception $e) {
            Log::channel('openwa')->error('Webhook processing error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Processing error'], 500);
        }
    }

    /**
     * Manejar mensaje recibido
     */
    protected function handleMessageReceived(array $event): void
    {
        $message = $event['message'] ?? $event['data'] ?? [];

        if (empty($message)) {
            return;
        }

        // Extraer datos
        $messageId = $message['id'] ?? $message['_id'] ?? null;
        $chatId = $message['from'] ?? $message['chatId'] ?? null;
        $text = $message['body'] ?? $message['text'] ?? '';

        if (!$chatId) {
            Log::channel('openwa')->warning('Message received without chatId', ['message' => $message]);
            return;
        }

        // Ignorar mensajes del propietario (self)
        if (isset($message['isGroupMsg'], $message['isSentByMe'])
            && !$message['isGroupMsg']
            && $message['isSentByMe']
        ) {
            return;
        }

        // Guardar mensaje
        WhatsappMessage::create([
            'session_id' => $event['session'] ?? 'default',
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $text,
            'direction' => 'inbound',
            'status' => 'received',
            'payload' => $message,
            'received_at' => now(),
        ]);

        Log::channel('openwa')->info('Message received', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => substr($text, 0, 50),
        ]);
    }

    /**
     * Manejar cambio de estado de sesión
     */
    protected function handleSessionStatus(array $event): void
    {
        $status = $event['status'] ?? $event['state'] ?? null;
        $sessionId = $event['session'] ?? $event['sessionId'] ?? 'default';

        Log::channel('openwa')->info('Session status changed', [
            'session_id' => $sessionId,
            'status' => $status,
        ]);

        // Aquí podrías actualizar estado en caché, DB, etc.
        // Por ejemplo, guardar en caché:
        // Cache::put("openwa.session.{$sessionId}.status", $status, now()->addHours(24));
    }

    /**
     * Manejar QR de sesión
     */
    protected function handleSessionQr(array $event): void
    {
        $qr = $event['qr'] ?? null;
        $sessionId = $event['session'] ?? $event['sessionId'] ?? 'default';

        if (!$qr) {
            return;
        }

        Log::channel('openwa')->info('Session QR generated', [
            'session_id' => $sessionId,
            'qr_length' => strlen($qr),
        ]);

        // Aquí podrías emitir evento para mostrar QR en tiempo real
        // broadcast(new SessionQrGenerated($sessionId, $qr));
    }

    /**
     * Manejar desconexión de sesión
     */
    protected function handleSessionDisconnected(array $event): void
    {
        $sessionId = $event['session'] ?? $event['sessionId'] ?? 'default';

        Log::channel('openwa')->warning('Session disconnected', [
            'session_id' => $sessionId,
        ]);

        // Aquí podrías limpiar caché, notificar a admins, etc.
    }

    /**
     * Manejar cambio de estado de mensaje
     */
    protected function handleMessageStatus(array $event): void
    {
        $messageId = $event['messageId'] ?? $event['id'] ?? null;
        $status = $event['status'] ?? null;

        if (!$messageId || !$status) {
            return;
        }

        // Actualizar estado del mensaje si existe
        $message = WhatsappMessage::where('message_id', $messageId)->first();

        if ($message) {
            match ($status) {
                'delivered' => $message->markAsDelivered(),
                'read' => $message->markAsRead(),
                'failed' => $message->markAsFailed($event['error'] ?? 'Unknown error'),
                default => null,
            };

            Log::channel('openwa')->info('Message status updated', [
                'message_id' => $messageId,
                'status' => $status,
            ]);
        }
    }

    /**
     * Manejar evento desconocido
     */
    protected function handleUnknownEvent(array $event): void
    {
        Log::channel('openwa')->debug('Unknown webhook event', ['event' => $event]);
    }

    /**
     * Validar firma HMAC del webhook
     */
    protected function validateHmac(Request $request): bool
    {
        $secret = config('openwa.webhook_secret');

        if (!$secret) {
            return true; // No validar si no hay secreto configurado
        }

        $signature = $request->headers->get('X-HMAC-SHA256');

        if (!$signature) {
            return false;
        }

        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $secret, false);

        return hash_equals($signature, $expectedSignature);
    }

    /**
     * Verificar si evento ya fue procesado
     */
    protected function isProcessed(string $eventId): bool
    {
        // En producción, usar Redis o DB para persistencia
        // Redis::exists("openwa.webhook.{$eventId}")
        return isset(static::$processedEvents[$eventId]);
    }

    /**
     * Marcar evento como procesado
     */
    protected function markAsProcessed(string $eventId): void
    {
        // En producción, usar Redis o DB
        // Redis::setex("openwa.webhook.{$eventId}", 86400, true);
        static::$processedEvents[$eventId] = true;
    }
}


