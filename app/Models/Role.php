<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = [
        'role',
        'description',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function accessPermissions()
    {
        return $this->belongsToMany(AccessPermission::class, 'role_access_permission')
            ->withTimestamps();
    }
}
