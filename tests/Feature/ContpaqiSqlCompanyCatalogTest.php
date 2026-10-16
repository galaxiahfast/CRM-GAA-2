<?php

namespace Tests\Feature;

use App\Services\Backups\ContpaqiBackupProfile;
use App\Services\Backups\ContpaqiSqlCompanyCatalog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class ContpaqiSqlCompanyCatalogTest extends TestCase
{
    public function test_sql_instances_classify_companies_and_allow_a_company_in_both_departments(): void
    {
        config()->set('backup-storage.sql_catalog', [
            'enabled' => true,
            'username' => 'readonly',
            'password' => 'secret',
            'sqlcmd_path' => 'sqlcmd',
            'timeout_seconds' => 15,
            'cache_seconds' => 300,
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
        $both = ['company_folder' => 'ctEN_AMBAS'];
        $this->assertTrue($profiles->matches($both, $profiles->profile('merida-nominas')));
        $this->assertTrue($profiles->matches($both, $profiles->profile('merida-contabilidad')));
        $this->assertFalse($profiles->matches(['company_folder' => 'ctNO_EXISTE'], $profiles->profile('merida-contabilidad')));

        Process::assertRan(function ($process): bool {
            $command = is_array($process->command)
                ? implode(' ', $process->command)
                : $process->command;

            return str_contains($command, 'sys.databases')
                && ! preg_match('/\b(?:INSERT|UPDATE|DELETE|ALTER|CREATE|DROP|EXEC)\b/i', $command);
        });
    }
}
