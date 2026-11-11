<?php

namespace App\Services\ManagerControl;

/**
 * Combina lo descubierto en ManagerControl.exe.config (servidor/instancia/base) con .env
 * (que tiene prioridad y es la ÚNICA fuente de usuario y contraseña).
 */
class ConnectionSettingsResolver
{
    public function __construct(private readonly ConfigFileParser $parser) {}

    public function resolve(): ConnectionSettings
    {
        $fromFile = ['host' => null, 'instance' => null, 'port' => null, 'database' => null];
        $path = (string) config('managercontrol.config_path');
        if ($path !== '' && is_file($path)) {
            $fromFile = $this->parser->parseFile($path, (string) config('managercontrol.config_connection_name'));
        }

        $envHost = config('managercontrol.host');
        $host = filled($envHost) ? (string) $envHost : $fromFile['host'];
        $database = filled(config('managercontrol.database')) ? (string) config('managercontrol.database') : $fromFile['database'];
        // Si el servidor viene de .env, no se hereda la instancia ni el puerto del archivo.
        $instance = filled($envHost) ? config('managercontrol.instance') : $fromFile['instance'];
        $port = filled(config('managercontrol.port')) ? (int) config('managercontrol.port') : (filled($envHost) ? null : $fromFile['port']);

        if (blank($host) || blank($database)) {
            throw new ManagerControlException('Falta el servidor o la base de datos: defina MANAGERCONTROL_HOST y MANAGERCONTROL_DATABASE en .env.');
        }
        $useSql = filled(config('managercontrol.sql_username'));
        $user = (string) config($useSql ? 'managercontrol.sql_username' : 'managercontrol.username');
        $pass = (string) config($useSql ? 'managercontrol.sql_password' : 'managercontrol.password');
        if ($user === '' || $pass === '') {
            throw new ManagerControlException('Faltan MANAGERCONTROL_SQL_USERNAME y MANAGERCONTROL_SQL_PASSWORD en .env (login de SQL Server de solo lectura).');
        }

        return new ConnectionSettings(
            (string) $host,
            filled($instance) ? (string) $instance : null,
            $port,
            (string) $database,
            $user,
            $pass,
        );
    }
}
