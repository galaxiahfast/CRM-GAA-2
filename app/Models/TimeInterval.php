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

    protected static function booted(): void
    {
        // Cualquier cambio en el tiempo del Cronómetro se manda enseguida a ManagerControl (ver TimerSync::requestFor)
        $request = fn (TimeInterval $interval) => \App\Services\ManagerControl\TimerSync::requestFor(
            TimeEntry::withoutGlobalScopes()->whereKey($interval->time_entry_id)->value('user_id')
        );
        static::saved($request);
        static::deleted($request);
    }

    public function timeEntry()
    {
        return $this->belongsTo(TimeEntry::class);
    }
}