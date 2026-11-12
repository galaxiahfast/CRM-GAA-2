<?php

namespace App\Services\ManagerControl;

interface WriteConnection
{
    /** INSERT de UN registro; devuelve el id generado (columna identidad). */
    public function insert(string $sql, array $bindings): int;

    /** UPDATE parametrizado; devuelve cuántas filas cambió. */
    public function update(string $sql, array $bindings): int;
}
