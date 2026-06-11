<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Cierre forzoso nocturno de cronómetros olvidados (reglas 8.6 / 12.2).
Schedule::command('time:auto-close')->dailyAt('23:59');
