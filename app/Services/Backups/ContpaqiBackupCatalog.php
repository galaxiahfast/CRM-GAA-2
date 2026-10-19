<?php

namespace App\Services\Backups;

use Carbon\CarbonImmutable;
use FilesystemIterator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use Throwable;

class ContpaqiBackupCatalog
{
    private const CACHE_VERSION = 5;

    private const PRELOADED_DAYS = 60;

    private ?string $rootOverride = null;

    public function forRoot(?string $root): self
    {
        $catalog = clone $this;
        $catalog->rootOverride = filled($root) ? rtrim((string) $root, '\\/') : null;

        return $catalog;
    }

    public function sourceRoot(): string
    {
        return $this->rootPath();
    }

    public function catalog(bool $fresh = false): array
    {
        if ($fresh) {
            Cache::forget($this->cacheKey());
        }

        return Cache::remember(
            $this->cacheKey(),
            now()->addSeconds(max(5, (int) config('backup-storage.server.cache_seconds', 60))),
            fn (): array => $this->scan(),
        );
    }

    public function dashboard(string $selectedDate): array
    {
        $metadata = Cache::get($this->metadataCacheKey());
        $fullCatalog = null;

        if (! is_array($metadata)) {
            $fullCatalog = $this->catalog();
            $this->primeDashboardCaches($fullCatalog);
            $metadata = Cache::get($this->metadataCacheKey());
        }

        if (! is_array($metadata)) {
            throw new RuntimeException('No fue posible preparar el catálogo de respaldos.');
        }
        Cache::forever($this->metadataCacheKey(), $metadata);

        $today = now()->toDateString();

        return $metadata + [
            'records' => $this->recordsForDate($selectedDate, $metadata['generation'], $fullCatalog, $metadata['companies']),
            'today_records' => $selectedDate === $today
                ? $this->recordsForDate($selectedDate, $metadata['generation'], $fullCatalog, $metadata['companies'])
                : $this->recordsForDate($today, $metadata['generation'], $fullCatalog, $metadata['companies']),
        ];
    }

    /**
     * Returns the compact records needed to build profile/date summaries.
     * Older caches are rebuilt from their already-preloaded daily entries so
     * opening the page never forces another recursive network scan.
     *
     * @return array<int, array<string, mixed>>
     */
    public function summaryRecords(): array
    {
        $metadata = Cache::get($this->metadataCacheKey());
        if (! is_array($metadata)) {
            $this->dashboard(now()->toDateString());
            $metadata = Cache::get($this->metadataCacheKey());
        }

        if (! is_array($metadata)) {
            return [];
        }
        if (is_array($metadata['summary_records'] ?? null)) {
            return $metadata['summary_records'];
        }

        $fullCatalog = null;
        $records = collect($metadata['dates'] ?? [])
            ->take(self::PRELOADED_DAYS)
            ->flatMap(function (array $day) use ($metadata, &$fullCatalog): array {
                return $this->recordsForDate(
                    (string) $day['date'],
                    (string) $metadata['generation'],
                    $fullCatalog,
                    $metadata['companies'],
                );
            })
            ->map(fn (array $record): array => $this->summaryRecord($record))
            ->values()
            ->all();

        $metadata['summary_records'] = $records;
        Cache::forever($this->metadataCacheKey(), $metadata);

        return $records;
    }

    public function refreshToday(): array
    {
        $today = now()->toDateString();

        $records = Cache::lock($this->cacheKey().':refresh-today', 55)->get(function () use ($today): array {
            $metadata = Cache::get($this->metadataCacheKey());

            if (! is_array($metadata)) {
                return $this->dashboard($today)['today_records'];
            }

            $startedAt = microtime(true);
            $records = $this->scanDate($today, $metadata['companies']);
            $expiresAt = $this->dashboardCacheExpiration();
            Cache::put($this->dateCacheKey($metadata['generation'], $today), $records, $expiresAt);

            $dateSummary = [
                'date' => $today,
                'total' => count($records),
                'complete' => collect($records)->where('complete', true)->count(),
                'partial' => collect($records)->where('complete', false)->count(),
                'size' => collect($records)->sum('total_size'),
            ];
            $metadata['dates'] = collect($metadata['dates'])
                ->reject(fn (array $date): bool => $date['date'] === $today)
                ->when($records !== [], fn ($dates) => $dates->prepend($dateSummary))
                ->sortByDesc('date')
                ->values()
                ->all();

            foreach ($records as $record) {
                if ($record['company_key'] !== null) {
                    $metadata['last_by_company'][$record['company_key']] = $record;
                }
            }
            $metadata['summary_records'] = collect($metadata['summary_records'] ?? [])
                ->reject(fn (array $record): bool => ($record['date'] ?? null) === $today)
                ->concat(collect($records)->map(fn (array $record): array => $this->summaryRecord($record)))
                ->values()
                ->all();

            $metadata['scanned_at'] = now()->toIso8601String();
            $metadata['scan_seconds'] = round(microtime(true) - $startedAt, 2);
            Cache::forever($this->metadataCacheKey(), $metadata);

            return $records;
        });

        return is_array($records) ? $records : $this->dashboard($today)['today_records'];
    }

    public function forget(): void
    {
        Cache::forget($this->cacheKey());
        Cache::forget($this->metadataCacheKey());
    }

    public function resolveDownload(string $kind, string $token): string
    {
        $basePath = match ($kind) {
            'database' => $this->companiesPath(),
            'archive' => $this->archivesPath(),
            default => throw new RuntimeException('Tipo de archivo no permitido.'),
        };

        $relativePath = $this->decodeToken($token);
        if (preg_match('/^([a-f0-9]{64})\|(.*)$/s', $relativePath, $matches) === 1) {
            $rootHash = $matches[1];
            $relativePath = $matches[2];
            if (! hash_equals($rootHash, $this->rootHash())) {
                $root = collect((array) config('backup-storage.backup_profiles', []))
                    ->pluck('source_root')
                    ->push((string) config('backup-storage.server.root'))
                    ->filter()
                    ->first(fn (string $candidate): bool => hash_equals($rootHash, $this->rootHash($candidate)));
                if (! is_string($root)) {
                    throw new RuntimeException('El origen del archivo ya no está configurado.');
                }

                return $this->forRoot($root)->resolveDownload($kind, $this->encodeToken($relativePath));
            }
        }
        $extension = Str::lower(pathinfo($relativePath, PATHINFO_EXTENSION));
        $allowedExtensions = $kind === 'database' ? ['bak'] : ['zip', 'rar'];

        if (! in_array($extension, $allowedExtensions, true)) {
            throw new RuntimeException('Extensión de archivo no permitida.');
        }

        $baseRealPath = realpath($basePath);
        $candidateRealPath = realpath($basePath.DIRECTORY_SEPARATOR.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath));

        if ($baseRealPath === false || $candidateRealPath === false || ! is_file($candidateRealPath)) {
            throw new RuntimeException('El archivo solicitado ya no está disponible.');
        }

        $normalizedBase = $this->normalizeAbsolutePath($baseRealPath).'/';
        $normalizedCandidate = $this->normalizeAbsolutePath($candidateRealPath);

        if (! str_starts_with($normalizedCandidate, $normalizedBase)) {
            throw new RuntimeException('La ruta solicitada no está permitida.');
        }

        return $candidateRealPath;
    }

    public function token(string $relativePath): string
    {
        return $this->encodeToken($this->rootHash().'|'.$relativePath);
    }

    private function encodeToken(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function rootHash(?string $root = null): string
    {
        return hash('sha256', $this->normalizeAbsolutePath($root ?? $this->rootPath()));
    }

    private function scan(): array
    {
        $startedAt = microtime(true);
        $companiesPath = $this->companiesPath();
        $archivesPath = $this->archivesPath();

        if (! is_dir($companiesPath) || ! is_readable($companiesPath)) {
            throw new RuntimeException('No fue posible leer la carpeta de empresas de CONTPAQi.');
        }

        if (! is_dir($archivesPath) || ! is_readable($archivesPath)) {
            throw new RuntimeException('No fue posible leer la carpeta de documentos digitales de CONTPAQi.');
        }

        $companies = $this->companyDirectories($companiesPath);
        $records = [];
        $databaseRecordKeysByStem = [];

        foreach ($this->files($companiesPath, ['bak']) as $file) {
            $parsed = $this->parseBackupName($file->getFilename());
            if ($parsed === null) {
                continue;
            }

            $relativePath = $this->relativePath($companiesPath, $file->getPathname());
            $companyFolder = Str::before($relativePath, '/');
            $sourceClassification = $this->sourceClassification($relativePath);
            $stemKey = Str::lower($parsed['stem']);
            $recordKey = Str::lower(($sourceClassification ?? 'unknown').'|'.$companyFolder.'|'.$parsed['stem']);
            $records[$recordKey] ??= $this->newRecord($parsed, $recordKey);
            $records[$recordKey]['company_folder'] = $companyFolder;
            $records[$recordKey]['company_key'] = $this->normalizeCompany($companyFolder);
            $records[$recordKey]['company'] = $this->displayCompany($parsed['company']);
            $records[$recordKey]['source_classification'] = $sourceClassification;
            $records[$recordKey]['database'] = $this->fileData($file, $relativePath);
            $databaseRecordKeysByStem[$stemKey][] = $recordKey;
        }

        foreach ($this->files($archivesPath, ['zip', 'rar']) as $file) {
            $parsed = $this->parseBackupName($file->getFilename());
            if ($parsed === null) {
                continue;
            }

            $stemKey = Str::lower($parsed['stem']);
            $recordKeys = array_values(array_unique($databaseRecordKeysByStem[$stemKey] ?? []));
            if ($recordKeys === []) {
                $recordKeys = ['archive|'.$stemKey];
                $records[$recordKeys[0]] ??= $this->newRecord($parsed, $recordKeys[0]);
            }
            $relativePath = $this->relativePath($archivesPath, $file->getPathname());
            $candidate = $this->fileData($file, $relativePath);

            foreach ($recordKeys as $recordKey) {
                if ($records[$recordKey]['archive'] === null || Str::lower($file->getExtension()) === 'zip') {
                    $records[$recordKey]['archive'] = $candidate;
                }
            }
        }

        foreach ($records as &$record) {
            if ($record['company_key'] === null) {
                $record['company_key'] = $this->bestCompanyKey($record['company_raw'], $companies);
            }

            $record['complete'] = $record['database'] !== null && $record['archive'] !== null;
            $record['total_size'] = (int) ($record['database']['size'] ?? 0) + (int) ($record['archive']['size'] ?? 0);
        }
        unset($record);

        $records = array_values($records);
        usort($records, fn (array $left, array $right): int => [$right['date'], $right['time'], $right['company']] <=> [$left['date'], $left['time'], $left['company']]);

        $dates = [];
        $lastByCompany = [];
        foreach ($records as $record) {
            $date = $record['date'];
            $dates[$date] ??= ['date' => $date, 'total' => 0, 'complete' => 0, 'partial' => 0, 'size' => 0];
            $dates[$date]['total']++;
            $dates[$date][$record['complete'] ? 'complete' : 'partial']++;
            $dates[$date]['size'] += $record['total_size'];

            $companyKey = $record['company_key'];
            if ($companyKey !== null && ! isset($lastByCompany[$companyKey])) {
                $lastByCompany[$companyKey] = $record;
            }
        }

        krsort($dates);

        $catalog = [
            'connected' => true,
            'root' => $this->rootPath(),
            'scanned_at' => now()->toIso8601String(),
            'scan_seconds' => round(microtime(true) - $startedAt, 2),
            'companies' => array_values($companies),
            'records' => $records,
            'dates' => array_values($dates),
            'last_by_company' => $lastByCompany,
        ];

        $this->primeDashboardCaches($catalog);

        return $catalog;
    }

    private function scanDate(string $date, array $companies): array
    {
        $compactDate = str_replace('-', '', $date);
        $records = [];
        $databaseRecordKeysByStem = [];
        $companyMap = collect($companies)->keyBy('key')->all();
        $companiesPath = $this->companiesPath();
        $archivesPath = $this->archivesPath();

        $databasePatterns = [
            $companiesPath.DIRECTORY_SEPARATOR.'*'.DIRECTORY_SEPARATOR.'*-'.$compactDate.'-????.bak',
            $companiesPath.DIRECTORY_SEPARATOR.'*'.DIRECTORY_SEPARATOR.'*'.DIRECTORY_SEPARATOR.'*-'.$compactDate.'-????.bak',
        ];

        foreach ($this->globFiles($databasePatterns) as $path) {
            $file = new SplFileInfo($path);
            $parsed = $this->parseBackupName($file->getFilename());
            if ($parsed === null || $parsed['date'] !== $date) {
                continue;
            }

            $relativePath = $this->relativePath($companiesPath, $file->getPathname());
            $companyFolder = Str::before($relativePath, '/');
            $sourceClassification = $this->sourceClassification($relativePath);
            $stemKey = Str::lower($parsed['stem']);
            $recordKey = Str::lower(($sourceClassification ?? 'unknown').'|'.$companyFolder.'|'.$parsed['stem']);
            $records[$recordKey] ??= $this->newRecord($parsed, $recordKey);
            $records[$recordKey]['company_folder'] = $companyFolder;
            $records[$recordKey]['company_key'] = $this->normalizeCompany($companyFolder);
            $records[$recordKey]['company'] = $this->displayCompany($parsed['company']);
            $records[$recordKey]['source_classification'] = $sourceClassification;
            $records[$recordKey]['database'] = $this->fileData($file, $relativePath);
            $databaseRecordKeysByStem[$stemKey][] = $recordKey;
        }

        $archivePatterns = [
            $archivesPath.DIRECTORY_SEPARATOR.'*-'.$compactDate.'-????.zip',
            $archivesPath.DIRECTORY_SEPARATOR.'*-'.$compactDate.'-????.rar',
            $archivesPath.DIRECTORY_SEPARATOR.'*'.DIRECTORY_SEPARATOR.'*-'.$compactDate.'-????.zip',
            $archivesPath.DIRECTORY_SEPARATOR.'*'.DIRECTORY_SEPARATOR.'*-'.$compactDate.'-????.rar',
        ];

        foreach ($this->globFiles($archivePatterns) as $path) {
            $file = new SplFileInfo($path);
            $parsed = $this->parseBackupName($file->getFilename());
            if ($parsed === null || $parsed['date'] !== $date) {
                continue;
            }

            $stemKey = Str::lower($parsed['stem']);
            $recordKeys = array_values(array_unique($databaseRecordKeysByStem[$stemKey] ?? []));
            if ($recordKeys === []) {
                $recordKeys = ['archive|'.$stemKey];
                $records[$recordKeys[0]] ??= $this->newRecord($parsed, $recordKeys[0]);
            }
            $relativePath = $this->relativePath($archivesPath, $file->getPathname());
            $candidate = $this->fileData($file, $relativePath);
            foreach ($recordKeys as $recordKey) {
                if ($records[$recordKey]['archive'] === null || Str::lower($file->getExtension()) === 'zip') {
                    $records[$recordKey]['archive'] = $candidate;
                }
            }
        }

        foreach ($records as &$record) {
            if ($record['company_key'] === null) {
                $record['company_key'] = $this->bestCompanyKey($record['company_raw'], $companyMap);
            }
            $record['complete'] = $record['database'] !== null && $record['archive'] !== null;
            $record['total_size'] = (int) ($record['database']['size'] ?? 0) + (int) ($record['archive']['size'] ?? 0);
        }
        unset($record);

        $records = array_values($records);
        usort($records, fn (array $left, array $right): int => [$right['time'], $right['company']] <=> [$left['time'], $left['company']]);

        return $records;
    }

    private function globFiles(array $patterns): array
    {
        $files = [];

        foreach ($patterns as $pattern) {
            foreach (glob($pattern) ?: [] as $path) {
                if (is_file($path)) {
                    $files[$this->normalizeAbsolutePath($path)] = $path;
                }
            }
        }

        return array_values($files);
    }

    private function primeDashboardCaches(array $catalog): void
    {
        $generation = hash('sha256', $catalog['scanned_at'].$this->rootPath());
        $expiresAt = $this->dashboardCacheExpiration();
        $metadata = $catalog;
        $metadata['summary_records'] = collect($catalog['records'])
            ->map(fn (array $record): array => $this->summaryRecord($record))
            ->values()
            ->all();
        unset($metadata['records']);
        $metadata['generation'] = $generation;

        Cache::forever($this->metadataCacheKey(), $metadata);

        $preloadedDates = collect($catalog['dates'])
            ->take(self::PRELOADED_DAYS)
            ->pluck('date')
            ->all();
        $recordsByDate = collect($catalog['records'])
            ->whereIn('date', $preloadedDates)
            ->groupBy('date');

        foreach ($preloadedDates as $date) {
            Cache::put(
                $this->dateCacheKey($generation, $date),
                $recordsByDate->get($date, collect())->values()->all(),
                $expiresAt,
            );
        }
    }

    /** @return array<string, mixed> */
    private function summaryRecord(array $record): array
    {
        return [
            'company_key' => $record['company_key'] ?? null,
            'company_folder' => $record['company_folder'] ?? null,
            'source_classification' => $record['source_classification'] ?? null,
            'date' => $record['date'],
            'complete' => (bool) $record['complete'],
            'total_size' => (int) $record['total_size'],
            'occurred_at' => $record['occurred_at'],
        ];
    }

    private function recordsForDate(string $date, string $generation, ?array &$fullCatalog, ?array $companies = null): array
    {
        $cacheKey = $this->dateCacheKey($generation, $date);
        $records = Cache::get($cacheKey);

        if (is_array($records)) {
            return $records;
        }

        $records = $companies !== null
            ? $this->scanDate($date, $companies)
            : collect(($fullCatalog ??= $this->catalog())['records'])
                ->where('date', $date)
                ->values()
                ->all();
        Cache::put($cacheKey, $records, $this->dashboardCacheExpiration());

        return $records;
    }

    private function companyDirectories(string $companiesPath): array
    {
        $companies = [];
        $iterator = new FilesystemIterator($companiesPath, FilesystemIterator::SKIP_DOTS);

        foreach ($iterator as $directory) {
            if (! $directory->isDir() || ! Str::startsWith(Str::lower($directory->getFilename()), 'ct')) {
                continue;
            }

            $folder = $directory->getFilename();
            $key = $this->normalizeCompany($folder);
            $companies[$key] = [
                'key' => $key,
                'folder' => $folder,
                'name' => $this->displayCompany($folder),
            ];
        }

        uasort($companies, fn (array $left, array $right): int => strnatcasecmp($left['name'], $right['name']));

        return $companies;
    }

    /** @return iterable<SplFileInfo> */
    private function files(string $path, array $extensions): iterable
    {
        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY,
                RecursiveIteratorIterator::CATCH_GET_CHILD,
            );

            foreach ($iterator as $file) {
                if ($file->isFile() && in_array(Str::lower($file->getExtension()), $extensions, true)) {
                    yield $file;
                }
            }
        } catch (Throwable $exception) {
            throw new RuntimeException('No fue posible terminar la lectura del servidor de respaldos.', previous: $exception);
        }
    }

    private function parseBackupName(string $filename): ?array
    {
        if (! preg_match('/^(?<company>.+)-(?<date>\d{8})-(?<time>\d{4})\.(?<extension>bak|zip|rar)$/iu', $filename, $matches)) {
            return null;
        }

        try {
            $date = CarbonImmutable::createFromFormat('!Ymd Hi', $matches['date'].' '.$matches['time'], config('app.timezone'));
        } catch (Throwable) {
            return null;
        }
        if ($date === false || $date->format('Ymd Hi') !== $matches['date'].' '.$matches['time']) {
            return null;
        }

        return [
            'stem' => pathinfo($filename, PATHINFO_FILENAME),
            'company' => $matches['company'],
            'date' => $date->toDateString(),
            'time' => $date->format('H:i:s'),
            'occurred_at' => $date->toIso8601String(),
        ];
    }

    private function newRecord(array $parsed, ?string $identity = null): array
    {
        return [
            'id' => hash('sha256', Str::lower($identity ?? 'archive|'.$parsed['stem'])),
            'stem' => $parsed['stem'],
            'company_raw' => $parsed['company'],
            'company' => $this->displayCompany($parsed['company']),
            'company_folder' => null,
            'company_key' => null,
            'source_classification' => null,
            'date' => $parsed['date'],
            'time' => $parsed['time'],
            'occurred_at' => $parsed['occurred_at'],
            'database' => null,
            'archive' => null,
            'complete' => false,
            'total_size' => 0,
        ];
    }

    private function fileData(SplFileInfo $file, string $relativePath): array
    {
        return [
            'name' => $file->getFilename(),
            'relative_path' => $relativePath,
            'token' => $this->token($relativePath),
            'extension' => Str::lower($file->getExtension()),
            'size' => $file->getSize(),
            'modified_at' => CarbonImmutable::createFromTimestamp($file->getMTime())->toIso8601String(),
        ];
    }

    private function bestCompanyKey(string $company, array $companies): ?string
    {
        $candidate = $this->normalizeCompany($company);
        if (isset($companies[$candidate])) {
            return $candidate;
        }

        foreach (array_keys($companies) as $key) {
            if (strlen($key) >= 8 && (str_starts_with($candidate, $key) || str_starts_with($key, $candidate))) {
                return $key;
            }
        }

        return null;
    }

    private function displayCompany(string $value): string
    {
        $value = preg_replace('/^ct(?=[\pL\d_])/iu', '', $value) ?? $value;
        $value = str_replace('_', ' ', $value);

        return trim(preg_replace('/\s+/u', ' ', $value) ?? $value);
    }

    private function normalizeCompany(string $value): string
    {
        $value = preg_replace('/^ct(?=[\pL\d_])/iu', '', $value) ?? $value;

        return Str::lower(preg_replace('/[^a-z0-9]+/i', '', Str::ascii($value)) ?? '');
    }

    private function relativePath(string $basePath, string $path): string
    {
        $base = rtrim(str_replace('\\', '/', $basePath), '/').'/';
        $normalizedPath = str_replace('\\', '/', $path);

        return ltrim(substr($normalizedPath, strlen($base)), '/');
    }

    private function sourceClassification(string $relativePath): ?string
    {
        $segments = array_values(array_filter(explode('/', str_replace('\\', '/', $relativePath)), 'strlen'));

        if (count($segments) === 2) {
            return 'accounting';
        }

        if (count($segments) >= 3 && Str::lower($segments[1]) === 'respaldo') {
            return 'payroll';
        }

        return null;
    }

    private function decodeToken(string $token): string
    {
        if ($token === '' || ! preg_match('/^[A-Za-z0-9_-]+$/', $token)) {
            throw new RuntimeException('Identificador de archivo inválido.');
        }

        $decoded = base64_decode(strtr($token, '-_', '+/').str_repeat('=', (4 - strlen($token) % 4) % 4), true);
        if ($decoded === false || str_contains($decoded, "\0") || preg_match('~(^|[\\/])\.\.([\\/]|$)~', $decoded) || preg_match('~^[\\/]|^[A-Za-z]:~', $decoded)) {
            throw new RuntimeException('Identificador de archivo inválido.');
        }

        return $decoded;
    }

    private function normalizeAbsolutePath(string $path): string
    {
        $normalized = rtrim(str_replace('\\', '/', $path), '/');

        return DIRECTORY_SEPARATOR === '\\' ? Str::lower($normalized) : $normalized;
    }

    private function rootPath(): string
    {
        return $this->rootOverride
            ?? rtrim((string) config('backup-storage.server.root'), '\\/');
    }

    private function companiesPath(): string
    {
        return $this->rootPath().DIRECTORY_SEPARATOR.trim((string) config('backup-storage.server.companies_directory', 'Empresas'), '\\/');
    }

    private function archivesPath(): string
    {
        return $this->rootPath().DIRECTORY_SEPARATOR.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, trim((string) config('backup-storage.server.archives_directory', 'Index/respaldos'), '\\/'));
    }

    private function cacheKey(): string
    {
        return 'contpaqi-backup-catalog:v'.self::CACHE_VERSION.':'.hash('sha256', $this->rootPath());
    }

    private function metadataCacheKey(): string
    {
        return $this->cacheKey().':metadata';
    }

    private function dateCacheKey(string $generation, string $date): string
    {
        return $this->cacheKey().':date:'.$generation.':'.$date;
    }

    private function cacheExpiration(): \DateTimeInterface
    {
        return now()->addSeconds(max(5, (int) config('backup-storage.server.cache_seconds', 60)));
    }

    private function dashboardCacheExpiration(): \DateTimeInterface
    {
        return now()->addDay();
    }
}
