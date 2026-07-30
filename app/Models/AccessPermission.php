<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AccessPermission extends Model
{
    protected $fillable = [
        'key',
        'name',
        'module',
        'description',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_access_permission')
            ->withTimestamps();
    }
}
