<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    public const PROFILE_ADMINISTRATOR = 'administrator';

    public const PROFILE_AUXILIARY = 'auxiliary';

    public const PROFILE_CUSTOM = 'custom';

    protected $fillable = [
        'role',
        'description',
        'permission_profile',
    ];

    public static function permissionProfiles(): array
    {
        return [
            self::PROFILE_ADMINISTRATOR,
            self::PROFILE_AUXILIARY,
            self::PROFILE_CUSTOM,
        ];
    }

    public function usesPermissionProfile(string $profile): bool
    {
        return $this->permission_profile === $profile;
    }

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
