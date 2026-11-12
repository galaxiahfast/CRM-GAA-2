<?php

namespace App\Services\ManagerControl;

interface WriteConnection
{
    /** INSERT de UN registro; devuelve el id generado (columna identidad). */
    public function insert(string $sql, array $bindings): int;

    /** UPDATE parametrizado; devuelve cuántas filas cambió. */
    public function update(string $sql, array $bindings): int;

    /**
     * Revisa la cuenta de escritura SIN escribir nada: con qué login entra y qué permisos tiene sobre la tabla de horas.
     *
     * @return array{login: string, database: string, select: bool, insert: bool, update: bool, delete: bool, db_owner: bool}
     */
    public function diagnostics(): array;
}
