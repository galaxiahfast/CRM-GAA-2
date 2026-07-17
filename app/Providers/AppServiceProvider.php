<?php

namespace App\Providers;

use App\Models\User;
use App\Models\UserOrganizationalProfile;
use App\Observers\UserHierarchyObserver;
use App\Observers\UserOrganizationalProfileHierarchyObserver;
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
        User::observe(UserHierarchyObserver::class);
        UserOrganizationalProfile::observe(UserOrganizationalProfileHierarchyObserver::class);

        /**
         * 💡 SUPERADMIN BYPASS (Gate::before)
         * Si el usuario es Administrador, saltarse todas las comprobaciones.
         */
        Gate::before(function (User $user, string $ability) {
            // Evaluamos tanto por método como por ID de rol clásico (1 = Administrador)
            if ((method_exists($user, 'isAdmin') && $user->isAdmin()) || (int) $user->role_id === 1) {
                return true;
            }
        });

        /**
         * Permiso para operar el reloj de control de tiempos (Cualquier usuario logueado)
         */
        Gate::define('operate-time-tracking', function (User $user) {
            // Permitir a Administrador, Coordinador, Contador, Auxiliar (IDs del 1 al 5)
            $roleName = optional($user->role)->role ?? optional($user->role)->name;

            return in_array((int) $user->role_id, [1, 2, 3, 4, 5], true)
                || in_array($roleName, ['Administrador', 'Coordinador', 'Contador', 'Auxiliar'], true);
        });

        /**
         * Permiso para ver la sección de Productividad
         */
        Gate::define('view-time-productivity', function (User $user) {
            // Permitir a Administrador, Coordinador, Contador, Auxiliar (IDs del 1 al 5)
            $roleName = optional($user->role)->role ?? optional($user->role)->name;

            return in_array((int) $user->role_id, [1, 2, 3, 4, 5], true)
                || in_array($roleName, ['Administrador', 'Coordinador', 'Contador', 'Auxiliar'], true);
        });
    }
}
