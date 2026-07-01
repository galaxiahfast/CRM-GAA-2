<?php

namespace App\Services\TimeControl;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class AttendanceService
{
    public function __construct(
        private AttendanceSettingsService $settingsService,
    ) {}

    /**
     * Procesa registros crudos de asistencia agrupados por día.
     *
     * @param  array{hourly_rate: float, bonus_amount: float, day_overrides: array<string, array{hourly_rate: float, bonus_amount: float, modified_individual: bool}>}  $settings
     */
    public function processPayroll(Collection $records, array $settings): array
    {
        $diasDict = [];
        $detalles = [];

        foreach ($records as $reg) {
            $detalles[] = [
                'fh' => $reg->auth_datetime,
                'id' => $reg->employee_id,
                'nombre' => $reg->person_name,
                'direction' => $reg->direction,
            ];

            $dateStr = $reg->auth_date;
            $timestamp = Carbon::parse($reg->auth_datetime);

            $diasDict[$dateStr][] = [
                'objeto' => $timestamp,
                'hora_txt' => $timestamp->format('H:i:s'),
            ];
        }

        krsort($diasDict);

        $resumenNomina = [];
        $totalSegundosPeriodo = 0;
        $totalDecimalPeriodo = 0.0;
        $totalPagoBasePeriodo = 0.0;
        $totalBonosPeriodo = 0.0;
        $totalGeneralPeriodo = 0.0;

        foreach ($diasDict as $fechaStr => $items) {
            usort($items, fn ($a, $b) => $a['objeto']->timestamp <=> $b['objeto']->timestamp);

            $marcasImprimir = array_column($items, 'hora_txt');
            $marcaciones = array_column($items, 'objeto');
            $cantidadMarcaciones = count($marcaciones);
            $tieneImpares = $cantidadMarcaciones % 2 !== 0;
            $esCorrecto = ! $tieneImpares && $cantidadMarcaciones > 0;

            $tiempoNeto = 0;

            if ($esCorrecto) {
                for ($i = 0; $i < $cantidadMarcaciones; $i += 2) {
                    $entrada = $marcaciones[$i];
                    $salida = $marcaciones[$i + 1];

                    if ($salida->greaterThan($entrada)) {
                        $tiempoNeto += abs($salida->diffInSeconds($entrada));
                    }
                }
            }

            $horasDecimal = $esCorrecto ? round($tiempoNeto / 3600, 2) : 0.0;

            $rates = $this->settingsService->resolveForDay($settings, $fechaStr, $esCorrecto);
            $hourlyRate = $rates['hourly_rate'];
            $bonoDia = $rates['bonus_amount'];

            $pagoBase = $esCorrecto ? round($horasDecimal * $hourlyRate, 2) : 0.0;
            $totalDia = $esCorrecto ? round($pagoBase + $bonoDia, 2) : 0.0;

            $resumenNomina[] = [
                'fecha' => $fechaStr,
                'neto' => $this->formatearSegundos($tiempoNeto),
                'horas_decimal' => number_format($horasDecimal, 2, '.', ''),
                'pago_horas' => '$'.number_format($pagoBase, 2, '.', ','),
                'bono' => '$'.number_format($bonoDia, 2, '.', ','),
                'total' => '$'.number_format($totalDia, 2, '.', ','),
                'requiere_revision' => $tieneImpares,
                'estado' => $tieneImpares ? 'Impar / Revisar' : 'Correcto',
                'modified_individual' => $rates['modified_individual'],
                'hourly_rate' => $hourlyRate,
                'detalles_marcas' => implode(', ', $marcasImprimir),
                'tiempo_segundos' => $tiempoNeto,
                'pago_base_raw' => $pagoBase,
                'bono_raw' => $bonoDia,
                'total_raw' => $totalDia,
            ];

            if ($esCorrecto) {
                $totalSegundosPeriodo += $tiempoNeto;
                $totalDecimalPeriodo += $horasDecimal;
                $totalPagoBasePeriodo += $pagoBase;
                $totalBonosPeriodo += $bonoDia;
                $totalGeneralPeriodo += $totalDia;
            }
        }

        return [
            'detalles' => $detalles,
            'resumen' => $resumenNomina,
            'settings' => [
                'hourly_rate' => $settings['hourly_rate'],
                'bonus_amount' => $settings['bonus_amount'],
            ],
            'totales_pie' => [
                'tiempo' => $this->formatearSegundos($totalSegundosPeriodo),
                'decimal' => number_format($totalDecimalPeriodo, 2, '.', ''),
                'pago_h' => '$'.number_format($totalPagoBasePeriodo, 2, '.', ','),
                'bonos' => '$'.number_format($totalBonosPeriodo, 2, '.', ','),
                'general' => '$'.number_format($totalGeneralPeriodo, 2, '.', ','),
            ],
            'total_general' => '$'.number_format($totalGeneralPeriodo, 2, '.', ','),
        ];
    }

    /**
     * Obtiene registros normalizados de control_de_horas para un empleado y rango.
     */
    public function fetchRecords(string $employeeId, string $from, string $to): Collection
    {
        return \Illuminate\Support\Facades\DB::table('control_de_horas')
            ->where('employeeID', $employeeId)
            ->whereBetween('authDate', [$from, $to])
            ->orderBy('authDateTime', 'asc')
            ->get()
            ->map(fn ($reg) => (object) [
                'auth_datetime' => $reg->authDateTime,
                'employee_id' => $reg->employeeID,
                'person_name' => $reg->personName,
                'direction' => $reg->direction ?? null,
                'auth_date' => $reg->authDate,
            ]);
    }

    private function formatearSegundos(int $segundos): string
    {
        $segundos = max(0, $segundos);

        $h = intdiv($segundos, 3600);
        $m = intdiv($segundos % 3600, 60);
        $s = $segundos % 60;

        return sprintf('%02dh %02dm %02ds', $h, $m, $s);
    }
}
