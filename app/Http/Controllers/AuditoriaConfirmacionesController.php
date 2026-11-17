<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AuditoriaConfirmacionesController extends Controller
{
    private const TOOLS = [
        'confirmaciones' => [
            'title' => 'Confirmaciones',
            'file' => 'auditoria-confirmaciones.html',
        ],
    ];

    public function show(string $tool): View
    {
        $definition = $this->definition($tool);

        return view('confirmaciones.tool', [
            'title' => $definition['title'],
            'contentUrl' => route('confirmaciones.content', $tool),
        ]);
    }

    public function content(string $tool): BinaryFileResponse
    {
        $definition = $this->definition($tool);

        return response()->file(resource_path('tools/'.$definition['file']), [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /** @return array{title: string, file: string} */
    private function definition(string $tool): array
    {
        abort_unless(array_key_exists($tool, self::TOOLS), 404);

        return self::TOOLS[$tool];
    }
}
