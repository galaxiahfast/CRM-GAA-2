<?php

namespace Tests\Unit;

use App\Services\Reports\Exporters\PdfExporter;
use App\Services\Reports\ReportData;
use App\Services\Reports\ReportSection;
use ReflectionMethod;
use Tests\TestCase;

class AttendancePdfFormatTest extends TestCase
{
    public function test_attendance_pdf_uses_print_layout_and_marks_only_late_arrivals(): void
    {
        $report = new ReportData(
            title: 'Informe individual del Reloj checador',
            filenameBase: 'reloj-checador-individual_2026-10-01_2026-10-15',
            sections: [new ReportSection(
                title: 'Detalle de asistencia',
                columns: ['Fecha Jornada', 'Marcas / Chequeos', 'Tiempo Neto'],
                rows: [
                    ['2026-10-01', '09:00:00, 16:00:00', '07h 00m 00s'],
                    ['2026-10-02', '09:00:01, 16:00:00', '06h 59m 59s'],
                    ['TOTAL ACUMULADO', '', '13h 59m 59s'],
                ],
            )],
        );

        $exporter = app(PdfExporter::class);
        $method = new ReflectionMethod($exporter, 'renderHtml');
        $html = $method->invoke($exporter, $report);

        $this->assertStringContainsString('@page{size:A4 landscape;margin:12mm}', $html);
        $this->assertStringContainsString('<body class="attendance-print">', $html);
        $this->assertStringContainsString('font-family:"DejaVu Sans Mono",Courier,monospace;font-size:9px', $html);
        $this->assertSame(1, substr_count($html, 'class="late-arrival"'));

        $pdf = $exporter->render($report);
        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertSame(1, preg_match('/\/MediaBox\s*\[\s*0(?:\.0+)?\s+0(?:\.0+)?\s+([\d.]+)\s+([\d.]+)\s*\]/', $pdf, $mediaBox));
        $this->assertGreaterThan((float) $mediaBox[2], (float) $mediaBox[1]);
    }
}
