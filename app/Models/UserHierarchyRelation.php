<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserHierarchyRelation extends Model
{
    protected $fillable = [
        'subordinate_id',
        'superior_id',
        'job_position_id',
        'physical_area_id',
    ];

    public function subordinate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subordinate_id');
    }

    public function superior(): BelongsTo
    {
        return $this->belongsTo(User::class, 'superior_id');
    }

    public function jobPosition(): BelongsTo
    {
        return $this->belongsTo(JobPosition::class);
    }

    public function physicalArea(): BelongsTo
    {
        return $this->belongsTo(PhysicalArea::class);
    }
}
