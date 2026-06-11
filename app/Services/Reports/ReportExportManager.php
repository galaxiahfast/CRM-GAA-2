<?php

namespace App\Services\Reports;

use App\Services\Reports\Exporters\CsvExporter;
use App\Services\Reports\Exporters\PdfExporter;
use App\Services\Reports\Exporters\ReportExporter;
use App\Services\Reports\Exporters\TxtExporter;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Punto único de acceso a los exportadores. Resuelve el formato solicitado
 * y devuelve una descarga lista para usarse desde controladores o Livewire.
 */
class ReportExportManager
{
    /** @var array<string, ReportExporter> */
    private array $exporters = [];

    public function __construct(CsvExporter $csv, TxtExporter $txt, PdfExporter $pdf)
    {
        foreach ([$csv, $txt, $pdf] as $exporter) {
            $this->exporters[$exporter->format()] = $exporter;
        }
    }

    public function supports(string $format): bool
    {
        return isset($this->exporters[strtolower($format)]);
    }

    /** @return list<string> */
    public function formats(): array
    {
        return array_keys($this->exporters);
    }

    public function download(string $format, ReportData $data): StreamedResponse
    {
        $format = strtolower($format);
        abort_unless($this->supports($format), 404);

        $exporter = $this->exporters[$format];
        $content = $exporter->render($data);
        $filename = $data->filenameBase.'.'.$exporter->extension();

        return response()->streamDownload(
            fn () => print ($content),
            $filename,
            ['Content-Type' => $exporter->contentType()],
        );
    }
}
