<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PermissionGroup extends Model
{
    protected $fillable = ['name', 'description', 'is_system'];

    protected function casts(): array
    {
        return ['is_system' => 'boolean'];
    }

    public function permissions()
    {
        return $this->belongsToMany(AccessPermission::class, 'permission_group_access_permission')->withTimestamps();
    }

    public function roles()
    {
        return $this->hasMany(Role::class);
    }
}
