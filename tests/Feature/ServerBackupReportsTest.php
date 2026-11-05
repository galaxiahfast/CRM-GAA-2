<?php

namespace Tests\Feature;

use App\Livewire\Support\ServerBackupReports;
use App\Mail\BackupReportMail;
use App\Models\Role;
use App\Models\User;
use App\Services\Support\BackupLogReaderService;
use App\Services\Support\BackupReportPdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Attributes\Renderless;
use Livewire\Livewire;
use ReflectionMethod;
use Tests\TestCase;

class ServerBackupReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_administrators_can_open_backup_reports(): void
    {
        $admin = $this->userWithRole('Administrador', 'admin-backups@datamid.test');
        $auxiliary = $this->userWithRole('Auxiliar', 'aux-backups@datamid.test');

        $this->get(route('soporte.reportes-respaldos'))->assertRedirect(route('login'));
        $this->actingAs($auxiliary)->get(route('soporte.reportes-respaldos'))->assertForbidden();
        $this->flushSession();
        $this->actingAs($admin)->get(route('soporte.reportes-respaldos'))
            ->assertOk()
            ->assertSeeText('Informe Técnico de Respaldos')
            ->assertSeeText('Actualizar registros');
    }

    public function test_report_starts_with_the_five_servers_and_fifteen_profiles(): void
    {
        $admin = $this->userWithRole('Administrador', 'admin-defaults@datamid.test');

        Livewire::actingAs($admin)->test(ServerBackupReports::class)
            ->assertSet('totalProfiles', 15)
            ->assertSet('reading', false)
            ->assertSet('initialScanStarted', false)
            ->assertSet('scanAuthorized', false)
            ->assertCount('servers', 5)
            ->assertSeeText('Agregar servidor')
            ->assertSet('servers.0.share', '\\\\SRVIFAC\\log')
            ->assertSeeText('Compaqi (Mérida)')
            ->assertSeeText('Compaqi (Auditoría)');
    }

    public function test_rows_can_remove_a_profile_or_a_server_directly(): void
    {
        $admin = $this->userWithRole('Administrador', 'admin-row-delete@datamid.test');

        Livewire::actingAs($admin)->test(ServerBackupReports::class)
            ->assertSet('totalProfiles', 15)
            ->call('removeProfile', 0, 0)
            ->assertSet('totalProfiles', 14)
            ->call('removeServer', 0)
            ->assertCount('servers', 4);
    }

    public function test_a_server_can_be_added_from_the_servers_header(): void
    {
        $admin = $this->userWithRole('Administrador', 'admin-add-server@datamid.test');

        Livewire::actingAs($admin)->test(ServerBackupReports::class)
            ->call('addServer')
            ->assertSet('showServerEditor', true)
            ->assertSet('addingServer', true)
            ->set('serverForm', ['name' => 'Nuevo', 'ip' => '192.168.2.250', 'share' => '\\NUEVO\log'])
            ->call('saveServer')
            ->assertCount('servers', 6)
            ->assertSet('servers.0.name', 'Nuevo')
            ->assertSet('servers.0.profiles', [])
            ->assertSet('showServerEditor', false)
            ->assertSet('addingServer', false);
    }

    public function test_saving_without_opening_the_add_form_does_not_create_a_server(): void
    {
        $admin = $this->userWithRole('Administrador', 'admin-no-create-server@datamid.test');

        Livewire::actingAs($admin)->test(ServerBackupReports::class)
            ->set('serverForm', ['name' => 'Nuevo', 'ip' => '192.168.2.250', 'share' => '\\\\NUEVO\\log'])
            ->call('saveServer')
            ->assertCount('servers', 5)
            ->assertSet('showServerEditor', false);
    }

    public function test_incremental_reading_advances_without_restarting_the_scan(): void
    {
        $admin = $this->userWithRole('Administrador', 'admin-progress-backups@datamid.test');
        $reader = \Mockery::mock(BackupLogReaderService::class);
        $reader->shouldReceive('read')->once()->andReturn([
            'available' => true,
            'done' => true,
            'date' => '01/10/2026 08:00 a. m.',
            'errors' => [],
            'failedFiles' => [],
            'issues' => [],
            'failed' => false,
            'message' => '',
            'processed' => '10/10',
            'source' => 'C:\\Datos',
            'destination' => 'D:\\Respaldos',
            'duration' => '00:00:10',
        ]);
        $this->app->instance(BackupLogReaderService::class, $reader);

        Livewire::actingAs($admin)->test(ServerBackupReports::class)
            ->call('startReading')
            ->assertSet('initialScanStarted', true)
            ->call('readNext')
            ->assertSet('reading', true)
            ->assertSet('processedProfiles', 1)
            ->assertCount('pendingProfiles', 14);
    }

    public function test_completed_scan_never_starts_another_reading_by_itself(): void
    {
        $admin = $this->userWithRole('Administrador', 'admin-scan-guard@datamid.test');
        $reader = \Mockery::mock(BackupLogReaderService::class);
        $reader->shouldReceive('read')->once()->andReturn([
            'available' => true, 'done' => true, 'date' => '02/10/2026 08:00 a. m.',
            'errors' => [], 'failedFiles' => [], 'issues' => [], 'failed' => false,
            'message' => '', 'processed' => '10/10', 'source' => 'C:\\Datos',
            'destination' => 'D:\\Respaldos', 'duration' => '00:00:10',
        ]);
        $this->app->instance(BackupLogReaderService::class, $reader);

        Livewire::actingAs($admin)->test(ServerBackupReports::class)
            ->call('startReading')
            ->set('pendingProfiles', [['server' => 0, 'profile' => 0]])
            ->set('totalProfiles', 1)
            ->call('readNext')
            ->assertSet('reading', false)
            ->assertSet('processedProfiles', 1)
            ->assertSet('initialScanStarted', true)
            ->assertSet('scanAuthorized', false)
            ->assertDontSee('autoScanStarted', false)
            ->assertDontSee('$wire.startReading()', false);
    }

    public function test_last_saved_report_is_shown_as_finished_and_only_refreshes_when_requested(): void
    {
        $admin = $this->userWithRole('Administrador', 'admin-previous-report@datamid.test');
        $profile = config('backup-reports.servers.0.profiles.0');
        Cache::forever('backup-reports.last-snapshot', [
            'generatedAt' => '2026-10-24T16:40:00-06:00',
            'servers' => [[
                'key' => 'server-0',
                'profiles' => [[
                    'name' => $profile['name'], 'log' => $profile['log'], 'available' => true, 'done' => true,
                    'date' => '24/10/2026 04:40 p. m.', 'errors' => [], 'failedFiles' => [], 'issues' => [],
                    'failed' => false, 'message' => '', 'processed' => '158/176', 'source' => '', 'destination' => '',
                    'duration' => '', 'readBytes' => 0, 'totalBytes' => 0, 'readSeconds' => 0,
                ]],
            ]],
        ]);

        Livewire::actingAs($admin)->test(ServerBackupReports::class)
            ->assertSet('reading', false)
            ->assertSet('servers.0.profiles.1.available', true)
            ->assertSet('servers.0.profiles.1.processed', '158/176')
            ->assertSet('servers.0.profiles.1.done', true)
            ->assertSet('processedProfiles', 1)
            ->assertSeeText('158/176')
            ->assertSeeText('Última lectura guardada: 24/10/2026')
            ->call('startReading')
            ->assertSet('servers.0.profiles.1.processed', '158/176')
            ->assertSet('servers.0.profiles.1.done', false)
            ->assertSet('processedProfiles', 0)
            ->assertSet('reading', true);
    }

    public function test_without_a_saved_report_nothing_is_read_when_the_page_opens(): void
    {
        $admin = $this->userWithRole('Administrador', 'admin-no-auto-read@datamid.test');

        Livewire::actingAs($admin)->test(ServerBackupReports::class)
            ->assertSet('reading', false)
            ->assertSet('processedProfiles', 0)
            ->assertSet('totalProfiles', 15)
            ->assertSeeText('Listo para consultar los registros.')
            ->assertDontSee('$wire.startReading()', false);
    }

    public function test_progress_bar_has_one_dot_per_server_and_fills_smoothly_for_at_least_five_seconds_each(): void
    {
        $admin = $this->userWithRole('Administrador', 'admin-progress-dots@datamid.test');
        $servers = array_values(array_filter(config('backup-reports.servers'), fn (array $server): bool => count($server['profiles']) > 0));
        // El informe lista los servidores de la cadena más corta a la más larga (a igual largo, alfabético)
        usort($servers, fn (array $a, array $b): int => mb_strlen($a['name']) <=> mb_strlen($b['name']) ?: strnatcmp(Str::lower(Str::ascii($a['name'])), Str::lower(Str::ascii($b['name']))));

        $component = Livewire::actingAs($admin)->test(ServerBackupReports::class);

        $cumulative = 0;
        foreach ($servers as $index => $server) {
            $cumulative += count($server['profiles']);
            $component
                ->assertSeeHtml('data-cum="'.$cumulative.'"')
                ->assertSeeHtml('data-n="'.($index + 1).'"')
                ->assertSeeHtml('data-of="'.count($servers).'"')
                ->assertSeeHtml('data-count="'.count($server['profiles']).'"')
                ->assertSeeHtml('data-name="'.e($server['name']).'"');
        }

        $component
            ->assertSeeHtml('data-backup-stages')
            ->assertSeeHtml('data-backup-stage')
            ->assertSeeHtml('stageToken(')
            ->assertDontSeeHtml('data-backup-milestone-tip')
            ->assertSeeHtml('segmentMs: 5000')
            ->assertSeeHtml('barWidth()')
            ->assertSeeHtml('scheduleRead()')
            ->assertDontSee('setTimeout(() => $wire.readNext(), 300)', false);
    }

    public function test_finished_scan_saves_the_last_report_for_the_next_visit(): void
    {
        $admin = $this->userWithRole('Administrador', 'admin-save-last-report@datamid.test');
        $reader = \Mockery::mock(BackupLogReaderService::class);
        $reader->shouldReceive('read')->once()->andReturn([
            'available' => true, 'done' => true, 'date' => '03/10/2026 08:00 a. m.',
            'errors' => [], 'failedFiles' => [], 'issues' => [], 'failed' => false,
            'message' => '', 'processed' => '10/10', 'source' => 'C:\\Datos',
            'destination' => 'D:\\Respaldos', 'duration' => '00:00:10',
        ]);
        $this->app->instance(BackupLogReaderService::class, $reader);

        Livewire::actingAs($admin)->test(ServerBackupReports::class)
            ->call('startReading')
            ->set('pendingProfiles', [['server' => 0, 'profile' => 0]])
            ->set('totalProfiles', 1)
            ->call('readNext')
            ->assertSet('reading', false);

        $snapshot = Cache::get('backup-reports.last-snapshot');
        $this->assertIsArray($snapshot);
        $this->assertSame('10/10', $snapshot['servers'][0]['profiles'][0]['processed']);
    }

    public function test_log_reader_extracts_latest_execution_and_failed_company(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'backup-log-').'.log.html';
        file_put_contents($path, <<<'HTML'
<!doctype html><html><body>
<a href="#cs_100">Ir al más reciente</a>
<h2 id="cs_99">14/09/2026 08:00 a. m.</h2><p>Anterior</p>
<h2 id="cs_100">15/09/2026 08:00 a. m.</h2>
<p>Create Synchronicity v6.0.0.0<br>Izquierda: C:\Compac\<br>Derecha: D:\Contpaq\<br>Hecho: 8/9<br>Tiempo Transcurrido: 00:02:10</p>
<table>
<tr><td>Falló</td><td>a</td><td>b</td><td>c</td><td>\ctBERTA_LORET_DE_MOLA_VADILLO.mdf</td></tr>
<tr><td>Falló</td><td>a</td><td>b</td><td>c</td><td>\ctBERTA_LORET_DE_MOLA_VADILLO_log.ldf</td></tr>
</table>
</body></html>
HTML);

        try {
            $result = app(BackupLogReaderService::class)->read($path, 'Compac');
            $this->assertTrue($result['available']);
            $this->assertSame('15/09/2026 08:00 a. m.', $result['date']);
            $this->assertSame('8/9', $result['processed']);
            $this->assertSame(['BERTA_LORET_DE_MOLA_VADILLO'], $result['errors']);
            $this->assertSame([
                ['path' => '\\ctBERTA_LORET_DE_MOLA_VADILLO.mdf', 'company' => 'BERTA_LORET_DE_MOLA_VADILLO'],
                ['path' => '\\ctBERTA_LORET_DE_MOLA_VADILLO_log.ldf', 'company' => 'BERTA_LORET_DE_MOLA_VADILLO'],
            ], $result['failedFiles']);
            $this->assertSame('D:\\Contpaq\\', $result['destination']);
        } finally {
            @unlink($path);
        }
    }

    public function test_log_reader_handles_a_very_large_latest_execution(): void
    {
        // Un log real de más de 1 MB en la última ejecución (miles de filas) agotaba el límite de retroceso de PCRE
        // y se mostraba como «La última ejecución no está completa en el log.»
        $rows = str_repeat("<tr><td>Error</td><td>a</td><td>b</td><td>C:\DATA\tempdb.mdf</td><td>C:\DATA\tempdb.mdf</td><td>El proceso no puede obtener acceso al archivo porque está siendo utilizado en otro proceso, y este texto se repite para que el bloque pese más de un megabyte.</td></tr>\n", 5000);
        $path = tempnam(sys_get_temp_dir(), 'backup-log-').'.log.html';
        file_put_contents($path, '<!doctype html><html><body><a href="#cs_200">Ir al más reciente</a>'
            .'<h2 id="cs_199">01/11/2026 08:00 a. m.</h2><p>Anterior</p>'
            .'<h2 id="cs_200">02/11/2026 08:00 a. m.</h2><p>Hecho: 1586/2972<br>Tiempo Transcurrido: 00:10:00</p><table>'.$rows.'</table></body></html>');

        try {
            $this->assertGreaterThan(1_000_000, filesize($path));
            $result = app(BackupLogReaderService::class)->read($path, 'base_contabilidad');
            $this->assertTrue($result['available']);
            $this->assertSame('02/11/2026 08:00 a. m.', $result['date']);
            $this->assertSame('1586/2972', $result['processed']);
            $this->assertTrue($result['failed']);
        } finally {
            @unlink($path);
        }
    }

    public function test_backup_management_and_document_links_are_grouped_in_sidebar_order(): void
    {
        $admin = $this->userWithRole('Administrador', 'admin-menu@datamid.test');
        $html = $this->actingAs($admin)->get(route('dashboard'))->assertOk()->getContent();

        $activitiesPosition = strpos($html, '>Actividades<');
        $managementPosition = strpos($html, '>Gestión de Respaldos<');
        $documentsPosition = strpos($html, '>Documentos<');
        $deliveryPosition = strpos($html, '>Hoja de Entrega<');
        $backupPosition = strpos($html, '>Reporte de Respaldos<');
        $technicalPosition = strpos($html, '>Reportes de Soporte<');
        $supportPosition = strpos($html, '>Soporte<');
        $this->assertNotFalse($activitiesPosition);
        $this->assertNotFalse($managementPosition);
        $this->assertNotFalse($documentsPosition);
        $this->assertNotFalse($deliveryPosition);
        $this->assertNotFalse($backupPosition);
        $this->assertNotFalse($technicalPosition);
        $this->assertNotFalse($supportPosition);
        $this->assertGreaterThan($activitiesPosition, $managementPosition);
        $this->assertGreaterThan($managementPosition, $documentsPosition);
        $this->assertGreaterThan($documentsPosition, $deliveryPosition);
        $this->assertGreaterThan($deliveryPosition, $technicalPosition);
        $this->assertGreaterThan($technicalPosition, $backupPosition);
        $this->assertGreaterThan($backupPosition, $supportPosition);
    }

    public function test_pdf_service_generates_a_valid_pdf(): void
    {
        $admin = $this->userWithRole('Administrador', 'admin-pdf-backups@datamid.test');
        $component = Livewire::actingAs($admin)->test(ServerBackupReports::class);
        $servers = $component->get('servers');
        $servers[0]['profiles'][0] = array_merge($servers[0]['profiles'][0], [
            'available' => true,
            'done' => true,
            'date' => '15/09/2026 08:00 a. m.',
            'processed' => '10/10',
        ]);

        $pdf = app(BackupReportPdfService::class)->generate(
            $servers,
            now(),
            'Reporte_de_Respaldos.pdf',
            'Soporte DataMID',
        );

        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertGreaterThan(5000, strlen($pdf));
    }

    public function test_pdf_cannot_be_downloaded_before_the_review_finishes(): void
    {
        $admin = $this->userWithRole('Administrador', 'admin-pdf-pending@datamid.test');

        Livewire::actingAs($admin)->test(ServerBackupReports::class)
            ->assertSeeHtml('disabled')
            ->call('downloadPdf')
            ->assertSet('statusMessage', 'Espera a que termine la revisión antes de guardar el PDF.');
    }

    public function test_administrator_can_download_the_section_guide_as_pdf(): void
    {
        $admin = $this->userWithRole('Administrador', 'admin-guide-pdf@datamid.test');

        Livewire::actingAs($admin)->test(ServerBackupReports::class)
            ->assertSeeText('Guía PDF')
            ->call('downloadGuide')
            ->assertFileDownloaded();
    }

    public function test_administrator_can_email_the_pdf_to_the_configured_recipients(): void
    {
        Mail::fake();
        config()->set('mail.mailers.backup_reports.password', 'smtp-test-password');
        $admin = $this->userWithRole('Administrador', 'admin-email-backups@datamid.test');

        Livewire::actingAs($admin)->test(ServerBackupReports::class)
            ->set('processedProfiles', 15)
            ->call('sendEmail')
            ->assertSet('emailSent', true);

        Mail::assertSent(BackupReportMail::class, function (BackupReportMail $mail): bool {
            return $mail->hasTo('dionicio.farfan@datamid.com.mx')
                && $mail->hasTo('soporte@datamid.com.mx')
                && $mail->hasTo('becarios@datamid.com.mx')
                && count($mail->attachments()) === 1;
        });
    }

    public function test_email_action_does_not_render_the_component_again(): void
    {
        $method = new ReflectionMethod(ServerBackupReports::class, 'sendEmail');

        $this->assertCount(1, $method->getAttributes(Renderless::class));
    }

    public function test_administrator_can_send_the_report_only_to_their_own_email(): void
    {
        Mail::fake();
        config()->set('mail.mailers.backup_reports.password', 'smtp-test-password');
        $admin = $this->userWithRole('Administrador', 'admin-email-self@datamid.test');

        Livewire::actingAs($admin)->test(ServerBackupReports::class)
            ->assertSeeText('Enviar reporte por correo')
            ->assertSeeText('Enviarme una copia')
            ->assertSeeText('Enviar reporte')
            ->set('emailRecipients', ['emiliano.ortiz@datamid.com.mx'])
            ->set('processedProfiles', 15)
            ->call('sendEmail')
            ->assertSet('emailSent', true);

        Mail::assertSent(BackupReportMail::class, function (BackupReportMail $mail): bool {
            return $mail->hasTo('emiliano.ortiz@datamid.com.mx')
                && ! $mail->hasTo('dionicio.farfan@datamid.com.mx')
                && ! $mail->hasTo('soporte@datamid.com.mx')
                && ! $mail->hasTo('becarios@datamid.com.mx');
        });
    }

    public function test_email_stays_disabled_until_every_profile_has_been_reviewed(): void
    {
        Mail::fake();
        config()->set('mail.mailers.backup_reports.password', 'smtp-test-password');
        $admin = $this->userWithRole('Administrador', 'admin-email-pending@datamid.test');

        Livewire::actingAs($admin)->test(ServerBackupReports::class)
            ->assertSeeHtml('disabled')
            ->call('sendEmail')
            ->assertSet('emailSent', false)
            ->assertSet('emailStatus', 'Primero debes revisar todos los servidores antes de enviar el reporte.');

        Mail::assertNothingSent();
    }

    public function test_email_button_reports_when_the_smtp_password_is_missing(): void
    {
        Mail::fake();
        config()->set('mail.mailers.backup_reports.password', null);
        $admin = $this->userWithRole('Administrador', 'admin-email-config@datamid.test');

        Livewire::actingAs($admin)->test(ServerBackupReports::class)
            ->set('processedProfiles', 15)
            ->call('sendEmail')
            ->assertSet('emailSent', false)
            ->assertSet('emailStatus', 'Falta configurar BACKUP_MAIL_PASSWORD en el archivo .env del servidor.');

        Mail::assertNothingSent();
    }

    private function userWithRole(string $roleName, string $email): User
    {
        $role = Role::query()->firstOrCreate(['role' => $roleName]);
        $user = User::query()->create([
            'name' => str($roleName)->before(' ')->value(),
            'last_name' => 'Prueba',
            'email' => $email,
            'password' => Hash::make('secret'),
            'role_id' => $role->id,
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();

        return $user;
    }
}
