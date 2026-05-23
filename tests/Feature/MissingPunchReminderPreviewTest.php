<?php

namespace Tests\Feature;

use App\Services\MissingPunchReminderService;
use Mockery;
use Tests\TestCase;

class MissingPunchReminderPreviewTest extends TestCase
{
    public function test_preview_view_renders_expected_candidates(): void
    {
        $this->withoutMiddleware();

        $mock = Mockery::mock(MissingPunchReminderService::class);
        $mock->shouldReceive('previewForDate')
            ->once()
            ->andReturn([
                'date' => '2026-05-22',
                'status' => 'ok',
                'total_linked' => 3,
                'total_with_punch' => 1,
                'total_absent' => 1,
                'total_candidates' => 1,
                'candidates' => [
                    [
                        'trabajador_id' => 185,
                        'nombre' => 'Alejandro Test',
                        'email' => 'alejandroaanaa.28@gmail.com',
                        'tfno' => '622435165',
                        'usuario_id' => 12,
                        'user_fichaje_id' => 4,
                    ],
                ],
            ]);

        $this->app->instance(MissingPunchReminderService::class, $mock);

        $response = $this->get(route('automation.missing-punch.preview', ['date' => '2026-05-22']));

        $response->assertOk();
        $response->assertSee('Vista previa: recordatorio de no fichaje');
        $response->assertSee('Alejandro Test');
        $response->assertSee('622435165');
    }
}

