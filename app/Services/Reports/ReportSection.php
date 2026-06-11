<?php

namespace App\Services\Reports;

/**
 * Una sección tabular de un reporte (un título + cabeceras + filas).
 *
 * Estructura genérica y agnóstica al formato: cada exportador (CSV, TXT,
 * PDF) sabe cómo renderizarla.
 */
class ReportSection
{
    /**
     * @param  list<string>  $columns
     * @param  list<list<string|int>>  $rows
     * @param  list<array{date: string, rows: list<list<string|int>>}>|null  $dayGroups
     */
    public function __construct(
        public readonly string $title,
        public readonly array $columns,
        public readonly array $rows,
        public readonly ?array $dayGroups = null,
    ) {}
}
