<?php

namespace App\Services\Reports\Exporters;

use App\Services\Reports\ReportData;

class CsvExporter implements ReportExporter
{
    public function format(): string
    {
        return 'csv';
    }

    public function extension(): string
    {
        return 'csv';
    }

    public function contentType(): string
    {
        return 'text/csv';
    }

    public function render(ReportData $data): string
    {
        $handle = fopen('php://temp', 'r+');

        fputcsv($handle, [$data->title]);
        fputcsv($handle, ['Generado', $data->generatedAt()->format('d/m/Y H:i:s')]);

        foreach ($data->meta as $label => $value) {
            fputcsv($handle, [$label, $value]);
        }

        foreach ($data->sections as $section) {
            fputcsv($handle, []);
            fputcsv($handle, [$section->title]);
            fputcsv($handle, $section->columns);

            foreach ($section->rows as $row) {
                fputcsv($handle, $row);
            }
        }

        rewind($handle);
        $contents = stream_get_contents($handle);
        fclose($handle);

        // BOM UTF-8 para que Excel respete los acentos.
        return "\xEF\xBB\xBF".$contents;
    }
}
