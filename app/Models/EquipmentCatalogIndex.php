<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EquipmentCatalogIndex extends Model
{
    protected $table = 'equipment_catalog_index';

    protected $fillable = [
        'normalized_key', 'brand', 'model', 'equipment_type', 'serial_number',
        'typical_accessories', 'aliases', 'usage_count', 'search_text',
    ];

    protected $casts = [
        'typical_accessories' => 'array',
        'aliases' => 'array',
        'usage_count' => 'integer',
    ];
}
