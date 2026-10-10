<?php

namespace Database\Seeders;

use App\Models\BackupUpload;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PharData;

class BackupDemoSeeder extends Seeder
{
    public function run(): void
    {
        $disk = Storage::disk((string) config('backup-storage.disk', 'local'));
        $userId = User::query()->with('role')->get()->first(fn (User $user) => $user->isAdmin())?->id;

        foreach (config('backup-storage.demo_companies', []) as $position => $company) {
            $slug = Str::slug($company);
            $date = now()->subMinutes(($position + 1) * 7);
            $upload = BackupUpload::query()->firstOrCreate([
                'upload_key' => hash('sha256', 'visual-demo-merida-'.$slug),
            ], [
                'site' => 'merida',
                'user_id' => $userId,
                'original_name' => $slug.'_respaldo_demo.zip',
                'extension' => 'zip',
                'mime_type' => 'application/zip',
                'size' => 1,
                'chunk_size' => 1024,
                'total_chunks' => 1,
                'uploaded_chunks' => [0],
                'received_bytes' => 1,
                'status' => BackupUpload::STATUS_COMPLETED,
                'assigned_customer' => $company,
                'notes' => 'Registro de demostración para pruebas visuales del módulo.',
                'last_activity_at' => $date,
                'completed_at' => $date,
            ]);

            $indexName = $slug.'_20261009.index';
            $bakName = $slug.'_20261009.bak';
            $indexContents = "Índice de demostración para {$company}\n";
            $bakContents = "Respaldo de demostración para {$company}\n";
            $temporaryBase = tempnam(sys_get_temp_dir(), 'backup-demo-');
            if ($temporaryBase === false) {
                continue;
            }
            @unlink($temporaryBase);
            $temporaryZip = $temporaryBase.'.zip';
            $archive = new PharData($temporaryZip);
            $archive->addFromString("{$company}/Index/{$indexName}", $indexContents);
            $archive->addFromString("{$company}/Bak/{$bakName}", $bakContents);
            unset($archive);
            $zipContents = file_get_contents($temporaryZip) ?: '';
            @unlink($temporaryZip);

            $archivePath = "backup-archives/merida/{$upload->id}_{$slug}_demo.zip";
            $indexPath = "backups/merida/{$slug}/Index/{$upload->id}_0_{$indexName}";
            $bakPath = "backups/merida/{$slug}/Bak/{$upload->id}_1_{$bakName}";
            $disk->put($archivePath, $zipContents);
            $disk->put($indexPath, $indexContents);
            $disk->put($bakPath, $bakContents);

            $upload->forceFill([
                'size' => strlen($zipContents),
                'received_bytes' => strlen($zipContents),
                'storage_path' => $archivePath,
                'checksum' => hash('sha256', $zipContents),
                'manifest' => [
                    ['customer' => $company, 'category' => 'index', 'name' => $indexName, 'path' => $indexPath, 'size' => strlen($indexContents)],
                    ['customer' => $company, 'category' => 'bak', 'name' => $bakName, 'path' => $bakPath, 'size' => strlen($bakContents)],
                ],
                'last_activity_at' => $date,
                'completed_at' => $date,
                'superseded_at' => null,
            ])->save();
        }
    }
}
