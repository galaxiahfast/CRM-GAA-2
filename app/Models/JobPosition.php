<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobPosition extends Model
{
    use HasFactory;

    public const PAYMENT_HOURLY = 'hourly';

    public const PAYMENT_FULL_TIME = 'full_time';

    protected $fillable = ['name', 'payment_type'];

    public static function paymentTypes(): array
    {
        return [self::PAYMENT_HOURLY, self::PAYMENT_FULL_TIME];
    }

    public function isHourly(): bool
    {
        return $this->payment_type === self::PAYMENT_HOURLY;
    }

    public function hierarchyRelations()
    {
        return $this->hasMany(UserHierarchyRelation::class);
    }

    // Historial de perfiles organizacionales que han tenido este puesto
    public function organizationalProfiles()
    {
        return $this->hasMany(UserOrganizationalProfile::class);
    }
}
