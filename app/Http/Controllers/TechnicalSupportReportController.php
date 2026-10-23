<?php

namespace App\Http\Controllers;

use App\Models\TechnicalSupportReport;
use App\Services\Support\TechnicalSupportReportPdfService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class TechnicalSupportReportController extends Controller
{
    public function index(TechnicalSupportReportPdfService $pdf): View
    {
        $catalog = collect($pdf->catalog())
            ->map(fn (array $item, string $key): array => ['key' => $key, ...$item])
            ->values();

        return view('support.technical-reports', [
            'activeTab' => 'generate',
            'catalog' => $catalog,
        ]);
    }

    public function history(): View
    {
        return view('support.technical-reports', [
            'activeTab' => 'history',
            'reports' => TechnicalSupportReport::query()
                ->latest('issued_at')
                ->latest('id')
                ->paginate(20),
        ]);
    }

    public function store(Request $request, TechnicalSupportReportPdfService $pdf): RedirectResponse
    {
        $catalog = $pdf->catalog();
        $validated = $request->validate([
            'service_recipient' => ['required', 'string', 'max:180'],
            'supports' => ['required', 'array', 'min:1', 'max:8'],
            'supports.*' => ['required', 'string', 'distinct', Rule::in(array_keys($catalog))],
            'support_quantities' => ['required', 'array'],
            'support_quantities.*' => ['required', 'integer', 'min:1', 'max:999'],
            'versions_applied' => ['nullable', 'string', 'max:2000'],
        ], [
            'supports.required' => 'Selecciona al menos un servicio de soporte.',
            'supports.max' => 'Selecciona como máximo ocho servicios por reporte.',
        ]);

        $selectedSupports = collect($validated['supports'])
            ->map(fn (string $key): array => [
                'key' => $key,
                ...$catalog[$key],
                'quantity' => (int) data_get($validated, 'support_quantities.'.$key, 1),
            ])
            ->values()
            ->all();

        $hasServerUpdate = collect($validated['supports'])
            ->contains(fn (string $key): bool => str_ends_with($key, '_server_update'));

        $versions = collect(preg_split('/\r\n|\r|\n/', (string) ($validated['versions_applied'] ?? '')))
            ->map(fn (string $version): string => trim($version))
            ->filter()
            ->values()
            ->take(20)
            ->all();

        if ($hasServerUpdate && $versions === []) {
            throw ValidationException::withMessages([
                'versions_applied' => 'Escribe todas las versiones aplicadas en la actualización del servidor.',
            ]);
        }

        $details = [
            'versions_applied' => $hasServerUpdate ? $versions : [],
        ];

        $report = DB::transaction(function () use ($selectedSupports, $details, $validated): TechnicalSupportReport {
            $localIssuedAt = now(config('support.timezone', 'America/Mexico_City'));
            $report = TechnicalSupportReport::create([
                'folio' => 'PENDING-'.Str::uuid(),
                'issued_at' => $localIssuedAt->clone()->utc(),
                'responsible' => 'Julian Emiliano Ortiz Rivero',
                'service_recipient' => trim($validated['service_recipient']),
                'supports' => $selectedSupports,
                'details' => $details,
                'created_by' => auth()->id(),
            ]);

            $report->update([
                'folio' => 'ST-'.$localIssuedAt->format('Ymd').'-'.str_pad((string) $report->id, 4, '0', STR_PAD_LEFT),
            ]);

            return $report->refresh();
        });

        return redirect()
            ->route('soporte.reportes-tecnicos.history', ['created' => $report->folio])
            ->with('status', 'Reporte '.$report->folio.' generado correctamente.');
    }

    public function pdf(
        Request $request,
        TechnicalSupportReport $report,
        TechnicalSupportReportPdfService $pdf,
    ): Response {
        $data = $pdf->data($report);
        $disposition = $request->boolean('download') ? 'attachment' : 'inline';

        return response($pdf->generate($report), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition.'; filename="'.$data['filename'].'"',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
