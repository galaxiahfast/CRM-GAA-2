<?php

namespace Tests\Feature;

use App\Services\Backups\ContpaqiBackupProfile;
use App\Services\Backups\ContpaqiSqlCompanyCatalog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class ContpaqiSqlCompanyCatalogTest extends TestCase
{
    public function test_physical_bak_path_disambiguates_a_company_registered_in_both_systems(): void
    {
        config()->set('backup-storage.sql_catalog', [
            'enabled' => true,
            'username' => 'readonly',
            'password' => 'secret',
            'sqlcmd_path' => 'sqlcmd',
            'timeout_seconds' => 15,
            'cache_seconds' => 300,
            'databases' => [
                'payroll' => 'nomGenerales',
                'accounting' => 'GeneralesSQL',
            ],
            'sites' => [
                'merida' => [
                    'payroll' => 'SRVCONTPAQ\\NOMINAS',
                    'accounting' => 'SRVCONTPAQ\\CONTABILIDAD',
                ],
            ],
        ]);
        Cache::flush();
        Process::fake([
            '*NOMINAS*' => ['ctSOLO_NOMINAS', 'ctEN_AMBAS', 'nomGenerales'],
            '*CONTABILIDAD*' => ['ctSOLO_CONTABILIDAD', 'ctEN_AMBAS', 'document_123_content'],
        ]);

        $catalog = app(ContpaqiSqlCompanyCatalog::class)->catalog('merida');

        $this->assertArrayHasKey('ctsolo_nominas', $catalog['payroll']);
        $this->assertArrayHasKey('ctsolo_contabilidad', $catalog['accounting']);
        $this->assertArrayHasKey('cten_ambas', $catalog['payroll']);
        $this->assertArrayHasKey('cten_ambas', $catalog['accounting']);
        $this->assertArrayNotHasKey('nomgenerales', $catalog['payroll']);
        $this->assertArrayNotHasKey('document_123_content', $catalog['accounting']);

        $profiles = app(ContpaqiBackupProfile::class);
        $payroll = ['company_folder' => 'ctEN_AMBAS', 'source_classification' => 'payroll'];
        $accounting = ['company_folder' => 'ctEN_AMBAS', 'source_classification' => 'accounting'];
        $this->assertTrue($profiles->matches($payroll, $profiles->profile('merida-nominas')));
        $this->assertFalse($profiles->matches($payroll, $profiles->profile('merida-contabilidad')));
        $this->assertTrue($profiles->matches($accounting, $profiles->profile('merida-contabilidad')));
        $this->assertFalse($profiles->matches($accounting, $profiles->profile('merida-nominas')));
        $this->assertFalse($profiles->matches(['company_folder' => 'ctNO_EXISTE'], $profiles->profile('merida-contabilidad')));

        Process::assertRan(function ($process): bool {
            $command = is_array($process->command)
                ? implode(' ', $process->command)
                : $process->command;

            return (str_contains($command, 'dbo.nom10000') || str_contains($command, 'dbo.ListaEmpresas'))
                && ! preg_match('/\b(?:INSERT|UPDATE|DELETE|ALTER|CREATE|DROP|EXEC)\b/i', $command);
        });
    }
}
