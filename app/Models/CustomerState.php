<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerState extends Model
{
    protected $fillable = [
        "state_id",
        "customer_id"
    ];
}
