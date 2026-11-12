<?php

namespace App\Services\ManagerControl;

interface CatalogInsertConnection
{
    public function assertReady(): void;

    /** @param array<string, scalar|null> $values */
    public function insert(string $kind, array $values): int;
}
