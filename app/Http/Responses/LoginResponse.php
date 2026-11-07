<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    /**
     * Quien entra siempre llega a la pantalla de inicio, aunque haya intentado abrir otra ruta protegida antes del login.
     */
    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return response()->json(['two_factor' => false]);
        }

        $request->session()->forget('url.intended');

        return redirect()->to(route('inicio', absolute: false));
    }
}
