<?php

namespace Tests\Unit\Jobs;

use App\Jobs\SendAutomaticMessageStepJob;
use App\Services\WhatsApp\WhatsappNotificationService;
use Mockery;
use Tests\TestCase;

class SendAutomaticMessageStepJobAttachmentFallbackTest extends TestCase
{
    public function test_it_sends_text_fallback_when_attachment_fails(): void
    {
        $service = Mockery::mock(WhatsappNotificationService::class);

        $service->shouldReceive('sendFileToPhone')
            ->once()
            ->andThrow(new \RuntimeException('OpenWA no soporta el archivo'));

        $service->shouldReceive('sendToPhone')
            ->once()
            ->with('34600111222', 'Mensaje de respaldo', null, true);

        $job = new SendAutomaticMessageStepJob([
            'type' => 'person',
            'person_mode' => 'phone',
            'phone' => '34600111222',
            'message' => 'Mensaje de respaldo',
            'attachment_urls' => ['https://cdn.example.com/file.xlsx'],
            'attachment_names' => ['file.xlsx'],
        ]);

        $job->handle($service);

        $this->assertTrue(true);
    }

    public function test_it_throws_when_all_attachments_fail_and_there_is_no_text(): void
    {
        $service = Mockery::mock(WhatsappNotificationService::class);

        $service->shouldReceive('sendFileToPhone')
            ->once()
            ->andThrow(new \RuntimeException('OpenWA no soporta el archivo'));

        $service->shouldNotReceive('sendToPhone');

        $job = new SendAutomaticMessageStepJob([
            'type' => 'person',
            'person_mode' => 'phone',
            'phone' => '34600111222',
            'message' => '',
            'attachment_urls' => ['https://cdn.example.com/file.xlsx'],
            'attachment_names' => ['file.xlsx'],
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No se pudo enviar ningun adjunto');

        $job->handle($service);
    }
}

