<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerService extends Model
{
    protected $fillable = [
        'customer_id',
        'sub_service_id',
    ];
}
