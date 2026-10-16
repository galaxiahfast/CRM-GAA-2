<?php

namespace App\Livewire\Backups;

use App\Jobs\CopyContpaqiBackups;
use App\Models\User;
use App\Services\Backups\ContpaqiBackupCatalog;
use App\Services\Backups\ContpaqiBackupExport;
use App\Services\Backups\ContpaqiBackupProcessLauncher;
use App\Services\Backups\ContpaqiBackupProfile;
use App\Services\Backups\ContpaqiSqlCompanyCatalog;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
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

    public array $selectedRecords = [];

    public ?string $currentExportId = null;

    public ?array $exportStatus = null;

    public ?string $exportMessage = null;

    public int $exportErrorVersion = 0;

    public ?array $syncResult = null;

    public string $destinationKey = '';

    public string $destinationFolder = '';

    public string $destinationBrowsePath = '';

    #[Url(as: 'departamento')]
    public string $backupProfile = '';

    public ?string $selectionNotice = null;

    public bool $showDeleteConfirmation = false;

    public array $pendingDeleteRecords = [];

    public string $deletePassword = '';

    public string $deleteTwoFactorCode = '';

    public ?string $actionMessage = null;

    public function mount(ContpaqiBackupExport $export, ContpaqiBackupProfile $profiles): void
    {
        Gate::authorize('access-system-backups');

        if (! in_array($this->activeView, ['backups', 'companies', 'history'], true)) {
            $this->activeView = 'backups';
        }

        if (! in_array($this->status, ['all', 'complete', 'partial', 'available', 'backed_up'], true)) {
            $this->status = 'all';
        }

        if ($this->selectedDate === '') {
            $this->selectedDate = now()->toDateString();
        }

        $enabledKeys = collect($profiles->profiles())->pluck('key');
        if (! $enabledKeys->contains($this->backupProfile)) {
            $this->backupProfile = $profiles->defaultKey();
        }
        $this->destinationKey = $this->destinationKeyForProfile($profiles);
        $this->adoptActiveExport($export);
    }

    public function updatedBackupProfile(ContpaqiBackupProfile $profiles): void
    {
        abort_unless(collect($profiles->profiles())->pluck('key')->contains($this->backupProfile), 422);
        $this->destinationKey = $this->destinationKeyForProfile($profiles);
        $this->destinationFolder = '';
        $this->destinationBrowsePath = '';
        $this->selectedRecords = [];
        $this->status = 'all';
        $this->selectionNotice = null;
        $this->syncResult = null;
        $this->exportMessage = null;
    }

    public function selectView(string $view): void
    {
        abort_unless(in_array($view, ['backups', 'companies', 'history'], true), 404);
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
        $this->selectionNotice = null;
    }

    public function selectAllAvailable(ContpaqiBackupCatalog $catalog, ContpaqiBackupExport $export, ContpaqiBackupProfile $profiles): void
    {
        $records = $this->profileDashboard($catalog, $profiles)['records'];
        $availability = $export->recordAvailability($this->selectedDate, $records, $this->destinationFolder, $this->destinationKey);
        $selectableStates = $this->status === 'backed_up'
            ? ['backed_up']
            : ['available', 'outdated', 'destination_missing', 'folder_missing'];
        $this->selectedRecords = collect($records)
            ->filter(fn (array $record): bool => in_array($availability[$record['id']] ?? null, $selectableStates, true))
            ->pluck('id')
            ->values()
            ->all();
        $this->selectionNotice = null;
    }

    public function toggleAllAvailable(ContpaqiBackupCatalog $catalog, ContpaqiBackupExport $export, ContpaqiBackupProfile $profiles): void
    {
        $records = $this->profileDashboard($catalog, $profiles)['records'];
        $availability = $export->recordAvailability($this->selectedDate, $records, $this->destinationFolder, $this->destinationKey);
        $selectableStates = $this->status === 'backed_up'
            ? ['backed_up']
            : ['available', 'outdated', 'destination_missing', 'folder_missing'];
        $availableIds = collect($records)
            ->filter(fn (array $record): bool => in_array($availability[$record['id']] ?? null, $selectableStates, true))
            ->pluck('id')
            ->values();

        $allSelected = $availableIds->isNotEmpty()
            && collect($this->selectedRecords)->intersect($availableIds)->count() === $availableIds->count();
        $this->selectedRecords = $allSelected ? [] : $availableIds->all();
        $this->selectionNotice = null;
    }

    public function clearSelection(): void
    {
        $this->selectedRecords = [];
        $this->selectionNotice = null;
    }

    public function updatedSelectedRecords(): void
    {
        $this->selectionNotice = null;
    }

    public function updatedStatus(string $status): void
    {
        if ($status === 'partial') {
            $this->selectedRecords = [];
            $this->selectionNotice = null;
        }
    }

    public function updatedDestinationFolder(ContpaqiBackupCatalog $catalog, ContpaqiBackupExport $export, ContpaqiBackupProfile $profiles): void
    {
        $this->syncResult = null;
        $this->exportMessage = null;
        $this->selectionNotice = null;

        if ($this->destinationFolder === '' || $this->selectedRecords === []) {
            return;
        }

        $selectedBeforeValidation = count($this->selectedRecords);
        $records = $this->profileDashboard($catalog, $profiles)['records'];
        $availability = $export->recordAvailability($this->selectedDate, $records, $this->destinationFolder, $this->destinationKey);
        $selectableIds = collect($records)
            ->filter(fn (array $record): bool => in_array($availability[$record['id']] ?? null, ['available', 'outdated', 'backed_up'], true))
            ->pluck('id');

        $this->selectedRecords = collect($this->selectedRecords)
            ->intersect($selectableIds)
            ->values()
            ->all();

        $removed = $selectedBeforeValidation - count($this->selectedRecords);
        if ($removed > 0) {
            $this->selectionNotice = $removed === 1
                ? 'Se deseleccionó 1 empresa porque ya está respaldada o no está disponible en esta carpeta.'
                : "Se deseleccionaron {$removed} empresas porque ya están respaldadas o no están disponibles en esta carpeta.";
        }
    }

    public function updatedDestinationKey(): void
    {
        $this->destinationFolder = '';
        $this->destinationBrowsePath = '';
        $this->syncResult = null;
        $this->exportMessage = null;
        $this->selectionNotice = null;
    }

    public function browseDestinationFolder(string $path, ContpaqiBackupExport $export): void
    {
        $path = trim(str_replace('\\', '/', $path), '/');
        abort_if($path === '' || str_contains($path, "\0") || collect(explode('/', $path))->contains(fn (string $segment): bool => in_array($segment, ['', '.', '..'], true)), 422);
        abort_unless($export->destinationFolderExists($path, $this->destinationKey), 422);
        $this->destinationBrowsePath = $path;
    }

    public function browseDestinationUp(): void
    {
        $this->destinationBrowsePath = str_contains($this->destinationBrowsePath, '/')
            ? (string) Str::beforeLast($this->destinationBrowsePath, '/')
            : '';
    }

    public function startExport(ContpaqiBackupCatalog $catalog, ContpaqiBackupExport $export, ContpaqiBackupProcessLauncher $launcher): void
    {
        Gate::authorize('upload-system-backups');
        $this->exportMessage = null;

        try {
            if ($this->currentExportId !== null) {
                $current = $export->find($this->currentExportId);
                if (in_array($current['status'] ?? null, ['queued', 'copying', 'cancelling'], true)) {
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
            $this->destinationKey = (string) $state['destination_key'];
            $this->currentExportId = $state['id'];
            $this->exportStatus = $state;
            if (! $launcher->launch($state['id'])) {
                CopyContpaqiBackups::dispatchAfterResponse($state['id'])->onConnection('sync');
            }
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

    public function cancelExport(ContpaqiBackupExport $export): void
    {
        Gate::authorize('upload-system-backups');

        if ($this->currentExportId === null) {
            return;
        }

        $this->exportStatus = $export->cancel($this->currentExportId);
    }

    public function openDeleteConfirmation(ContpaqiBackupCatalog $catalog, ContpaqiBackupExport $export, ContpaqiBackupProfile $profiles): void
    {
        Gate::authorize('manage-system-backups');
        $this->resetErrorBag(['deletePassword', 'deleteTwoFactorCode']);
        $this->exportMessage = null;
        $user = auth()->user();
        if ($user === null || blank($user->two_factor_secret) || blank($user->two_factor_confirmed_at)) {
            $this->exportMessage = 'Activa y confirma la autenticación en dos pasos en tu perfil antes de eliminar respaldos.';
            $this->exportErrorVersion++;

            return;
        }

        $records = $this->profileDashboard($catalog, $profiles)['records'];
        $availability = $export->recordAvailability($this->selectedDate, $records, $this->destinationFolder, $this->destinationKey);
        $deletable = collect($records)
            ->whereIn('id', $this->selectedRecords)
            ->filter(fn (array $record): bool => in_array($availability[$record['id']] ?? null, ['backed_up', 'outdated'], true))
            ->values();
        if ($deletable->isEmpty()) {
            $this->exportMessage = 'Selecciona al menos una empresa que tenga un respaldo en la carpeta elegida.';
            $this->exportErrorVersion++;

            return;
        }

        $this->pendingDeleteRecords = $deletable->pluck('id')->all();
        $this->deletePassword = '';
        $this->deleteTwoFactorCode = '';
        $this->showDeleteConfirmation = true;
    }

    public function closeDeleteConfirmation(): void
    {
        $this->showDeleteConfirmation = false;
        $this->pendingDeleteRecords = [];
        $this->deletePassword = '';
        $this->deleteTwoFactorCode = '';
        $this->resetErrorBag(['deletePassword', 'deleteTwoFactorCode']);
    }

    public function deleteDestinationBackups(
        ContpaqiBackupCatalog $catalog,
        ContpaqiBackupExport $export,
        ContpaqiBackupProfile $profiles,
        TwoFactorAuthenticationProvider $twoFactorProvider,
    ): void {
        Gate::authorize('manage-system-backups');
        $this->resetErrorBag(['deletePassword', 'deleteTwoFactorCode']);
        $this->validate([
            'deletePassword' => ['required', 'string'],
            'deleteTwoFactorCode' => ['required', 'digits:6'],
        ], [
            'deletePassword.required' => 'Escribe tu contraseña.',
            'deleteTwoFactorCode.required' => 'Escribe el código de tu autenticador.',
            'deleteTwoFactorCode.digits' => 'El código debe tener 6 dígitos.',
        ]);

        $user = auth()->user();
        $rateKey = 'delete-backup-confirmation:'.($user?->getAuthIdentifier() ?? request()->ip());
        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            $this->addError('deleteTwoFactorCode', 'Demasiados intentos. Espera '.RateLimiter::availableIn($rateKey).' segundos.');

            return;
        }
        RateLimiter::hit($rateKey, 60);

        if ($user === null || ! Hash::check($this->deletePassword, (string) $user->password)) {
            $this->addError('deletePassword', 'La contraseña no es correcta.');

            return;
        }
        try {
            $validTwoFactor = filled($user->two_factor_secret)
                && filled($user->two_factor_confirmed_at)
                && $twoFactorProvider->verify(decrypt($user->two_factor_secret), $this->deleteTwoFactorCode);
        } catch (Throwable) {
            $validTwoFactor = false;
        }
        if (! $validTwoFactor) {
            $this->addError('deleteTwoFactorCode', 'El código del autenticador no es correcto.');

            return;
        }

        $records = $this->profileDashboard($catalog, $profiles)['records'];
        $availability = $export->recordAvailability($this->selectedDate, $records, $this->destinationFolder, $this->destinationKey);
        $deletable = collect($records)
            ->whereIn('id', $this->pendingDeleteRecords)
            ->filter(fn (array $record): bool => in_array($availability[$record['id']] ?? null, ['backed_up', 'outdated'], true))
            ->values()
            ->all();

        try {
            $deleted = $export->deleteDestinationBackups(
                $this->selectedDate,
                $deletable,
                $this->destinationFolder,
                $this->destinationKey,
                (int) $user->getAuthIdentifier(),
            );
            RateLimiter::clear($rateKey);
            $this->closeDeleteConfirmation();
            $this->selectedRecords = [];
            $this->actionMessage = $deleted === 1 ? 'Se eliminó 1 respaldo de la memoria.' : "Se eliminaron {$deleted} respaldos de la memoria.";
        } catch (RuntimeException $exception) {
            $this->exportMessage = $exception->getMessage();
            $this->exportErrorVersion++;
        }
    }

    public function refreshSharedExportStatus(ContpaqiBackupExport $export): void
    {
        Gate::authorize('access-system-backups');

        if ($this->currentExportId !== null) {
            $state = $export->find($this->currentExportId);
            if ($state !== null) {
                $this->exportStatus = $state;
                if (in_array($state['status'] ?? null, ['queued', 'copying', 'cancelling'], true)) {
                    return;
                }
            }

            $this->currentExportId = null;
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

    public function verifySynchronization(ContpaqiBackupCatalog $catalog, ContpaqiBackupExport $export, ContpaqiBackupProfile $profiles, ContpaqiSqlCompanyCatalog $sqlCatalog): void
    {
        Gate::authorize('access-system-backups');

        try {
            $catalog->forget();
            $export->forgetDetectedVolumes();
            $sqlCatalog->forget((string) ($profiles->profile($this->backupProfile)['site'] ?? 'merida'));

            if ($this->selectedDate === now()->toDateString()) {
                $catalog->refreshToday();
            }

            $data = $this->profileDashboard($catalog, $profiles);
            $availability = $export->recordAvailability($this->selectedDate, $data['records'], $this->destinationFolder, $this->destinationKey);
            $completedDestinations = $export->completedDestinations($this->selectedDate, $this->destinationKey);
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

    public function render(ContpaqiBackupCatalog $catalog, ContpaqiBackupExport $export, ContpaqiBackupProfile $profiles)
    {
        Gate::authorize('access-system-backups');

        try {
            $data = $this->profileDashboard($catalog, $profiles);
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
        $completedDestinations = $export->completedDestinations($this->selectedDate, $this->destinationKey);
        $recordsForDate = collect($data['records'])
            ->when($this->status === 'complete', fn ($records) => $records->where('complete', true))
            ->when($this->status === 'available', fn ($records) => $records->filter(
                fn (array $record): bool => in_array($recordAvailability[$record['id']] ?? null, ['available', 'outdated'], true)
            ))
            ->when($this->status === 'backed_up', fn ($records) => $records->filter(
                fn (array $record): bool => ($recordAvailability[$record['id']] ?? null) === 'backed_up'
            ))
            ->when($this->status === 'partial', fn ($records) => $records->where('complete', false))
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
        })->values();

        $summary = [
            'companies' => count($data['companies']),
            'complete_today' => $completeTodayByCompany->count(),
            'partial_today' => $partialTodayByCompany->keys()->diff($completeTodayByCompany->keys())->count(),
        ];
        $summary['missing_today'] = max(0, $summary['companies'] - $summary['complete_today']);

        $selectedCompleteRecords = collect($data['records'])
            ->whereIn('id', $this->selectedRecords)
            ->filter(fn (array $record): bool => in_array($recordAvailability[$record['id']] ?? null, ['available', 'outdated', 'backed_up', 'destination_missing', 'folder_missing'], true));
        $selectedDeletableCount = $selectedCompleteRecords
            ->filter(fn (array $record): bool => in_array($recordAvailability[$record['id']] ?? null, ['backed_up', 'outdated'], true))
            ->count();
        $headerSelectableStates = $this->status === 'backed_up'
            ? ['backed_up']
            : ['available', 'outdated', 'destination_missing', 'folder_missing'];
        $headerSelectableCount = collect($recordAvailability)
            ->filter(fn (string $state): bool => in_array($state, $headerSelectableStates, true))
            ->count();
        $movementHistory = $this->activeView === 'history'
            ? collect($export->history())
            : collect();
        $historyUsers = User::query()
            ->whereIn('id', $movementHistory->pluck('user_id')->filter()->unique())
            ->get(['id', 'name', 'last_name'])
            ->mapWithKeys(fn (User $user): array => [$user->id => trim($user->name.' '.$user->last_name)]);
        $movementHistory = $movementHistory->map(function (array $movement) use ($historyUsers): array {
            $movement['user_name'] = $historyUsers->get($movement['user_id']) ?: 'Usuario no disponible';

            return $movement;
        })->all();

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
            'selectedDeletableCount' => $selectedDeletableCount,
            'headerSelectableCount' => $headerSelectableCount,
            'recordAvailability' => $recordAvailability,
            'completedDestinations' => $completedDestinations,
            'availableExportCount' => collect($recordAvailability)->filter(fn (string $state): bool => in_array($state, ['available', 'outdated', 'destination_missing', 'folder_missing'], true))->count(),
            'backedUpExportCount' => count($completedDestinations),
            'outdatedExportCount' => collect($recordAvailability)->filter(fn (string $state): bool => $state === 'outdated')->count(),
            'destinationFolders' => $export->destinationFolders($this->destinationKey, $this->destinationBrowsePath),
            'backupProfiles' => $profiles->profiles(true),
            'currentBackupProfile' => $profiles->profile($this->backupProfile),
            'movementHistory' => $movementHistory,
        ])->layout('layouts.app');
    }

    private function profileDashboard(ContpaqiBackupCatalog $catalog, ContpaqiBackupProfile $profiles): array
    {
        $dashboard = $catalog->dashboard($this->selectedDate);
        $dashboard['all_records'] = $catalog->catalog()['records'];

        return $profiles->dashboard($dashboard, $this->backupProfile);
    }

    private function destinationKeyForProfile(ContpaqiBackupProfile $profiles): string
    {
        if ($this->backupProfile !== 'merida-contabilidad') {
            return $this->backupProfile;
        }

        $legacyRoot = rtrim(str_replace('\\', '/', (string) config('backup-storage.export.destination_root')), '/');
        $profileRoot = rtrim(str_replace('\\', '/', (string) ($profiles->profile($this->backupProfile)['destination_root'] ?? '')), '/');

        return strcasecmp($legacyRoot, $profileRoot) === 0 ? $this->backupProfile : 'principal';
    }
}
