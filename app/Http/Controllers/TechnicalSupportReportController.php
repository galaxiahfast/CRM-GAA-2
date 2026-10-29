<?php

namespace App\Http\Controllers;

use App\Models\TechnicalSupportReport;
use App\Models\TechnicalSupportReportMovement;
use App\Services\Support\TechnicalSupportReportPdfService;
use Carbon\CarbonImmutable;
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
            'trashedReports' => $this->trashedReports(),
        ]);
    }

    public function history(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $recipient = trim((string) $request->query('recipient', ''));
        $target = trim((string) $request->query('target', ''));
        $requestedDate = trim((string) $request->query('date', ''));
        $timezone = config('support.timezone', 'America/Mexico_City');

        $filterReports = TechnicalSupportReport::query()
            ->get(['service_recipient', 'supports', 'issued_at']);

        $dateOptions = $filterReports
            ->groupBy(fn (TechnicalSupportReport $report): string => $report->issued_at->timezone($timezone)->format('Y-m-d'))
            ->map(function ($reports, string $date) use ($timezone): array {
                $localDate = CarbonImmutable::createFromFormat('Y-m-d', $date, $timezone)->locale('es');

                return [
                    'date' => $date,
                    'label' => ucfirst($localDate->translatedFormat('l, d \d\e F \d\e Y')),
                    'count' => $reports->count(),
                ];
            })
            ->sortByDesc('date')
            ->values();

        $date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $requestedDate) === 1
            && $dateOptions->contains('date', $requestedDate)
                ? $requestedDate
                : (string) ($dateOptions->first()['date'] ?? '');

        $localDateStart = $date !== ''
            ? CarbonImmutable::createFromFormat('Y-m-d H:i:s', $date.' 00:00:00', $timezone)
            : null;

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
            ->when($localDateStart !== null, fn ($query) => $query->whereBetween('issued_at', [
                $localDateStart->utc(),
                $localDateStart->endOfDay()->utc(),
            ]))
            ->latest('issued_at')
            ->latest('id');

        return view('support.technical-reports', [
            'activeTab' => 'history',
            'trashedReports' => $this->trashedReports(),
            'reports' => $reportsQuery->paginate(20)->withQueryString(),
            'filters' => compact('search', 'recipient', 'target', 'date'),
            'dateOptions' => $dateOptions,
            'folioOptions' => TechnicalSupportReport::query()
                ->when($localDateStart !== null, fn ($query) => $query->whereBetween('issued_at', [
                    $localDateStart->utc(),
                    $localDateStart->endOfDay()->utc(),
                ]))
                ->latest('issued_at')
                ->latest('id')
                ->pluck('folio')
                ->values(),
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

    public function movementHistory(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $action = trim((string) $request->query('action', ''));
        $actor = trim((string) $request->query('user', ''));
        $requestedDate = trim((string) $request->query('date', ''));
        $timezone = config('support.timezone', 'America/Mexico_City');

        if (! in_array($action, [
            TechnicalSupportReportMovement::ACTION_CREATED,
            TechnicalSupportReportMovement::ACTION_DELETED,
            TechnicalSupportReportMovement::ACTION_RESTORED,
            TechnicalSupportReportMovement::ACTION_PURGED,
        ], true)) {
            $action = '';
        }

        $allMovements = TechnicalSupportReportMovement::query()
            ->with('user:id,name')
            ->get(['id', 'folio', 'action', 'user_id', 'user_name', 'occurred_at']);

        $dateOptions = $allMovements
            ->groupBy(fn (TechnicalSupportReportMovement $movement): string => $movement->occurred_at->timezone($timezone)->format('Y-m-d'))
            ->map(function ($movements, string $date) use ($timezone): array {
                $localDate = CarbonImmutable::createFromFormat('Y-m-d', $date, $timezone)->locale('es');

                return [
                    'date' => $date,
                    'label' => ucfirst($localDate->translatedFormat('l, d \d\e F \d\e Y')),
                    'count' => $movements->count(),
                ];
            })
            ->sortByDesc('date')
            ->values();

        $date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $requestedDate) === 1
            && $dateOptions->contains('date', $requestedDate)
                ? $requestedDate
                : (string) ($dateOptions->first()['date'] ?? '');

        $localDateStart = $date !== ''
            ? CarbonImmutable::createFromFormat('Y-m-d H:i:s', $date.' 00:00:00', $timezone)
            : null;

        $inSelectedDate = fn ($query) => $query->when($localDateStart !== null, fn ($query) => $query->whereBetween('occurred_at', [
            $localDateStart->utc(),
            $localDateStart->endOfDay()->utc(),
        ]));

        $movementsQuery = TechnicalSupportReportMovement::query()
            ->with('user:id,name,email')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('folio', 'like', '%'.$search.'%')
                        ->orWhere('user_name', 'like', '%'.$search.'%')
                        ->orWhere('user_email', 'like', '%'.$search.'%');
                });
            })
            ->when($action !== '', fn ($query) => $query->where('action', $action))
            ->when($actor !== '', function ($query) use ($actor): void {
                $query->where(function ($query) use ($actor): void {
                    $query->where('user_name', $actor)
                        ->orWhereHas('user', fn ($user) => $user->where('name', $actor));
                });
            });

        $movementsQuery = $inSelectedDate($movementsQuery)
            ->latest('occurred_at')
            ->latest('id');

        return view('support.technical-reports', [
            'activeTab' => 'movements',
            'trashedReports' => $this->trashedReports(),
            'movements' => $movementsQuery->paginate(30)->withQueryString(),
            'filters' => compact('search', 'action', 'date') + ['user' => $actor],
            'dateOptions' => $dateOptions,
            'folioOptions' => $inSelectedDate(TechnicalSupportReportMovement::query())
                ->latest('occurred_at')
                ->latest('id')
                ->pluck('folio')
                ->unique()
                ->values(),
            'actionOptions' => [
                ['value' => TechnicalSupportReportMovement::ACTION_CREATED, 'label' => 'Reporte generado'],
                ['value' => TechnicalSupportReportMovement::ACTION_DELETED, 'label' => 'Reporte eliminado'],
                ['value' => TechnicalSupportReportMovement::ACTION_RESTORED, 'label' => 'Reporte restaurado'],
                ['value' => TechnicalSupportReportMovement::ACTION_PURGED, 'label' => 'Borrado definitivo'],
            ],
            'userOptions' => $allMovements
                ->map(fn (TechnicalSupportReportMovement $movement): string => trim((string) ($movement->user_name ?: $movement->user?->name)))
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

        $actor = $request->user();

        $report = DB::transaction(function () use ($selectedSupports, $details, $validated, $actor): TechnicalSupportReport {
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

            $report->movements()->create([
                'folio' => $report->folio,
                'action' => TechnicalSupportReportMovement::ACTION_CREATED,
                'user_id' => $actor?->id,
                'user_name' => $actor?->name,
                'user_email' => $actor?->email,
                'report_snapshot' => $report->fresh()->toArray(),
                'occurred_at' => now(),
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

    public function destroy(Request $request, TechnicalSupportReport $report): RedirectResponse
    {
        $user = $request->user();

        // Solo el administrador o quien generó el reporte puede eliminarlo.
        abort_unless($user !== null && ($user->isAdmin() || (int) $report->created_by === (int) $user->id), 403);

        $folio = $report->folio;

        DB::transaction(function () use ($report, $user): void {
            $report->movements()->create([
                'folio' => $report->folio,
                'action' => TechnicalSupportReportMovement::ACTION_DELETED,
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_email' => $user->email,
                'report_snapshot' => $report->toArray(),
                'occurred_at' => now(),
            ]);

            $report->forceFill(['deleted_by' => $user->id])->save();
            $report->delete();
        });

        return back()->with('status', 'Reporte '.$folio.' enviado a la papelera.');
    }

    public function restore(Request $request, TechnicalSupportReport $report): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeTrashAction($user, $report);

        DB::transaction(function () use ($report, $user): void {
            $report->restore();
            $report->forceFill(['deleted_by' => null])->save();

            $report->movements()->create([
                'folio' => $report->folio,
                'action' => TechnicalSupportReportMovement::ACTION_RESTORED,
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_email' => $user->email,
                'report_snapshot' => $report->toArray(),
                'occurred_at' => now(),
            ]);
        });

        return back()->with('status', 'Reporte '.$report->folio.' restaurado correctamente.');
    }

    public function purge(Request $request, TechnicalSupportReport $report): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeTrashAction($user, $report);

        $folio = $report->folio;

        DB::transaction(function () use ($report, $user): void {
            $report->movements()->create([
                'folio' => $report->folio,
                'action' => TechnicalSupportReportMovement::ACTION_PURGED,
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_email' => $user->email,
                'report_snapshot' => $report->toArray(),
                'occurred_at' => now(),
            ]);

            $report->forceDelete();
        });

        return back()->with('status', 'Reporte '.$folio.' eliminado de forma permanente.');
    }

    /**
     * Reportes en la papelera que el usuario puede gestionar: el administrador ve todos;
     * los demás, solo los que generaron.
     */
    private function trashedReports()
    {
        $user = auth()->user();

        return TechnicalSupportReport::onlyTrashed()
            ->when($user !== null && ! $user->isAdmin(), fn ($query) => $query->where('created_by', $user->id))
            ->latest('deleted_at')
            ->limit(200)
            ->get();
    }

    private function authorizeTrashAction($user, TechnicalSupportReport $report): void
    {
        abort_unless(
            $user !== null
            && $report->trashed()
            && ($user->isAdmin() || (int) $report->created_by === (int) $user->id),
            403,
        );
    }

    public function downloadSelection(
        Request $request,
        TechnicalSupportReportPdfService $pdf,
    ): BinaryFileResponse {
        $validated = $request->validate([
            'report_folios' => ['required', 'array', 'min:1', 'max:50'],
            'report_folios.*' => ['required', 'string', 'distinct', Rule::exists('technical_support_reports', 'folio')->whereNull('deleted_at')],
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
