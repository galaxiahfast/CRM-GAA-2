<?php

namespace App\Services\Reports\Exporters;

use App\Services\Reports\ReportData;
use Barryvdh\DomPDF\Facade\Pdf;

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
        return Pdf::loadView('reports.report', ['data' => $data])
            ->setPaper('a4')
            ->output();
    }
}
