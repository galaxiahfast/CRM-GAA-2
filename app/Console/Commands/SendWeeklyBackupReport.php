<?php

namespace App\Console\Commands;

use App\Services\Support\WeeklyBackupReportService;
use Illuminate\Console\Command;

class SendWeeklyBackupReport extends Command
{
    protected $signature = 'backup-reports:send-weekly {--dry-run : Lee los logs y genera el PDF, pero no envía el correo}';

    protected $description = 'Lee los logs de respaldos y envía el Reporte de respaldos en PDF a soporte y a Emiliano Ortiz (se programa cada lunes a las 9:00).';

    public function handle(WeeklyBackupReportService $report): int
    {
        $result = $report->run(! $this->option('dry-run'));

        $this->line('Perfiles leídos: '.$result['profiles'].' · con error: '.$result['errors']);

        if ($result['ok']) {
            $this->info($result['status']);

            return self::SUCCESS;
        }

        $this->error($result['status']);

        return self::FAILURE;
    }
}
