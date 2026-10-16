<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Services\Backups\ContpaqiBackupCatalog;
use App\Services\Backups\ContpaqiBackupExport;
use App\Services\Backups\ContpaqiBackupProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ContpaqiBackupCatalogTest extends TestCase
{
    public function test_backup_profiles_separate_accounting_and_payroll_records(): void
    {
        $profiles = app(ContpaqiBackupProfile::class);
        $accounting = ['company_folder' => 'ctEMPRESA_CONTABLE', 'company_raw' => 'EMPRESA CONTABLE', 'company' => 'EMPRESA CONTABLE', 'stem' => 'EMPRESA CONTABLE-20261016-0900'];
        $payrollByFolder = ['company_folder' => 'ct1_EMPRESA_NOMINA', 'company_raw' => 'EMPRESA NOMINA', 'company' => 'EMPRESA NOMINA', 'stem' => 'EMPRESA NOMINA-20261016-0900'];
        $payrollByPeriod = ['company_folder' => null, 'company_raw' => 'EMPRESA Q19 2026', 'company' => 'EMPRESA Q19 2026', 'stem' => 'EMPRESA Q19 2026-20261016-0900'];

        $this->assertTrue($profiles->matches($accounting, $profiles->profile('merida-contabilidad')));
        $this->assertFalse($profiles->matches($accounting, $profiles->profile('merida-nominas')));
        $this->assertTrue($profiles->matches($payrollByFolder, $profiles->profile('merida-nominas')));
        $this->assertTrue($profiles->matches($payrollByPeriod, $profiles->profile('merida-nominas')));
    }

    use RefreshDatabase;

    private string $catalogRoot;

    private string $exportRoot;

    private array $exportIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->catalogRoot = storage_path('framework/testing/contpaqi-'.uniqid());
        $this->exportRoot = storage_path('framework/testing/contpaqi-export-'.uniqid());
        File::ensureDirectoryExists($this->catalogRoot.'/Empresas/ctEmpresa_Demo/Respaldo');
        File::ensureDirectoryExists($this->catalogRoot.'/Empresas/ctEmpresa_Pendiente');
        File::ensureDirectoryExists($this->catalogRoot.'/Index/respaldos');
        File::ensureDirectoryExists($this->exportRoot);
        File::ensureDirectoryExists($this->exportRoot.'/ANGEL MOO');
        config()->set('backup-storage.server.root', $this->catalogRoot);
        config()->set('backup-storage.server.cache_seconds', 60);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->catalogRoot);
        File::deleteDirectory($this->exportRoot);
        foreach ($this->exportIds as $id) {
            File::delete(storage_path("app/private/backup-exports/{$id}.json"));
        }
        parent::tearDown();
    }

    public function test_catalog_pairs_files_groups_days_and_marks_missing_companies(): void
    {
        $date = now()->format('Ymd');
        File::put($this->catalogRoot."/Empresas/ctEmpresa_Demo/Respaldo/ctEmpresa_Demo-{$date}-0915.bak", 'database');
        File::put($this->catalogRoot."/Index/respaldos/ctEmpresa_Demo-{$date}-0915.zip", 'archive');
        File::put($this->catalogRoot.'/Index/respaldos/Empresa_Antigua-20261001-1010.zip', 'legacy');

        $catalog = app(ContpaqiBackupCatalog::class)->catalog(true);
        $today = collect($catalog['records'])->firstWhere('date', now()->toDateString());

        $this->assertCount(2, $catalog['companies']);
        $this->assertNotNull($today);
        $this->assertTrue($today['complete']);
        $this->assertSame('Empresa Demo', $today['company']);
        $this->assertSame('bak', $today['database']['extension']);
        $this->assertSame('zip', $today['archive']['extension']);
        $this->assertSame(2, collect($catalog['dates'])->count());
    }

    public function test_download_is_read_only_and_cannot_escape_the_allowed_directories(): void
    {
        $path = $this->catalogRoot.'/Empresas/ctEmpresa_Demo/ctEmpresa_Demo-20261014-1200.bak';
        File::put($path, 'read-only-content');

        $role = Role::query()->create(['role' => 'Contador', 'permission_profile' => Role::PROFILE_CUSTOM]);
        $user = User::query()->create([
            'name' => 'Usuario',
            'last_name' => 'Prueba',
            'email' => 'catalog@datamid.test',
            'email_verified_at' => now(),
            'password' => Hash::make('secret'),
            'role_id' => $role->id,
        ]);

        $catalog = app(ContpaqiBackupCatalog::class);
        $token = $catalog->token('ctEmpresa_Demo/ctEmpresa_Demo-20261014-1200.bak');

        $this->actingAs($user)
            ->get(route('activity-backups.server.download', ['kind' => 'database', 'token' => $token]))
            ->assertOk()
            ->assertDownload(basename($path));

        $this->assertSame('read-only-content', File::get($path));

        $escapeToken = $catalog->token('../secreto.bak');
        $this->get(route('activity-backups.server.download', ['kind' => 'database', 'token' => $escapeToken]))
            ->assertNotFound();
    }

    public function test_minute_refresh_reads_only_the_current_day_and_updates_the_dashboard(): void
    {
        $catalog = app(ContpaqiBackupCatalog::class);
        $initial = $catalog->catalog(true);
        $date = now()->format('Ymd');

        File::put($this->catalogRoot."/Empresas/ctEmpresa_Demo/ctEmpresa_Demo-{$date}-1545.bak", 'new database');
        File::put($this->catalogRoot."/Index/respaldos/ctEmpresa_Demo-{$date}-1545.zip", 'new archive');

        $records = $catalog->refreshToday();
        $dashboard = $catalog->dashboard(now()->toDateString());

        $this->assertSame([], collect($initial['records'])->where('date', now()->toDateString())->values()->all());
        $this->assertCount(1, $records);
        $this->assertTrue($records[0]['complete']);
        $this->assertSame('ctEmpresa_Demo-'.now()->format('Ymd').'-1545', $records[0]['stem']);
        $this->assertSame($records, $dashboard['today_records']);
    }

    public function test_complete_backups_are_copied_into_date_and_company_folders_without_changing_the_source(): void
    {
        $date = now()->format('Ymd');
        $databasePath = $this->catalogRoot."/Empresas/ctEmpresa_Demo/Respaldo/ctEmpresa_Demo-{$date}-1715.bak";
        $archivePath = $this->catalogRoot."/Index/respaldos/ctEmpresa_Demo-{$date}-1715.zip";
        File::put($databasePath, 'database-content');
        File::put($archivePath, 'archive-content');
        config()->set('backup-storage.export.destination_root', $this->exportRoot);
        config()->set('backup-storage.export.directory', 'Respaldos CONTPAQi');

        $catalog = app(ContpaqiBackupCatalog::class);
        $record = collect($catalog->catalog(true)['records'])->firstWhere('date', now()->toDateString());
        $export = app(ContpaqiBackupExport::class);
        $state = $export->create(now()->toDateString(), [$record['id']], 1, 'ANGEL MOO', $catalog);
        $this->exportIds[] = $state['id'];
        $this->assertSame('copying', $export->recordAvailability(now()->toDateString(), [$record], 'ANGEL MOO')[$record['id']]);
        $export->execute($state['id'], $catalog);

        $destination = $this->exportRoot.'/ANGEL MOO/'.now()->format('d-m-Y').'/ctEmpresa_Demo';
        $this->assertSame('database-content', File::get($databasePath));
        $this->assertSame('archive-content', File::get($archivePath));
        $this->assertSame('database-content', File::get($destination.'/BAK/'.basename($databasePath)));
        $this->assertSame('archive-content', File::get($destination.'/INDEX/'.basename($archivePath)));
        $this->assertCount(1, File::glob($this->exportRoot.'/ANGEL MOO/'.now()->format('d-m-Y').'/_registro-*.json'));
        $this->assertSame('completed', $export->find($state['id'])['status']);
        $this->assertSame(2, $export->find($state['id'])['completed_files']);
        $this->assertSame('backed_up', $export->recordAvailability(now()->toDateString(), [$record], 'ANGEL MOO')[$record['id']]);
        $completedDestinations = $export->completedDestinations(now()->toDateString());
        $this->assertArrayHasKey($record['id'], $completedDestinations);
        $this->assertSame(realpath($destination), realpath($completedDestinations[$record['id']][0]['path']));

        try {
            $export->create(now()->toDateString(), [$record['id']], 1, 'ANGEL MOO', $catalog);
            $this->fail('La misma empresa no debe poder respaldarse dos veces el mismo día.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('ya fue respaldada', $exception->getMessage());
        }
    }

    public function test_queued_export_uses_the_destination_validated_when_it_was_created(): void
    {
        $date = now()->format('Ymd');
        File::put($this->catalogRoot."/Empresas/ctEmpresa_Demo/Respaldo/ctEmpresa_Demo-{$date}-1715.bak", 'database-content');
        File::put($this->catalogRoot."/Index/respaldos/ctEmpresa_Demo-{$date}-1715.zip", 'archive-content');
        config()->set('backup-storage.export.destination_root', $this->exportRoot);

        $catalog = app(ContpaqiBackupCatalog::class);
        $record = collect($catalog->catalog(true)['records'])->firstWhere('date', now()->toDateString());
        $export = app(ContpaqiBackupExport::class);
        $state = $export->create(now()->toDateString(), [$record['id']], 1, 'ANGEL MOO', $catalog);
        $this->exportIds[] = $state['id'];

        config()->set('backup-storage.export.destination_root', '');
        $export->execute($state['id'], $catalog);

        $this->assertSame('completed', $export->find($state['id'])['status']);
        $this->assertFileExists($this->exportRoot.'/ANGEL MOO/'.now()->format('d-m-Y').'/ctEmpresa_Demo/BAK/ctEmpresa_Demo-'.$date.'-1715.bak');
    }

    public function test_empty_cached_destination_configuration_uses_the_fixed_server_share(): void
    {
        config()->set('backup-storage.export.destination_root', '');

        $destination = app(ContpaqiBackupExport::class)->destination();

        $this->assertTrue($destination['configured']);
        $this->assertSame('principal', $destination['key']);
        $this->assertStringContainsString('srvcontpaq', strtolower((string) $destination['root']));
    }

    public function test_cancelling_a_queued_export_keeps_it_locked_until_the_worker_stops(): void
    {
        $date = now()->format('Ymd');
        File::put($this->catalogRoot."/Empresas/ctEmpresa_Demo/Respaldo/ctEmpresa_Demo-{$date}-1715.bak", 'database-content');
        File::put($this->catalogRoot."/Index/respaldos/ctEmpresa_Demo-{$date}-1715.zip", 'archive-content');
        config()->set('backup-storage.export.destination_root', $this->exportRoot);

        $catalog = app(ContpaqiBackupCatalog::class);
        $record = collect($catalog->catalog(true)['records'])->firstWhere('date', now()->toDateString());
        $export = app(ContpaqiBackupExport::class);
        $state = $export->create(now()->toDateString(), [$record['id']], 1, 'ANGEL MOO', $catalog);
        $this->exportIds[] = $state['id'];

        $this->assertSame('cancelling', $export->cancel($state['id'])['status']);
        $this->assertSame($state['id'], $export->latestActive()['id']);

        $export->execute($state['id'], $catalog);

        $this->assertSame('cancelled', $export->find($state['id'])['status']);
        $this->assertNull($export->latestActive());
    }

    public function test_export_rejects_incomplete_records(): void
    {
        $date = now()->format('Ymd');
        File::put($this->catalogRoot."/Empresas/ctEmpresa_Demo/ctEmpresa_Demo-{$date}-1800.bak", 'database-only');
        $catalog = app(ContpaqiBackupCatalog::class);
        $record = collect($catalog->catalog(true)['records'])->firstWhere('date', now()->toDateString());
        $export = app(ContpaqiBackupExport::class);

        config()->set('backup-storage.export.destination_root', $this->exportRoot);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Selecciona únicamente empresas');
        $export->create(now()->toDateString(), [$record['id']], 1, 'ANGEL MOO', $catalog);
    }

    public function test_export_destination_cannot_be_the_server_or_one_of_its_subdirectories(): void
    {
        config()->set('backup-storage.export.destination_root', $this->catalogRoot.'/Empresas');

        $destination = app(ContpaqiBackupExport::class)->destination();

        $this->assertTrue($destination['configured']);
        $this->assertFalse($destination['connected']);
    }

    public function test_only_the_latest_complete_backup_for_the_same_company_and_day_can_be_selected(): void
    {
        $date = now()->format('Ymd');
        foreach (['0900', '1730'] as $time) {
            File::put($this->catalogRoot."/Empresas/ctEmpresa_Demo/ctEmpresa_Demo-{$date}-{$time}.bak", 'database-'.$time);
            File::put($this->catalogRoot."/Index/respaldos/ctEmpresa_Demo-{$date}-{$time}.zip", 'archive-'.$time);
        }
        config()->set('backup-storage.export.destination_root', $this->exportRoot);

        $catalog = app(ContpaqiBackupCatalog::class);
        $records = collect($catalog->catalog(true)['records'])->where('date', now()->toDateString())->values();
        $availability = app(ContpaqiBackupExport::class)->recordAvailability(now()->toDateString(), $records->all(), 'ANGEL MOO');

        $this->assertSame('available', $availability[$records->firstWhere('time', '17:30:00')['id']]);
        $this->assertSame('older', $availability[$records->firstWhere('time', '09:00:00')['id']]);
    }

    public function test_a_newer_server_backup_is_marked_for_update_after_the_company_was_exported(): void
    {
        $date = now()->format('Ymd');
        File::put($this->catalogRoot."/Empresas/ctEmpresa_Demo/ctEmpresa_Demo-{$date}-0900.bak", 'database-old');
        File::put($this->catalogRoot."/Index/respaldos/ctEmpresa_Demo-{$date}-0900.zip", 'archive-old');
        config()->set('backup-storage.export.destination_root', $this->exportRoot);

        $catalog = app(ContpaqiBackupCatalog::class);
        $oldRecord = collect($catalog->catalog(true)['records'])->firstWhere('time', '09:00:00');
        $export = app(ContpaqiBackupExport::class);
        $state = $export->create(now()->toDateString(), [$oldRecord['id']], 1, 'ANGEL MOO', $catalog);
        $this->exportIds[] = $state['id'];
        $export->execute($state['id'], $catalog);

        File::put($this->catalogRoot."/Empresas/ctEmpresa_Demo/ctEmpresa_Demo-{$date}-1730.bak", 'database-new');
        File::put($this->catalogRoot."/Index/respaldos/ctEmpresa_Demo-{$date}-1730.zip", 'archive-new');
        $records = collect($catalog->catalog(true)['records'])->where('date', now()->toDateString())->values();
        $availability = $export->recordAvailability(now()->toDateString(), $records->all(), 'ANGEL MOO');

        $this->assertSame('outdated', $availability[$records->firstWhere('time', '17:30:00')['id']]);
        $this->assertSame('older', $availability[$records->firstWhere('time', '09:00:00')['id']]);
    }
}
