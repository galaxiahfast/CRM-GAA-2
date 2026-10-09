<?php

namespace Tests\Feature;

use App\Jobs\AssembleBackupUpload;
use App\Models\BackupUpload;
use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
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

    private function customer(User $creator): Customer
    {
        return Customer::query()->create([
            'name' => 'Cliente Contable',
            'rfc' => 'XAXX010101000',
            'created_by' => $creator->id,
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
        $customer = $this->customer($admin);
        $payload = str_repeat('A', 1024).str_repeat('B', 476);

        $response = $this->actingAs($admin)->postJson(route('activity-backups.uploads.initialize'), [
            'site' => 'merida', 'customer_id' => $customer->id, 'category' => 'bak',
            'file_name' => 'empresa_2026.bak', 'mime_type' => 'application/octet-stream',
            'size' => strlen($payload), 'fingerprint' => 'empresa:1500:12345',
        ])->assertOk();

        $uploadId = $response->json('id');
        $upload = BackupUpload::query()->findOrFail($uploadId);
        $this->assertSame(2, $upload->total_chunks);

        $this->postRaw(route('activity-backups.uploads.chunks.store', [$upload, 0]), substr($payload, 0, 1024))->assertOk();
        $status = $this->getJson(route('activity-backups.uploads.status', $upload))->assertOk();
        $this->assertSame([0], $status->json('uploadedChunks'));

        // Reenviar un fragmento ya confirmado no duplica bytes ni progreso.
        $this->postRaw(route('activity-backups.uploads.chunks.store', [$upload, 0]), substr($payload, 0, 1024))->assertOk();
        $this->postRaw(route('activity-backups.uploads.chunks.store', [$upload, 1]), substr($payload, 1024))->assertOk();
        $this->postJson(route('activity-backups.uploads.complete', $upload))->assertAccepted();
        $this->postJson(route('activity-backups.uploads.complete', $upload))->assertAccepted();
        Queue::assertPushed(AssembleBackupUpload::class, 1);
        Queue::assertPushed(AssembleBackupUpload::class, fn (AssembleBackupUpload $job) => $job->uploadId === $upload->id);

        (new AssembleBackupUpload($upload->id))->handle();
        $upload->refresh();
        $this->assertSame(BackupUpload::STATUS_COMPLETED, $upload->status);
        $this->assertSame(hash('sha256', $payload), $upload->checksum);
        $this->assertSame($payload, Storage::disk('local')->get($upload->storage_path));

        $this->get(route('activity-backups.download', $upload))
            ->assertOk()
            ->assertDownload('empresa_2026.bak');
    }

    public function test_secondary_upload_waits_when_the_concurrency_limit_is_reached(): void
    {
        Storage::fake('local');
        config()->set('backup-storage.max_active_uploads', 1);
        $admin = $this->user('Administrador', 'admin-queue@datamid.test');
        $customer = $this->customer($admin);
        $base = ['site' => 'tulum', 'customer_id' => $customer->id, 'category' => 'index', 'mime_type' => 'application/octet-stream', 'size' => 100];

        $this->actingAs($admin)->postJson(route('activity-backups.uploads.initialize'), $base + ['file_name' => 'primero.index', 'fingerprint' => 'first'])->assertJsonPath('status', 'uploading');
        $this->postJson(route('activity-backups.uploads.initialize'), $base + ['file_name' => 'segundo.index', 'fingerprint' => 'second'])->assertJsonPath('status', 'waiting');
    }

    private function postRaw(string $uri, string $content)
    {
        return $this->call('POST', $uri, [], [], [], [
            'CONTENT_TYPE' => 'application/octet-stream',
            'HTTP_ACCEPT' => 'application/json',
        ], $content);
    }
}
