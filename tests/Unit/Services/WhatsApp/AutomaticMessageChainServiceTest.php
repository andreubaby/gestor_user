<?php

namespace Tests\Unit\Services\WhatsApp;

use App\Jobs\SendAutomaticMessageStepJob;
use App\Services\WhatsApp\AutomaticMessageChainService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AutomaticMessageChainServiceTest extends TestCase
{
    public function test_dispatch_chain_validates_attachment_url_before_enqueue(): void
    {
        config()->set('openwa.attachment_validation.enabled', true);

        Http::fake([
            'https://cdn.example.com/*' => Http::response('', 200, [
                'Content-Type' => 'application/pdf',
                'Content-Length' => '1024',
            ]),
        ]);

        Queue::fake();

        $service = app(AutomaticMessageChainService::class);
        $service->dispatchChain([
            [
                'type' => 'person',
                'person_mode' => 'phone',
                'phone' => '34600111222',
                'message' => 'Hola',
                'attachment_url' => 'https://cdn.example.com/file.pdf',
            ],
        ], 1, 'Test secuencia', null);

        Queue::assertPushed(SendAutomaticMessageStepJob::class, 1);
    }

    public function test_dispatch_chain_fails_when_attachment_is_not_accessible(): void
    {
        config()->set('openwa.attachment_validation.enabled', true);

        Http::fake([
            '*' => Http::response('', 404),
        ]);

        $service = app(AutomaticMessageChainService::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('no accesible');

        $service->dispatchChain([
            [
                'type' => 'person',
                'person_mode' => 'phone',
                'phone' => '34600111222',
                'message' => 'Hola',
                'attachment_url' => 'https://broken.example.com/file.pdf',
            ],
        ], 1, 'Test secuencia', null);
    }
}

