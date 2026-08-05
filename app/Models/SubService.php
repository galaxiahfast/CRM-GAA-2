<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubService extends Model
{
    /**
     * Claves estables usadas por los flujos especiales de clientes.
     * Estas actividades pueden editar su nombre o descripción, pero no deben
     * cambiar de categoría ni eliminarse mientras esos flujos dependan de ellas.
     */
    public const PROTECTED_CATALOG_KEYS = [
        'subservice_IDNC_1',
        'subservice_DECL_6',
    ];

    protected $fillable = [
        'sub_service',
        'service_id',
        'description',
        'unique_key',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function isProtectedCatalogEntry(): bool
    {
        return in_array((string) $this->unique_key, self::PROTECTED_CATALOG_KEYS, true);
    }
}
