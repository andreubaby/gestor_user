<?php

/**
 * EJEMPLOS DE USO DE LA INTEGRACIÓN OPENWA
 *
 * Este archivo contiene ejemplos prácticos de cómo usar OpenWA
 * en diferentes contextos de tu aplicación Laravel.
 *
 * Ver OPENWA_README.md para documentación completa.
 */

// ─────────────────────────────────────────────────────────────────────────────
// 1. ENVIAR MENSAJE SIMPLE DESDE CONTROLADOR
// ─────────────────────────────────────────────────────────────────────────────

namespace App\Http\Controllers;

use App\Services\WhatsApp\WhatsappNotificationService;
use App\Jobs\SendWhatsappMessageJob;

class OrderController extends Controller
{
    public function confirmOrder(Request $request, WhatsappNotificationService $notifier)
    {
        $order = Order::create($request->validated());
        $user = $request->user();

        // Enviar notificación asincrónica (recomendado)
        $notifier->sendOrderUpdate($user, [
            'id' => $order->id,
            'status' => 'Confirmada',
            'total' => $order->total,
        ]);

        // O sincrónica (bloquea la respuesta)
        // $notifier->sendOrderUpdate($user, [...], async: false);

        return response()->json(['ok' => true, 'order_id' => $order->id]);
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// 2. ENVIAR OTP EN REGISTRO
// ─────────────────────────────────────────────────────────────────────────────

class AuthController extends Controller
{
    public function register(Request $request, WhatsappNotificationService $notifier)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => hash('bcrypt', $request->password),
        ]);

        // Generar código OTP
        $code = random_int(100000, 999999);

        // Guardar en caché por 10 minutos
        cache()->put("otp.{$user->id}", $code, now()->addMinutes(10));

        // Enviar por WhatsApp
        $notifier->sendOtp($user, (string) $code);

        return response()->json([
            'message' => 'Código OTP enviado a WhatsApp',
            'user_id' => $user->id,
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $user = User::findOrFail($request->user_id);
        $cachedCode = cache()->get("otp.{$user->id}");

        if ($cachedCode && $cachedCode == $request->code) {
            cache()->forget("otp.{$user->id}");
            $user->update(['phone_verified_at' => now()]);

            return response()->json(['message' => 'Teléfono verificado']);
        }

        return response()->json(['error' => 'Código inválido'], 422);
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// 3. ENVIAR MEDIANTE MAIL (EN MAILABLE)
// ─────────────────────────────────────────────────────────────────────────────

namespace App\Mail;

use App\Services\WhatsApp\WhatsappNotificationService;
use Illuminate\Mail\Mailable;

class WelcomeMail extends Mailable
{
    public function __construct(
        private $user
    ) {}

    public function build()
    {
        // Enviar email
        $email = $this->subject('Bienvenido')
            ->view('mail.welcome')
            ->with(['user' => $this->user]);

        // ADEMÁS, enviar WhatsApp
        app(WhatsappNotificationService::class)->sendWelcomeMessage($this->user);

        return $email;
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// 4. USAR JOB DIRECTAMENTE
// ─────────────────────────────────────────────────────────────────────────────

use App\Jobs\SendWhatsappMessageJob;
use App\Models\WhatsappMessage;

// Opción A: Desde teléfono
SendWhatsappMessageJob::dispatch('612345678', 'Tu pedido está en camino', $userId);

// Opción B: Desde Chat ID
SendWhatsappMessageJob::dispatch('34612345678@c.us', 'Tu pedido está en camino', $userId);

// Opción C: Desde mensaje guardado en DB
$message = WhatsappMessage::create([
    'user_id' => $userId,
    'chat_id' => '34612345678@c.us',
    'text' => 'Contenido del mensaje',
    'direction' => 'outbound',
    'status' => 'pending',
]);

SendWhatsappMessageJob::dispatch($message);

// ─────────────────────────────────────────────────────────────────────────────
// 5. PROCESAR WEBHOOKS PERSONALIZADOS
// ─────────────────────────────────────────────────────────────────────────────

// Los webhooks se procesan automáticamente en OpenWAWebhookController,
// pero puedes escuchar eventos con listeners personalizados:

namespace App\Listeners;

use App\Models\WhatsappMessage;

class ProcessWhatsappMessage
{
    public function handle(WhatsappMessage $message)
    {
        // Cuando se guarde un mensaje entrante
        if ($message->direction === 'inbound') {
            // Procesar mensaje
            $this->handleIncomingMessage($message);
        }
    }

    private function handleIncomingMessage(WhatsappMessage $message)
    {
        $text = strtolower($message->text);

        // Ejemplo: Auto-responder
        if (str_contains($text, 'hola')) {
            app(\App\Services\WhatsApp\WhatsappNotificationService::class)
                ->sendToChatId($message->chat_id, '¡Hola! ¿En qué puedo ayudarte?');
        }

        // Ejemplo: Buscar usuario y guardar relación
        if ($user = User::where('phone', $message->chat_id)->first()) {
            $message->update(['user_id' => $user->id]);
        }
    }
}

// Registrar listener en EventServiceProvider:
protected $listen = [
    \Illuminate\Database\Events\ModelSaved::class => [
        \App\Listeners\ProcessWhatsappMessage::class,
    ],
];

// ─────────────────────────────────────────────────────────────────────────────
// 6. GENERAR MENSAJES DINÁMICOS COMPLEJOS
// ─────────────────────────────────────────────────────────────────────────────

use App\Services\WhatsApp\WhatsappNotificationService;

class NotificationService
{
    public function __construct(
        private WhatsappNotificationService $whatsapp
    ) {}

    public function sendOrderStatusNotification($order)
    {
        $user = $order->user;
        $statusText = $this->getStatusEmoji($order->status);

        $message = <<<MSG
$statusEmoji *Actualización de Pedido*

Pedido: #{$order->id}
Estado: {$order->status}
Fecha: {$order->updated_at->locale('es')->format('d/m/Y H:i')}

💰 Total: €{$order->total}
📦 Items: {$order->items->count()}

{$this->getEstimatedDelivery($order)}

¿Preguntas? Escribe "ayuda"
MSG;

        $this->whatsapp->sendToUser($user, $message);
    }

    private function getStatusEmoji($status): string
    {
        return match ($status) {
            'pendiente' => '⏳',
            'confirmada' => '✅',
            'en_camino' => '🚚',
            'entregada' => '📦',
            'cancelada' => '❌',
            default => '❓',
        };
    }

    private function getEstimatedDelivery($order): string
    {
        if (!$order->estimated_delivery) {
            return '';
        }

        return "⏰ Entrega estimada: {$order->estimated_delivery->format('d/m/Y')}";
    }
}

// Uso:
app(NotificationService::class)->sendOrderStatusNotification($order);

// ─────────────────────────────────────────────────────────────────────────────
// 7. USAR CLIENT DIRECTAMENTE (Avanzado)
// ─────────────────────────────────────────────────────────────────────────────

use App\Services\OpenWA\OpenWAClient;

class AdminController
{
    public function broadcastMessage(Request $request, OpenWAClient $client)
    {
        $users = User::whereNotNull('phone')->get();

        foreach ($users as $user) {
            // No es lo más eficiente para 1000+ usuarios
            // Mejor usar Queue
            try {
                $client->sendText($user->phone, $request->message);
            } catch (\Exception $e) {
                \Log::error("Failed to send to {$user->phone}", ['error' => $e->getMessage()]);
            }
        }

        return response()->json(['sent' => $users->count()]);
    }

    public function getSessionStatus(OpenWAClient $client)
    {
        $session = $client->getSession();

        return response()->json([
            'status' => $session['status'],
            'connected' => $session['isConnected'],
            'battery' => $session['batteryLevel'] ?? null,
            'phone_number' => $session['phoneNumber'] ?? null,
        ]);
    }

    public function registerWebhook(OpenWAClient $client)
    {
        $response = $client->registerWebhook(
            url: route('api.webhooks.openwa'),
            events: [
                'message.received',
                'message.status',
                'session.status',
                'session.disconnected',
            ],
            secret: config('openwa.webhook_secret')
        );

        return response()->json([
            'message' => 'Webhook registered',
            'response' => $response,
        ]);
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// 8. COMANDOS ARTISAN PERSONALIZADOS
// ─────────────────────────────────────────────────────────────────────────────

namespace App\Console\Commands;

use App\Services\OpenWA\OpenWAClient;
use Illuminate\Console\Command;

class RegisterOpenwaWebhook extends Command
{
    protected $signature = 'openwa:register-webhook {url?}';
    protected $description = 'Registra el webhook de OpenWA';

    public function handle(OpenWAClient $client)
    {
        $url = $this->argument('url') ?? route('api.webhooks.openwa');

        $this->info("Registrando webhook en: $url");

        try {
            $response = $client->registerWebhook(
                url: $url,
                events: ['message.received', 'session.status', 'message.status'],
                secret: config('openwa.webhook_secret')
            );

            $this->info('✅ Webhook registrado exitosamente');
            $this->line(json_encode($response, JSON_PRETTY_PRINT));
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}

// Uso:
// php artisan openwa:register-webhook
// php artisan openwa:register-webhook https://produccion.com/api/webhooks/openwa

// ─────────────────────────────────────────────────────────────────────────────
// 9. CONSULTAR HISTORIAL DE MENSAJES
// ─────────────────────────────────────────────────────────────────────────────

use App\Models\WhatsappMessage;

$userId = auth()->id();

// Todos los mensajes
$messages = WhatsappMessage::forUser($userId)->get();

// Solo entrantes
$inbound = WhatsappMessage::forUser($userId)->inbound()->get();

// Solo salientes
$outbound = WhatsappMessage::forUser($userId)->outbound()->get();

// Por rango de fechas
$recent = WhatsappMessage::forUser($userId)
    ->whereBetween('created_at', [now()->subDays(7), now()])
    ->get();

// Con estado específico
$delivered = WhatsappMessage::forUser($userId)
    ->where('status', 'delivered')
    ->get();

// Contar no leídos
$unread = WhatsappMessage::inbound()
    ->where('status', '!=', 'read')
    ->count();

// ─────────────────────────────────────────────────────────────────────────────
// 10. TESTEAR MANUALMENTE
// ─────────────────────────────────────────────────────────────────────────────

// Desde tinker:
// php artisan tinker

$user = User::first();
app(\App\Services\WhatsApp\WhatsappNotificationService::class)
    ->sendWelcomeMessage($user);

// Verificar que se guardó
WhatsappMessage::latest()->first();

// Ver logs
tail -f storage/logs/openwa.log

// ─────────────────────────────────────────────────────────────────────────────

