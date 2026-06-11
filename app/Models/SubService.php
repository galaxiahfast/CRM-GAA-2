<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubService extends Model
{
    protected $fillable = [
        "sub_service",
        "service_id",
        "description",
    ];
    public function timeEntries()
    {
        return $this->hasMany(TimeEntry::class);
    }
}
