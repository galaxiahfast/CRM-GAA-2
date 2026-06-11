<?php

namespace App\Services\Reports\Exporters;

use App\Services\Reports\ReportData;

/**
 * Contrato común de todos los formatos de exportación. Añadir un formato
 * nuevo se reduce a implementar esta interfaz y registrarla en el manager.
 */
interface ReportExporter
{
    /** Clave del formato usada por la UI (p. ej. "csv"). */
    public function format(): string;

    /** Extensión del archivo generado (sin punto). */
    public function extension(): string;

    /** Content-Type HTTP de la descarga. */
    public function contentType(): string;

    /** Genera el contenido del archivo a partir del reporte. */
    public function render(ReportData $data): string;
}
