<?php

namespace App\Services\TimeControl\Exceptions;

use RuntimeException;

/** Se lanza cuando el usuario ya tiene un cronómetro activo (regla 8.2). */
class ActiveEntryException extends RuntimeException
{
    public function __construct(string $message = 'Ya tienes una actividad en progreso o pausada. Finalízala antes de iniciar otra.')
    {
        parent::__construct($message);
    }
}
