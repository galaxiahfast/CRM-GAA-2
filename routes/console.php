<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schedule;
use App\Mail\ReporteSemanal;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Envíos de Reportes de Correo Existentes
Schedule::call(function () {
    Mail::to('diego_cen@gonzalezalonzo.net')->send(new ReporteSemanal());
})->cron('0 8 5,17,28 * *');

/*
|--------------------------------------------------------------------------
| Sincronización del Checador Biométrico (Modo Espejo)
|--------------------------------------------------------------------------
*/

// 1. REVISIÓN INCREMENTAL (Cada minuto)
// Captura marcas nuevas al instante de forma rápida y silenciosa
Schedule::command('biometric:sync')->everyMinute()->withoutOverlapping();

// 2. MANTENIMIENTO PROFUNDO (Una vez al día)
// Valida los últimos 30 días contra el hardware y purga inconsistencias a las 11:00 PM
Schedule::command('biometric:sync --maintenance')->dailyAt('23:00')->withoutOverlapping();

// Cierra los cronómetros que sigan activos al terminar la jornada. Se usa
// una zona horaria explícita porque APP_TIMEZONE puede permanecer en UTC.
Schedule::command('time:auto-close')
    ->dailyAt(config('time-control.auto_close_at', '21:00'))
    ->timezone(config('time-control.timezone', 'America/Mexico_City'))
    ->withoutOverlapping();
