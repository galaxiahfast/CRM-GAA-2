<?php

namespace App\Services\Support;

use App\Models\ServiceOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class EquipmentDeliveryPdfService
{
    public function generate(ServiceOrder $report, string $copyType = 'both', ?string $documentType = null): string
    {
        if (! in_array($copyType, ['both', 'datamid', 'cliente'], true)) {
            throw new InvalidArgumentException('Tipo de copia no válido.');
        }

        $copies = $copyType === 'both' ? ['datamid', 'cliente'] : [$copyType];
        $documentType ??= $report->loadMissing('movimientos')->documentType();
        if (! in_array($documentType, ['recepcion', 'entrega', 'prestamo', 'devolucion', 'compra'], true)) {
            throw new InvalidArgumentException('Tipo de documento no válido.');
        }

        return Pdf::loadView('pdf.equipment-delivery-note', [
            'report' => $report,
            'copies' => $copies,
            'documentType' => $documentType,
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
