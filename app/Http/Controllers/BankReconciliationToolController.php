<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BankReconciliationToolController extends Controller
{
    private const TOOLS = [
        'ocr' => [
            'title' => 'Conciliación Bancaria OCR',
            'file' => 'conciliacion-bancaria-ocr.html',
        ],
        'bancaria' => [
            'title' => 'Conciliación Bancaria',
            'file' => 'conciliacion-bancaria.html',
        ],
    ];

    public function show(string $tool): View
    {
        $definition = $this->definition($tool);

        return view('bank-reconciliation.tool', [
            'title' => $definition['title'],
            'contentUrl' => route('bank-reconciliation.content', $tool),
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
