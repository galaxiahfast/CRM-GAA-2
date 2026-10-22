<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TechnicalSupportReport extends Model
{
    protected $fillable = [
        'folio',
        'issued_at',
        'responsible',
        'supports',
        'details',
        'created_by',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'supports' => 'array',
        'details' => 'array',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getRouteKeyName(): string
    {
        return 'folio';
    }
}
