<?php

namespace App\Services\Backups;

use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;

class BackupInfrastructurePdfService
{
    public function generate(): string
    {
        $username = (string) config('backup-storage.sql_catalog.username', 'usuario_lectura');
        $sqlUser = str_replace(']', ']]', $username);

        return Pdf::loadView('pdf.backup-infrastructure-guide', [
            'generatedAt' => CarbonImmutable::now(config('backup-storage.display_timezone')),
            'applicationHost' => (string) config('backup-storage.infrastructure.application_host', '192.168.2.249'),
            'sourceHost' => (string) config('backup-storage.infrastructure.source_host', '192.168.2.210'),
            'sourceRoot' => (string) config('backup-storage.server.root'),
            'profiles' => collect(config('backup-storage.backup_profiles', []))
                ->filter(fn (array $profile): bool => (bool) ($profile['enabled'] ?? false))
                ->map(fn (array $profile, string $key): array => $profile + ['key' => $key])
                ->values()
                ->all(),
            'sqlUsername' => $username,
            'sqlCommands' => [
                'login' => "USE [master];\nIF SUSER_ID(N'{$sqlUser}') IS NULL\n    CREATE LOGIN [{$sqlUser}] WITH PASSWORD = N'<CONTRASEÑA_SEGURA>', CHECK_POLICY = ON, CHECK_EXPIRATION = ON;",
                'payroll' => "USE [nomGenerales];\nIF USER_ID(N'{$sqlUser}') IS NULL CREATE USER [{$sqlUser}] FOR LOGIN [{$sqlUser}];\nGRANT CONNECT TO [{$sqlUser}];\nGRANT SELECT ON OBJECT::[dbo].[nom10000] TO [{$sqlUser}];",
                'accounting' => "USE [GeneralesSQL];\nIF USER_ID(N'{$sqlUser}') IS NULL CREATE USER [{$sqlUser}] FOR LOGIN [{$sqlUser}];\nGRANT CONNECT TO [{$sqlUser}];\nGRANT SELECT ON OBJECT::[dbo].[ListaEmpresas] TO [{$sqlUser}];",
            ],
        ])->setPaper('a4')->output();
    }
}
