<?php

namespace Tests\Feature;

use App\Livewire\Support\ServerBackupReports;
use App\Mail\BackupReportMail;
use App\Models\Role;
use App\Models\User;
use App\Services\Support\BackupLogReaderService;
use App\Services\Support\WeeklyBackupReportService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class WeeklyBackupReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget(WeeklyBackupReportService::LAST_RUN_KEY);
        Cache::forget(WeeklyBackupReportService::SNAPSHOT_KEY);
        config()->set('backup-reports.weekly', [
            'enabled' => true,
            'day' => 1,
            'time' => '09:00',
            'recipients' => ['soporte@datamid.com.mx', 'emiliano.ortiz@datamid.com.mx'],
        ]);
    }

    public function test_weekly_report_is_scheduled_every_monday_at_nine_in_mexico_city(): void
    {
        $this->app->make(Kernel::class)->bootstrap();

        $event = collect(app(Schedule::class)->events())
            ->first(fn ($event): bool => str_contains((string) $event->command, 'backup-reports:send-weekly'));

        $this->assertNotNull($event, 'El envío semanal del reporte de respaldos no está programado.');
        $this->assertSame('0 9 * * 1', $event->expression);
        $this->assertSame(config('support.timezone', 'America/Mexico_City'), $event->timezone);
    }

    public function test_weekly_command_sends_the_pdf_only_to_support_and_emiliano(): void
    {
        Mail::fake();
        config()->set('mail.mailers.backup_reports.password', 'smtp-test-password');
        // Los destinatarios del envío manual no deben mezclarse con los del lunes.
        config()->set('backup-reports.email.recipients', [
            'dionicio.farfan@datamid.com.mx', 'soporte@datamid.com.mx', 'becarios@datamid.com.mx',
        ]);
        $this->mockReader(fn (MockInterface $reader) => $reader->shouldReceive('read')->times(15)->andReturn($this->logResult()));

        $this->artisan('backup-reports:send-weekly')->assertExitCode(0);

        Mail::assertSent(BackupReportMail::class, 1);
        Mail::assertSent(BackupReportMail::class, function (BackupReportMail $mail): bool {
            return $mail->hasTo('soporte@datamid.com.mx')
                && $mail->hasTo('emiliano.ortiz@datamid.com.mx')
                && ! $mail->hasTo('dionicio.farfan@datamid.com.mx')
                && ! $mail->hasTo('becarios@datamid.com.mx')
                && count($mail->to) === 2
                && count($mail->attachments()) === 1;
        });

        $lastRun = Cache::get(WeeklyBackupReportService::LAST_RUN_KEY);
        $this->assertTrue($lastRun['sent']);
        $this->assertSame(15, $lastRun['profiles']);
        $this->assertSame(0, $lastRun['errors']);
        $this->assertSame('10/10', Cache::get(WeeklyBackupReportService::SNAPSHOT_KEY)['servers'][0]['profiles'][0]['processed']);
    }

    public function test_weekly_command_still_sends_when_a_log_cannot_be_read(): void
    {
        Mail::fake();
        config()->set('mail.mailers.backup_reports.password', 'smtp-test-password');
        $this->mockReader(fn (MockInterface $reader) => $reader->shouldReceive('read')->times(15)->andThrow(new RuntimeException('Log no disponible o sin permiso.')));

        $this->artisan('backup-reports:send-weekly')->assertExitCode(0);

        Mail::assertSent(BackupReportMail::class, fn (BackupReportMail $mail): bool => $mail->hasTo('soporte@datamid.com.mx'));
        $profile = Cache::get(WeeklyBackupReportService::SNAPSHOT_KEY)['servers'][0]['profiles'][0];
        $this->assertFalse($profile['available']);
        $this->assertTrue($profile['done']);
        $this->assertSame('Log no disponible o sin permiso.', $profile['message']);
    }

    public function test_weekly_command_counts_profiles_with_errors(): void
    {
        Mail::fake();
        config()->set('mail.mailers.backup_reports.password', 'smtp-test-password');
        $this->mockReader(fn (MockInterface $reader) => $reader->shouldReceive('read')->times(15)->andReturn(
            $this->logResult(['failed' => true, 'errors' => ['EMPRESA_UNO']]),
        ));

        $this->artisan('backup-reports:send-weekly')->assertExitCode(0);

        $this->assertSame(15, Cache::get(WeeklyBackupReportService::LAST_RUN_KEY)['errors']);
    }

    public function test_weekly_command_fails_without_reading_when_the_smtp_password_is_missing(): void
    {
        Mail::fake();
        config()->set('mail.mailers.backup_reports.password', null);
        $this->mockReader(fn (MockInterface $reader) => $reader->shouldNotReceive('read'));

        $this->artisan('backup-reports:send-weekly')->assertExitCode(1);

        Mail::assertNothingSent();
        $lastRun = Cache::get(WeeklyBackupReportService::LAST_RUN_KEY);
        $this->assertFalse($lastRun['sent']);
        $this->assertSame('Falta configurar BACKUP_MAIL_PASSWORD en el archivo .env del servidor.', $lastRun['status']);
    }

    public function test_weekly_command_fails_when_there_are_no_valid_recipients(): void
    {
        Mail::fake();
        config()->set('mail.mailers.backup_reports.password', 'smtp-test-password');
        config()->set('backup-reports.weekly.recipients', ['no-es-un-correo']);
        $this->mockReader(fn (MockInterface $reader) => $reader->shouldNotReceive('read'));

        $this->artisan('backup-reports:send-weekly')->assertExitCode(1);

        Mail::assertNothingSent();
        $this->assertSame(
            'No hay destinatarios válidos configurados para el envío semanal.',
            Cache::get(WeeklyBackupReportService::LAST_RUN_KEY)['status'],
        );
    }

    public function test_dry_run_reads_the_logs_and_builds_the_pdf_without_sending_anything(): void
    {
        Mail::fake();
        config()->set('mail.mailers.backup_reports.password', null);
        $this->mockReader(fn (MockInterface $reader) => $reader->shouldReceive('read')->times(15)->andReturn($this->logResult()));

        $this->artisan('backup-reports:send-weekly', ['--dry-run' => true])->assertExitCode(0);

        Mail::assertNothingSent();
        $this->assertNull(Cache::get(WeeklyBackupReportService::LAST_RUN_KEY));
    }

    public function test_weekly_recipients_are_validated_and_deduplicated(): void
    {
        config()->set('backup-reports.weekly.recipients', [
            ' soporte@datamid.com.mx ', 'soporte@datamid.com.mx', 'no-es-correo', 'emiliano.ortiz@datamid.com.mx',
        ]);

        $this->assertSame(
            ['soporte@datamid.com.mx', 'emiliano.ortiz@datamid.com.mx'],
            app(WeeklyBackupReportService::class)->recipients(),
        );
    }

    public function test_report_page_shows_the_weekly_schedule_and_keeps_the_manual_send(): void
    {
        $admin = $this->userWithRole('Administrador', 'admin-weekly-page@datamid.test');

        Livewire::actingAs($admin)->test(ServerBackupReports::class)
            ->assertSeeText('Envío automático')
            ->assertSeeText('Cada lunes a las 9:00')
            ->assertSeeText('Destinatarios del lunes')
            ->assertSeeText('soporte@datamid.com.mx')
            ->assertSeeText('emiliano.ortiz@datamid.com.mx')
            ->assertSeeText('Sin ejecutarse')
            ->assertSeeText('Activo')
            ->assertSeeText('Enviar reporte por correo')
            ->assertSeeText('Guardar PDF');
    }

    public function test_report_page_shows_the_result_of_the_last_automatic_send(): void
    {
        $admin = $this->userWithRole('Administrador', 'admin-weekly-last@datamid.test');
        Cache::forever(WeeklyBackupReportService::LAST_RUN_KEY, [
            'at' => '2026-11-02T09:01:12-06:00', 'ok' => true, 'sent' => true,
            'status' => 'Reporte enviado correctamente.', 'recipients' => [], 'profiles' => 15, 'errors' => 0,
        ]);

        Livewire::actingAs($admin)->test(ServerBackupReports::class)
            ->assertSeeText('Enviado · 02/11/2026 09:01')
            ->assertSeeText('Último envío automático')
            ->assertDontSeeText('Sin ejecutarse');
    }

    public function test_report_page_explains_why_the_last_automatic_send_failed(): void
    {
        $admin = $this->userWithRole('Administrador', 'admin-weekly-failed@datamid.test');
        Cache::forever(WeeklyBackupReportService::LAST_RUN_KEY, [
            'at' => '2026-11-02T09:00:03-06:00', 'ok' => false, 'sent' => false,
            'status' => 'Falta configurar BACKUP_MAIL_PASSWORD en el archivo .env del servidor.',
            'recipients' => [], 'profiles' => 0, 'errors' => 0,
        ]);

        Livewire::actingAs($admin)->test(ServerBackupReports::class)
            ->assertSeeText('No se envió · 02/11/2026 09:00')
            ->assertSeeText('Falta configurar BACKUP_MAIL_PASSWORD');
    }

    public function test_report_page_marks_the_weekly_send_as_disabled_when_it_is_turned_off(): void
    {
        config()->set('backup-reports.weekly.enabled', false);
        $admin = $this->userWithRole('Administrador', 'admin-weekly-off@datamid.test');

        Livewire::actingAs($admin)->test(ServerBackupReports::class)
            ->assertSeeText('Desactivado')
            ->assertDontSeeText('Activo');
    }

    private function mockReader(callable $configure): void
    {
        $reader = \Mockery::mock(BackupLogReaderService::class);
        $configure($reader);
        $this->app->instance(BackupLogReaderService::class, $reader);
    }

    /** @return array<string, mixed> */
    private function logResult(array $overrides = []): array
    {
        return array_merge([
            'available' => true,
            'done' => true,
            'date' => '02/11/2026 08:00 a. m.',
            'errors' => [],
            'failedFiles' => [],
            'issues' => [],
            'failed' => false,
            'message' => '',
            'processed' => '10/10',
            'source' => 'C:\\Datos',
            'destination' => 'D:\\Respaldos',
            'duration' => '00:00:10',
        ], $overrides);
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
