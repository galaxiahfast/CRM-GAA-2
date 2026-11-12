<?php

namespace App\Console\Commands;

use App\Services\ManagerControl\CatalogSynchronizer;
use App\Services\ManagerControl\ManagerControlException;
use Illuminate\Console\Command;

class ManagerControlSyncCatalogs extends Command
{
    protected $signature = 'managercontrol:sync-catalogs {--apply : Copiar los faltantes después de verificar permisos y coincidencias}';

    protected $description = 'Compara clientes, cuentas y actividades del CRM con ManagerControl; --apply importa los faltantes seguros.';

    public function handle(CatalogSynchronizer $sync): int
    {
        $apply = (bool) $this->option('apply');
        try {
            $result = $sync->run($apply);
        } catch (ManagerControlException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $rows = [];
        foreach (['customers' => 'Clientes', 'sections' => 'Cuentas', 'activities' => 'Actividades'] as $kind => $label) {
            $item = $result['counts'][$kind];
            $rows[] = [$label, $item['linked'], $item['matched'], $item['would_create'] + $item['pending_section'], $item['created'], $item['conflict']];
        }
        $this->table(['Catálogo', 'Vinculados', 'Coincidencias', 'Por importar', 'Importados', 'Revisar'], $rows);
        foreach ($result['issues'] as $issue) {
            $this->warn("{$issue['kind']} #{$issue['crm_id']}: {$issue['reason']}");
        }
        if (! $apply) {
            $this->info('Vista previa: no se escribió en SQL Server ni en el CRM.');
        } else {
            $this->info('Importación terminada. Las coincidencias y los IDs importados quedaron vinculados en el CRM.');
        }
        if ($result['issues'] !== []) {
            $this->warn(count($result['issues']).' registro(s) quedaron pendientes de revisión y no se importaron.');
        }

        return self::SUCCESS;
    }
}
