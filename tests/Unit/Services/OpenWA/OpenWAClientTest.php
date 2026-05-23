<?php

namespace Tests\Unit\Services\OpenWA;

use App\Exceptions\OpenWAException;
use App\Services\OpenWA\OpenWAClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenWAClientTest extends TestCase
{
    protected OpenWAClient $client;

    protected function setUp(): void
    {
        parent::setUp();

        config(['openwa' => [
            'base_url' => 'http://localhost:3000',
            'api_key' => 'test-api-key',
            'session_id' => 'default',
            'webhook_secret' => null,
            'request_timeout' => 30000,
            'logging' => ['enabled' => false],
            'default_country_code' => '34',
        ]]);

        $this->client = new OpenWAClient();
    }

    /** @test */
    public function it_sends_text_message_by_phone_number()
    {
        Http::fake([
            'http://localhost:3000/*' => Http::response([
                'id' => '123456789',
                'status' => 'sent',
            ]),
        ]);

        $response = $this->client->sendText('612345678', 'Hola mundo');

        Http::assertSent(function ($request) {
            return $request->url() === 'http://localhost:3000/api/sessions/default/messages/send-text'
                && $request->method() === 'POST'
                && $request->hasHeader('X-API-Key')
                && str_contains($request->body(), '"chatId":"34612345678@c.us"')
                && str_contains($request->body(), '"text":"Hola mundo"');
        });

        $this->assertIsArray($response);
        $this->assertArrayHasKey('id', $response);
    }

    /** @test */
    public function it_sends_text_message_by_chat_id()
    {
        Http::fake([
            'http://localhost:3000/*' => Http::response([
                'id' => '987654321',
                'status' => 'sent',
            ]),
        ]);

        $response = $this->client->sendTextToChatId('34612345678@c.us', 'Test message');

        Http::assertSent(function ($request) {
            return str_contains($request->body(), '"chatId":"34612345678@c.us"')
                && str_contains($request->body(), '"text":"Test message"');
        });

        $this->assertIsArray($response);
        $this->assertEquals('987654321', $response['id']);
    }

    /** @test */
    public function it_gets_session_status()
    {
        Http::fake([
            'http://localhost:3000/*' => Http::response([
                'sessionId' => 'default',
                'status' => 'CONNECTED',
                'status_code' => 'CONNECTED',
            ]),
        ]);

        $response = $this->client->getSession();

        Http::assertSent(function ($request) {
            return $request->url() === 'http://localhost:3000/api/sessions/default'
                && $request->method() === 'GET'
                && $request->hasHeader('X-API-Key');
        });

        $this->assertIsArray($response);
        $this->assertEquals('CONNECTED', $response['status']);
    }

    /** @test */
    public function it_gets_session_groups()
    {
        Http::fake([
            'http://localhost:3000/*' => Http::response([
                'value' => [
                    ['id' => '120363000000000001@g.us', 'name' => 'Grupo A'],
                    ['id' => '120363000000000002@g.us', 'name' => 'Grupo B'],
                ],
            ]),
        ]);

        $response = $this->client->getSessionGroups();

        Http::assertSent(function ($request) {
            return $request->url() === 'http://localhost:3000/api/sessions/default/groups'
                && $request->method() === 'GET';
        });

        $this->assertIsArray($response);
        $this->assertCount(2, $response);
        $this->assertEquals('Grupo A', $response[0]['name']);
    }

    /** @test */
    public function it_auto_recovers_session_id_when_get_session_returns_404()
    {
        Http::fake(function (Request $request) {
            if ($request->url() === 'http://localhost:3000/api/sessions/default') {
                return Http::response(['message' => 'Session not found'], 404);
            }

            if ($request->url() === 'http://localhost:3000/api/sessions') {
                return Http::response([
                    ['sessionId' => 'new-session', 'status' => 'CONNECTED', 'isConnected' => true],
                ], 200);
            }

            if ($request->url() === 'http://localhost:3000/api/sessions/new-session') {
                return Http::response([
                    'sessionId' => 'new-session',
                    'status' => 'CONNECTED',
                    'isConnected' => true,
                ], 200);
            }

            return Http::response([], 500);
        });

        $response = $this->client->getSession();

        $this->assertEquals('new-session', $response['sessionId']);
        Http::assertSentCount(3);
        Http::assertSent(fn (Request $request) => $request->url() === 'http://localhost:3000/api/sessions/new-session');
    }

    /** @test */
    public function it_auto_recovers_session_id_when_send_text_returns_404()
    {
        Http::fake(function (Request $request) {
            if ($request->url() === 'http://localhost:3000/api/sessions/default/messages/send-text') {
                return Http::response(['message' => 'Session not found'], 404);
            }

            if ($request->url() === 'http://localhost:3000/api/sessions') {
                return Http::response([
                    'sessions' => [
                        ['sessionId' => 'regenerated', 'status' => 'CONNECTED'],
                    ],
                ], 200);
            }

            if ($request->url() === 'http://localhost:3000/api/sessions/regenerated/messages/send-text') {
                return Http::response([
                    'id' => 'msg-1',
                    'status' => 'sent',
                ], 200);
            }

            return Http::response([], 500);
        });

        $response = $this->client->sendText('612345678', 'Hola');

        $this->assertEquals('sent', $response['status']);
        Http::assertSentCount(3);
        Http::assertSent(fn (Request $request) => $request->url() === 'http://localhost:3000/api/sessions/regenerated/messages/send-text');
    }

    /** @test */
    public function it_registers_webhook()
    {
        Http::fake([
            'http://localhost:3000/*' => Http::response([
                'status' => 'success',
                'message' => 'Webhook registered',
            ]),
        ]);

        $response = $this->client->registerWebhook(
            'http://example.com/webhooks/openwa',
            ['message.received', 'session.status'],
            'secret123'
        );

        Http::assertSent(function ($request) {
            $data = $request->data();

            return $request->url() === 'http://localhost:3000/api/sessions/default/webhooks/register'
                && ($data['url'] ?? null) === 'http://example.com/webhooks/openwa'
                && ($data['events'] ?? null) === ['message.received', 'session.status']
                && ($data['secret'] ?? null) === 'secret123';
        });

        $this->assertIsArray($response);
        $this->assertEquals('success', $response['status']);
    }

    /** @test */
    public function it_throws_exception_on_failed_request()
    {
        $this->expectException(OpenWAException::class);

        Http::fake([
            'http://localhost:3000/*' => Http::response([
                'error' => true,
                'message' => 'Invalid session',
            ], 400),
        ]);

        $this->client->sendText('612345678', 'Test');
    }

    /** @test */
    public function it_converts_phone_to_chat_id()
    {
        // Test método privado usando reflexión
        $reflection = new \ReflectionClass($this->client);
        $method = $reflection->getMethod('phoneToChatId');
        $method->setAccessible(true);

        // Caso 1: Número sin código de país
        $chatId = $method->invoke($this->client, '612345678');
        $this->assertEquals('34612345678@c.us', $chatId);

        // Caso 2: Número con código de país
        $chatId = $method->invoke($this->client, '34612345678');
        $this->assertEquals('34612345678@c.us', $chatId);

        // Caso 3: Número con 0 inicial
        $chatId = $method->invoke($this->client, '0612345678');
        $this->assertEquals('34612345678@c.us', $chatId);

        // Caso 4: Número con caracteres especiales
        $chatId = $method->invoke($this->client, '+34-612-345-678');
        $this->assertEquals('34612345678@c.us', $chatId);
    }

    /** @test */
    public function it_throws_exception_on_missing_configuration()
    {
        config(['openwa.api_key' => null]);

        $this->expectException(OpenWAException::class);
        $this->expectExceptionMessage('OpenWA configuration missing');

        new OpenWAClient();
    }
}

