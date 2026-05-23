<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Ejecuta las automatizaciones programadas cada minuto.
Schedule::command('app:execute-scheduled-automations')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('app:send-missing-punch-reminders')
    ->dailyAt((string) config('fichajes.missing_punch.schedule_time', '09:00'))
    ->withoutOverlapping();

