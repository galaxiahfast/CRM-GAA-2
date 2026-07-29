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
        $cacheKey = 'report-pdf:v3:'.hash('sha256', serialize([
            $data->title,
            $data->filenameBase,
            $data->meta,
            $data->sections,
        ]));

        $encodedPdf = Cache::store('file')->remember($cacheKey, now()->addMinutes(10), static fn () => base64_encode(Pdf::loadHTML($html)
            ->setPaper('a4')
            ->setOptions([
                'defaultFont' => 'Helvetica',
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
        $html = '<!doctype html><html lang="es"><head><meta charset="utf-8"><style>'
            .'body{font-family:Helvetica,Arial,sans-serif;font-size:11px;color:#1f2937;margin:24px}'
            .'h1{font-size:18px;margin:0 0 4px}.generated{color:#6b7280;font-size:10px;margin-bottom:12px}'
            .'h2{font-size:13px;margin:18px 0 6px;border-bottom:1px solid #e5e7eb;padding-bottom:2px}'
            .'h2.report-start{page-break-before:always}.meta,.section{width:100%;border-collapse:collapse}'
            .'.meta{margin-bottom:16px}.meta td{padding:2px 6px}.meta .label{color:#6b7280;width:220px}'
            .'.section th,.section td{border:1px solid #e5e7eb;padding:4px 6px;text-align:left}'
            .'.section th{background:#f3f4f6}.num{text-align:right!important;font-family:Courier,monospace}'
            .'.empty{color:#9ca3af;font-style:italic}.day-total{text-align:right;font-weight:bold;padding:6px}'
            .'</style></head><body>';

        $html .= '<h1>'.$this->escape($data->title).'</h1>';
        $html .= '<div class="generated">Generado: '.$this->escape($data->generatedAt()->format('d/m/Y H:i:s')).'</div>';

        if ($data->meta !== []) {
            $html .= '<table class="meta">';
            foreach ($data->meta as $label => $value) {
                $html .= '<tr><td class="label">'.$this->escape((string) $label).'</td><td>'.$this->escape((string) $value).'</td></tr>';
            }
            $html .= '</table>';
        }

        foreach ($data->sections as $sectionIndex => $section) {
            $startsReport = str_starts_with($section->title, 'Reporte individual:') && $sectionIndex > 0;
            $html .= '<h2'.($startsReport ? ' class="report-start"' : '').'>'.$this->escape($section->title).'</h2>';

            if ($section->dayGroups !== null) {
                if ($section->dayGroups === []) {
                    $html .= '<p class="empty">Sin registros en el periodo.</p>';
                    continue;
                }

                foreach ($section->dayGroups as $group) {
                    $html .= '<h3>'.$this->escape((string) $group['date']).'</h3>';
                    $html .= $this->table($section->columns, $group['rows'], true);
                    $html .= '<div class="day-total">Total del día: '.$this->escape($this->dayTotal($section->columns, $group['rows'])).'</div>';
                }

                continue;
            }

            $html .= $section->rows === []
                ? '<p class="empty">Sin datos.</p>'
                : $this->table($section->columns, $section->rows);
        }

        return $html.'</body></html>';
    }

    private function table(array $columns, array $rows, bool $timeColumns = false): string
    {
        $html = '<table class="section"><thead><tr>';
        foreach ($columns as $index => $column) {
            $numeric = $timeColumns
                ? in_array($column, ['Inicio', 'Fin', 'Tiempo efectivo'], true)
                : $index > 0;
            $html .= '<th'.($numeric ? ' class="num"' : '').'>'.$this->escape((string) $column).'</th>';
        }
        $html .= '</tr></thead><tbody>';

        foreach ($rows as $row) {
            $html .= '<tr>';
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
