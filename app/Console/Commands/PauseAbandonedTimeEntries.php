<?php

namespace App\Console\Commands;

use App\Services\TimeControl\TimerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class PauseAbandonedTimeEntries extends Command
{
    protected $signature = 'time:pause-abandoned';

    protected $description = 'Pausa las actividades del Cronómetro de quien ya no tiene el CRM abierto (cerró la página o el navegador).';

    private const LAST_RUN_KEY = 'time:pause-abandoned:last-run';

    public function handle(TimerService $timer): int
    {
        // Si el programador estuvo detenido (se fue la luz, se reinició el servidor), durante ese tiempo nadie pudo avisar
        // que seguía con el CRM abierto. En la primera vuelta no se pausa a nadie: se espera un minuto a que los
        // navegadores que siguen abiertos vuelvan a avisar. En la siguiente vuelta solo se pausa a quien de verdad ya no
        // está, y se pausa en la última vez que se le vio (antes del apagón), así no se cuenta el tiempo apagado.
        $lastRun = Cache::get(self::LAST_RUN_KEY);
        Cache::put(self::LAST_RUN_KEY, now()->getTimestamp(), now()->addDay());
        if ($lastRun === null || now()->getTimestamp() - (int) $lastRun > 150) {
            $this->info('El programador estaba detenido: se espera a que los navegadores abiertos vuelvan a avisar antes de pausar.');

            return self::SUCCESS;
        }

        $paused = $timer->pauseAbandoned();

        if ($paused > 0) {
            $this->info("Actividades pausadas por ausencia: {$paused}");
        }

        return self::SUCCESS;
    }
}
