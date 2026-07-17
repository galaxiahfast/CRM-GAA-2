<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Las respuestas de Livewire son JSON. Un warning de PHP no debe inyectarse
// en el cuerpo de la respuesta y volverla inválida para el navegador.
ini_set('display_errors', '0');

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
(require_once __DIR__.'/../bootstrap/app.php')
    ->handleRequest(Request::capture());
