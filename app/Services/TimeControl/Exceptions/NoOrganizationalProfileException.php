<?php

namespace App\Services\TimeControl\Exceptions;

use RuntimeException;

/** Se lanza cuando el usuario no tiene un perfil organizacional activo. */
class NoOrganizationalProfileException extends RuntimeException
{
    public function __construct(string $message = 'No tienes un perfil organizacional activo (puesto/área). Contacta al administrador.')
    {
        parent::__construct($message);
    }
}
