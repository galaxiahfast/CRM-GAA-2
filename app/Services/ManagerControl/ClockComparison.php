<?php

namespace App\Services\ManagerControl;

use App\Models\ManagerControlEmployeeLink;
use App\Models\User;
use App\Services\TimeControl\AttendanceService;
use App\Services\TimeControl\AttendanceSettingsService;

/**
 * Tiempo que cada día marca el reloj checador del CRM (tabla control_de_horas, por ID de checador del usuario), para compararlo
 * con el tiempo capturado en ManagerControl. Solo lectura: usa el mismo cálculo del módulo Reloj Checador (marcas por pares,
 * con las correcciones de día ya guardadas), así lo que se ve aquí coincide con lo que se ve allá.
 */
class ClockComparison
{
    public function __construct(private readonly AttendanceService $attendance, private readonly AttendanceSettingsService $settings) {}

    /** Usuario del CRM asignado a un colaborador de ManagerControl. */
    public function userForLegacyEmployee(int $legacyEmployeeId): ?User
    {
        return ManagerControlEmployeeLink::query()->where('legacy_employee_id', $legacyEmployeeId)->first()?->user;
    }

    /**
     * @return array{state: string, days: array<string, array{seconds: int, review: bool, marks: int}>}
     *                                                                                                  state: ok | no_user | no_clock_id
     */
    public function days(?User $user, string $from, string $to): array
    {
        if ($user === null) {
            return ['state' => 'no_user', 'days' => []];
        }
        $clockId = trim((string) $user->employee_id);
        if ($clockId === '') {
            return ['state' => 'no_clock_id', 'days' => []];
        }

        $profile = $user->loadMissing('activeOrganizationalProfile')->activeOrganizationalProfile;
        $settings = $this->settings->getSettings(
            $clockId,
            $profile ? (float) $profile->hourly_rate : null,
            $profile ? (float) $profile->food_allowance : null,
        );
        $result = $this->attendance->processPayroll($this->attendance->fetchRecords($clockId, $from, $to), $settings);

        $days = [];
        foreach ($result['resumen'] as $row) {
            $days[(string) $row['fecha']] = [
                'seconds' => (int) $row['tiempo_segundos'],
                'review' => (bool) $row['requiere_revision'],
                'marks' => count($row['marks']),
            ];
        }

        return ['state' => 'ok', 'days' => $days];
    }
}
