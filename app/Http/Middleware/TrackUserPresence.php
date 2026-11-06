<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Marca la última actividad (y el apartado donde está) de la persona autenticada para saber quién está en línea.
 * Escribe como máximo una vez por minuto, o al cambiar de apartado, con una sola consulta ligera.
 */
class TrackUserPresence
{
    /** Nombre de ruta (o prefijo terminado en punto) => apartado del menú. */
    private const SECTIONS = [
        'inicio' => 'Inicio',
        'dashboard' => 'Panel general',
        'bank-reconciliation.' => 'Conciliación bancaria',
        'organigrama.' => 'Organigrama',
        'administracion.' => 'Administración',
        'time.admin.' => 'Supervisión de horas',
        'time.attendance' => 'Reloj Checador',
        'time.reports' => 'Mi productividad',
        'time.' => 'Registro de actividades',
        'activity-backups.' => 'Gestión de respaldos',
        'soporte.hoja-entrega' => 'Hoja de entrega',
        'soporte.reportes-tecnicos' => 'Reportes de soporte',
        'soporte.reportes-respaldos' => 'Reporte de respaldos',
        'soporte.preguntas' => 'Preguntas',
        'soporte.' => 'Soporte técnico',
        'profile.' => 'Mi cuenta',
        'profiles.' => 'Perfiles',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User) {
            try {
                $this->track($request, $user);
            } catch (\Throwable) {
                // La presencia nunca debe impedir que la página cargue.
            }
        }

        return $next($request);
    }

    private function track(Request $request, User $user): void
    {
        // Solo las visitas reales a una página cambian el apartado; las actualizaciones de Livewire no.
        $section = $request->isMethod('GET') && ! $request->expectsJson() && ! $request->headers->has('X-Livewire')
            ? $this->sectionFor((string) $request->route()?->getName())
            : null;

        $stale = ! $user->last_seen_at || $user->last_seen_at->lt(now()->subMinute());
        $changed = $section !== null && $section !== $user->last_seen_section;

        if (! $stale && ! $changed) {
            return;
        }

        $values = ['last_seen_at' => now()];
        if ($section !== null) {
            $values['last_seen_section'] = $section;
        }

        User::query()->whereKey($user->getKey())->update($values);
        $user->forceFill($values)->syncOriginal();
    }

    private function sectionFor(string $routeName): ?string
    {
        foreach (self::SECTIONS as $prefix => $label) {
            if ($routeName === $prefix || (str_ends_with($prefix, '.') && str_starts_with($routeName, $prefix))) {
                return $label;
            }
        }

        return null;
    }
}
