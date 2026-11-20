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
    /** Mismos nombres que las tarjetas de Inicio. Lo más específico va primero. */
    private const SECTIONS = [
        'inicio' => 'Inicio',
        'organigrama.' => 'Organigrama',
        'dashboard' => 'Panel General',
        'dashboard.' => 'Panel General',
        'customers.' => 'Clientes',
        'time.admin.attendance' => 'Reloj Checador',
        'time.attendance' => 'Reloj Checador',
        'time.admin.' => 'Supervisión de Horas',
        'time.dashboard' => 'Gráficos Generales',
        'time.reports' => 'Mi productividad',
        'time.' => 'Cronómetro',
        'testing.own-hours.' => 'Manager Control',
        'testing.managercontrol.' => 'Manager Control',
        'activity-backups.' => 'Gestión de Respaldos',
        'confirmaciones.' => 'Confirmaciones de Saldos',
        'administracion.' => 'Administración',
        'soporte.hoja-entrega' => 'Hoja de Entrega',
        'soporte.reportes-tecnicos' => 'Reportes de Soporte',
        'soporte.reportes-respaldos' => 'Reporte de Respaldos',
        'soporte.preguntas' => 'Preguntas',
        'soporte.ticket' => 'Chat de Soporte',
        'soporte.' => 'Soporte',
        'profile.' => 'Mi Cuenta',
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
            ? $this->sectionFor($request)
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

    private function sectionFor(Request $request): ?string
    {
        $routeName = (string) $request->route()?->getName();

        // Conciliación: la misma ruta sirve a la bancaria y a la de OCR
        if (str_starts_with($routeName, 'bank-reconciliation.')) {
            return $request->route('tool') === 'ocr' ? 'Conciliación OCR' : 'Conciliación Bancaria';
        }

        foreach (self::SECTIONS as $prefix => $label) {
            if ($routeName === $prefix || (str_ends_with($prefix, '.') && str_starts_with($routeName, $prefix))) {
                return $label;
            }
        }

        return null;
    }
}
