<?php

namespace Tests\Unit\Jobs;

use App\Exceptions\OpenWAException;
use App\Jobs\SendWhatsappMessageJob;
use App\Models\User;
use App\Models\WhatsappMessage;
use App\Services\OpenWA\OpenWAClient;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SendWhatsappMessageJobTest extends TestCase
{
    /** @test */
    public function it_sends_saved_message()
    {
        Log::shouldReceive('channel')->andReturnSelf();
        Log::shouldReceive('info')->twice();

        $this->mock(OpenWAClient::class, function ($mock) {
            $mock->shouldReceive('sendTextToChatId')
                ->once()
                ->with('34612345678@c.us', 'Test message')
                ->andReturn(['id' => 'msg-123']);
        });

        $message = WhatsappMessage::create([
            'chat_id' => '34612345678@c.us',
            'text' => 'Test message',
            'direction' => 'outbound',
            'status' => 'pending',
        ]);

        $job = new SendWhatsappMessageJob($message);
        $job->handle(app(OpenWAClient::class));

        $message->refresh();

        $this->assertEquals('sent', $message->status);
        $this->assertEquals('msg-123', $message->message_id);
        $this->assertNotNull($message->sent_at);
    }

    /** @test */
    public function it_sends_new_message()
    {
        Log::shouldReceive('channel')->andReturnSelf();
        Log::shouldReceive('info');

        $this->mock(OpenWAClient::class, function ($mock) {
            $mock->shouldReceive('sendTextToChatId')
                ->once()
                ->with('34612345678@c.us', 'Direct message')
                ->andReturn(['id' => 'msg-456']);
        });

        $job = new SendWhatsappMessageJob('34612345678@c.us', 'Direct message', 1);
        $job->handle(app(OpenWAClient::class));

        $this->assertDatabaseHas('whatsapp_messages', [
            'chat_id' => '34612345678@c.us',
            'text' => 'Direct message',
            'status' => 'sent',
            'message_id' => 'msg-456',
        ]);
    }

    /** @test */
    public function it_marks_message_as_failed_on_exception()
    {
        Log::shouldReceive('channel')->andReturnSelf();
        Log::shouldReceive('error');

        $this->mock(OpenWAClient::class, function ($mock) {
            $mock->shouldReceive('sendTextToChatId')
                ->once()
                ->andThrow(new OpenWAException('Send failed'));
        });

        $message = WhatsappMessage::create([
            'chat_id' => '34612345678@c.us',
            'text' => 'Test',
            'direction' => 'outbound',
            'status' => 'pending',
        ]);

        $job = new SendWhatsappMessageJob($message);

        try {
            $job->handle(app(OpenWAClient::class));
        } catch (OpenWAException $e) {
            // Expected
        }

        $message->refresh();

        $this->assertEquals('failed', $message->status);
        $this->assertNotNull($message->error_message);
    }
}

