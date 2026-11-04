<?php

namespace App\Http\Controllers;

use App\Services\Support\PanelGeneralGuidePdfService;
use Symfony\Component\HttpFoundation\Response;

class PanelGeneralGuideController extends Controller
{
    /** Guía en PDF del Panel General, con el formato de los Reportes de soporte. */
    public function __invoke(PanelGeneralGuidePdfService $guide): Response
    {
        abort_unless(auth()->check(), 403);

        $pdf = $guide->generate('panel_general');
        abort_if($pdf === null, 404);

        return response($pdf['content'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$pdf['filename'].'"',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
