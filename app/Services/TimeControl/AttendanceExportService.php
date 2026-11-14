<?php

namespace App\Services\TimeControl;

use App\Models\User;
use App\Services\Reports\ReportData;
use App\Services\Reports\ReportSection;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceExportService
{
    /** @var list<string> */
    private const COLUMNS = [
        'Fecha jornada',
        'Marcas / chequeos',
        'Tiempo neto',
        'Hrs. decimales',
        'Pago base',
        'Comida',
        'Bono',
        'Total del día',
        'Estado',
    ];

    /**
     * Construye los informes de selección del checador con la misma semántica
     * que Supervisión de horas: individual, grupal (un bloque por persona) y
     * general (consolidado de la selección).
     *
     * @param  Collection<int, User>  $users
     */
    public function selectionReport(
        string $mode,
        Collection $users,
        string $from,
        string $to,
        AttendanceService $attendanceService,
        AttendanceSettingsService $settingsService,
    ): ReportData {
        abort_unless(in_array($mode, ['individual', 'group', 'general'], true), 404);

        $reports = $users->map(function (User $user) use ($from, $to, $attendanceService, $settingsService): array {
            $profile = $user->activeOrganizationalProfile;
            $settings = $settingsService->getSettings(
                (string) $user->employee_id,
                $profile ? (float) $profile->hourly_rate : null,
                $profile ? (float) $profile->food_allowance : null,
            );
            $records = $attendanceService->fetchRecords((string) $user->employee_id, $from, $to);

            return [
                'user' => $user,
                'result' => $attendanceService->processPayroll($records, $settings),
            ];
        });

        $label = match ($mode) {
            'individual' => 'Informe individual del Reloj checador',
            'group' => 'Informes grupales del Reloj checador',
            default => 'Informe general del Reloj checador',
        };
        $filenameMode = match ($mode) {
            'individual' => 'individual',
            'group' => 'grupal',
            default => 'general',
        };

        $sections = $mode === 'general'
            ? [$this->generalSelectionSection($reports)]
            : $reports->map(fn (array $report) => $this->individualSelectionSection(
                $report['user'],
                $report['result'],
                $mode === 'group',
            ))->all();

        return new ReportData(
            title: $label,
            filenameBase: 'reloj-checador-'.$filenameMode.'_'.$from.'_'.$to,
            meta: [
                'Periodo' => $from.' — '.$to,
                'Colaboradores incluidos' => (string) $users->count(),
                'Selección' => $this->selectionNames($users),
            ],
            sections: $sections,
        );
    }

    /**
     * Reporte PDF del Reloj Checador del propio usuario con el formato de los reportes del CRM (el mismo generador
     * que Cronómetro y Productividad). Lleva las mismas columnas que la tabla «Marcas de tiempo» de la pantalla.
     *
     * @param  array<string, mixed>  $payrollResult  Resultado de AttendanceService::processPayroll
     */
    public function ownMarksReport(array $payrollResult, ?User $user, string $employeeId, string $from, string $to): ReportData
    {
        $rows = [];

        foreach ($payrollResult['resumen'] ?? [] as $item) {
            $rows[] = [
                Carbon::parse((string) $item['fecha'])->format('d/m/y'),
                (string) $item['detalles_marcas'],
                (string) $item['neto'],
                (string) $item['horas_decimal'],
                (string) $item['pago_horas'],
                (string) $item['total'],
                ! empty($item['requiere_revision']) ? 'revisar' : '',
            ];
        }

        $totales = $payrollResult['totales_pie'] ?? [];
        $rows[] = [
            'Total acumulado',
            '',
            $totales['tiempo'] ?? '00h 00m 00s',
            $totales['decimal'] ?? '0.00',
            $totales['pago_h'] ?? '$0.00',
            $totales['general'] ?? '$0.00',
        ];

        $name = $user ? mb_strtoupper(trim($user->name.' '.$user->last_name)) : 'ID '.$employeeId;
        $date = fn (string $value): string => Carbon::parse($value)->format('d/m/Y');

        return new ReportData(
            title: 'Marcas de tiempo del Reloj checador',
            filenameBase: 'reloj-checador-marcas_'.$from.'_'.$to,
            meta: [
                'Colaborador' => $name,
                'ID Checador' => $employeeId,
                'Periodo' => $date($from).' al '.$date($to),
                'Tiempo neto' => (string) ($totales['tiempo'] ?? '00h 00m 00s'),
                'Total acumulado' => (string) ($totales['general'] ?? '$0.00'),
            ],
            sections: [
                new ReportSection(
                    title: 'Marcas de tiempo',
                    columns: ['Fecha', 'Marcas / Chequeos', 'Tiempo neto', 'Hrs decimales', 'Pago base', 'Total del día'],
                    rows: $rows,
                ),
            ],
        );
    }

    /** @param array<int, array<string, mixed>> $changes */
    public function downloadChangeHistory(array $changes, bool $isExample): StreamedResponse
    {
        abort_if($changes === [], 422);

        $content = Pdf::loadView('livewire.time-control.reports.attendance-change-history-pdf', [
            'changes' => $changes,
            'isExample' => $isExample,
            'generatedAt' => Carbon::now(),
        ])->setPaper('a4', 'landscape')->output();

        return response()->streamDownload(
            fn () => print ($content),
            'reloj-checador-historial_'.Carbon::now()->format('Y-m-d').'.pdf',
            ['Content-Type' => 'application/pdf'],
        );
    }

    /** @param Collection<int, User> $users */
    private function selectionNames(Collection $users): string
    {
        $names = $users
            ->map(fn (User $user) => mb_strtoupper(trim($user->name.' '.$user->last_name)))
            ->join(', ');

        return Str::limit($names, 102, '...');
    }

    /** @param array<string, mixed> $result */
    private function individualSelectionSection(User $user, array $result, bool $batch): ReportSection
    {
        $name = mb_strtoupper(trim($user->name.' '.$user->last_name));
        $rows = $this->buildRows($result);
        $rows[] = $this->buildTotalRow($result);

        return new ReportSection(
            title: ($batch ? 'Reporte individual: ' : 'Detalle de asistencia: ').$name.' (ID '.$user->employee_id.')',
            columns: self::COLUMNS,
            rows: $rows,
        );
    }

    /** @param Collection<int, array{user: User, result: array<string, mixed>}> $reports */
    private function generalSelectionSection(Collection $reports): ReportSection
    {
        $rows = $reports->map(function (array $report): array {
            /** @var User $user */
            $user = $report['user'];
            $totals = $report['result']['totales_pie'] ?? [];

            return [
                mb_strtoupper(trim($user->name.' '.$user->last_name)),
                (string) $user->employee_id,
                $totals['tiempo'] ?? '00h 00m 00s',
                $totals['decimal'] ?? '0.00',
                $totals['pago_h'] ?? '$0.00',
                $totals['comida'] ?? '$0.00',
                $totals['bonos'] ?? '$0.00',
                $totals['general'] ?? '$0.00',
            ];
        })->all();

        return new ReportSection(
            title: 'Consolidado por colaborador',
            columns: ['Colaborador', 'ID Checador', 'Tiempo neto', 'Horas decimales', 'Pago base', 'Comida', 'Bonos', 'Total'],
            rows: $rows,
        );
    }

    /**
     * @param  array<string, mixed>  $payrollResult  Resultado de AttendanceService::processPayroll
     */
    public function download(string $format, array $payrollResult, array $meta): StreamedResponse
    {
        $format = strtolower($format);
        abort_unless(in_array($format, ['txt', 'csv', 'pdf'], true), 404);

        $filename = sprintf(
            'asistencia_checador_%s.%s',
            Carbon::now()->format('Ymd_His'),
            $format
        );

        $content = match ($format) {
            'txt' => $this->renderTxt($payrollResult, $meta),
            'csv' => $this->renderCsv($payrollResult, $meta),
            'pdf' => $this->renderPdf($payrollResult, $meta),
        };

        $contentType = match ($format) {
            'txt' => 'text/plain; charset=UTF-8',
            'csv' => 'text/csv; charset=UTF-8',
            'pdf' => 'application/pdf',
        };

        return response()->streamDownload(
            fn () => print ($content),
            $filename,
            ['Content-Type' => $contentType],
        );
    }

    /** @param array<string, mixed> $payrollResult */
    /** @param array<string, string> $meta */
    private function renderTxt(array $payrollResult, array $meta): string
    {
        $lines = [];
        $title = 'CONTROL DE ASISTENCIA BIOMÉTRICO (CHECADOR)';

        $lines[] = $title;
        $lines[] = str_repeat('=', mb_strlen($title));
        $lines[] = 'Generado: '.Carbon::now()->format('d/m/Y H:i:s');

        foreach ($meta as $label => $value) {
            $lines[] = $label.': '.$value;
        }

        $lines[] = '';
        $rows = $this->buildRows($payrollResult);
        $widths = $this->columnWidths(self::COLUMNS, $rows);

        $lines[] = $this->padRow(self::COLUMNS, $widths);
        $lines[] = str_repeat('-', array_sum($widths) + (count($widths) - 1) * 2);

        foreach ($rows as $row) {
            $lines[] = $this->padRow($row, $widths);
        }

        $lines[] = str_repeat('-', array_sum($widths) + (count($widths) - 1) * 2);
        $lines[] = $this->padRow($this->buildTotalRow($payrollResult), $widths);

        return implode("\n", $lines)."\n";
    }

    /** @param array<string, mixed> $payrollResult */
    /** @param array<string, string> $meta */
    private function renderCsv(array $payrollResult, array $meta): string
    {
        $handle = fopen('php://temp', 'r+');

        fputcsv($handle, ['CONTROL DE ASISTENCIA BIOMÉTRICO (CHECADOR)']);
        fputcsv($handle, ['Generado', Carbon::now()->format('d/m/Y H:i:s')]);

        foreach ($meta as $label => $value) {
            fputcsv($handle, [$label, $value]);
        }

        fputcsv($handle, []);
        fputcsv($handle, self::COLUMNS);

        foreach ($this->buildRows($payrollResult) as $row) {
            fputcsv($handle, $row);
        }

        fputcsv($handle, $this->buildTotalRow($payrollResult));

        rewind($handle);
        $contents = stream_get_contents($handle);
        fclose($handle);

        return "\xEF\xBB\xBF".$contents;
    }

    /** @param array<string, mixed> $payrollResult */
    /** @param array<string, string> $meta */
    private function renderPdf(array $payrollResult, array $meta): string
    {
        return Pdf::loadView('livewire.time-control.reports.attendance-payroll-pdf', [
            'meta' => $meta,
            'columns' => self::COLUMNS,
            'rows' => $this->buildRowsWithState($payrollResult),
            'totalRow' => $this->buildTotalRow($payrollResult),
            'generatedAt' => Carbon::now(),
        ])->setPaper('a4', 'landscape')->output();
    }

    /** @param array<string, mixed> $payrollResult */
    /** @return list<list<string>> */
    private function buildRows(array $payrollResult): array
    {
        $rows = [];

        foreach ($payrollResult['resumen'] ?? [] as $item) {
            $rows[] = [
                $item['fecha'],
                $item['detalles_marcas'],
                $item['neto'],
                $item['horas_decimal'],
                $item['pago_horas'],
                $item['comida'],
                $item['bono'],
                $item['total'],
                $item['estado'],
            ];
        }

        return $rows;
    }

    /** @param array<string, mixed> $payrollResult */
    /** @return list<array{cells: list<string>, estado: string, modified: bool}> */
    private function buildRowsWithState(array $payrollResult): array
    {
        $rows = [];

        foreach ($payrollResult['resumen'] ?? [] as $item) {
            $rows[] = [
                'cells' => [
                    $item['fecha'],
                    $item['detalles_marcas'],
                    $item['neto'],
                    $item['horas_decimal'],
                    $item['pago_horas'],
                    $item['comida'],
                    $item['bono'],
                    $item['total'],
                    $item['estado'],
                ],
                'estado' => $item['estado'],
                'modified' => (bool) ($item['modified_individual'] ?? false),
            ];
        }

        return $rows;
    }

    /** @param array<string, mixed> $payrollResult */
    /** @return list<string> */
    private function buildTotalRow(array $payrollResult): array
    {
        $totales = $payrollResult['totales_pie'] ?? [];

        return [
            'Total acumulado',
            '',
            $totales['tiempo'] ?? '00h 00m 00s',
            $totales['decimal'] ?? '0.00',
            $totales['pago_h'] ?? '$0.00',
            $totales['comida'] ?? '$0.00',
            $totales['bonos'] ?? '$0.00',
            $totales['general'] ?? '$0.00',
            '',
        ];
    }

    /** @param list<string> $headers */
    /** @param list<list<string>> $rows */
    /** @return list<int> */
    private function columnWidths(array $headers, array $rows): array
    {
        $widths = array_map(fn ($h) => mb_strlen($h), $headers);

        foreach ($rows as $row) {
            foreach ($row as $i => $cell) {
                $widths[$i] = max($widths[$i] ?? 0, mb_strlen((string) $cell));
            }
        }

        return $widths;
    }

    /** @param list<string> $cells */
    /** @param list<int> $widths */
    private function padRow(array $cells, array $widths): string
    {
        $padded = [];

        foreach ($cells as $i => $cell) {
            $value = (string) $cell;
            $width = $widths[$i] ?? mb_strlen($value);
            $padding = $width - mb_strlen($value);
            $padded[] = $padding > 0 ? $value.str_repeat(' ', $padding) : $value;
        }

        return implode('  ', $padded);
    }
}
