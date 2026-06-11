<?php

namespace App\Console\Commands;

use App\Services\TimeControl\TimerService;
use Illuminate\Console\Command;

class AutoCloseTimeEntries extends Command
{
    protected $signature = 'time:auto-close';

    protected $description = 'Cierre forzoso nocturno de cronómetros en progreso (estado 0 -> 3).';

    public function handle(TimerService $timer): int
    {
        $closed = $timer->autoCloseOpenEntries();

        $this->info("Registros cerrados automáticamente: {$closed}");

        return self::SUCCESS;
    }
}
