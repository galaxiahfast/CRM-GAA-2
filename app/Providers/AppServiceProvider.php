<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /**
         * 💡 SUPERADMIN BYPASS (Gate::before)
         * Si el usuario es Administrador (rol_id = 1), Laravel le otorgará 
         * acceso automático a absolutamente cualquier Gate del sistema sin restricciones.
         */
        Gate::before(function (User $user, string $ability) {
            if ($user->isAdmin()) {
                return true;
            }
        });

        /**
         * Permiso permanente para operar el reloj de control de tiempos (Auxiliares/Operadores)
         */
        Gate::define('operate-time-tracking', function (User $user) {
            // El administrador ya entra por el Gate::before superior.
            // Aquí definimos qué otros roles operativos (por ejemplo, el Rol ID 2) pueden usar el reloj.
            return in_array((int) $user->role_id, [1, 2]);
        });
    }
}