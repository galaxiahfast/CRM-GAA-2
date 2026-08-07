<?php

namespace App\Services\TimeControl;

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

        $hourly = $stored['hourly_rate']
            ?? $profileHourly
            ?? self::DEFAULT_HOURLY_RATE;

        $bonus = $stored['bonus_amount']
            ?? $profileBonus
            ?? self::DEFAULT_BONUS_AMOUNT;

        return [
            'hourly_rate' => (float) $hourly,
            'bonus_amount' => (float) $bonus,
            'day_overrides' => $stored['day_overrides'] ?? [],
        ];
    }

    public function saveGeneral(string $employeeId, float $hourlyRate, float $bonusAmount): void
    {
        $this->writeFile($employeeId, [
            'hourly_rate' => round($hourlyRate, 2),
            'bonus_amount' => round($bonusAmount, 2),
            'day_overrides' => [],
        ]);
    }

    /** @param list<string> $marksBefore @param list<string> $marksAfter */
    public function saveDayOverride(
        string $employeeId,
        string $date,
        float $dailyPayAmount,
        float $bonusAmount,
        string $comment,
        ?int $adminId = null,
        array $marksBefore = [],
        array $marksAfter = [],
    ): void
    {
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
            'daily_pay_before' => $previous['daily_pay_amount'] ?? null,
            'daily_pay_after' => round($dailyPayAmount, 2),
            'bonus_before' => $previous['bonus_amount'] ?? null,
            'bonus_after' => round($bonusAmount, 2),
        ];

        $dayOverrides[$date] = [
            'hourly_rate' => (float) ($previous['hourly_rate'] ?? $stored['hourly_rate'] ?? self::DEFAULT_HOURLY_RATE),
            'daily_pay_amount' => round($dailyPayAmount, 2),
            'bonus_amount' => round($bonusAmount, 2),
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
     * Resuelve tarifa y bono para un día concreto según jerarquía de modificaciones.
     *
     * @param  array{hourly_rate: float, bonus_amount: float, day_overrides: array<string, array{hourly_rate: float, bonus_amount: float, modified_individual: bool}>}  $settings
     * @return array{hourly_rate: float, bonus_amount: float, daily_pay_amount: ?float, modified_individual: bool, comment: ?string}
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
