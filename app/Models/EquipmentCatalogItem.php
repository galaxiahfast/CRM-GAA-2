<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EquipmentCatalogItem extends Model
{
    protected $fillable = [
        'equipment_type',
        'brand',
        'model',
        'keywords',
    ];
}
