<?php

namespace App\Services\ManagerControl;

use PDO;
use PDOException;

/**
 * Conexión SQL Server (pdo_sqlsrv) de solo lectura:
 *  - cada sentencia pasa por ReadOnlyGuard y se ejecuta preparada, con parámetros;
 *  - SQL Server no tiene "SET TRANSACTION READ ONLY": la sesión se verifica al conectar (la cuenta no
 *    puede ser sysadmin/db_owner/db_datawriter/db_ddladmin) y se usa ApplicationIntent=ReadOnly;
 *  - tiempos de espera cortos de login, consulta y bloqueo.
 */
class SqlServerReadOnlyConnection implements ReadOnlyConnection
{
    private const WRITE_ROLES = ['db_owner', 'db_datawriter', 'db_ddladmin'];

    private ?PDO $pdo = null;

    public function __construct(private readonly ConnectionSettingsResolver $resolver) {}

    public function select(string $sql, array $bindings = []): array
    {
        ReadOnlyGuard::assertReadOnly($sql);

        return $this->run($this->pdo(), $sql, $bindings);
    }

    public function diagnostics(): array
    {
        return $this->inspect($this->connect());
    }

    /**
     * @param  list<scalar|null>  $bindings
     * @return list<array<string, mixed>>
     */
    private function run(PDO $pdo, string $sql, array $bindings = []): array
    {
        ReadOnlyGuard::assertReadOnly($sql);
        $maxRows = (int) config('managercontrol.max_rows', 5000);

        try {
            $statement = $pdo->prepare($sql);
            $statement->execute(array_values($bindings));
            $rows = [];
            while (($row = $statement->fetch(PDO::FETCH_ASSOC)) !== false) {
                $rows[] = $row;
                if (count($rows) > $maxRows) {
                    throw new ManagerControlException("La consulta devolvió más de {$maxRows} filas; reduzca el periodo.");
                }
            }

            return $rows;
        } catch (PDOException $e) {
            // Sin SQL ni parámetros en el mensaje (pueden contener datos sensibles).
            throw new ManagerControlException('Error de la base ManagerControl (código '.$e->getCode().').', 0, $e);
        }
    }

    private function pdo(): PDO
    {
        if ($this->pdo !== null) {
            return $this->pdo;
        }

        $pdo = $this->connect();
        $info = $this->inspect($pdo);
        if ($info['can_write']) {
            throw new ManagerControlException('La cuenta configurada puede escribir en la base ('.implode(', ', $info['write_roles']).'). Use una cuenta de solo lectura (db_datareader).');
        }

        return $this->pdo = $pdo;
    }

    private function connect(): PDO
    {
        if (! extension_loaded('pdo_sqlsrv')) {
            throw new ManagerControlException('Falta la extensión PHP pdo_sqlsrv (Microsoft Drivers for PHP for SQL Server) para conectar a SQL Server.');
        }

        $settings = $this->resolver->resolve();
        $dsn = sprintf(
            'sqlsrv:Server=%s;Database=%s;ApplicationIntent=ReadOnly;LoginTimeout=%d;TrustServerCertificate=%d;APP=CRM-GAA-ReadOnly',
            $settings->server(),
            $settings->database,
            (int) config('managercontrol.login_timeout', 5),
            config('managercontrol.trust_server_certificate', true) ? 1 : 0,
        );

        try {
            $pdo = new PDO($dsn, $settings->username, $settings->password(), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::SQLSRV_ATTR_QUERY_TIMEOUT => (int) config('managercontrol.query_timeout', 15),
            ]);
        } catch (PDOException $e) {
            throw new ManagerControlException('No se pudo conectar a ManagerControl (código '.$e->getCode().').');
        }

        return $pdo;
    }

    /** @return array{engine: string, version: string, database: string, login: string, can_write: bool, write_roles: list<string>} */
    private function inspect(PDO $pdo): array
    {
        $row = $this->run($pdo, 'SELECT @@VERSION AS version, DB_NAME() AS db, SUSER_SNAME() AS login, '
            .'IS_SRVROLEMEMBER(\'sysadmin\') AS sysadmin, IS_MEMBER(\'db_owner\') AS db_owner, '
            .'IS_MEMBER(\'db_datawriter\') AS db_datawriter, IS_MEMBER(\'db_ddladmin\') AS db_ddladmin')[0] ?? [];

        $roles = [];
        if ((int) ($row['sysadmin'] ?? 0) === 1) {
            $roles[] = 'sysadmin';
        }
        foreach (self::WRITE_ROLES as $role) {
            if ((int) ($row[$role] ?? 0) === 1) {
                $roles[] = $role;
            }
        }

        return [
            'engine' => 'Microsoft SQL Server',
            'version' => trim(strtok((string) ($row['version'] ?? ''), "\n") ?: ''),
            'database' => (string) ($row['db'] ?? ''),
            'login' => (string) ($row['login'] ?? ''),
            'can_write' => $roles !== [],
            'write_roles' => $roles,
        ];
    }
}
