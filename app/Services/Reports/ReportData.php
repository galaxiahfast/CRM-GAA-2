<?php

namespace App\Services\Reports;

use Illuminate\Support\Carbon;

/**
 * Representación de un reporte independiente del formato de salida.
 *
 * Se compone de metadatos (filtros aplicados, totales) y de una o más
 * secciones tabulares. Cualquier exportador puede consumirla, lo que permite
 * añadir nuevos formatos sin tocar el origen de los datos.
 */
class ReportData
{
    /**
     * @param  array<string, string>  $meta  Pares etiqueta => valor (periodo, filtros, totales).
     * @param  list<ReportSection>  $sections
     */
    public function __construct(
        public readonly string $title,
        public readonly string $filenameBase,
        public readonly array $meta = [],
        public readonly array $sections = [],
        public readonly ?Carbon $generatedAt = null,
    ) {}

    public function generatedAt(): Carbon
    {
        return $this->generatedAt ?? Carbon::now();
    }
}
