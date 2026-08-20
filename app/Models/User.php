<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;

    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'last_name',
        'email',
        'password',
        'role_id',
        'employee_id', // Permitir guardar el ID vinculado al checador Hikvision
        'profile_cover_path',
        'profile_description',
        'instagram_url',
        'facebook_url',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_support_help_recipient' => 'boolean',
        ];
    }

    public function customers()
    {
        return $this->belongsToMany(Customer::class, 'customer_accountants', 'accountant_id', 'customer_id')
            ->withTimestamps();
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }

    public function interns()
    {
        return $this->belongsToMany(User::class, 'user_interns', 'intern_id', 'created_by');
    }

    /**
     * El rol Administrador está excluido del registro operativo de tiempos
     * (reglas 4.1, 8.8): solo supervisa, reporta y corrige.
     */
    public function isAdmin(): bool
    {
        // Evaluamos tanto por el ID 1 como por el nombre del rol en tu base de datos
        $roleName = optional($this->role)->role ?? optional($this->role)->name;

        return $this->role?->usesPermissionProfile(Role::PROFILE_ADMINISTRATOR)
            || (int) $this->role_id === 1
            || $roleName === 'Administrador';
    }

    /**
     * Obtener el perfil organizacional activo actualmente.
     * Permite acceder a 'hourly_rate' y 'food_allowance'.
     */
    public function activeOrganizationalProfile()
    {
        return $this->hasOne(UserOrganizationalProfile::class, 'user_id')->where('is_active', true);
    }

    /**
     * Historial completo de puestos y áreas del usuario (SCD Tipo 2)
     */
    public function organizationalProfiles()
    {
        return $this->hasMany(UserOrganizationalProfile::class, 'user_id');
    }

    /**
     * Todas las cabeceras de tiempo que ha registrado este colaborador
     */
    public function timeEntries()
    {
        return $this->hasMany(TimeEntry::class, 'user_id');
    }

    /**
     * Relaciones jerárquicas donde este usuario es subordinado.
     */
    public function hierarchyRelationsAsSubordinate()
    {
        return $this->hasMany(UserHierarchyRelation::class, 'subordinate_id');
    }

    /**
     * Relaciones jerárquicas donde este usuario es superior.
     */
    public function hierarchyRelationsAsSuperior()
    {
        return $this->hasMany(UserHierarchyRelation::class, 'superior_id');
    }

    /**
     * Superiores directos (jefes) del usuario.
     */
    public function superiors()
    {
        return $this->belongsToMany(
            User::class,
            'user_hierarchy_relations',
            'subordinate_id',
            'superior_id'
        )->withPivot(['job_position_id', 'physical_area_id'])
            ->withTimestamps();
    }

    /**
     * Subordinados directos del usuario.
     */
    public function subordinates()
    {
        return $this->belongsToMany(
            User::class,
            'user_hierarchy_relations',
            'superior_id',
            'subordinate_id'
        )->withPivot(['job_position_id', 'physical_area_id'])
            ->withTimestamps();
    }

    public function following(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_follows', 'follower_id', 'followed_id')->withTimestamps();
    }

    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_follows', 'followed_id', 'follower_id')->withTimestamps();
    }

    public function friends()
    {
        $friendIds = Friendship::query()
            ->where('status', Friendship::ACCEPTED)
            ->where(fn ($query) => $query->where('requester_id', $this->id)->orWhere('addressee_id', $this->id))
            ->get(['requester_id', 'addressee_id'])
            ->map(fn (Friendship $friendship) => $friendship->requester_id === $this->id
                ? $friendship->addressee_id
                : $friendship->requester_id);

        return static::query()->whereKey($friendIds);
    }
}
