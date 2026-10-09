<?php

namespace App\Services\Reports\Exporters;

use App\Services\Reports\ReportData;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Cache;

class PdfExporter implements ReportExporter
{
    public function format(): string
    {
        return 'pdf';
    }

    public function extension(): string
    {
        return 'pdf';
    }

    public function contentType(): string
    {
        return 'application/pdf';
    }

    public function render(ReportData $data): string
    {
        $html = $this->renderHtml($data);
        $isAttendanceReport = $this->isAttendanceReport($data);
        $cacheKey = 'report-pdf:v7:'.hash('sha256', serialize([
            $data->title,
            $data->filenameBase,
            $data->meta,
            $data->sections,
        ]));

        $encodedPdf = Cache::store('file')->remember($cacheKey, now()->addMinutes(10), static fn () => base64_encode(Pdf::loadHTML($html)
            ->setPaper('a4', $isAttendanceReport ? 'landscape' : 'portrait')
            ->setOptions([
                'defaultFont' => $isAttendanceReport ? 'DejaVu Sans Mono' : 'Helvetica',
                'isRemoteEnabled' => false,
                'isPhpEnabled' => false,
                'isJavascriptEnabled' => false,
                'isFontSubsettingEnabled' => false,
            ])
            ->output()));

        return base64_decode($encodedPdf, true) ?: '';
    }

    private function renderHtml(ReportData $data): string
    {
        $isAttendanceReport = $this->isAttendanceReport($data);
        $html = '<!doctype html><html lang="es"><head><meta charset="utf-8"><style>'
            .'body{font-family:Helvetica,Arial,sans-serif;font-size:11px;color:#1f2937;margin:24px}'
            .'h1{font-size:18px;margin:0 0 4px}.generated{color:#6b7280;font-size:10px;margin-bottom:12px}'
            .'h2{font-size:13px;margin:18px 0 6px;border-bottom:1px solid #e5e7eb;padding-bottom:2px}'
            .'h2.report-start{page-break-before:always}.meta,.section{width:100%;border-collapse:collapse}'
            .'.meta{margin-bottom:16px}.meta td{padding:2px 6px}.meta .label{color:#6b7280;width:220px}'
            .'.section th,.section td{border:1px solid #e5e7eb;padding:4px 6px;text-align:left}'
            .'.section th{background:#f3f4f6}.num{text-align:right!important;font-family:Courier,monospace}'
            .'.empty{color:#9ca3af;font-style:italic}.day-total{text-align:right;font-weight:bold;padding:6px}'
            .($isAttendanceReport
                ? '@page{size:A4 landscape;margin:12mm}'
                    .'body.attendance-print{font-family:"DejaVu Sans Mono",Courier,monospace;font-size:9px;line-height:1.35;color:#111827;margin:0}'
                    .'body.attendance-print h1,body.attendance-print h2,body.attendance-print h3,body.attendance-print .generated,body.attendance-print .meta,body.attendance-print .section,body.attendance-print .empty,body.attendance-print .day-total{font-family:"DejaVu Sans Mono",Courier,monospace;font-size:9px}'
                    .'body.attendance-print .report-header{border:1px solid #9ca3af;background:#fff;padding:10px 12px;margin-bottom:18px}'
                    .'body.attendance-print h1{margin:0 0 5px;font-weight:normal}'
                    .'body.attendance-print h2{margin:14px 0 6px;padding:8px;border:1px solid #9ca3af;background:#fff;font-weight:normal}'
                    .'body.attendance-print .generated{margin-bottom:10px}'
                    .'body.attendance-print .meta{margin-bottom:0}'
                    .'body.attendance-print .meta td{padding:2px 0}'
                    .'body.attendance-print .meta .label{width:180px}'
                    .'body.attendance-print .section{table-layout:auto}'
                    .'body.attendance-print .section th,body.attendance-print .section td{border:1px solid #9ca3af;padding:8px;vertical-align:middle;font-size:9px;font-weight:normal;font-family:"DejaVu Sans Mono",Courier,monospace}'
                    .'body.attendance-print .section th{background:#fff}'
                    .'body.attendance-print .section th,body.attendance-print .section th.num{text-align:center!important}'
                    .'body.attendance-print .section td,body.attendance-print .section td.num{text-align:left!important}'
                    .'body.attendance-print .section th.section-heading-cell{text-align:left!important;border-bottom:1px solid #9ca3af}'
                    .'body.attendance-print .section.report-start{page-break-before:always}'
                    .'body.attendance-print .section tr{page-break-inside:avoid}'
                    .'body.attendance-print .section td{white-space:nowrap}'
                    .'body.attendance-print .section th:nth-child(2),body.attendance-print .section td:nth-child(2){white-space:normal}'
                    .'body.attendance-print .section tr.late-arrival td:first-child{color:#b91c1c;font-weight:normal}'
                : '')
            .'</style></head><body>';

        if ($isAttendanceReport) {
            $html = str_replace('<body>', '<body class="attendance-print">', $html);
        }

        if ($isAttendanceReport) {
            $html .= '<div class="report-header">';
        }

        $html .= '<h1>'.$this->escape($data->title).'</h1>';
        $html .= '<div class="generated">Generado: '.$this->escape($data->generatedAt()->format('d/m/Y H:i:s')).'</div>';

        if ($data->meta !== []) {
            $html .= '<table class="meta">';
            foreach ($data->meta as $label => $value) {
                $html .= '<tr><td class="label">'.$this->escape((string) $label).'</td><td>'.$this->escape((string) $value).'</td></tr>';
            }
            $html .= '</table>';
        }

        if ($isAttendanceReport) {
            $html .= '</div>';
        }

        foreach ($data->sections as $sectionIndex => $section) {
            $startsReport = str_starts_with($section->title, 'Reporte individual:') && $sectionIndex > 0;

            if ($isAttendanceReport && $section->dayGroups === null && $section->rows !== []) {
                $html .= $this->table(
                    $section->columns,
                    $section->rows,
                    false,
                    true,
                    $section->title,
                    $startsReport,
                );
                continue;
            }

            $html .= '<h2'.($startsReport ? ' class="report-start"' : '').'>'.$this->escape($section->title).'</h2>';

            if ($section->dayGroups !== null) {
                if ($section->dayGroups === []) {
                    $html .= '<p class="empty">Sin registros en el periodo.</p>';
                    continue;
                }

                foreach ($section->dayGroups as $group) {
                    $html .= '<h3>'.$this->escape((string) $group['date']).'</h3>';
                    $html .= $this->table($section->columns, $group['rows'], true, $isAttendanceReport);
                    $html .= '<div class="day-total">Total del día: '.$this->escape($this->dayTotal($section->columns, $group['rows'])).'</div>';
                }

                continue;
            }

            $html .= $section->rows === []
                ? '<p class="empty">Sin datos.</p>'
                : $this->table($section->columns, $section->rows, false, $isAttendanceReport);
        }

        return $html.'</body></html>';
    }

    private function table(
        array $columns,
        array $rows,
        bool $timeColumns = false,
        bool $attendanceReport = false,
        ?string $sectionTitle = null,
        bool $startsReport = false,
    ): string
    {
        $html = '<table class="section'.($startsReport ? ' report-start' : '').'"><thead>';

        if ($sectionTitle !== null) {
            $html .= '<tr class="section-heading"><th class="section-heading-cell" colspan="'.count($columns).'">'.$this->escape($sectionTitle).'</th></tr>';
        }

        $html .= '<tr>';
        foreach ($columns as $index => $column) {
            $numeric = $timeColumns
                ? in_array($column, ['Inicio', 'Fin', 'Tiempo efectivo'], true)
                : $index > 0;
            $html .= '<th'.($numeric ? ' class="num"' : '').'>'.$this->escape((string) $column).'</th>';
        }
        $html .= '</tr></thead><tbody>';

        foreach ($rows as $row) {
            $lateArrival = $attendanceReport && $this->isLateAttendanceRow($columns, $row);
            $html .= '<tr'.($lateArrival ? ' class="late-arrival"' : '').'>';
            foreach (array_values($row) as $index => $cell) {
                $column = $columns[$index] ?? '';
                $numeric = $timeColumns
                    ? in_array($column, ['Inicio', 'Fin', 'Tiempo efectivo'], true)
                    : $index > 0;
                $html .= '<td'.($numeric ? ' class="num"' : '').'>'.$this->escape((string) $cell).'</td>';
            }
            $html .= '</tr>';
        }

        return $html.'</tbody></table>';
    }

    private function isAttendanceReport(ReportData $data): bool
    {
        return str_starts_with($data->filenameBase, 'reloj-checador-');
    }

    /** @param list<string> $columns @param list<string|int> $row */
    private function isLateAttendanceRow(array $columns, array $row): bool
    {
        $dateIndex = array_search('Fecha jornada', $columns, true);
        $marksIndex = array_search('Marcas / chequeos', $columns, true);

        if ($dateIndex === false || $marksIndex === false || empty($row[$dateIndex])) {
            return false;
        }

        if (! preg_match('/\b(\d{2}:\d{2}:\d{2})\b/', (string) ($row[$marksIndex] ?? ''), $matches)) {
            return false;
        }

        return $matches[1] > '09:00:00';
    }

    private function dayTotal(array $columns, array $rows): string
    {
        $timeIndex = array_search('Tiempo efectivo', $columns, true);
        $seconds = 0;

        if ($timeIndex !== false) {
            foreach ($rows as $row) {
                $parts = explode(':', (string) ($row[$timeIndex] ?? ''));
                if (count($parts) === 3) {
                    $seconds += ((int) $parts[0] * 3600) + ((int) $parts[1] * 60) + (int) $parts[2];
                }
            }
        }

        return sprintf('%02d:%02d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60), $seconds % 60);
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
