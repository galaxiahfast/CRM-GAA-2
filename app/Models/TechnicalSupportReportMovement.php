<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TechnicalSupportReportMovement extends Model
{
    public const ACTION_CREATED = 'created';

    public const ACTION_DELETED = 'deleted';

    public const ACTION_RESTORED = 'restored';

    public const ACTION_PURGED = 'purged';

    protected $fillable = [
        'technical_support_report_id',
        'folio',
        'action',
        'user_id',
        'user_name',
        'user_email',
        'report_snapshot',
        'occurred_at',
    ];

    protected $casts = [
        'report_snapshot' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(TechnicalSupportReport::class, 'technical_support_report_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
