<?php

namespace App\Services\Backups;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ContpaqiBackupExport
{
    public function destination(): array
    {
        $configuredPath = trim((string) config('backup-storage.export.destination_root'));
        if ($configuredPath === '') {
            return ['configured' => false, 'connected' => false, 'root' => null, 'free_bytes' => null];
        }

        if (! $this->isAbsolutePath($configuredPath)) {
            return ['configured' => true, 'connected' => false, 'root' => $configuredPath, 'free_bytes' => null];
        }

        $realPath = realpath($configuredPath);
        $connected = $realPath !== false && is_dir($realPath) && is_writable($realPath) && ! $this->overlapsSource($realPath);

        return [
            'configured' => true,
            'connected' => $connected,
            'root' => $realPath !== false ? $realPath : $configuredPath,
            'free_bytes' => $connected ? (@disk_free_space($realPath) ?: null) : null,
        ];
    }

    public function create(string $date, array $recordIds, int $userId, ContpaqiBackupCatalog $catalog): array
    {
        return Cache::lock('contpaqi-backup-export:create:'.$date, 15)->block(5, function () use ($date, $recordIds, $userId, $catalog): array {
            $destination = $this->requireDestination();
            $allRecords = $catalog->dashboard($date)['records'];
            $records = $this->selectedRecords($allRecords, $date, $recordIds);
            $availability = $this->recordAvailability($date, $allRecords);

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
                'destination_directory' => $this->safeSegment((string) config('backup-storage.export.directory', 'Respaldos CONTPAQi')),
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
                    'status' => 'pending',
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
        if ($state === null || $state['status'] === 'completed') {
            return;
        }

        try {
            $destination = $this->requireDestination();
            if ($this->normalize($destination['root']) !== $this->normalize((string) $state['destination_root'])) {
                throw new RuntimeException('El destino configurado cambió después de iniciar la copia.');
            }

            $records = $this->selectedRecords($catalog->dashboard($state['date'])['records'], $state['date'], $state['record_ids']);
            $dateDirectory = $this->join($destination['root'], $state['destination_directory'], $state['date']);
            File::ensureDirectoryExists($dateDirectory);

            $state['status'] = 'copying';
            $state['started_at'] = now()->toIso8601String();
            $state['destination_path'] = $dateDirectory;
            $this->write($id, $state);

            foreach ($records as $recordIndex => $record) {
                $companyDirectory = $this->join($dateDirectory, $this->safeSegment($record['company_folder'] ?: $record['company']));
                File::ensureDirectoryExists($companyDirectory);
                $state['current_company'] = $record['company'];
                $state['items'][$recordIndex]['status'] = 'copying';
                $this->write($id, $state);

                foreach ([['database', 'database'], ['archive', 'archive']] as [$kind, $field]) {
                    $file = $record[$field];
                    $source = $catalog->resolveDownload($kind, $file['token']);
                    $target = $this->join($companyDirectory, basename($file['name']));
                    $result = $this->copyVerified($source, $target, $id, $state);
                    $state['completed_files']++;
                    $state['items'][$recordIndex]['files'][] = $result;
                    $this->write($id, $state);
                }

                $state['items'][$recordIndex]['status'] = 'completed';
                $this->write($id, $state);
            }

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

    public function markFailed(string $id, string $message): void
    {
        $state = $this->find($id);
        if ($state === null || in_array($state['status'], ['completed', 'failed'], true)) {
            return;
        }

        $state['status'] = 'failed';
        $state['current_company'] = null;
        $state['error'] = mb_substr($message, 0, 1000);
        $state['completed_at'] = now()->toIso8601String();
        $this->write($id, $state);
    }

    /** @return array<string, string> */
    public function recordAvailability(string $date, array $records): array
    {
        $destination = $this->destination();
        $latestCompleteByCompany = collect($records)
            ->where('complete', true)
            ->groupBy(fn (array $record): string => $this->companyKey($record))
            ->map(fn ($companyRecords): array => $companyRecords->sortByDesc('occurred_at')->first());
        $latestIds = $latestCompleteByCompany->pluck('id')->flip();
        $destinationCopies = $destination['connected']
            ? $this->destinationCopies($date, collect($records)->where('complete', true)->values()->all(), $destination['root'])
            : ['record_ids' => [], 'company_keys' => []];
        $copyingCompanies = $destination['connected']
            ? $this->activeCompanyKeys($date, $destination['root'])
            : [];

        return collect($records)->mapWithKeys(function (array $record) use ($destination, $latestIds, $destinationCopies, $copyingCompanies): array {
            $companyKey = $this->companyKey($record);
            $status = match (true) {
                ! $record['complete'] => 'incomplete',
                ! $latestIds->has($record['id']) => 'older',
                in_array($companyKey, $copyingCompanies, true) => 'copying',
                in_array($record['id'], $destinationCopies['record_ids'], true) => 'backed_up',
                ! $destination['connected'] => 'destination_missing',
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

    private function destinationCopies(string $date, array $completeRecords, string $destinationRoot): array
    {
        $dateDirectory = $this->join(
            $destinationRoot,
            $this->safeSegment((string) config('backup-storage.export.directory', 'Respaldos CONTPAQi')),
            $date,
        );
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

            $database = $this->join($companyDirectory, basename($record['database']['name']));
            $archive = $this->join($companyDirectory, basename($record['archive']['name']));

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

    private function activeCompanyKeys(string $date, string $destinationRoot): array
    {
        if (! is_dir($this->stateDirectory())) {
            return [];
        }

        $keys = [];
        foreach (File::glob($this->stateDirectory().DIRECTORY_SEPARATOR.'*.json') as $path) {
            $id = pathinfo($path, PATHINFO_FILENAME);
            $state = $this->find($id);
            if ($state === null || $state['date'] !== $date || ! in_array($state['status'], ['queued', 'copying'], true)) {
                continue;
            }
            if ($this->normalize((string) ($state['destination_root'] ?? '')) !== $this->normalize($destinationRoot)) {
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
        $chunkSize = max(1024 * 1024, (int) config('backup-storage.export.chunk_size', 8 * 1024 * 1024));

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
                    if ($pendingProgress >= 64 * 1024 * 1024) {
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

    private function requireDestination(): array
    {
        $destination = $this->destination();
        if (! $destination['configured']) {
            throw new RuntimeException('Primero configura la ruta de la memoria externa.');
        }
        if (! $destination['connected']) {
            throw new RuntimeException('La memoria externa configurada no está conectada, no es escribible o coincide con el servidor de origen.');
        }

        return $destination;
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
