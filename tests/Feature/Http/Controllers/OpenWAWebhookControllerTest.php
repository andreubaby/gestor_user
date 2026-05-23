<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\WhatsappMessage;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class OpenWAWebhookControllerTest extends TestCase
{
    /** @test */
    public function it_receives_message_webhook_and_saves_message()
    {
        Log::shouldReceive('channel')->andReturnSelf();
        Log::shouldReceive('info')->times(2);

        $payload = [
            'event' => 'message.received',
            'session' => 'default',
            'message' => [
                'id' => '123456789',
                'from' => '34612345678@c.us',
                'body' => 'Hello from WhatsApp',
                'timestamp' => time(),
            ],
        ];

        $response = $this->postJson('/api/webhooks/openwa', $payload);

        $response->assertJson(['ok' => true]);

        $this->assertDatabaseHas('whatsapp_messages', [
            'chat_id' => '34612345678@c.us',
            'text' => 'Hello from WhatsApp',
            'direction' => 'inbound',
            'status' => 'received',
        ]);
    }

    /** @test */
    public function it_ignores_messages_sent_by_self()
    {
        Log::shouldReceive('channel')->andReturnSelf();
        Log::shouldReceive('info');

        $payload = [
            'event' => 'message.received',
            'session' => 'default',
            'message' => [
                'id' => '123456789',
                'from' => '34612345678@c.us',
                'body' => 'Message from self',
                'isGroupMsg' => false,
                'isSentByMe' => true,
            ],
        ];

        $response = $this->postJson('/api/webhooks/openwa', $payload);

        $response->assertJson(['ok' => true]);

        $this->assertDatabaseMissing('whatsapp_messages', [
            'text' => 'Message from self',
        ]);
    }

    /** @test */
    public function it_handles_session_status_webhook()
    {
        Log::shouldReceive('channel')->andReturnSelf();
        Log::shouldReceive('info');

        $payload = [
            'event' => 'session.status',
            'session' => 'default',
            'status' => 'CONNECTED',
        ];

        $response = $this->postJson('/api/webhooks/openwa', $payload);

        $response->assertJson(['ok' => true]);
    }

    /** @test */
    public function it_validates_hmac_signature()
    {
        config(['openwa.webhook_secret' => 'test-secret']);

        Log::shouldReceive('channel')->andReturnSelf();
        Log::shouldReceive('warning');

        $payload = ['event' => 'message.received'];

        $response = $this->postJson(
            '/api/webhooks/openwa',
            $payload,
            ['X-HMAC-SHA256' => 'invalid-signature']
        );

        $response->assertStatus(401);
        $response->assertJson(['error' => 'Invalid signature']);
    }

    /** @test */
    public function it_skips_hmac_validation_if_no_secret()
    {
        config(['openwa.webhook_secret' => null]);

        Log::shouldReceive('channel')->andReturnSelf();
        Log::shouldReceive('info');

        $payload = [
            'event' => 'session.status',
            'session' => 'default',
            'status' => 'CONNECTED',
        ];

        $response = $this->postJson('/api/webhooks/openwa', $payload);

        $response->assertJson(['ok' => true]);
    }

    /** @test */
    public function it_handles_message_status_webhook()
    {
        Log::shouldReceive('channel')->andReturnSelf();
        Log::shouldReceive('info')->times(2);

        // Primero crear el mensaje
        $message = WhatsappMessage::create([
            'session_id' => 'default',
            'chat_id' => '34612345678@c.us',
            'message_id' => '123456789',
            'text' => 'Test',
            'direction' => 'outbound',
            'status' => 'sent',
        ]);

        // Simular webhook de entrega
        $payload = [
            'event' => 'message.status',
            'messageId' => '123456789',
            'status' => 'delivered',
        ];

        $response = $this->postJson('/api/webhooks/openwa', $payload);

        $response->assertJson(['ok' => true]);

        $message->refresh();
        $this->assertEquals('delivered', $message->status);
        $this->assertNotNull($message->delivered_at);
    }

    /** @test */
    public function it_prevents_duplicate_webhook_processing()
    {
        Log::shouldReceive('channel')->andReturnSelf();
        Log::shouldReceive('info');
        Log::shouldReceive('debug');

        $payload = [
            'event' => 'message.received',
            'idempotencyKey' => 'unique-key-123',
            'session' => 'default',
            'message' => [
                'id' => '123456789',
                'from' => '34612345678@c.us',
                'body' => 'Test',
            ],
        ];

        // Primera llamada
        $response1 = $this->postJson('/api/webhooks/openwa', $payload);
        $response1->assertJson(['ok' => true]);

        // Segunda llamada (misma idempotencyKey)
        $response2 = $this->postJson('/api/webhooks/openwa', $payload);
        $response2->assertJson(['ok' => true, 'message' => 'Already processed']);

        // Solo debería haber un mensaje en la BD
        $this->assertEquals(
            1,
            WhatsappMessage::where('message_id', '123456789')->count()
        );
    }
}

