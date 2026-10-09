<?php

namespace Tests\Unit;

use App\Services\Reports\Exporters\PdfExporter;
use App\Services\Reports\ReportData;
use App\Services\Reports\ReportSection;
use App\Services\TimeControl\AttendanceExportService;
use App\Models\User;
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
                columns: ['Fecha jornada', 'Marcas / chequeos', 'Tiempo neto'],
                rows: [
                    ['2026-10-01', '09:00:00, 16:00:00', '07h 00m 00s'],
                    ['2026-10-02', '09:00:01, 16:00:00', '06h 59m 59s'],
                    ['Total acumulado', '', '13h 59m 59s'],
                ],
            )],
        );

        $exporter = app(PdfExporter::class);
        $method = new ReflectionMethod($exporter, 'renderHtml');
        $html = $method->invoke($exporter, $report);

        $this->assertStringContainsString('@page{size:A4 landscape;margin:12mm}', $html);
        $this->assertStringContainsString('<body class="attendance-print">', $html);
        $this->assertStringContainsString('<div class="report-header">', $html);
        $this->assertStringContainsString('font-family:"DejaVu Sans Mono",Courier,monospace;font-size:9px', $html);
        $this->assertStringContainsString('<tr class="section-heading"><th class="section-heading-cell" colspan="3">Detalle de asistencia</th></tr>', $html);
        $this->assertStringContainsString('.section th.num{text-align:center!important}', $html);
        $this->assertStringContainsString('.section td.num{text-align:left!important}', $html);
        $this->assertStringContainsString('font-size:9px;font-weight:normal', $html);
        $this->assertStringContainsString('.meta td{padding:2px 0}', $html);
        $this->assertStringContainsString('.meta{margin-bottom:0}', $html);
        $this->assertStringContainsString('border:1px solid #9ca3af;padding:8px', $html);
        $this->assertStringNotContainsString('background:#fff1f2', $html);
        $this->assertStringContainsString('Total acumulado', $html);
        $this->assertSame(1, substr_count($html, 'class="late-arrival"'));

        $pdf = $exporter->render($report);
        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertSame(1, preg_match('/\/MediaBox\s*\[\s*0(?:\.0+)?\s+0(?:\.0+)?\s+([\d.]+)\s+([\d.]+)\s*\]/', $pdf, $mediaBox));
        $this->assertGreaterThan((float) $mediaBox[2], (float) $mediaBox[1]);
    }

    public function test_long_attendance_selection_is_shortened_without_wrapping_the_header(): void
    {
        $users = collect([
            new User(['name' => 'Nombre extraordinariamente largo uno', 'last_name' => 'Apellido primero']),
            new User(['name' => 'Nombre extraordinariamente largo dos', 'last_name' => 'Apellido segundo']),
            new User(['name' => 'Nombre extraordinariamente largo tres', 'last_name' => 'Apellido tercero']),
        ]);

        $service = app(AttendanceExportService::class);
        $method = new ReflectionMethod($service, 'selectionNames');
        $selection = $method->invoke($service, $users);

        $this->assertLessThanOrEqual(105, mb_strlen($selection));
        $this->assertStringEndsWith('...', $selection);
    }
}
