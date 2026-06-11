<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class JobPosition extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    // Historial de perfiles organizacionales que han tenido este puesto
    public function organizationalProfiles()
    {
        return $this->hasMany(UserOrganizationalProfile::class);
    }
}