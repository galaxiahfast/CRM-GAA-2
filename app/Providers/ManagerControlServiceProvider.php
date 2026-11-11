<?php

namespace App\Providers;

use App\Models\User;
use App\Services\Authorization\PermissionAccessService;
use App\Services\ManagerControl\ReadOnlyConnection;
use App\Services\ManagerControl\SqlServerReadOnlyConnection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class ManagerControlServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Una sola conexión de solo lectura por petición.
        $this->app->scoped(ReadOnlyConnection::class, SqlServerReadOnlyConnection::class);
    }

    public function boot(): void
    {
        // Pruebas con el legacy ManagerControl: solo Administrador.
        Gate::define('view-legacy-managercontrol', fn (User $user): bool => $user->isAdmin());

        // Control Gerencial (ManagerControl): cualquier usuario con acceso a Mi productividad ve SUS propias actividades.
        // El menú se oculta a los administradores, que entran por «Pruebas».
        Gate::define('view-legacy-own-hours', fn (User $user): bool => app(PermissionAccessService::class)->allows($user, 'time-control.productivity.view'));
    }
}
