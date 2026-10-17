<?php

namespace App\Http\Controllers;

use App\Services\Backups\BackupInfrastructurePdfService;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class BackupInfrastructureGuideController extends Controller
{
    public function __invoke(BackupInfrastructurePdfService $pdf): Response
    {
        Gate::authorize('access-system-backups');

        return response($pdf->generate(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="guia-configuracion-respaldos.pdf"',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
