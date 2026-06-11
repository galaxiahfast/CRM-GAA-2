<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerFile extends Model
{
    protected $fillable = [
        'customer_id',
        'user_id',
        'sub_service_id',
        'file_path',
        'original_name',
        'file_type',
        'declaration_type',
        'statement_id',
        'state_id',
        'upload_period'
    ];
}
