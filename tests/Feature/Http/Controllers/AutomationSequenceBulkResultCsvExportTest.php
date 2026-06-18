<?php

namespace Tests\Feature\Http\Controllers;

use Illuminate\Auth\Middleware\Authenticate;
use Tests\TestCase;

class AutomationSequenceBulkResultCsvExportTest extends TestCase
{
    /** @test */
    public function it_exports_bulk_result_as_csv_from_session(): void
    {
        $this->withoutMiddleware(Authenticate::class);

        $bulkResult = [
            'action' => 'execute',
            'total' => 3,
            'ok' => [
                ['id' => 10, 'name' => 'Secuencia A', 'reason' => 'Encolada'],
            ],
            'skipped' => [
                ['id' => 11, 'name' => 'Secuencia B', 'reason' => 'Ya estaba activa'],
            ],
            'failed' => [
                ['id' => 12, 'name' => 'Secuencia C', 'reason' => 'No se pudo encolar'],
            ],
        ];

        $response = $this->withSession(['bulk_result' => $bulkResult])
            ->get(route('automation.sequences.bulkActions.exportCsv'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();
        $this->assertStringContainsString('status,id,nombre,motivo,accion', $csv);
        $this->assertStringContainsString('ok,10,"Secuencia A",Encolada,execute', $csv);
        $this->assertStringContainsString('skipped,11,"Secuencia B","Ya estaba activa",execute', $csv);
        $this->assertStringContainsString('failed,12,"Secuencia C","No se pudo encolar",execute', $csv);
    }

    /** @test */
    public function it_redirects_when_bulk_result_is_missing(): void
    {
        $this->withoutMiddleware(Authenticate::class);

        $response = $this->get(route('automation.sequences.bulkActions.exportCsv'));

        $response->assertRedirect(route('automation.sequences.index'));
        $response->assertSessionHas('error', 'No hay resultado masivo disponible para exportar.');
    }
}

