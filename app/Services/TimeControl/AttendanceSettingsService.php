<?php

namespace App\Services\TimeControl;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * Persistencia de tarifas generales y ajustes por día del checador.
 * Usa archivos JSON en storage — no altera tablas de la base de datos.
 */
class AttendanceSettingsService
{
    public const DEFAULT_HOURLY_RATE = 20.0;

    public const DEFAULT_BONUS_AMOUNT = 50.0;

    private const STORAGE_DIR = 'checador_settings';

    /**
     * @return array{hourly_rate: float, bonus_amount: float, day_overrides: array<string, array{hourly_rate: float, bonus_amount: float, modified_individual: bool}>}
     */
    public function getSettings(string $employeeId, ?float $profileHourly = null, ?float $profileBonus = null): array
    {
        $stored = $this->readFile($employeeId);

        $hourly = $profileHourly
            ?? $stored['hourly_rate']
            ?? self::DEFAULT_HOURLY_RATE;

        $bonus = $profileBonus
            ?? $stored['bonus_amount']
            ?? self::DEFAULT_BONUS_AMOUNT;

        return [
            'hourly_rate' => (float) $hourly,
            'bonus_amount' => (float) $bonus,
            'day_overrides' => $stored['day_overrides'] ?? [],
        ];
    }

    public function saveGeneral(
        string $employeeId,
        float $hourlyRate,
        float $bonusAmount,
        bool $preserveDayOverrides = false,
    ): void {
        $stored = $preserveDayOverrides ? $this->readFile($employeeId) : [];

        $this->writeFile($employeeId, [
            'hourly_rate' => round($hourlyRate, 2),
            'bonus_amount' => round($bonusAmount, 2),
            'day_overrides' => $stored['day_overrides'] ?? [],
        ]);
    }

    /**
     * Guarda una tarifa por hora y bono de comida específicos para el día.
     * Los ajustes antiguos con daily_pay_amount siguen siendo legibles en resolveForDay().
     *
     * @param  list<string>  $marksBefore
     * @param  list<string>  $marksAfter
     */
    public function saveDayOverride(
        string $employeeId,
        string $date,
        float $hourlyRate,
        float $bonusAmount,
        string $comment,
        ?int $adminId = null,
        array $marksBefore = [],
        array $marksAfter = [],
        float $extraBonusAmount = 0.0,
    ): void {
        // La regla de fin de semana se aplica también aquí para cubrir cualquier
        // entrada alternativa (API, Livewire o futuros consumidores del servicio).
        $bonusAmount = Carbon::parse($date)->isWeekend() ? 0.0 : $bonusAmount;

        $stored = $this->readFile($employeeId);

        $dayOverrides = $stored['day_overrides'] ?? [];
        $previous = $dayOverrides[$date] ?? [];
        $history = is_array($previous['history'] ?? null) ? $previous['history'] : [];
        $history[] = [
            'admin_id' => $adminId,
            'comment' => $comment,
            'changed_at' => now()->toIso8601String(),
            'marks_before' => array_values($marksBefore),
            'marks_after' => array_values($marksAfter),
            'hourly_rate_before' => $previous['hourly_rate'] ?? $stored['hourly_rate'] ?? self::DEFAULT_HOURLY_RATE,
            'hourly_rate_after' => round($hourlyRate, 2),
            'bonus_before' => $previous['bonus_amount'] ?? null,
            'bonus_after' => round($bonusAmount, 2),
            'extra_bonus_before' => $previous['extra_bonus_amount'] ?? 0.0,
            'extra_bonus_after' => round(max(0, $extraBonusAmount), 2),
        ];

        $dayOverrides[$date] = [
            'hourly_rate' => round($hourlyRate, 2),
            'bonus_amount' => round($bonusAmount, 2),
            'extra_bonus_amount' => round(max(0, $extraBonusAmount), 2),
            // Las marcas corregidas son una capa administrativa sobre el espejo
            // del biométrico. Guardarlas aquí evita que la sincronización vuelva a
            // mostrar las marcas originales del dispositivo.
            'marks' => array_values($marksAfter),
            'modified_individual' => true,
            'comment' => $comment,
            'modified_by' => $adminId,
            'modified_at' => now()->toIso8601String(),
            'history' => $history,
        ];

        $this->writeFile($employeeId, [
            'hourly_rate' => $stored['hourly_rate'] ?? self::DEFAULT_HOURLY_RATE,
            'bonus_amount' => $stored['bonus_amount'] ?? self::DEFAULT_BONUS_AMOUNT,
            'day_overrides' => $dayOverrides,
        ]);
    }

    /**
     * Devuelve las marcas administrativas del día cuando existe una corrección.
     *
     * @return list<string>|null
     */
    public function correctedMarksForDay(array $settings, string $date): ?array
    {
        $override = $settings['day_overrides'][$date] ?? null;
        $marks = is_array($override) ? ($override['marks'] ?? null) : null;

        // Compatibilidad con correcciones hechas antes de que las marcas se
        // guardaran como valor principal: el historial ya contenía el resultado.
        if (! is_array($marks) && is_array($override['history'] ?? null)) {
            $history = $override['history'];
            $lastChange = end($history);
            $marks = is_array($lastChange) ? ($lastChange['marks_after'] ?? null) : null;
        }

        if (! is_array($marks)) {
            return null;
        }

        return collect($marks)
            ->filter(fn ($mark) => is_string($mark) && preg_match('/^\d{2}:\d{2}:\d{2}$/', $mark))
            ->sort()
            ->values()
            ->all();
    }

    /**
     * Devuelve el historial persistido de correcciones de un colaborador.
     *
     * @return list<array<string, mixed>>
     */
    public function changeHistoryForEmployee(string $employeeId): array
    {
        $dayOverrides = $this->readFile($employeeId)['day_overrides'] ?? [];
        $changes = [];

        foreach ($dayOverrides as $date => $override) {
            if (! is_array($override)) {
                continue;
            }

            foreach (($override['history'] ?? []) as $change) {
                if (! is_array($change)) {
                    continue;
                }

                $changes[] = array_merge($change, [
                    'date' => (string) $date,
                    'employee_id' => $employeeId,
                ]);
            }
        }

        return $changes;
    }

    /**
     * Resuelve tarifa y bono para un día concreto según jerarquía de modificaciones.
     *
     * @param  array{hourly_rate: float, bonus_amount: float, day_overrides: array<string, array{hourly_rate: float, bonus_amount: float, modified_individual: bool}>}  $settings
     * @return array{hourly_rate: float, bonus_amount: float, extra_bonus_amount: float, daily_pay_amount: ?float, modified_individual: bool, comment: ?string}
     */
    public function resolveForDay(array $settings, string $date, bool $isCorrecto): array
    {
        $override = $settings['day_overrides'][$date] ?? null;

        $hourlyRate = $override['hourly_rate'] ?? $settings['hourly_rate'];
        $bonusAmount = $isCorrecto
            ? ($override['bonus_amount'] ?? $settings['bonus_amount'])
            : 0.0;

        return [
            'hourly_rate' => (float) $hourlyRate,
            'bonus_amount' => (float) $bonusAmount,
            'extra_bonus_amount' => (float) ($override['extra_bonus_amount'] ?? 0.0),
            'daily_pay_amount' => array_key_exists('daily_pay_amount', (array) $override)
                ? (float) $override['daily_pay_amount']
                : null,
            'modified_individual' => (bool) ($override['modified_individual'] ?? false),
            'comment' => isset($override['comment']) ? (string) $override['comment'] : null,
        ];
    }

    /** @return array<string, mixed> */
    private function readFile(string $employeeId): array
    {
        $path = $this->pathFor($employeeId);

        if (! Storage::disk('local')->exists($path)) {
            return [];
        }

        $contents = Storage::disk('local')->get($path);
        $decoded = json_decode($contents, true);

        return is_array($decoded) ? $decoded : [];
    }

    /** @param array<string, mixed> $data */
    private function writeFile(string $employeeId, array $data): void
    {
        Storage::disk('local')->makeDirectory(self::STORAGE_DIR);
        Storage::disk('local')->put(
            $this->pathFor($employeeId),
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    private function pathFor(string $employeeId): string
    {
        $safeId = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $employeeId) ?: 'unknown';

        return self::STORAGE_DIR.'/'.$safeId.'.json';
    }
}
