<?php

namespace App\Jobs;

use App\Services\Backups\ContpaqiBackupCatalog;
use App\Services\Backups\ContpaqiBackupExport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class CopyContpaqiBackups implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 7200;

    public int $uniqueFor = 7200;

    public function __construct(public string $exportId)
    {
        $this->onConnection((string) config('backup-storage.export.queue_connection', config('queue.default')));
        $this->onQueue('default');
    }

    public function uniqueId(): string
    {
        return $this->exportId;
    }

    public function handle(ContpaqiBackupExport $export, ContpaqiBackupCatalog $catalog): void
    {
        ignore_user_abort(true);
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        $export->execute($this->exportId, $catalog);
    }

    public function failed(?Throwable $exception): void
    {
        app(ContpaqiBackupExport::class)->markFailed(
            $this->exportId,
            $exception?->getMessage() ?: 'La copia no pudo completarse.',
        );
    }
}
