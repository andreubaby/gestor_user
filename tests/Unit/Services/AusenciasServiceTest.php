<?php

namespace Tests\Unit\Services;

use App\Services\AusenciasService;
use Carbon\Carbon;
use Tests\TestCase;

class AusenciasServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_resolve_pdf_date_uses_today_by_default(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 6, 18, 12, 0, 0));

        $service = app(AusenciasService::class);

        $this->assertSame('18/06/2026', $service->resolvePdfDate()->format('d/m/Y'));
    }

    public function test_resolve_pdf_date_supports_relative_offset(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 6, 18, 12, 0, 0));

        $service = app(AusenciasService::class);

        $this->assertSame('19/06/2026', $service->resolvePdfDate(null, 1)->format('d/m/Y'));
        $this->assertSame('17/06/2026', $service->resolvePdfDate(null, -1)->format('d/m/Y'));
    }

    public function test_resolve_pdf_date_accepts_explicit_date_formats(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 6, 18, 12, 0, 0));

        $service = app(AusenciasService::class);

        $this->assertSame('17/06/2026', $service->resolvePdfDate('17/06/2026')->format('d/m/Y'));
        $this->assertSame('17/06/2026', $service->resolvePdfDate('2026-06-17')->format('d/m/Y'));
    }
}

