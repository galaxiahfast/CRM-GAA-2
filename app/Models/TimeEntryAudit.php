<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimeEntryAudit extends Model
{
    protected $fillable = [
        'time_entry_id',
        'admin_id',
        'old_values',
        'new_values',
        'reason'
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array'
    ];

    public function timeEntry()
    {
        return $this->belongsTo(TimeEntry::class);
    }

    // El administrador que realizó la modificación
    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}