<?php

return [
    'timezone' => env('TIME_CONTROL_TIMEZONE', 'America/Mexico_City'),
    'workday_starts_at' => env('TIME_CONTROL_WORKDAY_STARTS_AT', '03:00'),
    'auto_close_at' => env('TIME_CONTROL_AUTO_CLOSE_AT', '21:00'),
    'max_daily_hours' => (int) env('TIME_CONTROL_MAX_DAILY_HOURS', 18),
];
