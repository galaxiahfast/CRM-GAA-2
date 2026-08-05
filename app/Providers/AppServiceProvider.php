<?php

namespace App\Providers;

use App\Models\User;
use App\Models\UserOrganizationalProfile;
use App\Observers\UserHierarchyObserver;
use App\Observers\UserOrganizationalProfileHierarchyObserver;
use App\Services\Authorization\PermissionAccessService;
use App\Services\Notifications\SystemNotificationService;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Throwable;

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

        $this->registerSystemNotificationListeners();

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

    private function registerSystemNotificationListeners(): void
    {
        Event::listen(Login::class, function (Login $event): void {
            if ($event->user instanceof User) {
                app(SystemNotificationService::class)->loginSucceeded(
                    $event->user,
                    request()->ip(),
                    request()->userAgent(),
                );
            }
        });

        Event::listen(Failed::class, function (Failed $event): void {
            try {
                $user = $event->user instanceof User
                    ? $event->user
                    : User::query()->where('email', $event->credentials['email'] ?? null)->first();

                if ($user) {
                    app(SystemNotificationService::class)->loginFailed($user, request()->ip());
                }
            } catch (Throwable) {
                // La alerta nunca debe interrumpir el flujo de autenticación.
            }
        });

        Event::listen(JobFailed::class, function (JobFailed $event): void {
            app(SystemNotificationService::class)->reportIncident($event->exception, context: [
                'source' => 'queue',
                'connection' => $event->connectionName,
                'queue' => $event->job->getQueue(),
                'job' => $event->job->resolveName(),
            ]);
        });

        Event::listen(ScheduledTaskFailed::class, function (ScheduledTaskFailed $event): void {
            app(SystemNotificationService::class)->reportIncident($event->exception, context: [
                'source' => 'scheduler',
                'task' => $event->task->getSummaryForDisplay(),
            ]);
        });
    }
}
