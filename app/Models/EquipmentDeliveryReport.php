<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipmentDeliveryReport extends Model
{
    use HasFactory;

    public const MOVEMENTS = [
        'recepcion' => 'Recepción para soporte',
        'entrega' => 'Entrega de equipo',
        'prestamo' => 'Préstamo de equipo',
        'compra' => 'Equipo de nueva compra',
    ];

    public const CONDITIONS = [
        'bueno' => 'Bueno',
        'con_detalles' => 'Con detalles',
        'danado' => 'Dañado',
    ];

    protected $fillable = [
        'folio',
        'request_token',
        'movement_type',
        'customer_id',
        'customer_name',
        'customer_contact',
        'delivered_by',
        'equipment_type',
        'brand',
        'model',
        'serial_number',
        'accessories',
        'physical_condition',
        'observations',
        'photo_path',
        'created_by',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function movementLabel(): string
    {
        return self::MOVEMENTS[$this->movement_type] ?? $this->movement_type;
    }

    public function conditionLabel(): string
    {
        return self::CONDITIONS[$this->physical_condition] ?? $this->physical_condition;
    }
}
