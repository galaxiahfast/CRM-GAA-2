<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubService extends Model
{
    protected $fillable = [
        "sub_service",
        "service_id",
        "description",
        "unique_key"
    ];

}
