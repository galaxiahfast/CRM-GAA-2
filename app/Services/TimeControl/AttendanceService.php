<?php

namespace App\Services\TimeControl;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class AttendanceService
{
    /**
     * Procesa una colección de registros crudos de asistencia agrupándolos por día
     * y calculando las horas trabajadas mediante pares Entrada/Salida.
     *
     * @param Collection $records Registros de la BD filtrados por empleado y fechas
     * @param float $hourlyRate Pago por hora configurado
     * @param float $bonusAmount Bono diario aplicable
     * @return array
     */
    public function processPayroll(Collection $records, float $hourlyRate, float $bonusAmount): array
    {
        $diasDict = [];
        $detalles = [];

        // 1. Filtrar y agrupar marcas por fecha (Y-m-d)
        foreach ($records as $reg) {
            $detalles[] = [
                'fh' => $reg->auth_datetime,
                'id' => $reg->employee_id,
                'nombre' => $reg->person_name,
                'direction' => $reg->direction
            ];

            $dateStr = $reg->auth_date;
            $diasDict[$dateStr][] = Carbon::parse($reg->auth_datetime);
        }

        // Ordenar las fechas de forma descendente (último día primero) como en tu Python
        krsort($diasDict);

        $resumenNomina = [];
        $totalSegundosPeriodo = 0;
        $totalDecimalPeriodo = 0.0;
        $totalBonosPeriodo = 0.0;
        $totalAcumuladoSoloHoras = 0.0;
        $totalAcumuladoConBono = 0.0;

        // 2. Procesar día por día
        foreach ($diasDict as $fechaStr => $marcaciones) {
            // Ordenar marcas cronológicamente
            sort($marcaciones);
            $cantidadMarcaciones = count($marcaciones);
            $tiempoNeto = 0;
            $tieneImpares = false;

            if ($cantidadMarcaciones > 0) {
                if ($cantidadMarcaciones % 2 === 0) {
                    // Caso Par: Procesar parejas consecutivas completos
                    for ($i = 0; $i < $cantidadMarcaciones; $i += 2) {
                        $entrada = $marcaciones[$i];
                        $salida = $marcaciones[$i + 1];
                        if ($salida->greaterThan($entrada)) {
                            $tiempoNeto += $salida->diffInSeconds($entrada);
                        }
                    }
                } else {
                    // Caso Impar: Ignorar la última marca huérfana
                    $tieneImpares = true;
                    $cantidadPares = $cantidadMarcaciones - 1;
                    for ($i = 0; $i < $cantidadPares; $i += 2) {
                        $entrada = $marcaciones[$i];
                        $salida = $marcaciones[$i + 1];
                        if ($salida->greaterThan($entrada)) {
                            $tiempoNeto += $salida->diffInSeconds($entrada);
                        }
                    }
                    // Opcional: registrar advertencia en los logs de Laravel si se desea
                    // \Log::warning("Día {$fechaStr} tiene marcas impares para el empleado.");
                }
            }

            // Convertir a horas decimales
            $horasDecimal = $tiempoNeto / 3600;
            
            // Calcular pago base del día
            $pagoHoras = $horasDecimal * $hourlyRate;

            // Calcular bono (Lunes a Viernes [0-4 en python, 1-5 en Carbon] y >= 5 horas [18000 seg])
            $carbonFecha = Carbon::parse($fechaStr);
            if ($carbonFecha->isWeekday() && $tiempoNeto >= 18000) {
                $bonoDia = $bonusAmount;
            } else {
                $bonoDia = 0.0;
            }

            $totalDia = $pagoHoras + $bonoDia;

            // Guardar el resumen formateado de la jornada
            $resumenNomina[] = [
                'fecha' => $fechaStr,
                'neto' => $this->formatearSegundos($tiempoNeto),
                'horas_decimal' => number_format($horasDecimal, 2, '.', ''),
                'pago_horas' => '$' . number_format($pagoHoras, 2, '.', ','),
                'bono' => '$' . number_format($bonoDia, 2, '.', ','),
                'total' => '$' . number_format($totalDia, 2, '.', ','),
                'requiere_revision' => $tieneImpares
            ];

            // Acumular totales globales del período
            $totalSegundosPeriodo += $tiempoNeto;
            $totalDecimalPeriodo += $horasDecimal;
            $totalBonosPeriodo += $bonoDia;
            $totalAcumuladoSoloHoras += $pagoHoras;
            $totalAcumuladoConBono += $totalDia;
        }

        return [
            'detalles' => $detalles,
            'resumen' => $resumenNomina,
            'totales_pie' => [
                'tiempo' => $this->formatearSegundos($totalSegundosPeriodo),
                'decimal' => number_format($totalDecimalPeriodo, 2, '.', ''),
                'pago_h' => '$' . number_format($totalAcumuladoSoloHoras, 2, '.', ','),
                'bonos' => '$' . number_format($totalBonosPeriodo, 2, '.', ','),
                'general' => '$' . number_format($totalAcumuladoConBono, 2, '.', ',')
            ],
            'total_general' => '$' . number_format($totalAcumuladoConBono, 2, '.', ',')
        ];
    }

    /**
     * Formatea segundos a la cadena legible (HHh MMm SSs) armada en tu script.
     */
    private function formatearSegundos(int $segundos): string
    {
        if ($segundos <= 0) return "00h 00m 00s";
        $h = intdiv($segundos, 3600);
        $m = intdiv($segundos % 3600, 60);
        $s = $segundos % 60;
        return sprintf('%02dh %02dm %02ds', $h, $m, $s);
    }
}