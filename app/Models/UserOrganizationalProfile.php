<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserOrganizationalProfile extends Model
{
    protected $fillable = [
        'user_id',
        'job_position_id',
        'physical_area_id',
        'valid_from',
        'valid_to',
        'is_active'
    ];

    protected $casts = [
        'valid_from' => 'date',
        'valid_to' => 'date',
        'is_active' => 'boolean',
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