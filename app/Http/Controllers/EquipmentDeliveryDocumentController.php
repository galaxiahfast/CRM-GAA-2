<?php

namespace App\Http\Controllers;

use App\Models\ServiceOrder;
use App\Services\Support\EquipmentDeliveryPdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class EquipmentDeliveryDocumentController extends Controller
{
    public function pdf(
        Request $request,
        ServiceOrder $report,
        EquipmentDeliveryPdfService $pdfService,
    ): Response {
        Gate::authorize('manage-delivery-notes');
        $copyType = (string) $request->query('copy', 'both');
        abort_unless(in_array($copyType, ['both', 'datamid', 'cliente'], true), 400);
        $suffix = $copyType === 'both' ? 'doble' : $copyType;
        $documentType = (string) $request->query('document', $report->loadMissing('movimientos')->documentType());
        abort_unless(in_array($documentType, ['recepcion', 'entrega', 'prestamo', 'devolucion', 'compra'], true), 400);
        $disposition = $request->boolean('print') ? 'inline' : 'attachment';

        return response($pdfService->generate($report, $copyType, $documentType), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition.'; filename="'.$report->folio.'_'.$documentType.'_'.$suffix.'.pdf"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function photo(ServiceOrder $report): BinaryFileResponse
    {
        Gate::authorize('manage-delivery-notes');
        abort_unless($report->photo_path && Storage::disk('local')->exists($report->photo_path), 404);

        return response()->file(Storage::disk('local')->path($report->photo_path), [
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
