<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Refrescar vistas materializadas cada 5 minutos
Schedule::command('mapa:refresh-views')->everyFiveMinutes();

// Limpiar sesiones expiradas
Schedule::command('auth:clear-resets')->everyFifteenMinutes();
