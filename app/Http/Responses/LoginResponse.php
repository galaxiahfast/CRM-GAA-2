<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    /**
     * Devuelve al usuario a la ruta protegida que solicitó originalmente o,
     * cuando llega directamente al login, a la pantalla de inicio ligera.
     */
    public function toResponse($request)
    {
        return $request->wantsJson()
            ? response()->json(['two_factor' => false])
            : redirect()->intended(route('inicio', absolute: false));
    }
}
