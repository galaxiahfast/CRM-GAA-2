<?php

namespace App\Services\Backups;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Throwable;

class ContpaqiSqlCompanyCatalog
{
    /** @var array<string, array{payroll: array<string, true>, accounting: array<string, true>}|null> */
    private array $requestCache = [];

    /**
     * @return array{payroll: array<string, true>, accounting: array<string, true>}|null
     */
    public function catalog(string $site, bool $refresh = false): ?array
    {
        if (! $this->configured($site)) {
            return null;
        }

        if (! $refresh && array_key_exists($site, $this->requestCache)) {
            return $this->requestCache[$site];
        }

        $cacheKey = $this->cacheKey($site);
        if (! $refresh && Cache::has($cacheKey)) {
            return $this->requestCache[$site] = Cache::get($cacheKey);
        }

        try {
            $instances = (array) config("backup-storage.sql_catalog.sites.{$site}", []);
            $catalog = [
                'payroll' => $this->readInstance((string) $instances['payroll'], 'payroll'),
                'accounting' => $this->readInstance((string) $instances['accounting'], 'accounting'),
            ];

            Cache::put($cacheKey, $catalog, max(1, (int) config('backup-storage.sql_catalog.cache_seconds', 300)));
            Cache::forever($cacheKey.':last-known', $catalog);

            return $this->requestCache[$site] = $catalog;
        } catch (Throwable $exception) {
            Log::warning('No se pudo leer el catálogo de empresas de SQL Server.', [
                'site' => $site,
                'error' => $exception->getMessage(),
            ]);

            return $this->requestCache[$site] = Cache::get($cacheKey.':last-known');
        }
    }

    public function forget(string $site): void
    {
        unset($this->requestCache[$site]);
        Cache::forget($this->cacheKey($site));
    }

    private function configured(string $site): bool
    {
        $instances = (array) config("backup-storage.sql_catalog.sites.{$site}", []);

        return (bool) config('backup-storage.sql_catalog.enabled', true)
            && filled(config('backup-storage.sql_catalog.username'))
            && filled(config('backup-storage.sql_catalog.password'))
            && filled($instances['payroll'] ?? null)
            && filled($instances['accounting'] ?? null);
    }

    /** @return array<string, true> */
    private function readInstance(string $instance, string $classification): array
    {
        [$database, $query] = match ($classification) {
            'payroll' => [
                (string) config('backup-storage.sql_catalog.databases.payroll', 'nomGenerales'),
                'SET NOCOUNT ON; SELECT RutaEmpresa FROM dbo.nom10000 WHERE DatosVigente = 1 AND RutaEmpresa IS NOT NULL ORDER BY RutaEmpresa;',
            ],
            'accounting' => [
                (string) config('backup-storage.sql_catalog.databases.accounting', 'GeneralesSQL'),
                'SET NOCOUNT ON; SELECT AliasBDD FROM dbo.ListaEmpresas WHERE AliasBDD IS NOT NULL ORDER BY AliasBDD;',
            ],
            default => throw new \InvalidArgumentException("Clasificación SQL no compatible: {$classification}."),
        };
        $result = Process::env([
            'SQLCMDPASSWORD' => (string) config('backup-storage.sql_catalog.password'),
        ])->timeout(max(5, (int) config('backup-storage.sql_catalog.timeout_seconds', 15)))
            ->run([
                (string) config('backup-storage.sql_catalog.sqlcmd_path', 'sqlcmd'),
                '-S', $instance,
                '-U', (string) config('backup-storage.sql_catalog.username'),
                '-d', $database,
                '-l', '8',
                '-b',
                '-W',
                '-h-1',
                '-r1',
                '-Q', $query,
            ]);

        if (! $result->successful()) {
            throw new \RuntimeException(trim($result->errorOutput()) ?: "No se pudo consultar {$instance}.");
        }

        return collect(preg_split('/\R/u', $result->output()) ?: [])
            ->map(fn (string $name): string => trim($name))
            ->filter(fn (string $name): bool => preg_match('/^(?:ct|nt|nct)/i', $name) === 1)
            ->mapWithKeys(fn (string $name): array => [Str::lower($name) => true])
            ->all();
    }

    private function cacheKey(string $site): string
    {
        return 'contpaqi-sql-company-catalog:'.Str::slug($site);
    }
}
