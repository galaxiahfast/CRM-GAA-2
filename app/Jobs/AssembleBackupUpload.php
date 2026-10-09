<?php

namespace App\Jobs;

use App\Models\BackupUpload;
use App\Services\Backups\BackupArchiveExtractor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class AssembleBackupUpload implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 7200;

    public int $uniqueFor = 7200;

    public function __construct(public string $uploadId)
    {
        $this->onQueue((string) config('backup-storage.queue', 'backup-uploads'));
    }

    public function uniqueId(): string
    {
        return $this->uploadId;
    }

    public function handle(BackupArchiveExtractor $extractor): void
    {
        $upload = BackupUpload::query()->findOrFail($this->uploadId);
        if ($upload->status === BackupUpload::STATUS_COMPLETED) {
            return;
        }

        $disk = Storage::disk((string) config('backup-storage.disk', 'local'));
        $chunks = collect($upload->uploaded_chunks ?? [])->map(fn ($value) => (int) $value)->unique()->sort()->values();
        if ($chunks->count() !== $upload->total_chunks) {
            throw new RuntimeException('No se recibieron todos los fragmentos del respaldo.');
        }

        $upload->forceFill(['status' => BackupUpload::STATUS_PROCESSING, 'error_message' => null])->save();

        $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($upload->original_name)) ?: 'respaldo.'.$upload->extension;
        $directory = sprintf('backup-archives/%s', $upload->site);
        $stagingPath = sprintf('backup-assembly/%s.part', $upload->id);
        $finalPath = $directory.'/'.$upload->id.'_'.$safeName;

        $disk->makeDirectory(dirname($stagingPath));
        $destination = fopen($disk->path($stagingPath), 'wb');
        if ($destination === false) {
            throw new RuntimeException('No fue posible crear el archivo temporal del respaldo.');
        }

        try {
            for ($index = 0; $index < $upload->total_chunks; $index++) {
                $chunkPath = sprintf('backup-chunks/%s/%d.part', $upload->id, $index);
                $source = $disk->readStream($chunkPath);
                if ($source === false) {
                    throw new RuntimeException("Falta el fragmento {$index} del respaldo.");
                }
                stream_copy_to_stream($source, $destination);
                fclose($source);
            }
        } finally {
            fclose($destination);
        }

        if ((int) $disk->size($stagingPath) !== $upload->size) {
            throw new RuntimeException('El tamaño final no coincide con el archivo seleccionado.');
        }

        $checksum = hash_file('sha256', $disk->path($stagingPath));
        $disk->makeDirectory($directory);
        if ($disk->exists($finalPath)) {
            $disk->delete($finalPath);
        }
        if (! $disk->move($stagingPath, $finalPath)) {
            throw new RuntimeException('No fue posible mover el respaldo a su carpeta definitiva.');
        }

        $manifest = $extractor->extract($disk, $finalPath, $upload->site, $upload->id);

        $disk->deleteDirectory('backup-chunks/'.$upload->id);
        $upload->forceFill([
            'status' => BackupUpload::STATUS_COMPLETED,
            'storage_path' => $finalPath,
            'manifest' => $manifest,
            'checksum' => $checksum,
            'received_bytes' => $upload->size,
            'completed_at' => now(),
            'last_activity_at' => now(),
        ])->save();
    }

    public function failed(?Throwable $exception): void
    {
        BackupUpload::query()->whereKey($this->uploadId)->update([
            'status' => BackupUpload::STATUS_FAILED,
            'error_message' => mb_substr($exception?->getMessage() ?: 'La carga no pudo procesarse.', 0, 1000),
            'last_activity_at' => now(),
        ]);
    }
}
