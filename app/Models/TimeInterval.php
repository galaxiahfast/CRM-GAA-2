<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimeInterval extends Model
{
    protected $fillable = [
        'time_entry_id',
        'started_at',
        'ended_at'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime'
    ];

    public function timeEntry()
    {
        return $this->belongsTo(TimeEntry::class);
    }
}