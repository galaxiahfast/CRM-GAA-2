<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class TimeEntry extends Model
{
    public const STATUS_IN_PROGRESS = 0;
    public const STATUS_PAUSED = 1;
    public const STATUS_FINISHED = 2;
    public const STATUS_AUTO_CLOSED = 3;

    public const STATUS_LABELS = [
        self::STATUS_IN_PROGRESS => 'En progreso',
        self::STATUS_PAUSED => 'Pausada',
        self::STATUS_FINISHED => 'Pausada',
        self::STATUS_AUTO_CLOSED => 'Cerrada automáticamente',
    ];

    protected $fillable = [
        'user_id',
        'customer_id',
        'sub_service_id',
        'role_id_snapshot',
        'job_position_id_snapshot',
        'physical_area_id_snapshot',
        'entry_date',
        'status',
        'sort_order',
        'total_duration_seconds',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'status' => 'integer',
        'sort_order' => 'integer',
        'total_duration_seconds' => 'integer',
    ];

    // Relaciones vivas actuales
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function subService()
    {
        return $this->belongsTo(SubService::class);
    }

    // Los "clics" del cronómetro
    public function intervals()
    {
        return $this->hasMany(TimeInterval::class);
    }

    // Historial de cambios manuales
    public function audits()
    {
        return $this->hasMany(TimeEntryAudit::class);
    }

    // Relaciones hacia los Snapshots (Fotografía estática del pasado)
    public function roleSnapshot()
    {
        return $this->belongsTo(Role::class, 'role_id_snapshot');
    }

    public function jobPositionSnapshot()
    {
        return $this->belongsTo(JobPosition::class, 'job_position_id_snapshot');
    }

    public function physicalAreaSnapshot()
    {
        return $this->belongsTo(PhysicalArea::class, 'physical_area_id_snapshot');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? 'Desconocido';
    }

    /**
     * Tiempo efectivo en segundos: suma de todos los intervalos cerrados más
     * el intervalo abierto contra el momento actual (ignora tiempos muertos).
     */
    public function calculateEffectiveSeconds(): int
    {
        return (int) $this->intervals->reduce(function (int $carry, TimeInterval $interval) {
            // ✅ Si no tiene ended_at, usar el momento actual
            $end = $interval->ended_at ?? now();
            $start = $interval->started_at;
            
            // ✅ Si ended_at es menor que started_at, usar abs() para obtener valor positivo
            // Esto soluciona el problema de intervalos invertidos
            $diff = abs($end->diffInSeconds($start));
            
            // ✅ Log solo si hay diferencia negativa (para depuración)
            if ($end->lt($start)) {
                Log::warning('Intervalo invertido detectado:', [
                    'entry_id' => $this->id,
                    'started_at' => $start,
                    'ended_at' => $end,
                    'diff_seconds' => $diff,
                    'formatted' => gmdate('H:i:s', $diff)
                ]);
            }
            
            return $carry + $diff;
        }, 0);
    }
}
