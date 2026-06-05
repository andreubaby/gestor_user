<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsappGroup;
use App\Jobs\SendWhatsappMessageJob;
use App\Jobs\SendWhatsappFileJob;
use App\Models\TrabajadorPolifonia;
use App\Models\User;
use App\Models\UserTrabajador;
use App\Models\UsuarioVinculado;
use App\Models\WhatsappMessage;
use App\Services\OpenWA\OpenWAClient;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para enviar notificaciones por WhatsApp
 *
 * Encapsula la lógica de negocio de mensajes WhatsApp
 *
 * Uso:
 *   app(WhatsappNotificationService::class)->sendWelcomeMessage($user);
 *   WhatsappNotificationService::send($user, 'Hola mundo');
 */
class WhatsappNotificationService
{
    protected OpenWAClient $client;

    public function __construct(OpenWAClient $client)
    {
        $this->client = $client;
    }

    /**
     * Enviar mensaje de bienvenida
     */
    public function sendWelcomeMessage(User $user): void
    {
        $phone = $this->resolvePhone($user);

        if (!$phone) {
            Log::channel('openwa')->warning('User has no phone number', ['user_id' => $user->id]);
            return;
        }

        $message = $this->buildWelcomeMessage($user);
        $this->sendToUser($user, $message, true, $phone);
    }

    /**
     * Enviar código OTP
     */
    public function sendOtp(User $user, string $code): void
    {
        $phone = $this->resolvePhone($user);

        if (!$phone) {
            Log::channel('openwa')->warning('User has no phone number for OTP', ['user_id' => $user->id]);
            return;
        }

        $message = "Tu código de verificación es: *{$code}*\n\nNo compartir este código con nadie.";
        $this->sendToUser($user, $message, true, $phone);
    }

    /**
     * Enviar actualización de orden
     *
     * @param User $user
     * @param array $orderData Datos de la orden (id, status, total, etc)
     */
    public function sendOrderUpdate(User $user, array $orderData): void
    {
        $phone = $this->resolvePhone($user);

        if (!$phone) {
            Log::channel('openwa')->warning('User has no phone number for order update', ['user_id' => $user->id]);
            return;
        }

        $message = $this->buildOrderUpdateMessage($orderData);
        $this->sendToUser($user, $message, true, $phone);
    }

    /**
     * Enviar mensaje genérico a usuario
     */
    public function sendToUser(User $user, string $message, bool $async = true, ?string $resolvedPhone = null): void
    {
        $phone = $resolvedPhone ?? $this->resolvePhone($user);

        if (!$phone) {
            throw new \InvalidArgumentException("User {$user->id} has no phone number (mysql_trabajadores.users.tfno / mysql_polifonia.trabajadores.tfno)");
        }

        if ($async) {
            $pendingMessage = $this->createPendingMessage(
                $this->phoneToChatId($phone),
                $message,
                $user->id
            );

            SendWhatsappMessageJob::dispatch($pendingMessage);
        } else {
            try {
                $this->client->sendText($phone, $message);
            } catch (\Exception $e) {
                Log::channel('openwa')->error('Failed to send message to user', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        }
    }

    /**
     * Enviar mensaje a número directo
     */
    public function sendToPhone(string $phone, string $message, ?int $userId = null, bool $async = true): void
    {
        if ($async) {
            $pendingMessage = $this->createPendingMessage(
                $this->phoneToChatId($phone),
                $message,
                $userId
            );

            SendWhatsappMessageJob::dispatch($pendingMessage);
        } else {
            $this->client->sendText($phone, $message);
        }
    }

    /**
     * Enviar mensaje a Chat ID directo (formato WhatsApp)
     */
    public function sendToChatId(string $chatId, string $message, ?int $userId = null, bool $async = true): void
    {
        if ($async) {
            $pendingMessage = $this->createPendingMessage($chatId, $message, $userId);

            SendWhatsappMessageJob::dispatch($pendingMessage);
        } else {
            $this->client->sendTextToChatId($chatId, $message);
        }
    }

    /**
     * Enviar archivo a chat ID.
     */
    public function sendFileToChatId(string $chatId, string $fileUrl, ?string $caption = null, ?string $filename = null, ?int $userId = null, bool $async = true): void
    {
        if ($async) {
            SendWhatsappFileJob::dispatch($chatId, $fileUrl, $caption, $filename, $userId);
        } else {
            $this->client->sendFileToChatId($chatId, $fileUrl, $caption, $filename);
        }
    }

    /**
     * Enviar archivo a teléfono.
     */
    public function sendFileToPhone(string $phone, string $fileUrl, ?string $caption = null, ?string $filename = null, ?int $userId = null, bool $async = true): void
    {
        $chatId = $this->phoneToChatId($phone);
        $this->sendFileToChatId($chatId, $fileUrl, $caption, $filename, $userId, $async);
    }

    /**
     * Enviar archivo a grupo local (miembros del grupo).
     */
    public function sendFileToGroup(WhatsappGroup $group, string $fileUrl, ?string $caption = null, ?string $filename = null, ?int $userId = null, bool $async = true): void
    {
        if (!$group || empty($group->chat_id)) {
            throw new \InvalidArgumentException('Grupo inválido o sin chat_id');
        }

        $memberCount = $group->members()->count();

        if ($memberCount === 0) {
            throw new \InvalidArgumentException('El grupo no tiene miembros');
        }

        if ($async) {
            $group->members()->each(function ($member) use ($fileUrl, $caption, $filename, $userId) {
                SendWhatsappFileJob::dispatch($member->chat_id, $fileUrl, $caption, $filename, $userId);
            });
        } else {
            $group->members()->each(function ($member) use ($fileUrl, $caption, $filename) {
                $this->client->sendFileToChatId($member->chat_id, $fileUrl, $caption, $filename);
            });
        }
    }

    /**
     * Construir mensaje de bienvenida
     */
    protected function buildWelcomeMessage(User $user): string
    {
        return "¡Hola {$user->name}! 🎉\n\n" .
            "Bienvenido a nuestro servicio.\n\n" .
            "Estamos aquí para ayudarte. ¿En qué podemos asistirte?";
    }

    /**
     * Construir mensaje de actualización de orden
     */
    protected function buildOrderUpdateMessage(array $orderData): string
    {
        $orderId = $orderData['id'] ?? '???';
        $status = $orderData['status'] ?? 'desconocido';
        $total = $orderData['total'] ?? 0;

        return "📦 *Actualización de Orden*\n\n" .
            "ID: {$orderId}\n" .
            "Estado: {$status}\n" .
            "Total: $" . $total . "\n\n" .
            "Gracias por tu compra.";
    }

    /**
     * Verificar si usuario tiene teléfono
     */
    protected function hasPhone(User $user): bool
    {
        return !empty($this->resolvePhone($user));
    }

    /**
     * Resolver teléfono para WhatsApp desde mysql_trabajadores.users.tfno
     * y, si no existe, desde mysql_polifonia.trabajadores.tfno.
     */
    protected function resolvePhone(User $user): ?string
    {
        $trabajadorId = UsuarioVinculado::query()
            ->where('usuario_id', $user->id)
            ->value('trabajador_id');

        $tfno = null;

        if ($trabajadorId) {
            $tfno = UserTrabajador::query()
                ->whereKey($trabajadorId)
                ->value('tfno');

            if (empty($tfno)) {
                $tfno = TrabajadorPolifonia::on('mysql_polifonia')
                    ->whereKey($trabajadorId)
                    ->value('tfno');
            }
        }

        return !empty($tfno) ? (string) $tfno : null;
    }

    /**
     * Convertir teléfono a Chat ID
     */
    protected function phoneToChatId(string $phone): string
    {
        // Remover caracteres especiales
        $phone = preg_replace('/[^\d]/', '', $phone);

        // Si comienza con 0, remover
        if (str_starts_with($phone, '0')) {
            $phone = substr($phone, 1);
        }

        // Agregar código de país si no lo tiene
        if (strlen($phone) <= 9) {
            $countryCode = config('openwa.default_country_code', '34');
            $phone = $countryCode . $phone;
        }

        return "{$phone}@c.us";
    }

    /**
     * Enviar mensaje a grupo de WhatsApp
     *
     * @param WhatsappGroup $group Grupo de WhatsApp
     * @param string $message Mensaje a enviar
     * @param bool $async Envío asincrónico (recomendado)
     */
    public function sendToGroup(WhatsappGroup $group, string $message, bool $async = true): void
    {
        if (!$group || empty($group->chat_id)) {
            throw new \InvalidArgumentException('Grupo inválido o sin chat_id');
        }

        $memberCount = $group->members()->count();

        if ($memberCount === 0) {
            throw new \InvalidArgumentException('El grupo no tiene miembros');
        }

        if ($async) {
            // Encolar un job por cada miembro
            $group->members()->each(function ($member) use ($message) {
                $pendingMessage = $this->createPendingMessage($member->chat_id, $message, null);

                SendWhatsappMessageJob::dispatch($pendingMessage);
            });

            Log::channel('openwa')->info('Group message queued', [
                'group_id' => $group->id,
                'group_name' => $group->name,
                'member_count' => $memberCount,
            ]);
        } else {
            // Envío sincrónico (menos recomendado para grupos grandes)
            $group->members()->each(function ($member) use ($message) {
                try {
                    $this->client->sendTextToChatId($member->chat_id, $message);
                } catch (\Exception $e) {
                    Log::channel('openwa')->error('Failed to send group message', [
                        'group_id' => $member->group_id,
                        'member_chat_id' => $member->chat_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            });
        }
    }

    protected function createPendingMessage(string $chatId, string $message, ?int $userId = null): WhatsappMessage
    {
        return WhatsappMessage::create([
            'user_id' => $userId,
            'session_id' => (string) config('openwa.session_id', 'default'),
            'chat_id' => $chatId,
            'text' => $message,
            'direction' => 'outbound',
            'status' => 'pending',
        ]);
    }
}




