<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Hash Driver
    |--------------------------------------------------------------------------
    */

    'driver' => env('HASH_DRIVER', 'bcrypt'),

    /*
    |--------------------------------------------------------------------------
    | Bcrypt Options
    |--------------------------------------------------------------------------
    |
    | Con 10 rondas la verificación tarda unos 50 ms (con 12, unos 200 ms). Es un costo de bcrypt
    | recomendado y mantiene el inicio de sesión por debajo de 1 s; las contraseñas guardadas con
    | más rondas se actualizan solas la próxima vez que la persona inicia sesión.
    |
    */

    'bcrypt' => [
        'rounds' => env('BCRYPT_ROUNDS', 10),
        'verify' => env('HASH_VERIFY', true),
        'limit' => env('BCRYPT_LIMIT', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Argon Options
    |--------------------------------------------------------------------------
    */

    'argon' => [
        'memory' => env('ARGON_MEMORY', 65536),
        'threads' => env('ARGON_THREADS', 1),
        'time' => env('ARGON_TIME', 4),
        'verify' => env('HASH_VERIFY', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rehash On Login
    |--------------------------------------------------------------------------
    */

    'rehash_on_login' => true,

];
