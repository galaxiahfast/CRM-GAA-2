<?php

namespace App\Http\Controllers;

use App\Models\TechnicalSupportReport;
use App\Services\Support\TechnicalSupportReportPdfService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use PharData;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

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

    public function history(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $recipient = trim((string) $request->query('recipient', ''));
        $target = trim((string) $request->query('target', ''));

        $reportsQuery = TechnicalSupportReport::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('folio', 'like', '%'.$search.'%')
                        ->orWhere('service_recipient', 'like', '%'.$search.'%')
                        ->orWhere('responsible', 'like', '%'.$search.'%')
                        ->orWhere('supports', 'like', '%'.$search.'%');
                });
            })
            ->when($recipient !== '', fn ($query) => $query->where('service_recipient', $recipient))
            ->when($target !== '', fn ($query) => $query->where('supports', 'like', '%'.$target.'%'))
            ->latest('issued_at')
            ->latest('id');

        $filterReports = TechnicalSupportReport::query()
            ->get(['service_recipient', 'supports']);

        return view('support.technical-reports', [
            'activeTab' => 'history',
            'reports' => $reportsQuery->paginate(20)->withQueryString(),
            'filters' => compact('search', 'recipient', 'target'),
            'recipientOptions' => $filterReports
                ->pluck('service_recipient')
                ->filter()
                ->unique()
                ->sort(SORT_NATURAL | SORT_FLAG_CASE)
                ->values(),
            'targetOptions' => $filterReports
                ->flatMap(fn (TechnicalSupportReport $report) => collect($report->supports ?? [])
                    ->flatMap(fn (array $support) => $support['targets'] ?? []))
                ->map(fn (string $name): string => trim($name))
                ->filter()
                ->unique()
                ->sort(SORT_NATURAL | SORT_FLAG_CASE)
                ->values(),
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
            'support_targets' => ['nullable', 'array'],
            'support_targets.*' => ['nullable', 'string', 'max:3000'],
            'versions_applied' => ['nullable', 'string', 'max:2000'],
        ], [
            'supports.required' => 'Selecciona al menos un servicio de soporte.',
            'supports.max' => 'Selecciona como máximo ocho servicios por reporte.',
        ]);

        $selectedSupports = collect($validated['supports'])
            ->map(function (string $key) use ($catalog, $validated): array {
                $targets = collect(preg_split('/\r\n|\r|\n/', (string) data_get($validated, 'support_targets.'.$key, '')))
                    ->map(fn (string $target): string => trim($target))
                    ->filter()
                    ->unique()
                    ->take(50)
                    ->values()
                    ->all();

                return [
                    'key' => $key,
                    ...$catalog[$key],
                    'quantity' => (int) data_get($validated, 'support_quantities.'.$key, 1),
                    'targets' => $targets,
                ];
            })
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

    public function downloadSelection(
        Request $request,
        TechnicalSupportReportPdfService $pdf,
    ): BinaryFileResponse {
        $validated = $request->validate([
            'report_folios' => ['required', 'array', 'min:1', 'max:50'],
            'report_folios.*' => ['required', 'string', 'distinct', 'exists:technical_support_reports,folio'],
        ]);

        $reports = TechnicalSupportReport::query()
            ->whereIn('folio', $validated['report_folios'])
            ->get()
            ->keyBy('folio');

        $temporaryDirectory = storage_path('app/tmp/support-report-zips');
        File::ensureDirectoryExists($temporaryDirectory);
        $temporaryBase = tempnam($temporaryDirectory, 'support-reports-');

        if ($temporaryBase === false) {
            abort(500, 'No se pudo preparar la descarga de reportes.');
        }

        File::delete($temporaryBase);
        $archivePath = $temporaryBase.'.zip';

        try {
            $archive = new PharData($archivePath);

            foreach ($validated['report_folios'] as $folio) {
                $report = $reports->get($folio);
                $archive->addFromString($pdf->data($report)['filename'], $pdf->generate($report));
            }

            unset($archive);
        } catch (Throwable $exception) {
            File::delete($archivePath);
            throw $exception;
        }

        $downloadName = 'reportes-soporte-seleccion-'
            .now(config('support.timezone', 'America/Mexico_City'))->format('Ymd-His')
            .'.zip';

        return response()
            ->download($archivePath, $downloadName, [
                'Content-Type' => 'application/zip',
                'Cache-Control' => 'private, no-store, max-age=0',
                'X-Content-Type-Options' => 'nosniff',
            ])
            ->deleteFileAfterSend(true);
    }
}
