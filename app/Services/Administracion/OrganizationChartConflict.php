<?php

namespace App\Services\Administracion;

use RuntimeException;

/**
 * El organigrama cambió desde que la pantalla lo dibujó (otra persona movió o quitó a alguien): el cambio pedido ya no
 * corresponde a lo que se veía y no se aplica, para no deshacer en silencio el trabajo de otro.
 */
class OrganizationChartConflict extends RuntimeException
{
}
