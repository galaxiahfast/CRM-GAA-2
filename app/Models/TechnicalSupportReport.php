<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TechnicalSupportReport extends Model
{
    protected $fillable = [
        'folio',
        'issued_at',
        'responsible',
        'service_recipient',
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

    public function movements(): HasMany
    {
        return $this->hasMany(TechnicalSupportReportMovement::class);
    }

    public function getRouteKeyName(): string
    {
        return 'folio';
    }
}
