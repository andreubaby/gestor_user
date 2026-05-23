<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule)
    {
        // Ejecutar las automatizaciones programadas cada minuto
        $schedule->command('app:execute-scheduled-automations')
            ->everyMinute()
            ->withoutOverlapping()
            ->onFailure(function () {
                \Log::error('Error al ejecutar automatizaciones programadas');
            });

    }

    /**
     * Register the commands for the application.
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}

