<?php

namespace App\Services\Reports\Exporters;

use App\Services\Reports\ReportData;
use App\Services\Reports\ReportSection;

class TxtExporter implements ReportExporter
{
    public function format(): string
    {
        return 'txt';
    }

    public function extension(): string
    {
        return 'txt';
    }

    public function contentType(): string
    {
        return 'text/plain';
    }

    public function render(ReportData $data): string
    {
        $lines = [];
        $lines[] = mb_strtoupper($data->title);
        $lines[] = str_repeat('=', mb_strlen($data->title));
        $lines[] = 'Generado: '.$data->generatedAt()->format('d/m/Y H:i:s');

        foreach ($data->meta as $label => $value) {
            $lines[] = $label.': '.$value;
        }

        foreach ($data->sections as $section) {
            $lines[] = '';
            $lines[] = $section->title;
            $lines[] = str_repeat('-', mb_strlen($section->title));
            $lines = array_merge($lines, $this->renderTable($section));
        }

        return implode("\n", $lines)."\n";
    }

    /** @return list<string> */
    private function renderTable(ReportSection $section): array
    {
        $widths = [];
        foreach ($section->columns as $i => $column) {
            $widths[$i] = mb_strlen((string) $column);
        }
        foreach ($section->rows as $row) {
            foreach (array_values($row) as $i => $cell) {
                $widths[$i] = max($widths[$i] ?? 0, mb_strlen((string) $cell));
            }
        }

        $format = fn (array $cells) => implode('  ', array_map(
            fn ($i, $cell) => $this->pad((string) $cell, $widths[$i] ?? 0),
            array_keys($cells),
            array_values($cells),
        ));

        if ($section->rows === []) {
            return ['(Sin datos)'];
        }

        $out = [rtrim($format($section->columns))];
        foreach ($section->rows as $row) {
            $out[] = rtrim($format(array_values($row)));
        }

        return $out;
    }

    /** Rellena a la derecha respetando multibyte (compatible con PHP 8.2). */
    private function pad(string $value, int $width): string
    {
        $padding = $width - mb_strlen($value);

        return $padding > 0 ? $value.str_repeat(' ', $padding) : $value;
    }
}
