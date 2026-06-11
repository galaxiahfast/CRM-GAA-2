<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerAccountant extends Model
{
    protected $fillable = [
        "customer_id",
        "accountant_id",
        "status"
    ];
}
