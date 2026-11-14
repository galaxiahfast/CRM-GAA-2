<?php

use App\Mail\ReporteSemanal;
use App\Models\SupportChatMessage;
use App\Services\Backups\ContpaqiBackupCatalog;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schedule;

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
    Mail::to('diego_cen@gonzalezalonzo.net')->send(new ReporteSemanal);
})->cron('0 8 5,17,28 * *');

/*
|--------------------------------------------------------------------------
| Sincronización del Checador Biométrico (Modo Espejo)
|--------------------------------------------------------------------------
*/

// 1. REVISIÓN INCREMENTAL (Cada minuto)
// Captura marcas nuevas al instante de forma rápida y silenciosa
Schedule::command('biometric:sync')->everyMinute()->withoutOverlapping();

// Actualiza únicamente el índice del día actual de CONTPAQi. El proceso es
// de solo lectura y se ejecuta dentro del mismo schedule:work de desarrollo.
Schedule::call(fn () => app(ContpaqiBackupCatalog::class)->refreshToday())
    ->everyMinute()
    ->name('contpaqi-backups:refresh-today')
    ->withoutOverlapping();

// 2. REVISIÓN DE 30 DÍAS (Una vez al día)
// Recupera marcas faltantes sin borrar ni sobrescribir correcciones locales.
Schedule::command('biometric:sync --maintenance')
    ->dailyAt('23:00')
    ->timezone(config('time-control.timezone', 'America/Mexico_City'))
    ->withoutOverlapping();

// Pausa la actividad del Cronómetro de quien cerró la página o el navegador: mientras el CRM esté abierto (en
// cualquier apartado) el navegador avisa cada 30 s; tras unos minutos sin aviso se pausa en la última vez que se le vio.
Schedule::command('time:pause-abandoned')->everyMinute()->withoutOverlapping();

// Envía las actividades del Cronómetro a ManagerControl (solo en ese sentido). Solo corre si está activado en el .env
// (MANAGERCONTROL_TIMER_SYNC_ENABLED=true); las actividades en progreso se envían cuando se pausan o se cierran.
if (config('managercontrol.timer_sync.enabled')) {
    Schedule::command('managercontrol:sync-timer --apply')
        ->everyThirtyMinutes()
        ->name('managercontrol:sync-timer')
        ->withoutOverlapping(25);
}

// Cierra los cronómetros que sigan activos al terminar la jornada. Se usa
// una zona horaria explícita porque APP_TIMEZONE puede permanecer en UTC.
Schedule::command('time:auto-close')
    ->dailyAt(config('time-control.auto_close_at', '21:00'))
    ->timezone(config('time-control.timezone', 'America/Mexico_City'))
    ->withoutOverlapping();

// Reporte de respaldos: cada lunes a las 9:00 (hora de México) se leen los logs y el PDF se envía solo a
// soporte y a Emiliano Ortiz (config/backup-reports.php > weekly). El envío manual sigue en la pantalla.
if (config('backup-reports.weekly.enabled', true)) {
    Schedule::command('backup-reports:send-weekly')
        ->weeklyOn((int) config('backup-reports.weekly.day', 1), (string) config('backup-reports.weekly.time', '09:00'))
        ->timezone(config('support.timezone', 'America/Mexico_City'))
        ->name('backup-reports:send-weekly')
        ->withoutOverlapping(120);
}

// Evita que la recolección de sesiones vencidas recaiga aleatoriamente sobre
// una petición interactiva. El pequeño lottery de respaldo sigue habilitado.
Schedule::call(function (): void {
    if (config('session.driver') !== 'database') {
        return;
    }

    $cutoff = now()->subMinutes((int) config('session.lifetime', 120))->timestamp;
    DB::table((string) config('session.table', 'sessions'))
        ->where('last_activity', '<=', $cutoff)
        ->delete();
})->everyThirtyMinutes()
    ->name('sessions:prune-expired')
    ->withoutOverlapping();

// El chat general de soporte conserva únicamente los mensajes del día actual.
Schedule::call(function () {
    $startOfToday = now(config('support.timezone', 'America/Mexico_City'))->startOfDay()->utc();
    SupportChatMessage::query()->where('created_at', '<', $startOfToday)->delete();
})->dailyAt('00:05')
    ->timezone(config('support.timezone', 'America/Mexico_City'))
    ->name('support-chat:clear-previous-days')
    ->withoutOverlapping();
