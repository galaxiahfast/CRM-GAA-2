<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', '/auth/google/callback'),
        // Dominios de correo que pueden entrar con Google, separados por comas. Si está vacío, el botón no aparece.
        'allowed_domains' => array_values(array_filter(array_map(
            static fn (string $domain): string => strtolower(trim($domain)),
            explode(',', (string) env('GOOGLE_ALLOWED_DOMAINS', '')),
        ))),
        // Cuentas nuevas: pago por hora y apoyo de comida iniciales (el administrador los ajusta después)
        'new_user_hourly_rate' => env('GOOGLE_NEW_USER_HOURLY_RATE', 25),
        'new_user_food_allowance' => env('GOOGLE_NEW_USER_FOOD_ALLOWANCE', 50),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'biometric' => [
        'ip' => env('BIOMETRIC_IP', '192.168.2.239'),
        'username' => env('BIOMETRIC_USERNAME', 'admin'),
        'password' => env('BIOMETRIC_PASSWORD'),
    ],

];
