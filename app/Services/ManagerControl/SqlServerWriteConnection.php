<?php

namespace App\Services\ManagerControl;

use PDO;
use PDOException;

/**
 * Conexión de ESCRITURA a ManagerControl, separada de la de lectura:
 *  - credenciales propias (MANAGERCONTROL_WRITE_USERNAME/PASSWORD), pensadas para una cuenta con permisos
 *    solo de INSERT/UPDATE sobre ControlTiempos.RegistroDiario;
 *  - solo acepta una sentencia INSERT o UPDATE sobre esa tabla (nunca DELETE, DDL, EXEC ni varias sentencias);
 *  - cada sentencia corre en su propia transacción: si cambia un número inesperado de filas, se revierte.
 */
class SqlServerWriteConnection implements WriteConnection
{
    private ?PDO $pdo = null;

    public function __construct(private readonly ConnectionSettingsResolver $resolver) {}

    public function insert(string $sql, array $bindings): int
    {
        $this->guard($sql, 'INSERT');

        return $this->transaction(function (PDO $pdo) use ($sql, $bindings): int {
            $statement = $pdo->prepare($sql);
            $statement->execute(array_values($bindings));
            $id = (int) ($statement->fetchColumn() ?: 0);
            if ($id < 1) {
                throw new ManagerControlException('ManagerControl no devolvió el número del registro nuevo.');
            }

            return $id;
        });
    }

    public function update(string $sql, array $bindings): int
    {
        $this->guard($sql, 'UPDATE');

        return $this->transaction(function (PDO $pdo) use ($sql, $bindings): int {
            $statement = $pdo->prepare($sql);
            $statement->execute(array_values($bindings));
            $count = $statement->rowCount();
            if ($count !== 1) {
                throw new ManagerControlException('No se guardó: el registro ya no existe, fue dado de baja o se modificó desde ManagerControl mientras lo editabas. Vuelve a cargar la tabla e inténtalo de nuevo.');
            }

            return $count;
        });
    }

    public function diagnostics(): array
    {
        $pdo = $this->pdo();
        $object = '['.config('managercontrol.schema.hours.db_schema', 'dbo').'].['.config('managercontrol.schema.hours.table').']';
        try {
            // Consulta fija (solo lectura de metadatos): no recibe datos de la persona y no escribe nada.
            $row = $pdo->query("SELECT SUSER_NAME() AS login_name, DB_NAME() AS db_name, "
                ."ISNULL(HAS_PERMS_BY_NAME('{$object}', 'OBJECT', 'SELECT'), 0) AS can_select, "
                ."ISNULL(HAS_PERMS_BY_NAME('{$object}', 'OBJECT', 'INSERT'), 0) AS can_insert, "
                ."ISNULL(HAS_PERMS_BY_NAME('{$object}', 'OBJECT', 'UPDATE'), 0) AS can_update, "
                ."ISNULL(HAS_PERMS_BY_NAME('{$object}', 'OBJECT', 'DELETE'), 0) AS can_delete, "
                .'ISNULL(IS_ROLEMEMBER(\'db_owner\'), 0) AS is_owner')->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new ManagerControlException('No se pudo revisar la cuenta de escritura (código '.$e->getCode().').', 0, $e);
        }

        return [
            'login' => (string) ($row['login_name'] ?? ''),
            'database' => (string) ($row['db_name'] ?? ''),
            'select' => (bool) ($row['can_select'] ?? false),
            'insert' => (bool) ($row['can_insert'] ?? false),
            'update' => (bool) ($row['can_update'] ?? false),
            'delete' => (bool) ($row['can_delete'] ?? false),
            'db_owner' => (bool) ($row['is_owner'] ?? false),
        ];
    }

    private function guard(string $sql, string $verb): void
    {
        $table = (string) config('managercontrol.schema.hours.table');
        $schema = (string) config('managercontrol.schema.hours.db_schema', 'dbo');
        $clean = trim($sql);
        $target = $verb === 'INSERT' ? "INSERT INTO [{$schema}].[{$table}]" : "UPDATE [{$schema}].[{$table}]";
        if (! str_starts_with($clean, $target) || str_contains($clean, ';')
            || preg_match('/\b(DELETE|DROP|ALTER|CREATE|TRUNCATE|EXEC|EXECUTE|MERGE|GRANT|REVOKE|DENY)\b/i', $clean)) {
            throw new ReadOnlyViolation("Solo se permite {$verb} sobre {$schema}.{$table}.");
        }
    }

    /** @template T @param callable(PDO): T $work @return T */
    private function transaction(callable $work): mixed
    {
        $pdo = $this->pdo();
        try {
            $pdo->beginTransaction();
            $result = $work($pdo);
            $pdo->commit();

            return $result;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($e instanceof ManagerControlException) {
                throw $e;
            }
            throw new ManagerControlException('No se pudo guardar en ManagerControl (código '.($e instanceof PDOException ? $e->getCode() : 'desconocido').').', 0, $e);
        }
    }

    private function pdo(): PDO
    {
        if ($this->pdo !== null) {
            return $this->pdo;
        }
        if (! config('managercontrol.write.enabled') || blank(config('managercontrol.write.username')) || blank(config('managercontrol.write.password'))) {
            throw new WriteDisabled('La escritura en ManagerControl está desactivada.');
        }
        if (! extension_loaded('pdo_sqlsrv')) {
            throw new ManagerControlException('Falta la extensión PHP pdo_sqlsrv para conectar a SQL Server.');
        }

        $settings = $this->resolver->resolve();
        $dsn = sprintf('sqlsrv:Server=%s;Database=%s;LoginTimeout=%d;TrustServerCertificate=%d;APP=CRM-GAA-Captura',
            $settings->server(), $settings->database, (int) config('managercontrol.login_timeout', 5), config('managercontrol.trust_server_certificate', true) ? 1 : 0);

        try {
            $this->pdo = new PDO($dsn, (string) config('managercontrol.write.username'), (string) config('managercontrol.write.password'), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::SQLSRV_ATTR_QUERY_TIMEOUT => (int) config('managercontrol.query_timeout', 15),
            ]);
        } catch (PDOException $e) {
            throw new ManagerControlException('No se pudo conectar a ManagerControl para guardar (código '.$e->getCode().').');
        }

        return $this->pdo;
    }
}
