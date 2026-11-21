<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Notifications\SupportPasswordReset;
use App\Notifications\VerifyAccountEmail;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new SupportPasswordReset($token));
    }

    /**
     * La confirmación del correo solo se exige a las cuentas que entran con Google: quien ya tenía cuenta con
     * contraseña conserva su acceso como estaba, y quien se vincula con Google debe confirmar su correo real.
     */
    public function hasVerifiedEmail(): bool
    {
        return $this->google_id === null || $this->email_verified_at !== null;
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyAccountEmail);
    }

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
        'birth_year',
        'birth_date',
        'gender',
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
    /** Si la foto de perfil existe de verdad en el disco (si el archivo falta, las pantallas muestran las iniciales en vez de una imagen rota). */
    public function hasProfilePhotoFile(): bool
    {
        return filled($this->profile_photo_path)
            && \Illuminate\Support\Facades\Storage::disk((string) config('jetstream.profile_photo_disk', 'public'))->exists($this->profile_photo_path);
    }

    /** Ilustraciones que se usan cuando la persona no tiene foto (public/img/avatars), por género. */
    public const DEFAULT_AVATARS = [
        'hombre' => ['hombre-1', 'hombre-2'],
        'mujer' => ['mujer-1', 'mujer-2', 'mujer-3', 'mujer-4'],
    ];

    /** Nombres de mujer que no terminan en «a» y de hombre que sí (para deducir el género cuando no lo capturó). */
    private const FEMALE_NAMES = ['ABIGAIL', 'ARACELI', 'BEATRIZ', 'BELEN', 'CARMEN', 'CONSUELO', 'DOLORES', 'ELIZABETH', 'ESTHER', 'GUADALUPE', 'INES', 'ISABEL', 'IVONNE', 'JAZMIN', 'KAREN', 'LIZBETH', 'LIZET', 'LUZ', 'MABEL', 'MARIBEL', 'MARISOL', 'MERCEDES', 'MIRIAM', 'NOEMI', 'PILAR', 'RAQUEL', 'ROCIO', 'RUTH', 'SOLEDAD', 'SUSAN', 'YARETZI', 'ZOE', 'NAYELI', 'MAYDELI', 'SHARLY', 'ITZEL', 'ARELI', 'DENISSE', 'IRIS', 'MONSERRAT', 'ANAHI'];
    private const MALE_NAMES = ['JOSUE', 'BAUTISTA', 'ELIAS', 'MATIAS', 'NICOLAS', 'TOMAS', 'LUCAS', 'ANDRES', 'JONAS', 'ISAIAS', 'JEREMIAS', 'ZACARIAS', 'GARCIA', 'BORJA', 'SANTANA'];

    /** Género capturado o, si no lo puso, el que se deduce de su primer nombre (María José → mujer, José María → hombre). */
    public function avatarGender(): string
    {
        if (in_array($this->gender, ['hombre', 'mujer'], true)) {
            return $this->gender;
        }

        $first = (string) \Illuminate\Support\Str::of((string) $this->name)->ascii()->upper()->trim()->explode(' ')->first();
        if (in_array($first, self::FEMALE_NAMES, true)) {
            return 'mujer';
        }
        if (in_array($first, self::MALE_NAMES, true)) {
            return 'hombre';
        }

        return str_ends_with($first, 'A') ? 'mujer' : 'hombre';
    }

    /** Ilustración por defecto: siempre la misma para cada persona, según su género. */
    public function defaultAvatarUrl(): string
    {
        $options = self::DEFAULT_AVATARS[$this->avatarGender()];

        return asset('img/avatars/'.$options[((int) $this->id) % count($options)].'.png');
    }

    /** Foto que se muestra: la que subió (si el archivo existe) o la ilustración por defecto según su género. */
    public function avatarUrl(): string
    {
        return $this->hasProfilePhotoFile() ? $this->profile_photo_url : $this->defaultAvatarUrl();
    }

    /** Jetstream usa esto cuando no hay foto: en vez de las letras, la ilustración según el género. */
    protected function defaultProfilePhotoUrl(): string
    {
        return $this->defaultAvatarUrl();
    }

    /** Edad aproximada a partir del año de nacimiento (null si no lo capturó). */
    public function approximateAge(): ?int
    {
        return $this->birth_year ? max(0, (int) now()->year - (int) $this->birth_year) : null;
    }

    /**
     * Los nombres se muestran y se guardan siempre en MAYÚSCULAS (con acentos y ñ): «Dioni Colaborador» → «DIONI COLABORADOR».
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): ?string => $value === null ? null : mb_strtoupper($value, 'UTF-8'),
            set: fn (?string $value): ?string => $value === null ? null : mb_strtoupper(trim($value), 'UTF-8'),
        );
    }

    protected function lastName(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): ?string => $value === null ? null : mb_strtoupper($value, 'UTF-8'),
            set: fn (?string $value): ?string => $value === null ? null : mb_strtoupper(trim($value), 'UTF-8'),
        );
    }

    /** Minutos sin actividad tras los que alguien deja de estar en línea (la página avisa cada 30 s que sigue abierta). */
    public const ONLINE_MINUTES = 3;

    /** Cuentas que siempre están en línea: los agentes (bots). */
    public static function alwaysOnlineEmails(): array
    {
        return array_values(array_filter((array) config('support.always_online_emails', [])));
    }

    /**
     * En línea: los agentes (bots) siempre; los demás solo si tienen la web abierta (actividad en los últimos minutos,
     * registrada por TrackUserPresence). Al cerrar sesión se quita la marca y dejan de aparecer al momento.
     */
    public function scopeOnline($query)
    {
        return $query->where(fn ($q) => $q->where('last_seen_at', '>=', now()->subMinutes(self::ONLINE_MINUTES))
            ->orWhereIn('email', self::alwaysOnlineEmails()));
    }

    public function isOnline(): bool
    {
        return in_array($this->email, self::alwaysOnlineEmails(), true)
            || ($this->last_seen_at !== null && $this->last_seen_at->gte(now()->subMinutes(self::ONLINE_MINUTES)));
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'birth_date' => 'date',
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
