<?php

namespace App\Console\Commands;

use App\Services\ManagerControl\ConfigFileParser;
use App\Services\ManagerControl\ConnectionSettingsResolver;
use App\Services\ManagerControl\ManagerControlException;
use App\Services\ManagerControl\ReadOnlyConnection;
use Illuminate\Console\Command;

class ManagerControlTestConnection extends Command
{
    protected $signature = 'managercontrol:test-connection {--config-only : Solo lee el .exe.config, sin conectar a la base}';

    protected $description = 'Prueba de conexión de SOLO LECTURA a la base de ManagerControl (nunca imprime contraseñas)';

    public function handle(ConfigFileParser $parser, ConnectionSettingsResolver $resolver, ReadOnlyConnection $db): int
    {
        $path = (string) config('managercontrol.config_path');
        try {
            $file = $parser->parseFile($path, (string) config('managercontrol.config_connection_name'));
            $this->info('ManagerControl.exe.config leído (solo lectura):');
            $this->table(['Dato', 'Valor'], [
                ['Motor', 'Microsoft SQL Server (System.Data.SqlClient)'],
                ['Servidor', $file['host']],
                ['Instancia', $file['instance'] ?? '—'],
                ['Puerto', $file['port'] ?? '— (dinámico / SQL Browser)'],
                ['Base de datos', $file['database'] ?? '—'],
                ['Credenciales dentro del archivo', $file['file_has_credentials'] ? 'sí (IGNORADAS y no mostradas; use MANAGERCONTROL_USERNAME/PASSWORD en .env)' : 'no'],
            ]);
        } catch (ManagerControlException $e) {
            $this->warn('Config del legacy no disponible: '.$e->getMessage());
        }

        if ($this->option('config-only')) {
            return self::SUCCESS;
        }

        try {
            $settings = $resolver->resolve();
            $this->line('Conexión que se usará (.env tiene prioridad):');
            $this->table(['Dato', 'Valor'], collect($settings->toSafeArray())->map(fn ($v, $k) => [$k, $v ?? '—'])->values()->all());

            $info = $db->diagnostics();
        } catch (ManagerControlException $e) {
            $this->error('ERROR: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->table(['Diagnóstico', 'Valor'], [
            ['Motor', $info['engine']],
            ['Versión', $info['version']],
            ['Base de datos', $info['database']],
            ['Login', $info['login']],
            ['Roles con escritura', $info['write_roles'] === [] ? 'ninguno' : implode(', ', $info['write_roles'])],
            ['Sesión de solo lectura', $info['can_write'] ? 'NO' : 'SÍ (cuenta sin permisos de escritura + guard SELECT-only)'],
        ]);

        if ($info['can_write']) {
            $this->error('La cuenta puede escribir: la aplicación se negará a consultar con ella. Pida una cuenta db_datareader.');

            return self::FAILURE;
        }

        $this->info('Conexión correcta y de solo lectura.');

        return self::SUCCESS;
    }
}
