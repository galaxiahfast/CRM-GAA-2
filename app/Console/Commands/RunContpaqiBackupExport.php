<?php

namespace App\Console\Commands;

use App\Services\Backups\ContpaqiBackupCatalog;
use App\Services\Backups\ContpaqiBackupExport;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Throwable;

class RunContpaqiBackupExport extends Command
{
    protected $signature = 'backups:copy-contpaqi {id}';

    protected $description = 'Ejecuta una copia de respaldos de CONTPAQi previamente validada';

    public function handle(ContpaqiBackupExport $export, ContpaqiBackupCatalog $catalog): int
    {
        $id = (string) $this->argument('id');
        if (! Str::isUuid($id) || $export->find($id) === null) {
            return self::INVALID;
        }

        ignore_user_abort(true);
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        try {
            $export->execute($id, $catalog);

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $export->markFailed($id, $exception->getMessage());
            report($exception);

            return self::FAILURE;
        }
    }
}
