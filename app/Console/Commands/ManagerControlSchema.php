<?php

namespace App\Console\Commands;

use App\Services\ManagerControl\ManagerControlException;
use App\Services\ManagerControl\ManagerControlRepository;
use Illuminate\Console\Command;

class ManagerControlSchema extends Command
{
    protected $signature = 'managercontrol:schema {filter? : Fragmento del nombre de tabla (p. ej. Hora, Cliente, Activ)}';

    protected $description = 'Lista tablas/columnas (INFORMATION_SCHEMA, solo lectura) para confirmar el mapa en config/managercontrol.php';

    public function handle(ManagerControlRepository $repository): int
    {
        try {
            $columns = $repository->schemaColumns($this->argument('filter'));
        } catch (ManagerControlException $e) {
            $this->error('ERROR: '.$e->getMessage());

            return self::FAILURE;
        }

        foreach (collect($columns)->groupBy('table') as $table => $cols) {
            $this->line("<info>{$table}</info>: ".$cols->map(fn ($c) => "{$c['column']} ({$c['type']})")->implode(', '));
        }
        $this->comment(count($columns).' columnas.');

        return self::SUCCESS;
    }
}
