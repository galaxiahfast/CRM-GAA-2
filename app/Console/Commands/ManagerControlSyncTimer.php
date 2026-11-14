<?php

namespace App\Console\Commands;

use App\Services\ManagerControl\ManagerControlException;
use App\Services\ManagerControl\TimerSync;
use Illuminate\Console\Command;

/**
 * Envía las actividades del Cronómetro a ManagerControl. Sin --apply solo muestra lo que haría (no escribe nada).
 *   php artisan managercontrol:sync-timer                 → revisión, sin escribir
 *   php artisan managercontrol:sync-timer --apply         → envía desde MANAGERCONTROL_TIMER_SYNC_FROM hasta hoy
 *   php artisan managercontrol:sync-timer --from=2026-11-12 --to=2026-11-14 --apply
 */
class ManagerControlSyncTimer extends Command
{
    protected $signature = 'managercontrol:sync-timer {--from= : Primer día (Y-m-d)} {--to= : Último día (Y-m-d)} {--apply : Escribe en ManagerControl (sin esta opción solo se revisa)}';

    protected $description = 'Envía las actividades del Cronómetro del CRM a ManagerControl (solo en ese sentido).';

    public function handle(TimerSync $sync): int
    {
        $apply = (bool) $this->option('apply');
        if ($apply && ! $sync->enabled()) {
            $this->warn('El envío a ManagerControl está apagado (MANAGERCONTROL_TIMER_SYNC_ENABLED=false) o la escritura no está activa. No se escribió nada.');

            return self::SUCCESS;
        }

        try {
            $result = $sync->run($this->option('from') ?: null, $this->option('to') ?: null, $apply);
        } catch (ManagerControlException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if (! $apply || $this->getOutput()->isVerbose()) {
            $this->table(['Usuario', 'Día', 'Cliente', 'Actividad', 'Tiempo', 'Acción', 'Detalle'], array_map(fn (array $row): array => [
                $row['key']['user_id'], $row['key']['work_date'], $row['key']['customer_id'], $row['key']['sub_service_id'],
                sprintf('%02d:%02d', intdiv($row['seconds'], 3600), intdiv($row['seconds'] % 3600, 60)), $row['action'], $row['message'],
            ], $result['rows']));
        }

        $this->info(($apply ? 'Enviado a ManagerControl' : 'Revisión (no se escribió nada)').": {$result['created']} nuevas, {$result['updated']} actualizadas, "
            ."{$result['unchanged']} sin cambios, {$result['deleted']} dadas de baja, {$result['edited']} editadas en ManagerControl, {$result['skipped']} sin enviar, {$result['errors']} con error.");

        return $result['errors'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
