<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Refrescar vistas materializadas cada 5 minutos
        $schedule->command('mapa:refresh-views --concurrent')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        // Limpiar auditoría antigua (opcional - descomentar para habilitar)
        // Se ejecuta el primer día de cada mes a las 3 AM
        // $schedule->command('auditoria:limpiar --dias=730 --auto')
        //     ->monthlyOn(1, '03:00')
        //     ->withoutOverlapping();

        // Limpiar tokens de password reset expirados
        $schedule->command('auth:clear-resets')
            ->everyFifteenMinutes();

        // Limpiar sesiones expiradas (si se usa database driver)
        $schedule->command('session:gc')
            ->daily();

        // Procesar exportaciones pendientes
        $schedule->command('queue:work --stop-when-empty')
            ->everyMinute()
            ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.


    */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
