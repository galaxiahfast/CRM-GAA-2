<?php

namespace App\Services\Backups;

use App\Models\BackupUpload;
use Illuminate\Support\Facades\Storage;

class BackupStoragePurger
{
    public function purge(BackupUpload $upload): void
    {
        $disk = Storage::disk((string) config('backup-storage.disk', 'local'));
        $paths = collect($upload->manifest ?? [])
            ->pluck('path')
            ->filter(fn ($path) => is_string($path) && str_starts_with($path, 'backups/'.$upload->site.'/'));

        if (filled($upload->storage_path) && str_starts_with($upload->storage_path, 'backup-archives/'.$upload->site.'/')) {
            $paths->push($upload->storage_path);
        }

        $paths->unique()->each(fn (string $path) => $disk->delete($path));
        $disk->deleteDirectory('backup-chunks/'.$upload->id);
        $disk->delete('backup-assembly/'.$upload->id.'.part');
        $upload->delete();
    }
}
