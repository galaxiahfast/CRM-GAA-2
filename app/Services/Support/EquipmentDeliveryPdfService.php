<?php

namespace App\Services\Support;

use App\Models\EquipmentDeliveryReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class EquipmentDeliveryPdfService
{
    public function generate(EquipmentDeliveryReport $report, string $copyType = 'both'): string
    {
        if (! in_array($copyType, ['both', 'datamid', 'cliente'], true)) {
            throw new InvalidArgumentException('Tipo de copia no válido.');
        }

        $copies = $copyType === 'both' ? ['datamid', 'cliente'] : [$copyType];

        return Pdf::loadView('pdf.equipment-delivery-note', [
            'report' => $report,
            'copies' => $copies,
            'logo' => $this->dataUri(public_path('img/delivery-notes/datamid-logo.png')),
            'headerDecoration' => $this->dataUri(public_path('img/delivery-notes/datamid-header.png')),
            'footerDecoration' => $this->dataUri(public_path('img/delivery-notes/datamid-footer.png')),
            'photo' => $report->photo_path && Storage::disk('local')->exists($report->photo_path)
                ? $this->dataUri(Storage::disk('local')->path($report->photo_path))
                : null,
        ])->setPaper('letter')->output();
    }

    private function dataUri(string $path): ?string
    {
        if (! is_file($path)) {
            return null;
        }

        $mime = mime_content_type($path) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($path));
    }
}
