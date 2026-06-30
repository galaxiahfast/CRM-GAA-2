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
            
            // Forzamos el parseo limpio del timestamp completo
            $timestamp = Carbon::parse($reg->auth_datetime);

            $diasDict[$dateStr][] = [
                'objeto' => $timestamp,
                'hora_txt' => $timestamp->format('H:i:s')
            ];
        }

        // Ordenar las fechas de forma descendente (último día primero)
        krsort($diasDict);

        $resumenNomina = [];
        $totalSegundosPeriodo = 0;
        $totalDecimalPeriodo = 0.0;
        $totalBonosPeriodo = 0.0;
        $totalAcumuladoSoloHoras = 0.0;
        $totalAcumuladoConBono = 0.0;

        // 2. Procesar día por día
        foreach ($diasDict as $fechaStr => $items) {
            
            // GARANTÍA DE ORDEN: Aseguramos el orden cronológico ascendente del día (de la mañana a la noche)
            usort($items, function ($a, $b) {
                return $a['objeto']->timestamp <=> $b['objeto']->timestamp;
            });

            // Extraer las marcas formateadas para la vista y los objetos para el cálculo
            $marcasImprimir = array_column($items, 'hora_txt');
            $marcaciones = array_column($items, 'objeto');

            $cantidadMarcaciones = count($marcaciones);
            $tiempoNeto = 0;
            $tieneImpares = false;

            if ($cantidadMarcaciones > 0) {
                // Si es impar, ignoramos el último registro huérfano para el cálculo numérico
                if ($cantidadMarcaciones % 2 !== 0) {
                    $tieneImpares = true;
                    $cantidadMarcaciones = $cantidadMarcaciones - 1;
                }

                // Procesar estrictamente por parejas consecutivas (0-1, 2-3, 4-5...)
                for ($i = 0; $i < $cantidadMarcaciones; $i += 2) {
                    $entrada = $marcaciones[$i];
                    $salida = $marcaciones[$i + 1];
                    
                    if ($salida->greaterThan($entrada)) {
                        // Forzamos la diferencia absoluta en segundos de forma explícita
                        $tiempoNeto += abs($salida->diffInSeconds($entrada));
                    }
                }
            }

            // Convertir a horas decimales de forma positiva garantizada
            $horasDecimal = max(0.0, $tiempoNeto / 3600);
            
            // Calcular pago base del día
            $pagoHoras = $horasDecimal * $hourlyRate;

            // Calcular bono (Lunes a Viernes y >= 5 horas [18000 seg])
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
                'requiere_revision' => $tieneImpares,
                'detalles_marcas' => implode(', ', $marcasImprimir)
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
     * Formatea segundos a la cadena legible (HHh MMm SSs).
     */
    private function formatearSegundos(int $segundos): string
    {
        // Forzamos que nunca procese negativos en la renderización de texto
        $segundos = max(0, $segundos);
        
        $h = intdiv($segundos, 3600);
        $m = intdiv($segundos % 3600, 60);
        $s = $segundos % 60;
        return sprintf('%02dh %02dm %02ds', $h, $m, $s);
    }
}