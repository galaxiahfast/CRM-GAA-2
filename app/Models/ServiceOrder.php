<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceOrder extends Model
{
    use HasFactory;

    protected $table = 'ordenes_servicio';

    public const TYPES = [
        'recepcion' => 'Recepción para soporte',
        'entrega' => 'Entrega directa',
        'prestamo' => 'Préstamo de equipo',
        'compra' => 'Compra de equipo',
    ];

    public const STATUSES = [
        'recibido' => 'Recibido',
        'en_diagnostico' => 'En diagnóstico',
        'en_reparacion' => 'En reparación',
        'listo' => 'Listo para entregar',
        'entregado' => 'Entregado',
        'prestado' => 'Prestado',
        'devuelto' => 'Devuelto',
        'vendido' => 'Vendido',
        'cancelado' => 'Cancelado',
    ];

    public const CONDITIONS = EquipmentDeliveryReport::CONDITIONS;

    protected $fillable = [
        'folio', 'request_token', 'tipo', 'cliente_id', 'contacto_id', 'cliente_nombre', 'contacto',
        'quien_entrega', 'tipo_equipo', 'equipo_marca', 'equipo_modelo', 'equipo_serie', 'accesorios',
        'estado_fisico', 'falla_reportada', 'diagnostico', 'reparacion_realizada', 'estado',
        'fecha_recepcion', 'fecha_entrega_prometida', 'fecha_entrega_real', 'fecha_limite_devolucion',
        'fecha_devolucion', 'firma_recepcion', 'firma_entrega', 'observaciones', 'precio', 'forma_pago',
        'garantia', 'foto_path', 'ocr_raw_text', 'creado_por', 'legacy_report_id',
    ];

    protected $casts = [
        'accesorios' => 'array',
        'fecha_recepcion' => 'datetime',
        'fecha_entrega_prometida' => 'datetime',
        'fecha_entrega_real' => 'datetime',
        'fecha_limite_devolucion' => 'datetime',
        'fecha_devolucion' => 'datetime',
        'firma_recepcion' => 'boolean',
        'firma_entrega' => 'boolean',
        'precio' => 'decimal:2',
    ];

    public function movimientos(): HasMany
    {
        return $this->hasMany(ServiceOrderMovement::class, 'orden_id')->orderBy('fecha');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'cliente_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function movementLabel(): string
    {
        return self::TYPES[$this->tipo] ?? $this->tipo;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->estado] ?? $this->estado;
    }

    public function conditionLabel(): string
    {
        return self::CONDITIONS[$this->estado_fisico] ?? $this->estado_fisico;
    }

    public function documentType(): string
    {
        return $this->movimientos->last()?->tipo_movimiento ?: $this->tipo;
    }

    public function getMovementTypeAttribute(): string
    {
        return $this->tipo;
    }

    public function getCustomerNameAttribute(): string
    {
        return $this->cliente_nombre;
    }

    public function getCustomerContactAttribute(): string
    {
        return $this->contacto;
    }

    public function getDeliveredByAttribute(): string
    {
        return $this->quien_entrega;
    }

    public function getEquipmentTypeAttribute(): string
    {
        return $this->tipo_equipo;
    }

    public function getBrandAttribute(): string
    {
        return $this->equipo_marca;
    }

    public function getModelAttribute(): string
    {
        return $this->equipo_modelo;
    }

    public function getSerialNumberAttribute(): string
    {
        return $this->equipo_serie;
    }

    public function getAccessoriesAttribute(): string
    {
        return implode(', ', $this->accesorios ?? []);
    }

    public function getPhysicalConditionAttribute(): string
    {
        return $this->estado_fisico;
    }

    public function getObservationsAttribute(): string
    {
        return (string) $this->observaciones;
    }

    public function getPhotoPathAttribute(): ?string
    {
        return $this->foto_path;
    }

    public function getCreatedByAttribute(): ?int
    {
        return $this->creado_por;
    }
}
