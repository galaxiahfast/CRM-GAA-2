<?php

namespace App\Livewire\Backups;

use App\Jobs\CopyContpaqiBackups;
use App\Services\Backups\ContpaqiBackupCatalog;
use App\Services\Backups\ContpaqiBackupExport;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use Livewire\Component;
use RuntimeException;
use Throwable;

class BackupStorageManager extends Component
{
    #[Url(as: 'vista')]
    public string $activeView = 'backups';

    #[Url(as: 'fecha')]
    public string $selectedDate = '';

    #[Url(as: 'estado')]
    public string $status = 'all';

    #[Url(as: 'q')]
    public string $search = '';

    public array $selectedRecords = [];

    public ?string $currentExportId = null;

    public ?array $exportStatus = null;

    public ?string $exportMessage = null;

    public int $exportErrorVersion = 0;

    public ?array $syncResult = null;

    public string $destinationKey = '';

    public string $destinationFolder = '';

    public function mount(ContpaqiBackupExport $export): void
    {
        Gate::authorize('access-system-backups');

        if (! in_array($this->activeView, ['backups', 'companies'], true)) {
            $this->activeView = 'backups';
        }

        if (! in_array($this->status, ['all', 'complete', 'partial', 'available', 'backed_up'], true)) {
            $this->status = 'all';
        }

        if ($this->selectedDate === '') {
            $this->selectedDate = now()->toDateString();
        }

        $this->destinationKey = (string) (collect($export->destinations())->first()['key'] ?? '');
        $this->adoptActiveExport($export);
    }

    public function selectView(string $view): void
    {
        abort_unless(in_array($view, ['backups', 'companies'], true), 404);
        $this->activeView = $view;
    }

    public function selectDate(string $date): void
    {
        abort_unless((bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $date), 422);
        $this->selectedDate = $date;
        $this->activeView = 'backups';
        $this->selectedRecords = [];
        $this->exportMessage = null;
        $this->syncResult = null;
    }

    public function selectAllComplete(ContpaqiBackupCatalog $catalog, ContpaqiBackupExport $export): void
    {
        $records = $catalog->dashboard($this->selectedDate)['records'];
        $availability = $export->recordAvailability($this->selectedDate, $records, $this->destinationFolder, $this->destinationKey);
        $this->selectedRecords = collect($records)
            ->filter(fn (array $record): bool => in_array($availability[$record['id']] ?? null, ['available', 'outdated', 'destination_missing', 'folder_missing'], true))
            ->pluck('id')
            ->values()
            ->all();
    }

    public function clearSelection(): void
    {
        $this->selectedRecords = [];
    }

    public function updatedDestinationFolder(ContpaqiBackupCatalog $catalog, ContpaqiBackupExport $export): void
    {
        $this->syncResult = null;
        $this->exportMessage = null;

        if ($this->destinationFolder === '' || $this->selectedRecords === []) {
            return;
        }

        $records = $catalog->dashboard($this->selectedDate)['records'];
        $availability = $export->recordAvailability($this->selectedDate, $records, $this->destinationFolder, $this->destinationKey);
        $selectableIds = collect($records)
            ->filter(fn (array $record): bool => in_array($availability[$record['id']] ?? null, ['available', 'outdated'], true))
            ->pluck('id');

        $this->selectedRecords = collect($this->selectedRecords)
            ->intersect($selectableIds)
            ->values()
            ->all();
    }

    public function updatedDestinationKey(): void
    {
        $this->destinationFolder = '';
        $this->syncResult = null;
        $this->exportMessage = null;
    }

    public function startExport(ContpaqiBackupCatalog $catalog, ContpaqiBackupExport $export): void
    {
        Gate::authorize('upload-system-backups');
        $this->exportMessage = null;

        try {
            if ($this->currentExportId !== null) {
                $current = $export->find($this->currentExportId);
                if (in_array($current['status'] ?? null, ['queued', 'copying'], true)) {
                    throw new RuntimeException('Espera a que termine la copia actual antes de iniciar otra.');
                }
            }
            $state = $export->create(
                $this->selectedDate,
                $this->selectedRecords,
                (int) auth()->id(),
                $this->destinationFolder,
                $catalog,
                $this->destinationKey,
            );
            $this->currentExportId = $state['id'];
            $this->exportStatus = $state;
            CopyContpaqiBackups::dispatch($state['id']);
            $this->refreshExportStatus($export);
            $this->selectedRecords = [];
        } catch (RuntimeException $exception) {
            $this->exportMessage = $exception->getMessage();
            $this->exportStatus = null;
            $this->currentExportId = null;
            $this->exportErrorVersion++;
        }
    }

    public function refreshExportStatus(ContpaqiBackupExport $export): void
    {
        if ($this->currentExportId === null) {
            return;
        }

        $state = $export->find($this->currentExportId);
        $this->exportStatus = $state;
    }

    public function refreshSharedExportStatus(ContpaqiBackupExport $export): void
    {
        Gate::authorize('access-system-backups');

        if ($this->currentExportId !== null) {
            $state = $export->find($this->currentExportId);
            if ($state !== null) {
                $this->exportStatus = $state;
                if (in_array($state['status'] ?? null, ['queued', 'copying'], true)) {
                    return;
                }
            }
        }

        $this->adoptActiveExport($export);
    }

    private function adoptActiveExport(ContpaqiBackupExport $export): void
    {
        $active = $export->latestActive();
        if ($active === null) {
            return;
        }

        $this->currentExportId = $active['id'];
        $this->exportStatus = $active;
    }

    public function verifySynchronization(ContpaqiBackupCatalog $catalog, ContpaqiBackupExport $export): void
    {
        Gate::authorize('access-system-backups');

        try {
            $catalog->forget();
            $export->forgetDetectedVolumes();

            if ($this->selectedDate === now()->toDateString()) {
                $catalog->refreshToday();
            }

            $data = $catalog->dashboard($this->selectedDate);
            $availability = $export->recordAvailability($this->selectedDate, $data['records'], $this->destinationFolder, $this->destinationKey);
            $completedDestinations = $export->completedDestinations($this->selectedDate);
            $latestComplete = collect($data['records'])
                ->where('complete', true)
                ->groupBy(fn (array $record): string => (string) ($record['company_key'] ?: $record['company']))
                ->map(fn ($records): array => $records->sortByDesc('occurred_at')->first());
            $completedRecordIds = collect(array_keys($completedDestinations));
            $completedCompanyKeys = collect($data['records'])
                ->whereIn('id', $completedRecordIds)
                ->map(fn (array $record): string => (string) ($record['company_key'] ?: $record['company']))
                ->unique();
            $latestStates = $latestComplete->map(function (array $record) use ($availability, $completedDestinations, $completedCompanyKeys): string {
                if (isset($completedDestinations[$record['id']])) {
                    return 'backed_up';
                }

                return $completedCompanyKeys->contains((string) ($record['company_key'] ?: $record['company']))
                    ? 'outdated'
                    : ($availability[$record['id']] ?? 'destination_missing');
            });

            $this->syncResult = [
                'checked_at' => now()->toIso8601String(),
                'server_complete' => $latestComplete->count(),
                'server_incomplete' => collect($data['records'])->where('complete', false)->count(),
                'synced' => $latestStates->filter(fn (string $state): bool => $state === 'backed_up')->count(),
                'pending' => $latestStates->filter(fn (string $state): bool => in_array($state, ['available', 'destination_missing', 'folder_missing'], true))->count(),
                'outdated' => $latestStates->filter(fn (string $state): bool => $state === 'outdated')->count(),
                'copying' => $latestStates->filter(fn (string $state): bool => $state === 'copying')->count(),
                'destination_connected' => $export->destination($this->destinationKey)['connected'],
                'error' => null,
            ];
            $this->dispatch('backup-catalog-refreshed');
        } catch (RuntimeException $exception) {
            $this->syncResult = [
                'checked_at' => now()->toIso8601String(),
                'error' => $exception->getMessage(),
            ];
        }
    }

    public function render(ContpaqiBackupCatalog $catalog, ContpaqiBackupExport $export)
    {
        Gate::authorize('access-system-backups');

        try {
            $data = $catalog->dashboard($this->selectedDate);
            $error = null;
        } catch (Throwable $exception) {
            $data = [
                'connected' => false,
                'root' => config('backup-storage.server.root'),
                'scanned_at' => null,
                'scan_seconds' => null,
                'companies' => [],
                'records' => [],
                'today_records' => [],
                'dates' => [],
                'last_by_company' => [],
            ];
            $error = $exception instanceof RuntimeException
                ? $exception->getMessage()
                : 'La conexión con el servidor se interrumpió durante la lectura.';
        }

        $today = now()->toDateString();
        $recordAvailability = $export->recordAvailability($this->selectedDate, $data['records'], $this->destinationFolder, $this->destinationKey);
        $completedDestinations = $export->completedDestinations($this->selectedDate);
        $term = Str::lower(Str::ascii(trim($this->search)));
        $recordsForDate = collect($data['records'])
            ->when($this->status === 'complete', fn ($records) => $records->where('complete', true))
            ->when($this->status === 'available', fn ($records) => $records->filter(
                fn (array $record): bool => in_array($recordAvailability[$record['id']] ?? null, ['available', 'outdated'], true)
            ))
            ->when($this->status === 'backed_up', fn ($records) => $records->filter(
                fn (array $record): bool => isset($completedDestinations[$record['id']])
            ))
            ->when($this->status === 'partial', fn ($records) => $records->where('complete', false))
            ->when($term !== '', fn ($records) => $records->filter(fn (array $record): bool => str_contains(
                Str::lower(Str::ascii($record['company'].' '.$record['stem'].' '.($record['company_folder'] ?? ''))),
                $term,
            )))
            ->values();

        $todayRecords = collect($data['today_records']);
        $completeTodayByCompany = $todayRecords
            ->where('complete', true)
            ->whereNotNull('company_key')
            ->groupBy('company_key');
        $partialTodayByCompany = $todayRecords
            ->where('complete', false)
            ->whereNotNull('company_key')
            ->groupBy('company_key');

        $companies = collect($data['companies'])->map(function (array $company) use ($completeTodayByCompany, $partialTodayByCompany, $data): array {
            $todayStatus = $completeTodayByCompany->has($company['key'])
                ? 'complete'
                : ($partialTodayByCompany->has($company['key']) ? 'partial' : 'missing');
            $last = $data['last_by_company'][$company['key']] ?? null;

            return $company + [
                'today_status' => $todayStatus,
                'today_records' => $completeTodayByCompany->get($company['key'], collect())->count()
                    + $partialTodayByCompany->get($company['key'], collect())->count(),
                'last_backup' => $last,
            ];
        })->when($term !== '', fn ($items) => $items->filter(fn (array $company): bool => str_contains(
            Str::lower(Str::ascii($company['name'].' '.$company['folder'])),
            $term,
        )))->values();

        $summary = [
            'companies' => count($data['companies']),
            'complete_today' => $completeTodayByCompany->count(),
            'partial_today' => $partialTodayByCompany->keys()->diff($completeTodayByCompany->keys())->count(),
        ];
        $summary['missing_today'] = max(0, $summary['companies'] - $summary['complete_today']);

        $selectedCompleteRecords = collect($data['records'])
            ->whereIn('id', $this->selectedRecords)
            ->filter(fn (array $record): bool => in_array($recordAvailability[$record['id']] ?? null, ['available', 'outdated', 'destination_missing', 'folder_missing'], true));

        return view('livewire.backups.backup-storage-manager', [
            'catalog' => $data,
            'error' => $error,
            'today' => $today,
            'records' => $recordsForDate,
            'companies' => $companies,
            'summary' => $summary,
            'exportDestinations' => $export->destinations(),
            'exportDestination' => $export->destination($this->destinationKey),
            'selectedExportCount' => $selectedCompleteRecords->count(),
            'selectedExportBytes' => (int) $selectedCompleteRecords->sum('total_size'),
            'recordAvailability' => $recordAvailability,
            'completedDestinations' => $completedDestinations,
            'availableExportCount' => collect($recordAvailability)->filter(fn (string $state): bool => in_array($state, ['available', 'outdated', 'destination_missing', 'folder_missing'], true))->count(),
            'backedUpExportCount' => count($completedDestinations),
            'outdatedExportCount' => collect($recordAvailability)->filter(fn (string $state): bool => $state === 'outdated')->count(),
            'destinationFolders' => $export->destinationFolders($this->destinationKey),
        ])->layout('layouts.app');
    }
}
