<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PhysicalArea extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    // Historial de perfiles organizacionales ubicados en esta área
    public function organizationalProfiles()
    {
        return $this->hasMany(UserOrganizationalProfile::class);
    }
}