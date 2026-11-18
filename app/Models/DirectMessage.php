<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DirectMessage extends Model
{
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

    /** Mensajes entre dos personas (en cualquier sentido). */
    public function scopeBetween($query, int $a, int $b)
    {
        return $query->where(fn ($q) => $q->where('sender_id', $a)->where('recipient_id', $b))
            ->orWhere(fn ($q) => $q->where('sender_id', $b)->where('recipient_id', $a));
    }
}
