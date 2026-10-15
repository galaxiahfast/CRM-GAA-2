<?php

namespace App\Services\Backups;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ContpaqiBackupExport
{
    public function destinations(): array
    {
        return collect($this->destinationDefinitions())
            ->map(fn (array $definition): array => $this->inspectDestination($definition))
            ->values()
            ->all();
    }

    public function forgetDetectedVolumes(): void
    {
        Cache::forget('contpaqi-backup-export:local-volumes');
    }

    public function destination(?string $key = null): array
    {
        $definitions = $this->destinationDefinitions();
        $definition = $key === null || $key === ''
            ? ($definitions[0] ?? null)
            : (collect($definitions)->firstWhere('key', $key) ?? ($definitions[0] ?? null));

        if ($definition === null) {
            return ['key' => $key, 'configured' => false, 'connected' => false, 'root' => null, 'free_bytes' => null, 'label' => 'Memoria no seleccionada'];
        }

        return $this->inspectDestination($definition);
    }

    private function inspectDestination(array $definition): array
    {
        $configuredPath = trim((string) ($definition['root'] ?? ''));
        $volumeLabel = trim((string) ($definition['label'] ?? 'Memoria externa'));
        $key = (string) ($definition['key'] ?? '');
        if ($configuredPath === '') {
            return ['key' => $key, 'configured' => false, 'connected' => false, 'writable' => false, 'root' => null, 'free_bytes' => null, 'label' => $volumeLabel, 'type' => $definition['type'] ?? null];
        }

        if (! $this->isAbsolutePath($configuredPath)) {
            return ['key' => $key, 'configured' => true, 'connected' => false, 'writable' => false, 'root' => $configuredPath, 'free_bytes' => null, 'label' => $volumeLabel, 'type' => $definition['type'] ?? null];
        }

        $realPath = realpath($configuredPath);
        $connected = $realPath !== false && is_dir($realPath) && is_readable($realPath) && ! $this->overlapsSource($realPath);

        return [
            'key' => $key,
            'configured' => true,
            'connected' => $connected,
            'writable' => $connected && is_writable($realPath),
            'root' => $realPath !== false ? $realPath : $configuredPath,
            'free_bytes' => $connected ? (@disk_free_space($realPath) ?: null) : null,
            'label' => $volumeLabel,
            'type' => $definition['type'] ?? null,
        ];
    }

    public function destinationFolders(?string $destinationKey = null): array
    {
        $destination = $this->destination($destinationKey);
        if (! $destination['connected']) {
            return [];
        }

        try {
            return collect(new \FilesystemIterator($destination['root'], \FilesystemIterator::SKIP_DOTS))
                ->filter(fn (\SplFileInfo $item): bool => $item->isDir() && ! $item->isLink() && ! str_starts_with($item->getFilename(), '$'))
                ->map(fn (\SplFileInfo $item): array => [
                    'name' => $item->getFilename(),
                    'writable' => is_writable($item->getPathname()),
                ])
                ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                ->values()
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    public function create(string $date, array $recordIds, int $userId, string $destinationFolder, ContpaqiBackupCatalog $catalog, ?string $destinationKey = null): array
    {
        return Cache::lock('contpaqi-backup-export:create', 15)->block(5, function () use ($date, $recordIds, $userId, $destinationFolder, $catalog, $destinationKey): array {
            if ($this->latestActive() !== null) {
                throw new RuntimeException('Ya hay un respaldo en proceso. Todos los usuarios pueden ver su avance; espera a que termine.');
            }

            $destination = $this->requireDestination($destinationKey);
            $folderPath = $this->requireDestinationFolder($destinationFolder, $destination['root']);
            $allRecords = $catalog->dashboard($date)['records'];
            $records = $this->selectedRecords($allRecords, $date, $recordIds);
            $availability = $this->recordAvailability($date, $allRecords, $destinationFolder, $destination['key']);

            foreach ($records as $record) {
                $status = $availability[$record['id']] ?? 'unavailable';
                if ($status === 'backed_up') {
                    throw new RuntimeException('Una de las empresas seleccionadas ya fue respaldada en esta memoria para ese día.');
                }
                if ($status === 'copying') {
                    throw new RuntimeException('Una de las empresas seleccionadas ya se está copiando.');
                }
                if (! in_array($status, ['available', 'outdated'], true)) {
                    throw new RuntimeException('La selección cambió. Elige únicamente empresas disponibles para respaldar.');
                }
            }

            $requiredBytes = (int) collect($records)->sum('total_size');
            if ($destination['free_bytes'] !== null && $destination['free_bytes'] < $requiredBytes + (64 * 1024 * 1024)) {
                throw new RuntimeException('La memoria externa no tiene espacio suficiente para la selección.');
            }

            $id = (string) Str::uuid();
            $state = [
                'id' => $id,
                'status' => 'queued',
                'date' => $date,
                'record_ids' => array_values(array_unique($recordIds)),
                'user_id' => $userId,
                'destination_root' => $destination['root'],
                'destination_key' => $destination['key'],
                'destination_label' => $destination['label'],
                'destination_folder' => basename($folderPath),
                'destination_path' => null,
                'total_files' => count($records) * 2,
                'completed_files' => 0,
                'total_bytes' => $requiredBytes,
                'copied_bytes' => 0,
                'current_company' => null,
                'items' => collect($records)->map(fn (array $record): array => [
                    'id' => $record['id'],
                    'company_key' => $this->companyKey($record),
                    'company' => $record['company'],
                    'company_folder' => $record['company_folder'] ?: $record['company'],
                    'status' => 'pending',
                    'destination_path' => null,
                    'files' => [],
                ])->values()->all(),
                'error' => null,
                'created_at' => now()->toIso8601String(),
                'started_at' => null,
                'completed_at' => null,
            ];
            $this->write($id, $state);

            return $state;
        });
    }

    public function execute(string $id, ContpaqiBackupCatalog $catalog): void
    {
        $state = $this->find($id);
        if ($state === null || in_array($state['status'] ?? null, ['completed', 'cancelled'], true)) {
            return;
        }

        if (($state['status'] ?? null) === 'cancelling') {
            $this->completeCancellation($id, $state);

            return;
        }

        try {
            $destination = $this->destinationFromState($state);

            $records = $this->selectedRecords($catalog->dashboard($state['date'])['records'], $state['date'], $state['record_ids']);
            $baseDirectory = $this->requireDestinationFolder($state['destination_folder'], $destination['root']);
            $dateDirectory = $this->join($baseDirectory, $this->dateFolder($state['date']));
            File::ensureDirectoryExists($dateDirectory);

            $state['status'] = 'copying';
            $state['started_at'] = now()->toIso8601String();
            $state['destination_path'] = $dateDirectory;
            $this->throwIfCancellationRequested($id);
            $this->write($id, $state);

            foreach ($records as $recordIndex => $record) {
                $this->throwIfCancellationRequested($id);
                $companyDirectory = $this->join($dateDirectory, $this->safeSegment($record['company_folder'] ?: $record['company']));
                $databaseDirectory = $this->join($companyDirectory, 'BAK');
                $archiveDirectory = $this->join($companyDirectory, 'INDEX');
                File::ensureDirectoryExists($databaseDirectory);
                File::ensureDirectoryExists($archiveDirectory);
                $state['current_company'] = $record['company'];
                $state['items'][$recordIndex]['status'] = 'copying';
                $state['items'][$recordIndex]['destination_path'] = $companyDirectory;
                $this->throwIfCancellationRequested($id);
                $this->write($id, $state);

                foreach ([['database', 'database'], ['archive', 'archive']] as [$kind, $field]) {
                    $this->throwIfCancellationRequested($id);
                    $file = $record[$field];
                    $source = $catalog->resolveDownload($kind, $file['token']);
                    $target = $this->join($kind === 'database' ? $databaseDirectory : $archiveDirectory, basename($file['name']));
                    $result = $this->copyVerified($source, $target, $id, $state);
                    $state['completed_files']++;
                    $state['items'][$recordIndex]['files'][] = $result;
                    $this->throwIfCancellationRequested($id);
                    $this->write($id, $state);
                }

                $state['items'][$recordIndex]['status'] = 'completed';
                $this->throwIfCancellationRequested($id);
                $this->write($id, $state);
            }

            $this->throwIfCancellationRequested($id);
            $state['status'] = 'completed';
            $state['current_company'] = null;
            $state['copied_bytes'] = $state['total_bytes'];
            $state['completed_at'] = now()->toIso8601String();
            $this->write($id, $state);

            $manifest = $state;
            unset($manifest['record_ids']);
            $manifestWritten = file_put_contents(
                $this->join($dateDirectory, '_registro-'.$id.'.json'),
                json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                LOCK_EX,
            );
            if ($manifestWritten === false) {
                throw new RuntimeException('Los archivos se copiaron, pero no fue posible guardar su registro de verificación.');
            }
        } catch (Throwable $exception) {
            $latestState = $this->find($id);
            if (($latestState['status'] ?? null) === 'cancelling') {
                $this->completeCancellation($id, $latestState);

                return;
            }

            $state['status'] = 'failed';
            $state['error'] = $exception instanceof RuntimeException
                ? $exception->getMessage()
                : 'La copia se interrumpió inesperadamente.';
            $state['current_company'] = null;
            $state['completed_at'] = now()->toIso8601String();
            $this->write($id, $state);
            throw $exception;
        }
    }

    public function find(string $id): ?array
    {
        if (! Str::isUuid($id)) {
            return null;
        }

        $path = $this->statePath($id);
        if (! is_file($path)) {
            return null;
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return null;
        }

        try {
            flock($handle, LOCK_SH);
            $contents = stream_get_contents($handle);
            flock($handle, LOCK_UN);
        } finally {
            fclose($handle);
        }

        $state = json_decode($contents ?: '', true);

        return is_array($state) ? $state : null;
    }

    public function latestActive(): ?array
    {
        if (! is_dir($this->stateDirectory())) {
            return null;
        }

        return collect(File::glob($this->stateDirectory().DIRECTORY_SEPARATOR.'*.json'))
            ->map(fn (string $path): ?array => $this->find(pathinfo($path, PATHINFO_FILENAME)))
            ->filter(function (?array $state): bool {
                if ($state === null || ! in_array($state['status'] ?? null, ['queued', 'copying', 'cancelling'], true)) {
                    return false;
                }
                $activityAt = $state['started_at'] ?? $state['created_at'] ?? null;

                return $activityAt !== null && now()->diffInHours($activityAt, absolute: true) <= 3;
            })
            ->sortByDesc(fn (array $state): string => (string) ($state['started_at'] ?? $state['created_at'] ?? ''))
            ->first();
    }

    /** @return array<string, array<int, array{label: string, path: string, completed_at: ?string}>> */
    public function completedDestinations(string $date): array
    {
        if (! is_dir($this->stateDirectory())) {
            return [];
        }

        $destinations = [];
        foreach (File::glob($this->stateDirectory().DIRECTORY_SEPARATOR.'*.json') as $path) {
            $state = $this->find(pathinfo($path, PATHINFO_FILENAME));
            if ($state === null || ($state['status'] ?? null) !== 'completed' || ($state['date'] ?? null) !== $date) {
                continue;
            }

            foreach ($state['items'] ?? [] as $item) {
                if (($item['status'] ?? null) !== 'completed' || ! filled($item['id'] ?? null)) {
                    continue;
                }

                $destinations[(string) $item['id']][] = [
                    'label' => (string) ($state['destination_label'] ?? 'Memoria externa'),
                    'path' => (string) ($item['destination_path'] ?? $state['destination_path'] ?? $state['destination_root'] ?? ''),
                    'completed_at' => $state['completed_at'] ?? null,
                ];
            }
        }

        foreach ($destinations as &$recordDestinations) {
            usort($recordDestinations, fn (array $left, array $right): int => strcmp((string) ($right['completed_at'] ?? ''), (string) ($left['completed_at'] ?? '')));
        }

        return $destinations;
    }

    public function markFailed(string $id, string $message): void
    {
        $state = $this->find($id);
        if ($state === null || in_array($state['status'], ['completed', 'failed', 'cancelled', 'cancelling'], true)) {
            return;
        }

        $state['status'] = 'failed';
        $state['current_company'] = null;
        $state['error'] = mb_substr($message, 0, 1000);
        $state['completed_at'] = now()->toIso8601String();
        $this->write($id, $state);
    }

    public function cancel(string $id): ?array
    {
        $state = $this->find($id);
        if ($state === null || ! in_array($state['status'] ?? null, ['queued', 'copying', 'cancelling'], true)) {
            return $state;
        }

        $state['status'] = 'cancelling';
        $state['error'] = null;
        $this->write($id, $state);

        return $state;
    }

    /** @return array<string, string> */
    public function recordAvailability(string $date, array $records, ?string $destinationFolder = null, ?string $destinationKey = null): array
    {
        $destination = $this->destination($destinationKey);
        $latestCompleteByCompany = collect($records)
            ->where('complete', true)
            ->groupBy(fn (array $record): string => $this->companyKey($record))
            ->map(fn ($companyRecords): array => $companyRecords->sortByDesc('occurred_at')->first());
        $latestIds = $latestCompleteByCompany->pluck('id')->flip();
        $folderPath = $destination['connected'] && filled($destinationFolder)
            ? $this->resolveDestinationFolder((string) $destinationFolder, $destination['root'])
            : null;
        $destinationCopies = $folderPath !== null
            ? $this->destinationCopies($date, collect($records)->where('complete', true)->values()->all(), $folderPath)
            : ['record_ids' => [], 'company_keys' => []];
        $copyingCompanies = $folderPath !== null
            ? $this->activeCompanyKeys($date, $destination['root'], basename($folderPath))
            : [];

        return collect($records)->mapWithKeys(function (array $record) use ($destination, $folderPath, $latestIds, $destinationCopies, $copyingCompanies): array {
            $companyKey = $this->companyKey($record);
            $status = match (true) {
                ! $record['complete'] => 'incomplete',
                ! $latestIds->has($record['id']) => 'older',
                in_array($companyKey, $copyingCompanies, true) => 'copying',
                in_array($record['id'], $destinationCopies['record_ids'], true) => 'backed_up',
                ! $destination['connected'] => 'destination_missing',
                $folderPath === null => 'folder_missing',
                in_array($companyKey, $destinationCopies['company_keys'], true) => 'outdated',
                default => 'available',
            };

            return [$record['id'] => $status];
        })->all();
    }

    private function selectedRecords(array $allRecords, string $date, array $recordIds): array
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            throw new RuntimeException('La fecha seleccionada no es válida.');
        }

        $ids = collect($recordIds)
            ->filter(fn ($id): bool => is_string($id) && preg_match('/^[a-f0-9]{64}$/', $id) === 1)
            ->unique()
            ->values();
        $records = collect($allRecords)
            ->where('complete', true)
            ->whereIn('id', $ids)
            ->values();

        if ($ids->isEmpty() || $records->count() !== $ids->count()) {
            throw new RuntimeException('Selecciona únicamente empresas que tengan su BAK y ZIP o RAR completos.');
        }

        return $records->all();
    }

    private function destinationCopies(string $date, array $completeRecords, string $destinationFolderPath): array
    {
        $dateDirectory = $this->join($destinationFolderPath, $this->dateFolder($date));
        if (! is_dir($dateDirectory)) {
            return ['record_ids' => [], 'company_keys' => []];
        }

        $recordIds = [];
        $companyKeys = [];
        foreach ($completeRecords as $record) {
            $companyKey = $this->companyKey($record);
            $companyDirectory = $this->join($dateDirectory, $this->safeSegment($record['company_folder'] ?: $record['company']));
            if (! is_dir($companyDirectory)) {
                continue;
            }

            $database = $this->join($companyDirectory, 'BAK', basename($record['database']['name']));
            $archive = $this->join($companyDirectory, 'INDEX', basename($record['archive']['name']));

            if (is_file($database) && is_file($archive)
                && filesize($database) === $record['database']['size']
                && filesize($archive) === $record['archive']['size']) {
                $recordIds[] = $record['id'];
                $companyKeys[] = $companyKey;
            }
        }

        return [
            'record_ids' => array_values(array_unique($recordIds)),
            'company_keys' => array_values(array_unique($companyKeys)),
        ];
    }

    private function activeCompanyKeys(string $date, string $destinationRoot, string $destinationFolder): array
    {
        if (! is_dir($this->stateDirectory())) {
            return [];
        }

        $keys = [];
        foreach (File::glob($this->stateDirectory().DIRECTORY_SEPARATOR.'*.json') as $path) {
            $id = pathinfo($path, PATHINFO_FILENAME);
            $state = $this->find($id);
            if ($state === null || $state['date'] !== $date || ! in_array($state['status'], ['queued', 'copying', 'cancelling'], true)) {
                continue;
            }
            if ($this->normalize((string) ($state['destination_root'] ?? '')) !== $this->normalize($destinationRoot)) {
                continue;
            }
            if (Str::lower((string) ($state['destination_folder'] ?? '')) !== Str::lower($destinationFolder)) {
                continue;
            }
            $activityAt = $state['started_at'] ?? $state['created_at'] ?? null;
            if ($activityAt !== null && now()->diffInHours($activityAt, absolute: true) > 3) {
                continue;
            }
            foreach ($state['items'] ?? [] as $item) {
                if (filled($item['company_key'] ?? null)) {
                    $keys[] = $item['company_key'];
                }
            }
        }

        return array_values(array_unique($keys));
    }

    private function companyKey(array $record): string
    {
        if (filled($record['company_key'] ?? null)) {
            return (string) $record['company_key'];
        }

        return Str::lower(preg_replace('/[^a-z0-9]+/i', '', Str::ascii((string) $record['company'])) ?: $record['id']);
    }

    private function copyVerified(string $source, string $target, string $exportId, array &$state): array
    {
        $sourceSize = filesize($source);
        if ($sourceSize === false) {
            throw new RuntimeException('No fue posible leer el tamaño de '.basename($source).'.');
        }

        if (is_file($target)) {
            if (filesize($target) !== $sourceSize || hash_file('sha256', $target) !== hash_file('sha256', $source)) {
                throw new RuntimeException('Ya existe un archivo diferente en el destino: '.basename($target).'. No se sobrescribió.');
            }

            $state['copied_bytes'] += $sourceSize;

            return ['name' => basename($target), 'size' => $sourceSize, 'status' => 'existing', 'checksum' => hash_file('sha256', $source)];
        }

        $partial = $target.'.partial-'.$exportId;
        $input = fopen($source, 'rb');
        $output = fopen($partial, 'xb');
        if ($input === false || $output === false) {
            if (is_resource($input)) {
                fclose($input);
            }
            if (is_resource($output)) {
                fclose($output);
            }
            @unlink($partial);
            throw new RuntimeException('No fue posible abrir '.basename($source).' para copiarlo.');
        }

        $hash = hash_init('sha256');
        $written = 0;
        $pendingProgress = 0;
        $chunkSize = max(1024 * 1024, (int) config('backup-storage.export.chunk_size', 16 * 1024 * 1024));
        $progressInterval = max(4 * 1024 * 1024, min(16 * 1024 * 1024, $chunkSize));

        try {
            try {
                while (! feof($input)) {
                    $chunk = fread($input, $chunkSize);
                    if ($chunk === false) {
                        throw new RuntimeException('Se interrumpió la lectura de '.basename($source).'.');
                    }
                    if ($chunk === '') {
                        continue;
                    }
                    hash_update($hash, $chunk);
                    $length = strlen($chunk);
                    $offset = 0;
                    while ($offset < $length) {
                        $bytes = fwrite($output, substr($chunk, $offset));
                        if ($bytes === false || $bytes === 0) {
                            throw new RuntimeException('Se interrumpió la escritura en la memoria externa.');
                        }
                        $offset += $bytes;
                    }
                    $written += $length;
                    $pendingProgress += $length;
                    if ($pendingProgress >= $progressInterval) {
                        $this->throwIfCancellationRequested($exportId);
                        $state['copied_bytes'] += $pendingProgress;
                        $pendingProgress = 0;
                        $this->write($exportId, $state);
                    }
                }
                fflush($output);
            } finally {
                fclose($input);
                fclose($output);
            }
        } catch (Throwable $exception) {
            @unlink($partial);
            throw $exception;
        }

        $state['copied_bytes'] += $pendingProgress;
        clearstatcache(true, $partial);
        if ($written !== $sourceSize || filesize($partial) !== $sourceSize) {
            @unlink($partial);
            throw new RuntimeException('La verificación de tamaño falló para '.basename($source).'.');
        }
        if (! rename($partial, $target)) {
            @unlink($partial);
            throw new RuntimeException('No fue posible finalizar '.basename($source).' en la memoria externa.');
        }

        return ['name' => basename($target), 'size' => $sourceSize, 'status' => 'copied', 'checksum' => hash_final($hash)];
    }

    private function requireDestination(?string $destinationKey = null): array
    {
        $destination = $this->destination($destinationKey);
        if (! $destination['configured']) {
            throw new RuntimeException('Primero configura la ruta de la memoria externa.');
        }
        if (! $destination['connected']) {
            throw new RuntimeException('La memoria externa no está conectada, no se puede leer o coincide con el servidor de origen.');
        }

        return $destination;
    }

    private function destinationFromState(array $state): array
    {
        $storedRoot = trim((string) ($state['destination_root'] ?? ''));
        $destination = $this->inspectDestination([
            'key' => (string) ($state['destination_key'] ?? 'principal'),
            'label' => (string) ($state['destination_label'] ?? 'Memoria externa'),
            'root' => $storedRoot,
            'type' => 'queued',
        ]);

        if (! $destination['configured'] || ! $destination['connected']) {
            throw new RuntimeException('La memoria externa no está conectada o ya no se puede leer desde el servidor.');
        }

        return $destination;
    }

    private function destinationDefinitions(): array
    {
        $configuredLabel = trim((string) config('backup-storage.export.volume_label', 'Memoria configurada')) ?: 'Memoria configurada';
        $configuredRoot = trim((string) config('backup-storage.export.destination_root'));
        if ($configuredRoot === '') {
            $configuredRoot = '\\\\Srvcontpaq\\e';
        }
        $definitions = [];

        if ($configuredRoot !== '') {
            $definitions[] = ['key' => 'principal', 'label' => $configuredLabel, 'root' => $configuredRoot, 'type' => 'configured'];
        }

        foreach ((array) config('backup-storage.export.additional_destinations', []) as $index => $definition) {
            if (! is_array($definition)) {
                continue;
            }
            $label = trim((string) ($definition['label'] ?? ''));
            $root = trim((string) ($definition['root'] ?? ''));
            if ($label === '' && $root === '') {
                continue;
            }
            $key = Str::slug((string) ($definition['key'] ?? $label ?: 'destino-'.$index));
            if ($key === '' || $key === 'principal') {
                $key = 'destino-'.$index;
            }
            $definitions[] = ['key' => $key, 'label' => $label ?: basename($root), 'root' => $root, 'type' => 'configured'];
        }

        foreach ($this->detectedLocalVolumes() as $volume) {
            $drive = strtoupper(rtrim((string) $volume['root'], '\\/'));
            $label = trim((string) ($volume['volume_label'] ?? ''));
            $definitions[] = [
                'key' => 'unidad-'.Str::lower(rtrim($drive, ':')),
                'label' => ($label !== '' ? $label : 'Unidad').' ('.$drive.')',
                'root' => $volume['root'],
                'type' => $volume['type'] ?? 'local',
            ];
        }

        $seenRoots = [];

        return collect($definitions)
            ->filter(function (array $definition) use (&$seenRoots): bool {
                $root = trim((string) ($definition['root'] ?? ''));
                if ($root === '') {
                    return true;
                }
                $normalized = $this->normalize($root);
                if (isset($seenRoots[$normalized])) {
                    return false;
                }
                $seenRoots[$normalized] = true;

                return true;
            })
            ->unique('key')
            ->sortByDesc(fn (array $definition): bool => (string) ($definition['root'] ?? '') !== '')
            ->values()
            ->all();
    }

    private function detectedLocalVolumes(): array
    {
        if (PHP_OS_FAMILY !== 'Windows' || ! config('backup-storage.export.detect_local_volumes', true)) {
            return [];
        }

        return Cache::remember('contpaqi-backup-export:local-volumes', 10, function (): array {
            if (! function_exists('shell_exec')) {
                return $this->fallbackLocalVolumes();
            }

            $script = <<<'POWERSHELL'
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
[System.IO.DriveInfo]::GetDrives() |
    Where-Object { $_.IsReady -and @('Fixed', 'Removable', 'Network') -contains $_.DriveType.ToString() } |
    ForEach-Object { [pscustomobject]@{ root = $_.Name; volume_label = $_.VolumeLabel; type = $_.DriveType.ToString() } } |
    ConvertTo-Json -Compress
POWERSHELL;
            $encoded = base64_encode(mb_convert_encoding($script, 'UTF-16LE', 'UTF-8'));

            try {
                $output = @shell_exec('powershell.exe -NoProfile -NonInteractive -EncodedCommand '.$encoded.' 2>NUL');
                $decoded = json_decode(trim((string) $output), true);
                if (! is_array($decoded)) {
                    return $this->fallbackLocalVolumes();
                }
                if (isset($decoded['root'])) {
                    $decoded = [$decoded];
                }

                $systemDrive = Str::upper(rtrim((string) ($_SERVER['SystemDrive'] ?? getenv('SystemDrive') ?: 'C:'), '\\/'));

                return collect($decoded)
                    ->filter(fn ($volume): bool => is_array($volume) && preg_match('/^[A-Za-z]:[\\\\\/]$/', (string) ($volume['root'] ?? '')) === 1)
                    ->filter(fn (array $volume): bool => Str::upper(rtrim((string) $volume['root'], '\\/')) !== $systemDrive)
                    ->map(fn (array $volume): array => [
                        'root' => strtoupper(substr((string) $volume['root'], 0, 2)).DIRECTORY_SEPARATOR,
                        'volume_label' => trim((string) ($volume['volume_label'] ?? '')),
                        'type' => (string) ($volume['type'] ?? 'local'),
                    ])
                    ->values()
                    ->all();
            } catch (Throwable) {
                return $this->fallbackLocalVolumes();
            }
        });
    }

    private function completeCancellation(string $id, array $state): void
    {
        $state['status'] = 'cancelled';
        $state['current_company'] = null;
        $state['error'] = null;
        $state['completed_at'] = now()->toIso8601String();
        $this->write($id, $state);
    }

    private function throwIfCancellationRequested(string $id): void
    {
        if (($this->find($id)['status'] ?? null) === 'cancelling') {
            throw new RuntimeException('El respaldo fue cancelado.');
        }
    }

    private function fallbackLocalVolumes(): array
    {
        $systemDrive = Str::upper(rtrim((string) ($_SERVER['SystemDrive'] ?? getenv('SystemDrive') ?: 'C:'), '\\/'));
        $volumes = [];

        foreach (range('D', 'Z') as $letter) {
            $root = $letter.':'.DIRECTORY_SEPARATOR;
            if ($letter.':' === $systemDrive || ! @is_dir($root)) {
                continue;
            }
            $volumes[] = ['root' => $root, 'volume_label' => '', 'type' => 'local'];
        }

        return $volumes;
    }

    private function requireDestinationFolder(string $folder, string $destinationRoot): string
    {
        $resolved = $this->resolveDestinationFolder($folder, $destinationRoot);
        if ($resolved === null) {
            throw new RuntimeException('Selecciona una carpeta válida dentro de la memoria CONTABILIDAD.');
        }
        if (! is_writable($resolved)) {
            throw new RuntimeException('PHP puede leer la memoria, pero no tiene permiso para escribir en la carpeta seleccionada.');
        }

        return $resolved;
    }

    private function resolveDestinationFolder(string $folder, string $destinationRoot): ?string
    {
        if ($folder === '' || basename($folder) !== $folder || str_contains($folder, "\0")) {
            return null;
        }

        $root = realpath($destinationRoot);
        $candidate = realpath($this->join($destinationRoot, $folder));
        if ($root === false || $candidate === false || ! is_dir($candidate) || is_link($candidate)) {
            return null;
        }

        return $this->normalize(dirname($candidate)) === $this->normalize($root) ? $candidate : null;
    }

    private function dateFolder(string $date): string
    {
        return CarbonImmutable::createFromFormat('!Y-m-d', $date)->format('d-m-Y');
    }

    private function write(string $id, array $state): void
    {
        File::ensureDirectoryExists($this->stateDirectory());
        $handle = fopen($this->statePath($id), 'c+b');
        if ($handle === false) {
            throw new RuntimeException('No fue posible guardar el progreso de la copia.');
        }
        try {
            flock($handle, LOCK_EX);
            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, json_encode($state, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
            fflush($handle);
            flock($handle, LOCK_UN);
        } finally {
            fclose($handle);
        }
    }

    private function stateDirectory(): string
    {
        return storage_path('app/private/backup-exports');
    }

    private function statePath(string $id): string
    {
        return $this->stateDirectory().DIRECTORY_SEPARATOR.$id.'.json';
    }

    private function join(string ...$parts): string
    {
        $originalFirst = (string) array_shift($parts);
        $first = rtrim($originalFirst, '\\/');
        if ($first === '' && str_starts_with($originalFirst, DIRECTORY_SEPARATOR)) {
            $first = DIRECTORY_SEPARATOR;
        }
        $rest = array_map(fn (string $part): string => trim($part, '\\/'), $parts);

        $separator = $first === DIRECTORY_SEPARATOR ? '' : DIRECTORY_SEPARATOR;

        return $first.($rest === [] ? '' : $separator.implode(DIRECTORY_SEPARATOR, $rest));
    }

    private function safeSegment(string $value): string
    {
        $value = str_replace(['<', '>', ':', '"', '/', '\\', '|', '?', '*'], '_', $value);
        $value = preg_replace('/[\x00-\x1F]/u', '_', $value) ?: 'Empresa';
        $value = rtrim(trim($value), '. ') ?: 'Empresa';
        if (preg_match('/^(CON|PRN|AUX|NUL|COM[1-9]|LPT[1-9])(?:\.|$)/i', $value)) {
            $value = '_'.$value;
        }

        return $value;
    }

    private function isAbsolutePath(string $path): bool
    {
        return preg_match('~^[A-Za-z]:[\\\\/]~', $path) === 1 || str_starts_with($path, '\\\\') || str_starts_with($path, '/');
    }

    private function overlapsSource(string $destination): bool
    {
        $source = realpath((string) config('backup-storage.server.root'));
        if ($source === false) {
            return false;
        }
        $source = $this->normalize($source);
        $destination = $this->normalize($destination);

        return $destination === $source || str_starts_with($destination.'/', $source.'/') || str_starts_with($source.'/', $destination.'/');
    }

    private function normalize(string $path): string
    {
        $path = rtrim(str_replace('\\', '/', $path), '/');

        return DIRECTORY_SEPARATOR === '\\' ? Str::lower($path) : $path;
    }
}
