<?php

namespace App\Http\Controllers;

use App\Services\Support\SupportGuidePdfService;
use App\Services\Authorization\PermissionAccessService;
use Symfony\Component\HttpFoundation\Response;

class SectionGuideController extends Controller
{
    public function __invoke(string $section, SupportGuidePdfService $guide, PermissionAccessService $permissions): Response
    {
        $categories = [
            'inicio' => 'inicio',
            'detalle-cliente' => 'detalle_cliente',
            'panel-control' => 'panel_control_horas',
            'supervision-horas' => 'supervision_horas',
            'cronometro' => 'cronometro',
            'productividad' => 'productividad',
            'chat-soporte' => 'chat_soporte',
        ];

        abort_unless(isset($categories[$section]), 404);

        if ($section === 'panel-control') {
            abort_unless($permissions->allows(auth()->user(), 'time-control.productivity.view'), 403);
        } elseif ($section === 'supervision-horas') {
            abort_unless($permissions->allows(auth()->user(), 'time-control.supervision.view'), 403);
        }

        $pdf = $guide->generate($categories[$section]);
        abort_if($pdf === null, 404);

        return response($pdf['content'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$pdf['filename'].'"',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
