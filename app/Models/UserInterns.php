<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserInterns extends Model
{
    protected $fillable = [
        "intern_id",
        "created_by"
    ];

}
