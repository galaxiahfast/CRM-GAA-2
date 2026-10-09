<?php

namespace Tests\Feature;

use App\Jobs\AssembleBackupUpload;
use App\Models\BackupUpload;
use App\Models\Role;
use App\Models\User;
use App\Services\Backups\BackupArchiveExtractor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BackupStorageManagerTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_administrators_and_accountants_can_open_the_backup_manager(): void
    {
        $admin = $this->user('Administrador', 'admin-storage@datamid.test');
        $accountant = $this->user('Contador', 'contador-storage@datamid.test');
        $auxiliary = $this->user('Auxiliar', 'aux-storage@datamid.test');

        $this->actingAs($admin)->get(route('activity-backups.index'))
            ->assertOk()
            ->assertSee('Respaldos Mérida')
            ->assertSee('Respaldos Tulum')
            ->assertSee('Historial de registros')
            ->assertSee('Seleccionar ZIP')
            ->assertDontSee('Selecciona un cliente')
            ->assertSee('data-backup-site', false);
        $this->assertTrue(Gate::forUser($accountant)->allows('manage-system-backups'));
        $this->assertFalse(Gate::forUser($auxiliary)->allows('manage-system-backups'));
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
        Queue::assertPushed(AssembleBackupUpload::class, fn (AssembleBackupUpload $job) => $job->uploadId === $upload->id);

        (new AssembleBackupUpload($upload->id))->handle(app(BackupArchiveExtractor::class));
        $upload->refresh();
        $this->assertSame(BackupUpload::STATUS_COMPLETED, $upload->status);
        $this->assertSame(hash('sha256', $payload), $upload->checksum);
        $this->assertSame($payload, Storage::disk('local')->get($upload->storage_path));
        $this->assertCount(2, $upload->manifest);
        $this->assertSame('Cliente Contable', $upload->manifest[0]['customer']);
        $this->assertSame('index', $upload->manifest[0]['category']);
        $this->assertSame($indexContents, Storage::disk('local')->get($upload->manifest[0]['path']));

        $this->get(route('activity-backups.download', $upload))
            ->assertOk()
            ->assertDownload('empresa_2026.zip');
        $this->get(route('activity-backups.files.download', [$upload, 1]))
            ->assertOk()
            ->assertDownload('empresa_2026.bak');
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
