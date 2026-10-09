<?php

namespace App\Http\Controllers;

use App\Jobs\AssembleBackupUpload;
use App\Models\BackupUpload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupUploadController extends Controller
{
    public function initialize(Request $request): JsonResponse
    {
        Gate::authorize('manage-system-backups');

        $data = $request->validate([
            'site' => ['required', Rule::in(array_keys(config('backup-storage.sites', [])))],
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'category' => ['required', Rule::in(['index', 'bak'])],
            'file_name' => ['required', 'string', 'max:255'],
            'mime_type' => ['nullable', 'string', 'max:150'],
            'size' => ['required', 'integer', 'min:1', 'max:'.config('backup-storage.max_file_size')],
            'fingerprint' => ['required', 'string', 'max:500'],
        ]);

        $extension = mb_strtolower((string) pathinfo(basename($data['file_name']), PATHINFO_EXTENSION));
        abort_unless($extension === $data['category'], 422, 'El archivo no corresponde con la carpeta seleccionada.');

        $uploadKey = hash('sha256', implode('|', [
            $request->user()->id, $data['site'], $data['customer_id'], $data['category'], $data['fingerprint'],
        ]));
        $chunkSize = max(1024, (int) config('backup-storage.chunk_size'));

        $upload = DB::transaction(function () use ($data, $extension, $uploadKey, $chunkSize, $request): BackupUpload {
            $existing = BackupUpload::query()
                ->where('upload_key', $uploadKey)
                ->where('user_id', $request->user()->id)
                ->whereNotIn('status', [BackupUpload::STATUS_FAILED])
                ->latest('created_at')
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            $this->releaseStaleSlots();
            $activeCount = BackupUpload::query()
                ->where('status', BackupUpload::STATUS_UPLOADING)
                ->lockForUpdate()
                ->count();

            return BackupUpload::query()->create([
                'upload_key' => $uploadKey,
                'site' => $data['site'],
                'customer_id' => $data['customer_id'],
                'user_id' => $request->user()->id,
                'category' => $data['category'],
                'original_name' => basename($data['file_name']),
                'extension' => $extension,
                'mime_type' => $data['mime_type'] ?? null,
                'size' => $data['size'],
                'chunk_size' => $chunkSize,
                'total_chunks' => (int) ceil($data['size'] / $chunkSize),
                'uploaded_chunks' => [],
                'status' => $activeCount < (int) config('backup-storage.max_active_uploads', 2)
                    ? BackupUpload::STATUS_UPLOADING
                    : BackupUpload::STATUS_WAITING,
                'last_activity_at' => now(),
            ]);
        });

        return response()->json($this->payload($upload->fresh()));
    }

    public function status(BackupUpload $upload): JsonResponse
    {
        Gate::authorize('manage-system-backups');
        $this->activateIfSlotAvailable($upload);

        return response()->json($this->payload($upload->fresh()));
    }

    public function storeChunk(Request $request, BackupUpload $upload, int $index): JsonResponse
    {
        Gate::authorize('manage-system-backups');
        abort_unless($upload->user_id === $request->user()->id || $request->user()->isAdmin(), 403);
        abort_if($index < 0 || $index >= $upload->total_chunks, 422, 'Índice de fragmento inválido.');

        $this->activateIfSlotAvailable($upload);
        $upload->refresh();
        if ($upload->status === BackupUpload::STATUS_WAITING) {
            return response()->json($this->payload($upload), 409);
        }
        abort_unless($upload->status === BackupUpload::STATUS_UPLOADING, 409, 'La carga no acepta más fragmentos.');

        $stream = $request->getContent(true);
        abort_unless(is_resource($stream), 422, 'No se recibió el fragmento.');
        $path = sprintf('backup-chunks/%s/%d.part', $upload->id, $index);
        $disk = Storage::disk((string) config('backup-storage.disk', 'local'));
        abort_unless($disk->writeStream($path, $stream), 500, 'No se pudo guardar el fragmento.');
        fclose($stream);

        $size = (int) $disk->size($path);
        abort_if($size < 1 || $size > $upload->chunk_size, 422, 'El tamaño del fragmento es inválido.');

        $upload = DB::transaction(function () use ($upload, $index, $disk): BackupUpload {
            $locked = BackupUpload::query()->lockForUpdate()->findOrFail($upload->id);
            $chunks = collect($locked->uploaded_chunks ?? [])->map(fn ($value) => (int) $value)->push($index)->unique()->sort()->values();
            $received = $chunks->sum(fn (int $chunk) => $disk->exists("backup-chunks/{$locked->id}/{$chunk}.part")
                ? (int) $disk->size("backup-chunks/{$locked->id}/{$chunk}.part")
                : 0);
            $locked->forceFill([
                'uploaded_chunks' => $chunks->all(),
                'received_bytes' => $received,
                'last_activity_at' => now(),
            ])->save();

            return $locked;
        });

        return response()->json($this->payload($upload));
    }

    public function complete(Request $request, BackupUpload $upload): JsonResponse
    {
        Gate::authorize('manage-system-backups');
        abort_unless($upload->user_id === $request->user()->id || $request->user()->isAdmin(), 403);

        if ($upload->status === BackupUpload::STATUS_COMPLETED) {
            return response()->json($this->payload($upload));
        }
        if (in_array($upload->status, [BackupUpload::STATUS_QUEUED, BackupUpload::STATUS_PROCESSING], true)) {
            return response()->json($this->payload($upload), 202);
        }

        $chunks = collect($upload->uploaded_chunks ?? [])->unique();
        abort_unless($chunks->count() === $upload->total_chunks, 422, 'Aún faltan fragmentos por cargar.');

        $upload->forceFill([
            'status' => BackupUpload::STATUS_QUEUED,
            'queued_at' => now(),
            'last_activity_at' => now(),
        ])->save();

        AssembleBackupUpload::dispatch($upload->id);

        return response()->json($this->payload($upload->fresh()), 202);
    }

    public function download(BackupUpload $upload): StreamedResponse
    {
        Gate::authorize('manage-system-backups');
        abort_unless($upload->status === BackupUpload::STATUS_COMPLETED && filled($upload->storage_path), 404);

        $disk = Storage::disk((string) config('backup-storage.disk', 'local'));
        abort_unless($disk->exists($upload->storage_path), 404);

        return $disk->download($upload->storage_path, $upload->original_name, [
            'Content-Type' => $upload->mime_type ?: 'application/octet-stream',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private function activateIfSlotAvailable(BackupUpload $upload): void
    {
        if ($upload->status !== BackupUpload::STATUS_WAITING) {
            return;
        }

        DB::transaction(function () use ($upload): void {
            $this->releaseStaleSlots();
            $locked = BackupUpload::query()->lockForUpdate()->findOrFail($upload->id);
            $activeCount = BackupUpload::query()
                ->where('status', BackupUpload::STATUS_UPLOADING)
                ->whereKeyNot($locked->id)
                ->lockForUpdate()
                ->count();
            if ($activeCount < (int) config('backup-storage.max_active_uploads', 2)) {
                $locked->forceFill(['status' => BackupUpload::STATUS_UPLOADING, 'last_activity_at' => now()])->save();
            }
        });
    }

    private function releaseStaleSlots(): void
    {
        BackupUpload::query()
            ->where('status', BackupUpload::STATUS_UPLOADING)
            ->where('last_activity_at', '<', now()->subMinutes((int) config('backup-storage.stale_after_minutes', 15)))
            ->update(['status' => BackupUpload::STATUS_WAITING]);
    }

    /** @return array<string, mixed> */
    private function payload(BackupUpload $upload): array
    {
        return [
            'id' => $upload->id,
            'status' => $upload->status,
            'fileName' => $upload->original_name,
            'size' => $upload->size,
            'chunkSize' => $upload->chunk_size,
            'totalChunks' => $upload->total_chunks,
            'uploadedChunks' => array_values($upload->uploaded_chunks ?? []),
            'receivedBytes' => $upload->received_bytes,
            'error' => $upload->error_message,
            'statusUrl' => route('activity-backups.uploads.status', $upload),
            'chunkUrlTemplate' => route('activity-backups.uploads.chunks.store', [$upload, '__INDEX__']),
            'completeUrl' => route('activity-backups.uploads.complete', $upload),
            'downloadUrl' => $upload->status === BackupUpload::STATUS_COMPLETED
                ? route('activity-backups.download', $upload)
                : null,
        ];
    }
}
