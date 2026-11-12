<?php

namespace App\Services\ManagerControl;

use PDO;
use PDOException;

/** Cuenta independiente, limitada a INSERT en los tres catálogos. No modifica registros existentes. */
class SqlServerCatalogInsertConnection implements CatalogInsertConnection
{
    private ?PDO $pdo = null;

    private const TABLES = [
        'customers' => ['Catalogos.Clientes', 'IDCliente', ['Nombre', 'RFC', 'CURP', 'TipoPersona', 'Pais', 'Estado', 'Ciudad', 'Direccion', 'CP', 'Telefonos', 'Celular', 'Fax', 'Email', 'RepresentanteLegal']],
        'sections' => ['Catalogos.Secciones', 'IDSeccion', ['IDDepartamento', 'Nombre', 'Descripcion']],
        'activities' => ['Catalogos.Actividades', 'IDActividad', ['IDSeccion', 'Nombre', 'Descripcion', 'Concluido']],
    ];

    public function __construct(private readonly ConnectionSettingsResolver $resolver) {}

    public function assertReady(): void
    {
        $statement = $this->pdo()->prepare('SELECT HAS_PERMS_BY_NAME(?, ?, ?)');
        foreach (self::TABLES as [$table]) {
            foreach (['SELECT', 'INSERT'] as $permission) {
                $statement->execute([$table, 'OBJECT', $permission]);
                if ((int) $statement->fetchColumn() !== 1) {
                    throw new ManagerControlException("La cuenta de catálogos no tiene permiso {$permission} en {$table}.");
                }
                $statement->closeCursor();
            }
        }
    }

    public function insert(string $kind, array $values): int
    {
        if (! isset(self::TABLES[$kind])) {
            throw new ManagerControlException('Catálogo no permitido.');
        }
        [$table, $idColumn, $fields] = self::TABLES[$kind];
        if (array_diff(array_keys($values), $fields) || array_diff($fields, array_keys($values))) {
            throw new ManagerControlException('Los campos del catálogo no coinciden con el esquema permitido.');
        }
        $columns = ['IDEmpresa', 'IDEjercicio', 'NombreUsuario', ...$fields, 'FechaCreacion', 'Baja'];
        $sql = 'INSERT INTO ['.str_replace('.', '].[', $table).'] ('.implode(', ', array_map(fn (string $column): string => "[{$column}]", $columns)).') '
            ."OUTPUT INSERTED.[{$idColumn}] VALUES (".implode(', ', array_fill(0, count($columns) - 2, '?')).', GETDATE(), 0)';
        $bindings = [(int) config('managercontrol.company_id'), (int) config('managercontrol.exercise_id'), 'CRM-GAA'];
        foreach ($fields as $field) {
            $bindings[] = $values[$field];
        }

        $pdo = $this->pdo();
        try {
            $pdo->beginTransaction();
            $statement = $pdo->prepare($sql);
            $statement->execute($bindings);
            $id = (int) $statement->fetchColumn();
            if ($id < 1) {
                throw new ManagerControlException('ManagerControl no devolvió el ID del catálogo nuevo.');
            }
            $pdo->commit();

            return $id;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($e instanceof ManagerControlException) {
                throw $e;
            }
            throw new ManagerControlException('No se pudo importar el catálogo en ManagerControl (código '.($e instanceof PDOException ? $e->getCode() : 'desconocido').').', 0, $e);
        }
    }

    private function pdo(): PDO
    {
        if ($this->pdo !== null) {
            return $this->pdo;
        }
        if (! config('managercontrol.catalog_write.enabled') || blank(config('managercontrol.catalog_write.username')) || blank(config('managercontrol.catalog_write.password'))) {
            throw new WriteDisabled('La importación de catálogos está desactivada o faltan sus credenciales.');
        }
        if (! extension_loaded('pdo_sqlsrv')) {
            throw new ManagerControlException('Falta la extensión pdo_sqlsrv.');
        }
        $settings = $this->resolver->resolve();
        $dsn = sprintf('sqlsrv:Server=%s;Database=%s;LoginTimeout=%d;TrustServerCertificate=%d;APP=CRM-GAA-Catalogos',
            $settings->server(), $settings->database, (int) config('managercontrol.login_timeout', 5), config('managercontrol.trust_server_certificate', true) ? 1 : 0);
        try {
            return $this->pdo = new PDO($dsn, (string) config('managercontrol.catalog_write.username'), (string) config('managercontrol.catalog_write.password'), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::SQLSRV_ATTR_QUERY_TIMEOUT => (int) config('managercontrol.query_timeout', 15),
            ]);
        } catch (PDOException $e) {
            throw new ManagerControlException('No se pudo conectar con la cuenta de catálogos (código '.$e->getCode().').');
        }
    }
}
