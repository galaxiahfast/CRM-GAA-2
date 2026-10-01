<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceOrderMovement extends Model
{
    protected $table = 'movimientos_orden';

    protected $fillable = ['orden_id', 'tipo_movimiento', 'fecha', 'usuario_id', 'notas', 'evidencia'];

    protected $casts = [
        'fecha' => 'datetime',
        'evidencia' => 'array',
    ];

    public function orden(): BelongsTo
    {
        return $this->belongsTo(ServiceOrder::class, 'orden_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
