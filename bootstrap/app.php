<?php

use App\Models\User;
use App\Services\Notifications\SystemNotificationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'access.permission' => \App\Http\Middleware\EnsureAccessPermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->report(function (Throwable $exception): void {
            if ($exception instanceof ValidationException
                || $exception instanceof AuthenticationException
                || $exception instanceof AuthorizationException
                || $exception instanceof ModelNotFoundException
                || ($exception instanceof HttpExceptionInterface && $exception->getStatusCode() < 500)) {
                return;
            }

            try {
                $user = auth()->user();

                app(SystemNotificationService::class)->reportIncident(
                    $exception,
                    $user instanceof User ? $user : null,
                    [
                        'source' => app()->runningInConsole() ? 'console' : 'web',
                        'route' => request()?->route()?->getName(),
                        'method' => request()?->method(),
                    ],
                );
            } catch (Throwable) {
                // El registro nativo continúa aunque el buzón no esté disponible.
            }
        });

        $exceptions->render(function (Throwable $exception, Request $request) {
            // Estas excepciones forman parte del flujo normal de Laravel
            // (redirecciones de autenticación, validación, autorización y 404).
            // Dejarlas al renderer nativo evita convertir una sesión vencida
            // en una página 500 genérica.
            if ($exception instanceof ValidationException
                || $exception instanceof AuthenticationException
                || $exception instanceof AuthorizationException
                || $exception instanceof ModelNotFoundException) {
                return null;
            }

            $status = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;

            if ($status < 500 || $request->expectsJson() || $request->hasHeader('X-Livewire')) {
                return null;
            }

            try {
                $reference = app(SystemNotificationService::class)->referenceFor($exception);
            } catch (Throwable) {
                $reference = 'INC-NO-DISPONIBLE';
            }

            return response()->view('errors.500', compact('reference'), 500);
        });
    })->create();
