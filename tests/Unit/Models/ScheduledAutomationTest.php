<?php

namespace Tests\Unit\Models;

use App\Models\ScheduledAutomation;
use Carbon\Carbon;
use Tests\TestCase;

class ScheduledAutomationTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_should_not_run_on_saturday_even_if_legacy_data_contains_weekend_days(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 5, 23, 9, 0, 0)); // Sabado

        $schedule = new ScheduledAutomation([
            'status' => 'active',
            'scheduled_time' => '09:00:00',
            'days_of_week' => ['2', '3', '4', '5', '6'],
        ]);

        $this->assertFalse($schedule->shouldRun());
    }

    public function test_calculate_next_execution_skips_weekend_when_only_legacy_weekend_days_exist(): void
    {
        $schedule = new ScheduledAutomation([
            'status' => 'active',
            'scheduled_time' => '09:00:00',
            'days_of_week' => ['0', '6'],
        ]);

        $reference = Carbon::create(2026, 5, 22, 18, 30, 0); // Viernes
        $next = $schedule->calculateNextExecutionFrom($reference);

        $this->assertSame('2026-05-26 09:00:00', $next->format('Y-m-d H:i:s')); // Martes
        $this->assertSame('2', (string) $next->dayOfWeek);
    }

    public function test_should_not_run_on_monday_even_if_monday_exists_in_legacy_days(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 5, 25, 9, 0, 0)); // Lunes

        $schedule = new ScheduledAutomation([
            'status' => 'active',
            'scheduled_time' => '09:00:00',
            'days_of_week' => ['1', '2', '3'],
        ]);

        $this->assertFalse($schedule->shouldRun());
    }
}


