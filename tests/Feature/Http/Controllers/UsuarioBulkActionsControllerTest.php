<?php

namespace Tests\Feature\Http\Controllers;

use App\Services\BulkUsuarioActionService;
use Illuminate\Auth\Middleware\Authenticate;
use Mockery;
use Tests\TestCase;

class UsuarioBulkActionsControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_stores_granular_result_for_activate_action(): void
    {
        $this->withoutMiddleware(Authenticate::class);

        $service = Mockery::mock(BulkUsuarioActionService::class);
        $service->shouldReceive('setActivoDetailed')
            ->once()
            ->with([10, 11], 1)
            ->andReturn([
                'processed' => 2,
                'ok' => [
                    ['id' => 10, 'name' => 'Mario', 'reason' => 'Activado'],
                ],
                'skipped' => [
                    ['id' => 11, 'name' => 'Ana', 'reason' => 'Ya estaba activo'],
                ],
                'failed' => [],
            ]);

        $this->app->instance(BulkUsuarioActionService::class, $service);

        $response = $this->post(route('usuarios.bulk.actions'), [
            'action' => 'activate',
            'worker_ids' => [10, 11],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $response->assertSessionHas('bulk_result', function (array $bulk) {
            return ($bulk['action'] ?? null) === 'activate'
                && ($bulk['total'] ?? null) === 2
                && count($bulk['ok'] ?? []) === 1
                && count($bulk['skipped'] ?? []) === 1
                && count($bulk['failed'] ?? []) === 0;
        });
    }

    /** @test */
    public function it_stores_granular_result_for_auto_link_action(): void
    {
        $this->withoutMiddleware(Authenticate::class);

        $service = Mockery::mock(BulkUsuarioActionService::class);
        $service->shouldReceive('autoLinkByEmail')
            ->once()
            ->with([21])
            ->andReturn([
                'processed' => 1,
                'created' => 1,
                'updated' => 0,
                'no_email' => 0,
                'no_match' => 0,
                'errors' => 0,
                'ok' => [
                    ['id' => 21, 'name' => 'Lucia', 'reason' => 'Vinculo creado'],
                ],
                'skipped' => [],
                'failed' => [],
            ]);

        $this->app->instance(BulkUsuarioActionService::class, $service);

        $response = $this->post(route('usuarios.bulk.actions'), [
            'action' => 'auto_link_email',
            'worker_ids' => [21],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $response->assertSessionHas('bulk_result', function (array $bulk) {
            return ($bulk['action'] ?? null) === 'auto_link_email'
                && ($bulk['total'] ?? null) === 1
                && count($bulk['ok'] ?? []) === 1
                && count($bulk['failed'] ?? []) === 0;
        });
    }
}

