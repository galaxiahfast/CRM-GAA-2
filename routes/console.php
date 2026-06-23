<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schedule;
use App\Mail\ReporteSemanal;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule::call(function () {
//     Mail::to('diego_cen@gonzalezalonzo.net')->send(new ReporteSemanal());
// })->monthlyOn(5, '8:00');

Schedule::call(function () {
    Mail::to('diego_cen@gonzalezalonzo.net')->send(new ReporteSemanal());
})->cron('0 8 5,17,28 * *');

