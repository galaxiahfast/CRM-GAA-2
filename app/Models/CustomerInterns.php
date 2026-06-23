<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerInterns extends Model
{
    protected $fillable = [
        'intern_id',
        'customer_id'
    ];
    
}
