<?php

namespace App\Services\ManagerControl;

interface ReadOnlyConnection
{
    /**
     * Ejecuta un SELECT con parámetros posicionales (?). Nunca recibe texto del usuario dentro del SQL.
     *
     * @param  list<scalar|null>  $bindings
     * @return list<array<string, mixed>>
     *
     * @throws ReadOnlyViolation|ManagerControlException
     */
    public function select(string $sql, array $bindings = []): array;

    /**
     * Verifica que la sesión sea de solo lectura. Devuelve datos de diagnóstico (sin secretos).
     *
     * @return array{engine: string, version: string, database: string, login: string, can_write: bool, write_roles: list<string>}
     */
    public function diagnostics(): array;
}
