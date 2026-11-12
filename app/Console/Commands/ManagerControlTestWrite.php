<?php

namespace App\Console\Commands;

use App\Services\ManagerControl\ManagerControlException;
use App\Services\ManagerControl\ManagerControlWriter;
use App\Services\ManagerControl\WriteConnection;
use App\Services\ManagerControl\WriteDisabled;
use Illuminate\Console\Command;

class ManagerControlTestWrite extends Command
{
    protected $signature = 'managercontrol:test-write';

    protected $description = 'Revisa la cuenta de ESCRITURA de ManagerControl sin escribir nada (nunca imprime contraseñas)';

    public function handle(ManagerControlWriter $writer, WriteConnection $db): int
    {
        $this->table(['Configuración', 'Valor'], [
            ['MANAGERCONTROL_WRITE_ENABLED', config('managercontrol.write.enabled') ? 'true' : 'false'],
            ['MANAGERCONTROL_WRITE_USERNAME', filled(config('managercontrol.write.username')) ? config('managercontrol.write.username') : '— (vacío)'],
            ['MANAGERCONTROL_WRITE_PASSWORD', filled(config('managercontrol.write.password')) ? 'definida' : '— (vacía)'],
            ['Empresa / Ejercicio', config('managercontrol.company_id').' / '.config('managercontrol.exercise_id')],
        ]);

        if (! $writer->enabled()) {
            $this->error('La escritura está DESACTIVADA: falta MANAGERCONTROL_WRITE_ENABLED=true, el usuario, la contraseña, la empresa o el ejercicio. Corrige el .env y corre php artisan optimize.');

            return self::FAILURE;
        }

        try {
            $info = $db->diagnostics();
        } catch (WriteDisabled|ManagerControlException $e) {
            $this->error('ERROR: '.$e->getMessage());
            $this->line('Revisa que el usuario y la contraseña de MANAGERCONTROL_WRITE_* existan en SQL Server y que la extensión pdo_sqlsrv esté activa.');

            return self::FAILURE;
        }

        $yes = fn (bool $value, string $good) => $value ? 'SÍ' : 'NO'.$good;
        $this->table(['Cuenta de escritura', 'Resultado'], [
            ['Login con el que entra', $info['login']],
            ['Base de datos', $info['database']],
            ['SELECT en la tabla de horas', $yes($info['select'], ' (falta: GRANT SELECT)')],
            ['INSERT (agregar actividades)', $yes($info['insert'], ' (falta: GRANT INSERT)')],
            ['UPDATE (editar y dar de baja)', $yes($info['update'], ' (falta: GRANT UPDATE)')],
            ['DELETE', $info['delete'] ? 'SÍ (no hace falta: la baja es lógica)' : 'NO (correcto: dar de baja es un UPDATE de Baja = 1)'],
            ['Miembro de db_owner', $info['db_owner'] ? 'SÍ (demasiado: use solo SELECT, INSERT y UPDATE)' : 'NO (correcto)'],
        ]);

        $missing = array_keys(array_filter(['SELECT' => ! $info['select'], 'INSERT' => ! $info['insert'], 'UPDATE' => ! $info['update']]));
        if ($missing !== []) {
            $this->error('Faltan permisos: '.implode(', ', $missing).'. Ejecute en SQL Server: GRANT SELECT, INSERT, UPDATE ON [ControlTiempos].[RegistroDiario] TO ['.$info['login'].'];');

            return self::FAILURE;
        }

        $this->info('La cuenta puede agregar, editar y dar de baja actividades. No se escribió nada en la base.');

        return self::SUCCESS;
    }
}
