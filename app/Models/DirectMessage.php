<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DirectMessage extends Model
{
    use Prunable;

    /** Los chats individuales se conservan una semana completa; no se reinician cada día. */
    public const RETENTION_DAYS = 7;

    protected $fillable = ['sender_id', 'recipient_id', 'body', 'read_at'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    /** Mensajes dentro del periodo de conservación. */
    public function scopeRetained($query)
    {
        return $query->where('created_at', '>=', now()->subDays(self::RETENTION_DAYS));
    }

    /** Lo que `model:prune` borra: mensajes con más de una semana. */
    public function prunable(): Builder
    {
        return static::query()->where('created_at', '<', now()->subDays(self::RETENTION_DAYS));
    }

    /** Mensajes entre dos personas (en cualquier sentido). */
    public function scopeBetween($query, int $a, int $b)
    {
        return $query->where(fn ($q) => $q->where('sender_id', $a)->where('recipient_id', $b))
            ->orWhere(fn ($q) => $q->where('sender_id', $b)->where('recipient_id', $a));
    }
}
