<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * La productividad ahora se muestra debajo del cronómetro; los administradores conservan su página propia.
 */
class RedirectProductivityToActivityHub
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->isAdmin()) {
            return $next($request);
        }

        return redirect(route('time.index').'#productividad');
    }
}
