<?php

namespace App\Providers;

use App\Models\User;
use App\Services\Authorization\PermissionAccessService;
use App\Services\ManagerControl\LegacyEmployeeResolver;
use App\Services\ManagerControl\CatalogInsertConnection;
use App\Services\ManagerControl\ManagerControlException;
use App\Services\ManagerControl\ReadOnlyConnection;
use App\Services\ManagerControl\SqlServerReadOnlyConnection;
use App\Services\ManagerControl\SqlServerCatalogInsertConnection;
use App\Services\ManagerControl\SqlServerWriteConnection;
use App\Services\ManagerControl\WriteConnection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class ManagerControlServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Una sola conexión de solo lectura por petición.
        $this->app->scoped(ReadOnlyConnection::class, SqlServerReadOnlyConnection::class);
        // Conexión de escritura aparte (apagada mientras MANAGERCONTROL_WRITE_ENABLED=false).
        $this->app->scoped(WriteConnection::class, SqlServerWriteConnection::class);
        $this->app->scoped(CatalogInsertConnection::class, SqlServerCatalogInsertConnection::class);
    }

    public function boot(): void
    {
        // Pruebas con el legacy ManagerControl: solo Administrador.
        Gate::define('view-legacy-managercontrol', fn (User $user): bool => $user->isAdmin());

        // Control Gerencial (ManagerControl): cualquier usuario con acceso a Mi productividad ve SUS propias actividades.
        // El menú se oculta a los administradores, que entran por «Pruebas».
        Gate::define('view-legacy-own-hours', fn (User $user): bool => app(PermissionAccessService::class)->allows($user, 'time-control.productivity.view'));

        // Capturar, editar o dar de baja actividades de un colaborador de ManagerControl:
        // el administrador las de cualquiera; los demás, solo las suyas (su asignación o la coincidencia de nombre).
        Gate::define('edit-legacy-hours', function (User $user, int $employeeId): bool {
            if ($user->isAdmin()) {
                return true;
            }
            if (! app(PermissionAccessService::class)->allows($user, 'time-control.productivity.view')) {
                return false;
            }
            try {
                return (app(LegacyEmployeeResolver::class)->forUser($user)['employee']['id'] ?? null) === $employeeId;
            } catch (ManagerControlException) {
                return false;
            }
        });
    }
}
