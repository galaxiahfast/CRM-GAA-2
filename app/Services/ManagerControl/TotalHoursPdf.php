<?php

namespace App\Services\ManagerControl;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;

/** Réplica del reporte «Total de Horas Laboradas» de ManagerControl (sin caché: son datos sensibles). */
class TotalHoursPdf
{
    /** @param array{id: int, name: string} $employee */
    public function html(array $employee, string $from, string $to, HoursSummary $summary): string
    {
        $logoPath = public_path('img/delivery-notes/datamid-logo.png');

        return view('pdf.managercontrol-total-hours', [
            'employee' => $employee,
            'from' => Carbon::parse($from)->format('d/m/Y'),
            'to' => Carbon::parse($to)->format('d/m/Y'),
            'summary' => $summary,
            'logo' => is_file($logoPath) ? 'data:image/png;base64,'.base64_encode((string) file_get_contents($logoPath)) : null,
            'generatedAt' => Carbon::now(),
        ])->render();
    }

    /** @param array{id: int, name: string} $employee */
    public function render(array $employee, string $from, string $to, HoursSummary $summary): string
    {
        return Pdf::loadHTML($this->html($employee, $from, $to, $summary))
            ->setPaper('letter', 'portrait')
            ->setOptions(['defaultFont' => 'DejaVu Sans', 'isRemoteEnabled' => false, 'isPhpEnabled' => false, 'isJavascriptEnabled' => false])
            ->output();
    }
}
