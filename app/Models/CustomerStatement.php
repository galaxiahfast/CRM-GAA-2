<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerStatement extends Model
{
    protected $fillable = [
        "customer_id",
        "statement_id"
    ];
}
