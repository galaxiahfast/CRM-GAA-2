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
        // Control de Horas: el Administrador queda excluido del registro
        // operativo (reglas 4.1, 8.8, 12.4) y es el único que puede corregir
        // y ver la supervisión global (8.5, 10.2).
        Gate::define('operate-time-tracking', fn (User $user) => ! $user->isAdmin());
        Gate::define('correct-time-tracking', fn (User $user) => $user->isAdmin());
        Gate::define('view-time-admin', fn (User $user) => $user->isAdmin());
    }
}
