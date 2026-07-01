<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserOrganizationalProfile extends Model
{
    protected $fillable = [
        'user_id',
        'job_position_id',
        'physical_area_id',
        'hourly_rate',       // 👈 Agregado: Tarifa por hora configurable
        'food_allowance',    // 👈 Agregado: Apoyo de comida diario configurable
        'valid_from',
        'valid_to',
        'is_active'
    ];

    protected $casts = [
        'valid_from' => 'date',
        'valid_to' => 'date',
        'is_active' => 'boolean',
        'hourly_rate' => 'decimal:2',    // 👈 Agregado para formatear siempre a dos decimales
        'food_allowance' => 'decimal:2', // 👈 Agregado para formatear siempre a dos decimales
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function jobPosition()
    {
        return $this->belongsTo(JobPosition::class);
    }

    public function physicalArea()
    {
        return $this->belongsTo(PhysicalArea::class);
    }
}