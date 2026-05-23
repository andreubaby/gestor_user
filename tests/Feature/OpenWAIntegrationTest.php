<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WhatsappMessage;
use App\Services\WhatsApp\WhatsappNotificationService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Test de integración completa de OpenWA
 *
 * Verifica que todos los componentes funcionan juntos
 */
class OpenWAIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['openwa' => [
            'base_url' => 'http://localhost:3000',
            'api_key' => 'test-api-key',
            'session_id' => 'default',
            'webhook_secret' => 'test-secret',
            'request_timeout' => 30000,
            'logging' => ['enabled' => false],
            'default_country_code' => '34',
        ]]);
    }

    /** @test */
    public function complete_workflow_send_message_to_user()
    {
        // Crear usuario con teléfono
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '612345678',
            'password' => bcrypt('password'),
        ]);

        // Mock OpenWA API
        Http::fake([
            'http://localhost:3000/*' => Http::response([
                'id' => 'msg-123',
                'status' => 'sent',
            ]),
        ]);

        // Enviar mensaje via servicio
        app(WhatsappNotificationService::class)
            ->sendWelcomeMessage($user);

        // El mensaje debería estar encolado (si usamos queue)
        // o guardado en BD si sincrónico
    }

    /** @test */
    public function webhook_flow_message_received()
    {
        // Simular webhook de OpenWA
        $webhookData = [
            'event' => 'message.received',
            'session' => 'default',
            'message' => [
                'id' => 'msg-456',
                'from' => '34612345678@c.us',
                'body' => 'Test message from user',
                'timestamp' => time(),
            ],
            'idempotencyKey' => 'unique-123',
        ];

        $response = $this->postJson('/api/webhooks/openwa', $webhookData);

        $response->assertJson(['ok' => true]);

        // Verificar que se guardó en BD
        $this->assertDatabaseHas('whatsapp_messages', [
            'chat_id' => '34612345678@c.us',
            'text' => 'Test message from user',
            'direction' => 'inbound',
        ]);
    }

    /** @test */
    public function webhook_prevents_duplicate_processing()
    {
        $webhookData = [
            'event' => 'message.received',
            'session' => 'default',
            'idempotencyKey' => 'dup-check-123',
            'message' => [
                'id' => 'msg-dup',
                'from' => '34612345678@c.us',
                'body' => 'Duplicate test',
            ],
        ];

        // Primera vez
        $response1 = $this->postJson('/api/webhooks/openwa', $webhookData);
        $response1->assertJson(['ok' => true]);

        // Segunda vez (misma key)
        $response2 = $this->postJson('/api/webhooks/openwa', $webhookData);
        $response2->assertJson(['ok' => true]);

        // Solo debería haber un registro
        $this->assertEquals(1, WhatsappMessage::where('message_id', 'msg-dup')->count());
    }

    /** @test */
    public function message_status_updates_are_tracked()
    {
        // Crear mensaje enviado
        $message = WhatsappMessage::create([
            'chat_id' => '34612345678@c.us',
            'message_id' => 'msg-track-123',
            'text' => 'Test tracking',
            'direction' => 'outbound',
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        // Simular webhook de entrega
        $this->postJson('/api/webhooks/openwa', [
            'event' => 'message.status',
            'messageId' => 'msg-track-123',
            'status' => 'delivered',
        ]);

        $message->refresh();

        $this->assertEquals('delivered', $message->status);
        $this->assertNotNull($message->delivered_at);
    }

    /** @test */
    public function user_phone_relationship_works()
    {
        $user = User::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '687654321',
            'password' => bcrypt('password'),
        ]);

        // Crear mensajes para este usuario
        WhatsappMessage::create([
            'user_id' => $user->id,
            'chat_id' => '34687654321@c.us',
            'text' => 'Message 1',
            'direction' => 'inbound',
        ]);

        WhatsappMessage::create([
            'user_id' => $user->id,
            'chat_id' => '34687654321@c.us',
            'text' => 'Message 2',
            'direction' => 'outbound',
        ]);

        // Verificar relación
        $this->assertEquals(2, $user->whatsappMessages()->count());
        $this->assertEquals(1, $user->whatsappMessages()->inbound()->count());
        $this->assertEquals(1, $user->whatsappMessages()->outbound()->count());
    }

    /** @test */
    public function can_query_messages_efficiently()
    {
        // Crear datos de prueba
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '666666666',
            'password' => bcrypt('password'),
        ]);

        for ($i = 0; $i < 5; $i++) {
            WhatsappMessage::create([
                'user_id' => $user->id,
                'chat_id' => '34666666666@c.us',
                'text' => "Message $i",
                'direction' => 'inbound',
                'status' => 'received',
            ]);
        }

        for ($i = 5; $i < 10; $i++) {
            WhatsappMessage::create([
                'user_id' => $user->id,
                'chat_id' => '34666666666@c.us',
                'text' => "Message $i",
                'direction' => 'outbound',
                'status' => 'sent',
            ]);
        }

        // Queries
        $this->assertEquals(5, WhatsappMessage::inbound()->count());
        $this->assertEquals(5, WhatsappMessage::outbound()->count());
        $this->assertEquals(10, WhatsappMessage::forUser($user->id)->count());
        $this->assertEquals(10, WhatsappMessage::forSession('default')->count());
        $this->assertEquals(10, WhatsappMessage::forChat('34666666666@c.us')->count());
    }

    /** @test */
    public function webhook_with_invalid_signature_is_rejected()
    {
        config(['openwa.webhook_secret' => 'secret-123']);

        $webhookData = [
            'event' => 'message.received',
            'message' => [
                'from' => '34612345678@c.us',
                'body' => 'Test',
            ],
        ];

        $response = $this->postJson(
            '/api/webhooks/openwa',
            $webhookData,
            ['X-HMAC-SHA256' => 'invalid-signature']
        );

        $response->assertStatus(401);
        $response->assertJson(['error' => 'Invalid signature']);
    }
}

