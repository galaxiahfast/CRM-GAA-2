<?php

namespace App\Providers;

use App\Models\User;
use App\Models\UserOrganizationalProfile;
use App\Observers\UserHierarchyObserver;
use App\Observers\UserOrganizationalProfileHierarchyObserver;
use App\Services\Authorization\PermissionAccessService;
use Illuminate\Support\Facades\Blade;
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

        // Capa opt-in para módulos con permisos dinámicos. Se mantiene separada
        // de Gate para que el bypass histórico de Administrador no impida revocar
        // una sección cuando su permiso deje de existir.
        Blade::if('rolePermission', function (string $permissionKey): bool {
            return app(PermissionAccessService::class)->allows(auth()->user(), $permissionKey);
        });

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
            return app(PermissionAccessService::class)->allows($user, 'activities.manage');
        });

        /**
         * Permiso para ver la sección de Productividad
         */
        Gate::define('view-time-productivity', function (User $user) {
            return app(PermissionAccessService::class)->allows($user, 'time-control.productivity.view');
        });

        Gate::define('view-time-admin', fn (User $user): bool => app(PermissionAccessService::class)
            ->allows($user, 'time-control.supervision.view'));

        Gate::define('correct-time-tracking', fn (User $user): bool => app(PermissionAccessService::class)
            ->allows($user, 'time-control.supervision.view'));
    }
}
