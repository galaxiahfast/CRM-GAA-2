<?php

namespace App\Http\Controllers;

use App\Jobs\AssembleBackupUpload;
use App\Models\BackupUpload;
use App\Services\Backups\BackupStoragePurger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use PharData;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupUploadController extends Controller
{
    public function example(): StreamedResponse
    {
        Gate::authorize('access-system-backups');

        return response()->streamDownload(function (): void {
            $temporary = tempnam(sys_get_temp_dir(), 'backup-example-');
            if ($temporary === false) {
                throw new RuntimeException('No fue posible crear el ZIP de ejemplo.');
            }

            @unlink($temporary);
            $temporary .= '.zip';
            $zip = new PharData($temporary);

            foreach (config('backup-storage.demo_companies', []) as $company) {
                $slug = Str::slug($company);
                $zip->addFromString("{$company}/Index/{$slug}_ejemplo.index", "Índice de prueba para {$company}\n");
                $zip->addFromString("{$company}/Bak/{$slug}_ejemplo.bak", "Respaldo de prueba para {$company}\n");
            }
            unset($zip);

            readfile($temporary);
            @unlink($temporary);
        }, 'respaldo-ejemplo-15-clientes.zip', [
            'Content-Type' => 'application/zip',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function initialize(Request $request): JsonResponse
    {
        Gate::authorize('upload-system-backups');

        $data = $request->validate([
            'site' => ['required', Rule::in(array_keys(config('backup-storage.sites', [])))],
            'product' => ['nullable', 'string', 'max:20'],
            'file_name' => ['required', 'string', 'max:255'],
            'mime_type' => ['nullable', 'string', 'max:150'],
            'size' => ['required', 'integer', 'min:1', 'max:'.config('backup-storage.max_file_size')],
            'fingerprint' => ['required', 'string', 'max:500'],
            'replace_upload_id' => ['nullable', 'uuid', 'exists:backup_uploads,id'],
        ]);

        $availableProducts = config('backup-storage.products.'.$data['site'], []);
        abort_if($availableProducts === [], 422, 'La sede seleccionada no tiene sistemas configurados.');
        $data['product'] = $data['product'] ?? array_key_first($availableProducts);
        abort_unless(array_key_exists($data['product'], $availableProducts), 422, 'El sistema seleccionado no está disponible en esta sede.');

        $extension = mb_strtolower((string) pathinfo(basename($data['file_name']), PATHINFO_EXTENSION));
        abort_unless($extension === 'zip', 422, 'Selecciona un archivo ZIP válido.');

        $replacement = filled($data['replace_upload_id'] ?? null)
            ? BackupUpload::query()->findOrFail($data['replace_upload_id'])
            : null;
        if ($replacement) {
            Gate::authorize('manage-system-backups');
        }
        abort_if($replacement && $replacement->site !== $data['site'], 422, 'El respaldo a reemplazar pertenece a otra sede.');
        abort_if($replacement && $replacement->product !== $data['product'], 422, 'El respaldo a reemplazar pertenece a otro sistema.');
        abort_if($replacement && $replacement->status !== BackupUpload::STATUS_COMPLETED, 422, 'Solo se puede reemplazar un respaldo completado.');

        $uploadKey = hash('sha256', implode('|', [
            $request->user()->id, $data['site'], $data['product'], $data['fingerprint'], $replacement?->id ?? '',
        ]));
        $chunkSize = max(1024, (int) config('backup-storage.chunk_size'));

        $upload = DB::transaction(function () use ($data, $extension, $uploadKey, $chunkSize, $request, $replacement): BackupUpload {
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
                'product' => $data['product'],
                'customer_id' => null,
                'user_id' => $request->user()->id,
                'category' => null,
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
                'supersedes_upload_id' => $replacement?->id,
                'assigned_customer' => $replacement?->assigned_customer,
                'notes' => $replacement?->notes,
                'last_activity_at' => now(),
            ]);
        });

        return response()->json($this->payload($upload->fresh()));
    }

    public function status(BackupUpload $upload): JsonResponse
    {
        Gate::authorize('upload-system-backups');
        $this->activateIfSlotAvailable($upload);

        return response()->json($this->payload($upload->fresh()));
    }

    public function storeChunk(Request $request, BackupUpload $upload, int $index): JsonResponse
    {
        Gate::authorize('upload-system-backups');
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
        $temporaryPath = $path.'.uploading';
        $disk = Storage::disk((string) config('backup-storage.disk', 'local'));
        $disk->delete($temporaryPath);

        try {
            abort_unless($disk->writeStream($temporaryPath, $stream), 500, 'No se pudo guardar el fragmento.');
        } finally {
            fclose($stream);
        }

        $size = (int) $disk->size($temporaryPath);
        $expectedSize = min($upload->chunk_size, $upload->size - ($index * $upload->chunk_size));
        if ($size !== $expectedSize) {
            $disk->delete($temporaryPath);
            abort(422, "El fragmento {$index} está incompleto; se esperaban {$expectedSize} bytes y llegaron {$size}. Reintenta la carga.");
        }

        if ($disk->exists($path)) {
            $disk->delete($path);
        }
        abort_unless($disk->move($temporaryPath, $path), 500, 'No se pudo confirmar el fragmento recibido.');

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
        Gate::authorize('upload-system-backups');
        abort_unless($upload->user_id === $request->user()->id || $request->user()->isAdmin(), 403);

        if ($upload->status === BackupUpload::STATUS_COMPLETED) {
            return response()->json($this->payload($upload));
        }
        if (in_array($upload->status, [BackupUpload::STATUS_QUEUED, BackupUpload::STATUS_PROCESSING], true)) {
            return response()->json($this->payload($upload), 202);
        }

        $state = $this->validatedChunkState($upload);
        if (count($state['chunks']) !== $upload->total_chunks) {
            $upload->forceFill([
                'uploaded_chunks' => $state['chunks'],
                'received_bytes' => $state['bytes'],
                'status' => BackupUpload::STATUS_UPLOADING,
                'last_activity_at' => now(),
            ])->save();

            abort(422, "Falta el fragmento {$state['missing']} o está incompleto. La carga se reanudará desde ese punto.");
        }

        $upload->forceFill([
            'status' => BackupUpload::STATUS_QUEUED,
            'queued_at' => now(),
            'last_activity_at' => now(),
        ])->save();

        AssembleBackupUpload::dispatch($upload->id);

        return response()->json($this->payload($upload->fresh()), 202);
    }

    public function cancel(Request $request, BackupUpload $upload, BackupStoragePurger $purger): JsonResponse
    {
        Gate::authorize('upload-system-backups');
        abort_unless($upload->user_id === $request->user()->id || $request->user()->isAdmin(), 403);
        abort_unless($upload->isCancellable(), 409, 'La carga ya está siendo procesada y no se puede cancelar.');

        $purger->purge($upload);

        return response()->json(['cancelled' => true]);
    }

    public function download(BackupUpload $upload): StreamedResponse
    {
        Gate::authorize('access-system-backups');
        abort_unless($upload->status === BackupUpload::STATUS_COMPLETED && filled($upload->storage_path), 404);

        $disk = Storage::disk((string) config('backup-storage.disk', 'local'));
        abort_unless($disk->exists($upload->storage_path), 404);

        return $disk->download($upload->storage_path, $upload->original_name, [
            'Content-Type' => $upload->mime_type ?: 'application/octet-stream',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function downloadFile(BackupUpload $upload, int $index): StreamedResponse
    {
        Gate::authorize('access-system-backups');
        abort_unless($upload->status === BackupUpload::STATUS_COMPLETED, 404);

        $file = collect($upload->manifest ?? [])->get($index);
        abort_unless(
            is_array($file)
            && filled($file['path'] ?? null)
            && str_starts_with((string) $file['path'], 'backups/'.$upload->site.'/'),
            404,
        );

        $disk = Storage::disk((string) config('backup-storage.disk', 'local'));
        abort_unless($disk->exists($file['path']), 404);

        return $disk->download($file['path'], basename((string) ($file['name'] ?? 'respaldo')), [
            'Content-Type' => 'application/octet-stream',
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

    /** @return array{chunks: array<int, int>, bytes: int, missing: int|null} */
    private function validatedChunkState(BackupUpload $upload): array
    {
        $disk = Storage::disk((string) config('backup-storage.disk', 'local'));
        $chunks = [];
        $bytes = 0;
        $missing = null;

        for ($index = 0; $index < $upload->total_chunks; $index++) {
            $path = "backup-chunks/{$upload->id}/{$index}.part";
            $expectedSize = min($upload->chunk_size, $upload->size - ($index * $upload->chunk_size));
            if (! $disk->exists($path) || (int) $disk->size($path) !== $expectedSize) {
                $missing ??= $index;

                continue;
            }

            $chunks[] = $index;
            $bytes += $expectedSize;
        }

        return ['chunks' => $chunks, 'bytes' => $bytes, 'missing' => $missing];
    }

    /** @return array<string, mixed> */
    private function payload(BackupUpload $upload): array
    {
        return [
            'id' => $upload->id,
            'status' => $upload->status,
            'product' => $upload->product,
            'fileName' => $upload->original_name,
            'size' => $upload->size,
            'chunkSize' => $upload->chunk_size,
            'totalChunks' => $upload->total_chunks,
            'uploadedChunks' => array_values($upload->uploaded_chunks ?? []),
            'receivedBytes' => $upload->received_bytes,
            'error' => $upload->error_message,
            'statusUrl' => route('activity-backups.uploads.status', $upload, false),
            'chunkUrlTemplate' => route('activity-backups.uploads.chunks.store', [$upload, '__INDEX__'], false),
            'completeUrl' => route('activity-backups.uploads.complete', $upload, false),
            'cancelUrl' => route('activity-backups.uploads.cancel', $upload, false),
            'downloadUrl' => $upload->status === BackupUpload::STATUS_COMPLETED
                ? route('activity-backups.download', $upload, false)
                : null,
        ];
    }
}
