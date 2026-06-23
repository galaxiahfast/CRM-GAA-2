<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = [
        'service',
        'description',
    ];

    public function subServices()
    {
        return $this->hasMany(SubService::class);
    }
}
