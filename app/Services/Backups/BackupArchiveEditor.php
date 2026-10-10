<?php

namespace App\Services\Backups;

use App\Models\BackupUpload;
use Illuminate\Support\Facades\Storage;
use PharData;
use RuntimeException;
use ZipArchive;

class BackupArchiveEditor
{
    public function deleteFile(BackupUpload $upload, int $fileIndex): void
    {
        if ($upload->status !== BackupUpload::STATUS_COMPLETED || blank($upload->storage_path)) {
            throw new RuntimeException('El respaldo todavía no está disponible para edición.');
        }

        $manifest = collect($upload->manifest ?? [])->values();
        $target = $manifest->get($fileIndex);
        if (! is_array($target) || blank($target['path'] ?? null)) {
            throw new RuntimeException('El archivo seleccionado ya no existe en el respaldo.');
        }

        $disk = Storage::disk((string) config('backup-storage.disk', 'local'));
        $remaining = $manifest->except($fileIndex)->values();
        $temporary = tempnam(sys_get_temp_dir(), 'backup-edit-');
        if ($temporary === false) {
            throw new RuntimeException('No fue posible preparar la nueva versión del respaldo.');
        }

        @unlink($temporary);
        $temporary .= '.zip';
        if (class_exists(ZipArchive::class)) {
            $zip = new ZipArchive;
            if ($zip->open($temporary, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('No fue posible reconstruir el paquete ZIP.');
            }
            foreach ($remaining as $file) {
                if (! is_array($file) || blank($file['path'] ?? null) || ! $disk->exists($file['path'])) {
                    continue;
                }

                $customer = trim(str_replace(['/', '\\'], '-', (string) ($file['customer'] ?? 'Cliente')));
                $category = ucfirst((string) ($file['category'] ?? 'bak'));
                $name = basename((string) ($file['name'] ?? 'respaldo.bak'));
                $zip->addFile($disk->path($file['path']), "{$customer}/{$category}/{$name}");
            }
            $zip->close();
        } else {
            $zip = new PharData($temporary);
            foreach ($remaining as $file) {
                if (! is_array($file) || blank($file['path'] ?? null) || ! $disk->exists($file['path'])) {
                    continue;
                }

                $customer = trim(str_replace(['/', '\\'], '-', (string) ($file['customer'] ?? 'Cliente')));
                $category = ucfirst((string) ($file['category'] ?? 'bak'));
                $name = basename((string) ($file['name'] ?? 'respaldo.bak'));
                $zip->addFile($disk->path($file['path']), "{$customer}/{$category}/{$name}");
            }
            unset($zip);
        }

        $stream = fopen($temporary, 'rb');
        if ($stream === false || ! $disk->writeStream($upload->storage_path, $stream)) {
            if (is_resource($stream)) {
                fclose($stream);
            }
            @unlink($temporary);
            throw new RuntimeException('No fue posible guardar la nueva versión del ZIP.');
        }
        fclose($stream);

        $contents = file_get_contents($temporary) ?: '';
        @unlink($temporary);
        $disk->delete((string) $target['path']);

        $upload->forceFill([
            'manifest' => $remaining->all(),
            'size' => strlen($contents),
            'checksum' => hash('sha256', $contents),
            'last_activity_at' => now(),
        ])->save();
    }
}
