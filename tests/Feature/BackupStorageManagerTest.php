<?php

namespace Tests\Feature;

use App\Jobs\AssembleBackupUpload;
use App\Jobs\CopyContpaqiBackups;
use App\Models\BackupUpload;
use App\Models\Role;
use App\Models\User;
use App\Services\Backups\BackupArchiveExtractor;
use App\Services\Backups\ContpaqiBackupCatalog;
use App\Services\Backups\ContpaqiBackupExport;
use App\Services\Backups\ContpaqiBackupProcessLauncher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Livewire\Livewire;
use Tests\TestCase;

class BackupStorageManagerTest extends TestCase
{
    use RefreshDatabase;

    private string $catalogRoot;

    private string $exportRoot;

    private array $exportStateIds = [];

    private array $backupEventIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->catalogRoot = storage_path('framework/testing/backup-manager-'.uniqid());
        $this->exportRoot = storage_path('framework/testing/backup-manager-export-'.uniqid());
        File::ensureDirectoryExists($this->catalogRoot.'/Empresas/ctEmpresa_Prueba');
        File::ensureDirectoryExists($this->catalogRoot.'/Index/respaldos');
        File::ensureDirectoryExists($this->exportRoot);
        File::ensureDirectoryExists($this->exportRoot.'/ANGEL MOO');
        config()->set('backup-storage.server.root', $this->catalogRoot);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->catalogRoot);
        File::deleteDirectory($this->exportRoot);
        foreach ($this->exportStateIds as $id) {
            File::delete(storage_path("app/private/backup-exports/{$id}.json"));
        }
        foreach ($this->backupEventIds as $id) {
            File::delete(storage_path("app/private/backup-events/{$id}.json"));
        }
        parent::tearDown();
    }

    private function user(string $roleName, string $email): User
    {
        $role = Role::query()->firstOrCreate(['role' => $roleName], [
            'permission_profile' => $roleName === 'Administrador' ? Role::PROFILE_ADMINISTRATOR : Role::PROFILE_CUSTOM,
        ]);

        return User::query()->create([
            'name' => $roleName,
            'last_name' => 'Prueba',
            'email' => $email,
            'email_verified_at' => now(),
            'password' => Hash::make('secret'),
            'role_id' => $role->id,
        ]);
    }

    public function test_every_role_except_interns_can_open_the_backup_manager(): void
    {
        $admin = $this->user('Administrador', 'admin-storage@datamid.test');
        $accountant = $this->user('Contador', 'contador-storage@datamid.test');
        $auxiliary = $this->user('Auxiliar', 'aux-storage@datamid.test');
        $coordinator = $this->user('Coordinador', 'coordinator-storage@datamid.test');
        $intern = $this->user('Becario', 'intern-storage@datamid.test');
        $seniorIntern = $this->user('Becario Senior', 'senior-intern-storage@datamid.test');
        config()->set('backup-storage.sites.cancun', 'Cancún');
        config()->set('backup-storage.parallel_chunks', 3);

        $this->actingAs($admin)->get(route('activity-backups.index'))
            ->assertOk()
            ->assertSee('Gestión de Respaldos')
            ->assertSee('Selecciona una carpeta para comprobar cuáles ya están respaldadas y definir el destino.')
            ->assertSee('wire:poll.15s="refreshSharedExportStatus"', false)
            ->assertDontSee('Generar respaldo de la empresa')
            ->assertDontSee('Configura y genera los respaldos fácilmente.')
            ->assertDontSee('Servidor conectado')
            ->assertDontSee('Subir respaldos')
            ->assertDontSee('Eliminar respaldo')
            ->assertDontSee('Cancelar subida');
        $this->assertTrue(Gate::forUser($accountant)->allows('manage-system-backups'));
        $this->assertFalse(Gate::forUser($auxiliary)->allows('manage-system-backups'));
        $this->assertTrue(Gate::forUser($auxiliary)->allows('access-system-backups'));
        $this->assertTrue(Gate::forUser($auxiliary)->allows('upload-system-backups'));
        $this->assertTrue(Gate::forUser($coordinator)->allows('upload-system-backups'));
        $this->assertFalse(Gate::forUser($intern)->allows('access-system-backups'));
        $this->assertFalse(Gate::forUser($intern)->allows('upload-system-backups'));
        $this->assertFalse(Gate::forUser($seniorIntern)->allows('upload-system-backups'));

        $this->flushSession()->actingAs($auxiliary)->get(route('activity-backups.index'))->assertOk();
        $this->flushSession()->actingAs($coordinator)->get(route('activity-backups.index'))->assertOk();
        $this->flushSession()->actingAs($intern)->get(route('activity-backups.index'))->assertForbidden();
        $this->flushSession()->actingAs($seniorIntern)->get(route('activity-backups.index'))->assertForbidden();

        $upload = [
            'site' => 'merida',
            'product' => 'contabilidad',
            'file_name' => 'auxiliar.zip',
            'mime_type' => 'application/zip',
            'size' => 100,
            'fingerprint' => 'auxiliar:upload',
        ];
        $this->flushSession()->actingAs($auxiliary)
            ->postJson(route('activity-backups.uploads.initialize'), $upload)
            ->assertOk();
        $this->flushSession()->actingAs($intern)
            ->postJson(route('activity-backups.uploads.initialize'), array_merge($upload, ['fingerprint' => 'intern:upload']))
            ->assertForbidden();

        $existing = BackupUpload::query()->create([
            'upload_key' => hash('sha256', 'managed-backup'),
            'site' => 'merida',
            'product' => 'contabilidad',
            'user_id' => $admin->id,
            'original_name' => 'administrado.zip',
            'extension' => 'zip',
            'size' => 100,
            'chunk_size' => 100,
            'total_chunks' => 1,
            'uploaded_chunks' => [0],
            'received_bytes' => 100,
            'status' => BackupUpload::STATUS_COMPLETED,
            'last_activity_at' => now(),
            'completed_at' => now(),
        ]);
        $this->flushSession()->actingAs($auxiliary)
            ->postJson(route('activity-backups.uploads.initialize'), array_merge($upload, [
                'fingerprint' => 'auxiliary:replacement',
                'replace_upload_id' => $existing->id,
            ]))
            ->assertForbidden();
    }

    public function test_interface_keeps_server_and_memory_states_separate_and_can_verify_synchronization(): void
    {
        $date = now()->format('Ymd');
        File::put($this->catalogRoot."/Empresas/ctEmpresa_Prueba/ctEmpresa_Prueba-{$date}-0900.bak", 'database');
        File::put($this->catalogRoot."/Index/respaldos/ctEmpresa_Prueba-{$date}-0900.zip", 'archive');
        File::ensureDirectoryExists($this->catalogRoot.'/Empresas/ctEmpresa_Incompleta');
        File::put($this->catalogRoot."/Empresas/ctEmpresa_Incompleta/ctEmpresa_Incompleta-{$date}-1000.bak", 'database-only');
        config()->set('backup-storage.export.destination_root', $this->exportRoot);
        $admin = $this->user('Administrador', 'admin-sync@datamid.test');

        Livewire::actingAs($admin)
            ->test(\App\Livewire\Backups\BackupStorageManager::class)
            ->set('destinationFolder', 'ANGEL MOO')
            ->assertSee('Archivos del servidor')
            ->assertSee('Respaldo en memoria')
            ->assertSee('BAK e INDEX')
            ->assertSee('Falta INDEX')
            ->assertSee('Completos')
            ->assertSee('Incompletos')
            ->call('verifySynchronization')
            ->assertSet('syncResult.server_complete', 1)
            ->assertSet('syncResult.server_incomplete', 1)
            ->assertSet('syncResult.synced', 0)
            ->assertSet('syncResult.pending', 1)
            ->assertSee('Sincronización comprobada')
            ->assertSeeHtml('backup-sync-toast fixed')
            ->assertSeeHtml('setTimeout(() => visible = false, 6000)')
            ->assertDontSee('Resultado de sincronización');
    }

    public function test_user_can_choose_a_folder_from_the_fixed_backup_memory(): void
    {
        $date = now()->format('Ymd');
        File::put($this->catalogRoot."/Empresas/ctEmpresa_Prueba/ctEmpresa_Prueba-{$date}-0900.bak", 'database');
        File::put($this->catalogRoot."/Index/respaldos/ctEmpresa_Prueba-{$date}-0900.zip", 'archive');
        config()->set('backup-storage.export.destination_root', $this->exportRoot);
        File::ensureDirectoryExists($this->exportRoot.'/ANGEL MOO/NOMINAS/SEMANALES');
        $admin = $this->user('Administrador', 'admin-destinations@datamid.test');

        Livewire::actingAs($admin)
            ->test(\App\Livewire\Backups\BackupStorageManager::class)
            ->assertSet('destinationKey', 'principal')
            ->assertDontSee('Seleccione la sede, el departamento y la carpeta')
            ->assertSee('Tulum · Contabilidad')
            ->assertSee('Próximamente')
            ->assertSee('Cargando respaldos…')
            ->assertSee(ucfirst(now()->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY')))
            ->assertSee('ANGEL MOO')
            ->call('browseDestinationFolder', 'ANGEL MOO')
            ->assertSet('destinationBrowsePath', 'ANGEL MOO')
            ->assertSee('NOMINAS')
            ->call('browseDestinationFolder', 'ANGEL MOO/NOMINAS')
            ->assertSet('destinationBrowsePath', 'ANGEL MOO/NOMINAS')
            ->assertSee('SEMANALES')
            ->set('destinationFolder', 'ANGEL MOO/NOMINAS/SEMANALES')
            ->assertSet('destinationFolder', 'ANGEL MOO/NOMINAS/SEMANALES')
            ->call('browseDestinationUp')
            ->assertSet('destinationBrowsePath', 'ANGEL MOO')
            ->assertDontSee('Se guardará en')
            ->assertSeeHtml('backup-catalog-scroll-vertical-visible')
            ->assertSeeHtml('relative h-[544px]')
            ->assertSeeHtml('class="h-[70px]"')
            ->assertDontSeeHtml('<tr @click="open = !open"');
    }

    public function test_destination_browser_rejects_paths_outside_the_usb(): void
    {
        config()->set('backup-storage.export.destination_root', $this->exportRoot);
        $admin = $this->user('Administrador', 'admin-invalid-destination@datamid.test');

        Livewire::actingAs($admin)
            ->test(\App\Livewire\Backups\BackupStorageManager::class)
            ->call('browseDestinationFolder', '../fuera')
            ->assertStatus(422);
    }

    public function test_user_can_switch_between_accounting_and_payroll_profiles(): void
    {
        $date = now()->format('Ymd');
        File::ensureDirectoryExists($this->catalogRoot.'/Empresas/ctEMPRESA_CONTABLE');
        File::ensureDirectoryExists($this->catalogRoot.'/Empresas/NtALMACEN_DE_PINT/Respaldo');
        File::put($this->catalogRoot."/Empresas/ctEMPRESA_CONTABLE/EMPRESA CONTABLE-{$date}-0900.bak", 'database');
        File::put($this->catalogRoot."/Index/respaldos/EMPRESA CONTABLE-{$date}-0900.zip", 'archive');
        File::put($this->catalogRoot."/Empresas/NtALMACEN_DE_PINT/Respaldo/ALMACEN DE PINTURAS ZONA MERIDA SA DE CV S#42 16 oct-{$date}-0910.bak", 'database');
        File::put($this->catalogRoot."/Index/respaldos/ALMACEN DE PINTURAS ZONA MERIDA SA DE CV S#42 16 oct-{$date}-0910.zip", 'archive');
        config()->set('backup-storage.export.destination_root', $this->exportRoot);
        config()->set('backup-storage.backup_profiles.merida-nominas.destination_root', $this->exportRoot);
        $admin = $this->user('Administrador', 'admin-backup-profiles@datamid.test');

        Livewire::actingAs($admin)
            ->test(\App\Livewire\Backups\BackupStorageManager::class)
            ->assertSee('EMPRESA CONTABLE')
            ->assertDontSee('ALMACEN DE PINTURAS ZONA MERIDA')
            ->set('backupProfile', 'merida-nominas')
            ->assertSet('destinationKey', 'merida-nominas')
            ->assertSet('destinationFolder', '')
            ->assertSee('Mérida · Nóminas')
            ->assertSee('ALMACEN DE PINTURAS ZONA MERIDA')
            ->assertDontSee('EMPRESA CONTABLE');
    }

    public function test_empty_status_filter_explains_that_other_records_exist(): void
    {
        $date = now()->format('Ymd');
        File::put($this->catalogRoot."/Empresas/ctEmpresa_Prueba/ctEmpresa_Prueba-{$date}-0900.bak", 'database');
        File::put($this->catalogRoot."/Index/respaldos/ctEmpresa_Prueba-{$date}-0900.zip", 'archive');
        $admin = $this->user('Administrador', 'admin-empty-status@datamid.test');

        Livewire::actingAs($admin)
            ->test(\App\Livewire\Backups\BackupStorageManager::class)
            ->set('status', 'partial')
            ->assertSee('No hay respaldos en este estado')
            ->assertSee('La fecha tiene 1 respaldo en otros estados.')
            ->assertSee('Mostrar todos los respaldos')
            ->set('status', 'all')
            ->assertSee('ctEmpresa_Prueba');
    }

    public function test_failed_export_is_shown_as_a_temporary_toast_without_progress_panel(): void
    {
        config()->set('backup-storage.export.destination_root', $this->exportRoot);
        $admin = $this->user('Administrador', 'admin-export-error@datamid.test');

        Livewire::actingAs($admin)
            ->test(\App\Livewire\Backups\BackupStorageManager::class)
            ->set('exportStatus', [
                'status' => 'failed',
                'error' => 'No fue posible copiar el respaldo.',
                'completed_at' => now()->toIso8601String(),
            ])
            ->assertSee('No fue posible copiar el respaldo.')
            ->assertSeeHtml('x-init="setTimeout(() => visible = false, 5000)"')
            ->assertDontSee('Copia interrumpida');
    }

    public function test_complete_companies_can_be_selected_before_choosing_the_destination(): void
    {
        $date = now()->format('Ymd');
        File::put($this->catalogRoot."/Empresas/ctEmpresa_Prueba/ctEmpresa_Prueba-{$date}-0900.bak", 'database');
        File::put($this->catalogRoot."/Index/respaldos/ctEmpresa_Prueba-{$date}-0900.zip", 'archive');
        config()->set('backup-storage.export.destination_root', $this->exportRoot);
        $record = collect(app(ContpaqiBackupCatalog::class)->dashboard(now()->toDateString())['records'])
            ->firstWhere('complete', true);
        $admin = $this->user('Administrador', 'admin-select-first@datamid.test');

        $component = Livewire::actingAs($admin)
            ->test(\App\Livewire\Backups\BackupStorageManager::class)
            ->assertSee('Seleccionar todas las empresas disponibles')
            ->assertDontSee('Generar respaldo de la empresa')
            ->assertDontSee('Buscar empresa o archivo')
            ->call('toggleAllAvailable')
            ->assertSet('selectedRecords', [$record['id']])
            ->assertSee('Empresa')
            ->assertSee('(1)')
            ->assertSeeHtml('order-1 h-[70px]')
            ->assertSee('Respaldar 1 empresa')
            ->call('toggleAllAvailable')
            ->assertSet('selectedRecords', [])
            ->call('toggleAllAvailable')
            ->assertSet('selectedRecords', [$record['id']])
            ->set('status', 'backed_up')
            ->assertSet('selectedRecords', [$record['id']])
            ->set('status', 'all')
            ->set('selectedRecords', [$record['id']])
            ->set('destinationFolder', 'ANGEL MOO')
            ->assertSet('selectedRecords', [$record['id']]);

        $component->assertDontSee('Se deseleccionó 1 empresa');
    }

    public function test_an_already_backed_up_company_remains_selectable_for_replacement(): void
    {
        $date = now()->format('Ymd');
        File::put($this->catalogRoot."/Empresas/ctEmpresa_Prueba/ctEmpresa_Prueba-{$date}-0900.bak", 'database');
        File::put($this->catalogRoot."/Index/respaldos/ctEmpresa_Prueba-{$date}-0900.zip", 'archive');
        config()->set('backup-storage.export.destination_root', $this->exportRoot);
        $catalog = app(ContpaqiBackupCatalog::class);
        $record = collect($catalog->dashboard(now()->toDateString())['records'])->firstWhere('complete', true);
        $export = app(ContpaqiBackupExport::class);
        $state = $export->create(now()->toDateString(), [$record['id']], 1, 'ANGEL MOO', $catalog);
        $this->exportStateIds[] = $state['id'];
        $export->execute($state['id'], $catalog);
        $admin = $this->user('Administrador', 'admin-selection-validation@datamid.test');

        Livewire::actingAs($admin)
            ->test(\App\Livewire\Backups\BackupStorageManager::class)
            ->set('selectedRecords', [$record['id']])
            ->assertSet('selectedRecords', [$record['id']])
            ->set('destinationFolder', 'ANGEL MOO')
            ->assertSet('selectedRecords', [$record['id']])
            ->assertSet('selectionNotice', null)
            ->assertSee('Reemplazar respaldo');
    }

    public function test_manager_can_delete_only_the_external_copy_with_password_and_two_factor_code(): void
    {
        $date = now()->format('Ymd');
        $databasePath = $this->catalogRoot."/Empresas/ctEmpresa_Prueba/ctEmpresa_Prueba-{$date}-0900.bak";
        $archivePath = $this->catalogRoot."/Index/respaldos/ctEmpresa_Prueba-{$date}-0900.zip";
        File::put($databasePath, 'database');
        File::put($archivePath, 'archive');
        config()->set('backup-storage.export.destination_root', $this->exportRoot);
        $catalog = app(ContpaqiBackupCatalog::class);
        $record = collect($catalog->dashboard(now()->toDateString())['records'])->firstWhere('complete', true);
        $export = app(ContpaqiBackupExport::class);
        $state = $export->create(now()->toDateString(), [$record['id']], 1, 'ANGEL MOO', $catalog);
        $this->exportStateIds[] = $state['id'];
        $export->execute($state['id'], $catalog);
        $destination = $this->exportRoot.'/ANGEL MOO/'.now()->format('d-m-Y').'/ctEmpresa_Prueba';

        $admin = $this->user('Administrador', 'admin-delete-backup@datamid.test');
        $admin->forceFill([
            'two_factor_secret' => encrypt('two-factor-secret'),
            'two_factor_confirmed_at' => now(),
        ])->save();
        $provider = \Mockery::mock(TwoFactorAuthenticationProvider::class);
        $provider->shouldReceive('verify')->once()->with('two-factor-secret', '000000')->andReturnFalse();
        $provider->shouldReceive('verify')->once()->with('two-factor-secret', '123456')->andReturnTrue();
        $this->app->instance(TwoFactorAuthenticationProvider::class, $provider);

        $component = Livewire::actingAs($admin)
            ->test(\App\Livewire\Backups\BackupStorageManager::class)
            ->set('destinationFolder', 'ANGEL MOO')
            ->set('selectedRecords', [$record['id']])
            ->call('openDeleteConfirmation')
            ->assertSet('showDeleteConfirmation', true)
            ->set('deletePassword', 'secret')
            ->set('deleteTwoFactorCode', '000000')
            ->call('deleteDestinationBackups')
            ->assertHasErrors('deleteTwoFactorCode')
            ->assertSet('showDeleteConfirmation', true);

        $this->assertDirectoryExists($destination);

        $component
            ->set('deleteTwoFactorCode', '123456')
            ->call('deleteDestinationBackups')
            ->assertHasNoErrors()
            ->assertSet('showDeleteConfirmation', false)
            ->assertSet('actionMessage', 'Se eliminó 1 respaldo de la memoria.');

        $this->assertDirectoryDoesNotExist($destination);
        $this->assertSame('database', File::get($databasePath));
        $this->assertSame('archive', File::get($archivePath));

        $deletion = collect($export->history())->first(fn (array $movement): bool =>
            $movement['type'] === 'deleted' && in_array('Empresa Prueba', $movement['companies'], true)
        );
        $this->assertNotNull($deletion);
        $this->assertSame($admin->id, $deletion['user_id']);
        $this->assertSame('ANGEL MOO', $deletion['destination_folder']);
        $this->backupEventIds[] = $deletion['id'];

        Livewire::actingAs($admin)
            ->test(\App\Livewire\Backups\BackupStorageManager::class, ['activeView' => 'history'])
            ->assertSee('Historial de movimientos')
            ->assertSee('Respaldo eliminado')
            ->assertSee('Empresa Prueba')
            ->assertSee('Administrador Prueba');
    }

    public function test_backup_deletion_is_blocked_until_two_factor_is_configured(): void
    {
        config()->set('backup-storage.export.destination_root', $this->exportRoot);
        $admin = $this->user('Administrador', 'admin-delete-without-2fa@datamid.test');

        Livewire::actingAs($admin)
            ->test(\App\Livewire\Backups\BackupStorageManager::class)
            ->call('openDeleteConfirmation')
            ->assertSet('showDeleteConfirmation', false)
            ->assertSet('exportMessage', 'Activa y confirma la autenticación en dos pasos en tu perfil antes de eliminar respaldos.');
    }

    public function test_export_recovers_the_fixed_destination_and_starts_a_background_process_without_a_worker(): void
    {
        Bus::fake([CopyContpaqiBackups::class]);
        $launcher = \Mockery::mock(ContpaqiBackupProcessLauncher::class);
        $launcher->shouldReceive('launch')->once()->andReturnTrue();
        $this->app->instance(ContpaqiBackupProcessLauncher::class, $launcher);
        $date = now()->format('Ymd');
        File::put($this->catalogRoot."/Empresas/ctEmpresa_Prueba/ctEmpresa_Prueba-{$date}-0900.bak", 'database');
        File::put($this->catalogRoot."/Index/respaldos/ctEmpresa_Prueba-{$date}-0900.zip", 'archive');
        config()->set('backup-storage.export.destination_root', $this->exportRoot);
        $record = collect(app(ContpaqiBackupCatalog::class)->dashboard(now()->toDateString())['records'])
            ->firstWhere('complete', true);
        $admin = $this->user('Administrador', 'admin-automatic-export@datamid.test');

        $component = Livewire::actingAs($admin)
            ->test(\App\Livewire\Backups\BackupStorageManager::class)
            ->set('destinationKey', 'unidad-anterior')
            ->set('destinationFolder', 'ANGEL MOO')
            ->set('selectedRecords', [$record['id']])
            ->call('startExport')
            ->assertSet('exportMessage', null)
            ->assertSet('destinationKey', 'principal')
            ->assertSet('exportStatus.status', 'queued');

        $exportId = $component->get('currentExportId');
        $this->exportStateIds[] = $exportId;
        Bus::assertNotDispatched(CopyContpaqiBackups::class);
    }

    public function test_all_users_see_the_same_active_export_and_queued_cancellation_releases_it(): void
    {
        $date = now()->format('Ymd');
        File::put($this->catalogRoot."/Empresas/ctEmpresa_Prueba/ctEmpresa_Prueba-{$date}-0900.bak", str_repeat('b', 1024));
        File::put($this->catalogRoot."/Index/respaldos/ctEmpresa_Prueba-{$date}-0900.zip", str_repeat('z', 2048));
        config()->set('backup-storage.export.destination_root', $this->exportRoot);
        $catalog = app(ContpaqiBackupCatalog::class);
        $record = collect($catalog->dashboard(now()->toDateString())['records'])->firstWhere('complete', true);
        $owner = $this->user('Administrador', 'owner-shared-export@datamid.test');
        $viewer = $this->user('Auxiliar', 'viewer-shared-export@datamid.test');
        $state = app(ContpaqiBackupExport::class)->create(
            now()->toDateString(),
            [$record['id']],
            $owner->id,
            'ANGEL MOO',
            $catalog,
        );
        $this->exportStateIds[] = $state['id'];

        $viewerComponent = Livewire::actingAs($viewer)
            ->test(\App\Livewire\Backups\BackupStorageManager::class)
            ->assertSet('currentExportId', $state['id'])
            ->assertSet('exportStatus.current_company', null)
            ->assertSee('Respaldo en espera')
            ->assertSee('Cancelar respaldo');

        $viewerComponent
            ->call('cancelExport')
            ->assertSet('exportStatus.status', 'cancelled')
            ->assertDontSee('Cancelando respaldo')
            ->call('refreshSharedExportStatus')
            ->assertSet('currentExportId', null);

        Livewire::actingAs($owner)
            ->test(\App\Livewire\Backups\BackupStorageManager::class)
            ->assertSet('currentExportId', null)
            ->assertDontSee('Respaldo en espera');
    }

    public function test_backup_products_are_restricted_by_site(): void
    {
        Storage::fake('local');
        config()->set('backup-storage.disk', 'local');
        $admin = $this->user('Administrador', 'admin-products@datamid.test');

        $this->actingAs($admin)->postJson(route('activity-backups.uploads.initialize'), [
            'site' => 'merida',
            'product' => 'nominas',
            'file_name' => 'nominas.zip',
            'mime_type' => 'application/zip',
            'size' => 100,
            'fingerprint' => 'merida:nominas',
        ])->assertOk()
            ->assertJsonPath('product', 'nominas');

        $this->postJson(route('activity-backups.uploads.initialize'), [
            'site' => 'tulum',
            'product' => 'nominas',
            'file_name' => 'nominas-tulum.zip',
            'mime_type' => 'application/zip',
            'size' => 100,
            'fingerprint' => 'tulum:nominas',
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'El sistema seleccionado no está disponible en esta sede.');

    }

    public function test_chunked_upload_can_resume_queue_assemble_and_download(): void
    {
        Storage::fake('local');
        Queue::fake();
        config()->set('backup-storage.disk', 'local');
        config()->set('backup-storage.chunk_size', 1024);
        config()->set('backup-storage.max_active_uploads', 1);

        $admin = $this->user('Administrador', 'admin-chunks@datamid.test');
        $indexContents = random_bytes(1600);
        $bakContents = 'contenido del respaldo BAK';
        $payload = $this->zip([
            'exportacion/Cliente Contable/Index/empresa_2026.index' => $indexContents,
            'exportacion/Cliente Contable/Bak/empresa_2026.bak' => $bakContents,
        ]);

        $response = $this->actingAs($admin)->postJson(route('activity-backups.uploads.initialize'), [
            'site' => 'merida', 'file_name' => 'empresa_2026.zip', 'mime_type' => 'application/zip',
            'size' => strlen($payload), 'fingerprint' => 'empresa:zip:12345',
        ])->assertOk();

        $uploadId = $response->json('id');
        $this->assertStringStartsWith('/', $response->json('statusUrl'));
        $this->assertStringStartsWith('/', $response->json('chunkUrlTemplate'));
        $this->assertStringStartsWith('/', $response->json('completeUrl'));
        $this->assertStringStartsWith('/', $response->json('cancelUrl'));
        $upload = BackupUpload::query()->findOrFail($uploadId);
        $this->assertGreaterThanOrEqual(2, $upload->total_chunks);

        $this->postRaw(route('activity-backups.uploads.chunks.store', [$upload, 0]), substr($payload, 0, 1024))->assertOk();
        $status = $this->getJson(route('activity-backups.uploads.status', $upload))->assertOk();
        $this->assertSame([0], $status->json('uploadedChunks'));

        // Reenviar un fragmento ya confirmado no duplica bytes ni progreso.
        $this->postRaw(route('activity-backups.uploads.chunks.store', [$upload, 0]), substr($payload, 0, 1024))->assertOk();
        for ($index = 1; $index < $upload->total_chunks; $index++) {
            $this->postRaw(route('activity-backups.uploads.chunks.store', [$upload, $index]), substr($payload, $index * 1024, 1024))->assertOk();
        }
        $this->postJson(route('activity-backups.uploads.complete', $upload))->assertAccepted();
        $this->postJson(route('activity-backups.uploads.complete', $upload))->assertAccepted();
        Queue::assertPushed(AssembleBackupUpload::class, 1);
        Queue::assertPushed(AssembleBackupUpload::class, fn (AssembleBackupUpload $job) => $job->uploadId === $upload->id
            && $job->connection === 'sync'
            && $job->queue === 'backup-uploads');

        (new AssembleBackupUpload($upload->id))->handle(app(BackupArchiveExtractor::class));
        $upload->refresh();
        $this->assertSame(BackupUpload::STATUS_COMPLETED, $upload->status);
        $this->assertSame(hash('sha256', $payload), $upload->checksum);
        $this->assertSame($payload, Storage::disk('local')->get($upload->storage_path));
        $this->assertStringStartsWith('backup-archives/merida/contabilidad/', $upload->storage_path);
        $this->assertCount(2, $upload->manifest);
        $this->assertSame('Cliente Contable', $upload->manifest[0]['customer']);
        $this->assertSame('index', $upload->manifest[0]['category']);
        $this->assertSame($indexContents, Storage::disk('local')->get($upload->manifest[0]['path']));
        $this->assertStringStartsWith('backups/merida/contabilidad/', $upload->manifest[0]['path']);

        $this->get(route('activity-backups.download', $upload))
            ->assertOk()
            ->assertDownload('empresa_2026.zip');
        $this->get(route('activity-backups.files.download', [$upload, 1]))
            ->assertOk()
            ->assertDownload('empresa_2026.bak');

        $upload->refresh();
        $this->assertCount(2, $upload->manifest);
    }

    public function test_non_intern_upload_is_assembled_automatically_without_a_queue_worker(): void
    {
        Storage::fake('local');
        config()->set('backup-storage.disk', 'local');
        config()->set('backup-storage.queue_connection', 'sync');
        config()->set('backup-storage.chunk_size', 1024 * 1024);

        $auxiliary = $this->user('Auxiliar', 'auxiliary-automatic-upload@datamid.test');
        $payload = $this->zip([
            'Cliente Automático/Index/empresa.index' => 'indice',
            'Cliente Automático/Bak/empresa.bak' => 'respaldo',
        ]);

        $response = $this->actingAs($auxiliary)->postJson(route('activity-backups.uploads.initialize'), [
            'site' => 'merida',
            'file_name' => 'automatico.zip',
            'mime_type' => 'application/zip',
            'size' => strlen($payload),
            'fingerprint' => 'automatico:zip:1',
        ])->assertOk();

        $upload = BackupUpload::query()->findOrFail($response->json('id'));
        $this->postRaw(route('activity-backups.uploads.chunks.store', [$upload, 0]), $payload)->assertOk();

        $this->postJson(route('activity-backups.uploads.complete', $upload))
            ->assertAccepted()
            ->assertJsonPath('status', BackupUpload::STATUS_COMPLETED);

        $upload->refresh();
        $this->assertSame(BackupUpload::STATUS_COMPLETED, $upload->status);
        $this->assertCount(2, $upload->manifest);
        $this->assertSame('Cliente Automático', $upload->manifest[0]['customer']);
        Storage::disk('local')->assertMissing("backup-chunks/{$upload->id}");
        Storage::disk('local')->assertExists($upload->storage_path);
    }

    public function test_example_zip_contains_the_fifteen_company_structures(): void
    {
        $admin = $this->user('Administrador', 'admin-example@datamid.test');

        $response = $this->actingAs($admin)->get(route('activity-backups.example'))
            ->assertOk()
            ->assertDownload('respaldo-ejemplo-15-clientes.zip');

        $temporaryBase = tempnam(sys_get_temp_dir(), 'backup-example-test-');
        @unlink($temporaryBase);
        $temporary = $temporaryBase.'.zip';
        file_put_contents($temporary, $response->streamedContent());
        $zip = new \PharData($temporary);
        $this->assertSame(30, iterator_count(new \RecursiveIteratorIterator($zip)));
        unset($zip);
        @unlink($temporary);
    }

    public function test_secondary_upload_waits_when_the_concurrency_limit_is_reached(): void
    {
        Storage::fake('local');
        config()->set('backup-storage.max_active_uploads', 1);
        $admin = $this->user('Administrador', 'admin-queue@datamid.test');
        $base = ['site' => 'tulum', 'mime_type' => 'application/zip', 'size' => 100];

        $this->actingAs($admin)->postJson(route('activity-backups.uploads.initialize'), $base + ['file_name' => 'primero.zip', 'fingerprint' => 'first'])->assertJsonPath('status', 'uploading');
        $this->postJson(route('activity-backups.uploads.initialize'), $base + ['file_name' => 'segundo.zip', 'fingerprint' => 'second'])->assertJsonPath('status', 'waiting');
    }

    public function test_an_incomplete_chunk_is_rejected_without_advancing_progress(): void
    {
        Storage::fake('local');
        config()->set('backup-storage.disk', 'local');
        config()->set('backup-storage.chunk_size', 1024);
        $admin = $this->user('Administrador', 'admin-partial-chunk@datamid.test');

        $response = $this->actingAs($admin)->postJson(route('activity-backups.uploads.initialize'), [
            'site' => 'merida', 'file_name' => 'parcial.zip', 'mime_type' => 'application/zip',
            'size' => 2048, 'fingerprint' => 'partial:chunk',
        ])->assertOk();
        $upload = BackupUpload::query()->findOrFail($response->json('id'));

        $this->postRaw(route('activity-backups.uploads.chunks.store', [$upload, 0]), random_bytes(512))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'El fragmento 0 está incompleto; se esperaban 1024 bytes y llegaron 512. Reintenta la carga.');

        Storage::disk('local')->assertMissing("backup-chunks/{$upload->id}/0.part");
        Storage::disk('local')->assertMissing("backup-chunks/{$upload->id}/0.part.uploading");
        $this->assertSame([], $upload->fresh()->uploaded_chunks);
        $this->assertSame(0, $upload->fresh()->received_bytes);
    }

    public function test_completion_reconciles_database_progress_with_physical_chunks(): void
    {
        Storage::fake('local');
        config()->set('backup-storage.disk', 'local');
        $admin = $this->user('Administrador', 'admin-missing-chunk@datamid.test');
        $upload = BackupUpload::query()->create([
            'upload_key' => hash('sha256', 'missing-chunk'), 'site' => 'merida', 'user_id' => $admin->id,
            'original_name' => 'incompleto.zip', 'extension' => 'zip', 'mime_type' => 'application/zip',
            'size' => 1024, 'chunk_size' => 1024, 'total_chunks' => 1, 'uploaded_chunks' => [0],
            'received_bytes' => 1024, 'status' => BackupUpload::STATUS_UPLOADING, 'last_activity_at' => now(),
        ]);

        $this->actingAs($admin)->postJson(route('activity-backups.uploads.complete', $upload))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Falta el fragmento 0 o está incompleto. La carga se reanudará desde ese punto.');

        $upload->refresh();
        $this->assertSame([], $upload->uploaded_chunks);
        $this->assertSame(0, $upload->received_bytes);
        $this->assertSame(BackupUpload::STATUS_UPLOADING, $upload->status);
    }

    public function test_assembler_reports_a_missing_chunk_without_a_php_type_error(): void
    {
        Storage::fake('local');
        config()->set('backup-storage.disk', 'local');
        $admin = $this->user('Administrador', 'admin-assembler-missing@datamid.test');
        $upload = BackupUpload::query()->create([
            'upload_key' => hash('sha256', 'assembler-missing'), 'site' => 'merida', 'user_id' => $admin->id,
            'original_name' => 'faltante.zip', 'extension' => 'zip', 'mime_type' => 'application/zip',
            'size' => 1024, 'chunk_size' => 1024, 'total_chunks' => 1, 'uploaded_chunks' => [0],
            'received_bytes' => 1024, 'status' => BackupUpload::STATUS_QUEUED, 'last_activity_at' => now(),
        ]);

        try {
            (new AssembleBackupUpload($upload->id))->handle(app(BackupArchiveExtractor::class));
            $this->fail('El ensamblador debió detectar el fragmento físico faltante.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Falta el fragmento 0 del respaldo.', $exception->getMessage());
        }

        Storage::disk('local')->assertMissing("backup-assembly/{$upload->id}.part");
    }

    public function test_an_active_or_queued_upload_can_be_cancelled_and_its_chunks_are_removed(): void
    {
        Storage::fake('local');
        config()->set('backup-storage.disk', 'local');
        config()->set('backup-storage.chunk_size', 1024);
        $admin = $this->user('Administrador', 'admin-cancel@datamid.test');

        $response = $this->actingAs($admin)->postJson(route('activity-backups.uploads.initialize'), [
            'site' => 'merida',
            'file_name' => 'cancelable.zip',
            'mime_type' => 'application/zip',
            'size' => 2048,
            'fingerprint' => 'cancelable:zip',
        ])->assertOk();
        $upload = BackupUpload::query()->findOrFail($response->json('id'));

        $this->postRaw(route('activity-backups.uploads.chunks.store', [$upload, 0]), random_bytes(1024))->assertOk();
        Storage::disk('local')->assertExists("backup-chunks/{$upload->id}/0.part");
        $upload->forceFill(['status' => BackupUpload::STATUS_QUEUED])->save();

        $this->deleteJson(route('activity-backups.uploads.cancel', $upload))
            ->assertOk()
            ->assertJson(['cancelled' => true]);

        $this->assertDatabaseMissing('backup_uploads', ['id' => $upload->id]);
        Storage::disk('local')->assertMissing("backup-chunks/{$upload->id}/0.part");
    }

    public function test_read_only_manager_does_not_expose_queued_upload_controls(): void
    {
        Storage::fake('local');
        config()->set('backup-storage.disk', 'local');
        $admin = $this->user('Administrador', 'admin-reloaded-queue@datamid.test');
        $upload = BackupUpload::query()->create([
            'upload_key' => hash('sha256', 'reloaded-queue'),
            'site' => 'merida',
            'user_id' => $admin->id,
            'original_name' => 'respaldo-en-cola.zip',
            'extension' => 'zip',
            'mime_type' => 'application/zip',
            'size' => 1024,
            'chunk_size' => 1024,
            'total_chunks' => 1,
            'uploaded_chunks' => [0],
            'received_bytes' => 1024,
            'status' => BackupUpload::STATUS_QUEUED,
            'queued_at' => now(),
            'last_activity_at' => now(),
        ]);
        Storage::disk('local')->put("backup-chunks/{$upload->id}/0.part", 'fragmento');

        Livewire::actingAs($admin)
            ->test(\App\Livewire\Backups\BackupStorageManager::class)
            ->assertDontSee('respaldo-en-cola.zip')
            ->assertDontSee('Cancelar carga');

        $this->assertDatabaseHas('backup_uploads', ['id' => $upload->id]);
        Storage::disk('local')->assertExists("backup-chunks/{$upload->id}/0.part");
    }

    public function test_only_zip_archives_can_be_initialized(): void
    {
        $admin = $this->user('Administrador', 'admin-invalid-zip@datamid.test');

        $this->actingAs($admin)->postJson(route('activity-backups.uploads.initialize'), [
            'site' => 'merida',
            'file_name' => 'respaldo.bak',
            'mime_type' => 'application/octet-stream',
            'size' => 100,
            'fingerprint' => 'invalid-backup',
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'Selecciona un archivo ZIP válido.');
    }

    public function test_read_only_manager_does_not_expose_metadata_or_purge_actions(): void
    {
        Storage::fake('local');
        config()->set('backup-storage.disk', 'local');
        $admin = $this->user('Administrador', 'admin-history@datamid.test');
        $upload = BackupUpload::query()->create([
            'upload_key' => hash('sha256', 'history-actions'),
            'site' => 'merida',
            'user_id' => $admin->id,
            'original_name' => 'historial.zip',
            'extension' => 'zip',
            'mime_type' => 'application/zip',
            'size' => 10,
            'chunk_size' => 1024,
            'total_chunks' => 1,
            'uploaded_chunks' => [0],
            'received_bytes' => 10,
            'status' => BackupUpload::STATUS_COMPLETED,
            'storage_path' => 'backup-archives/merida/historial.zip',
            'manifest' => [[
                'customer' => 'Cliente Historial', 'category' => 'bak', 'name' => 'historial.bak',
                'path' => 'backups/merida/cliente-historial/Bak/historial.bak', 'size' => 10,
            ]],
            'completed_at' => now(),
        ]);
        Storage::disk('local')->put($upload->storage_path, '0123456789');
        Storage::disk('local')->put($upload->manifest[0]['path'], '0123456789');

        Livewire::actingAs($admin)
            ->test(\App\Livewire\Backups\BackupStorageManager::class)
            ->assertDontSee('historial.zip')
            ->assertDontSee('Editar')
            ->assertDontSee('Eliminar');

        $this->assertDatabaseHas('backup_uploads', ['id' => $upload->id]);
        Storage::disk('local')->assertExists('backup-archives/merida/historial.zip');
        Storage::disk('local')->assertExists('backups/merida/cliente-historial/Bak/historial.bak');
    }

    public function test_replacement_upload_marks_the_previous_record_as_superseded(): void
    {
        Storage::fake('local');
        Queue::fake();
        config()->set('backup-storage.disk', 'local');
        config()->set('backup-storage.chunk_size', 1024);
        $admin = $this->user('Administrador', 'admin-replacement@datamid.test');
        $previous = BackupUpload::query()->create([
            'upload_key' => hash('sha256', 'previous'), 'site' => 'merida', 'user_id' => $admin->id,
            'original_name' => 'anterior.zip', 'extension' => 'zip', 'mime_type' => 'application/zip',
            'size' => 10, 'chunk_size' => 1024, 'total_chunks' => 1, 'uploaded_chunks' => [0],
            'received_bytes' => 10, 'status' => BackupUpload::STATUS_COMPLETED,
            'storage_path' => 'backup-archives/merida/anterior.zip', 'manifest' => [], 'completed_at' => now(),
        ]);
        $payload = $this->zip([
            'Cliente Reemplazo/Index/nuevo.index' => 'índice',
            'Cliente Reemplazo/Bak/nuevo.bak' => 'respaldo',
        ]);

        $response = $this->actingAs($admin)->postJson(route('activity-backups.uploads.initialize'), [
            'site' => 'merida', 'file_name' => 'nuevo.zip', 'mime_type' => 'application/zip',
            'size' => strlen($payload), 'fingerprint' => 'nuevo:zip:replacement',
            'replace_upload_id' => $previous->id,
        ])->assertOk();
        $replacement = BackupUpload::query()->findOrFail($response->json('id'));
        $this->postRaw(route('activity-backups.uploads.chunks.store', [$replacement, 0]), $payload)->assertOk();
        $this->postJson(route('activity-backups.uploads.complete', $replacement))->assertAccepted();
        (new AssembleBackupUpload($replacement->id))->handle(app(BackupArchiveExtractor::class));

        $this->assertNotNull($previous->fresh()->superseded_at);
        $this->assertSame($previous->id, $replacement->fresh()->supersedes_upload_id);
    }

    private function postRaw(string $uri, string $content)
    {
        return $this->call('POST', $uri, [], [], [], [
            'CONTENT_TYPE' => 'application/octet-stream',
            'HTTP_ACCEPT' => 'application/json',
        ], $content);
    }

    /** @param array<string, string> $files */
    private function zip(array $files): string
    {
        $path = tempnam(sys_get_temp_dir(), 'backup-test-').'.zip';
        $archive = new \PharData($path);
        foreach ($files as $name => $contents) {
            $archive->addFromString($name, $contents);
        }
        unset($archive);

        $contents = file_get_contents($path);
        @unlink($path);

        return $contents === false ? '' : $contents;
    }
}
