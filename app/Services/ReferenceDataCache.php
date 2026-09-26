<?php

namespace App\Services;

use App\Models\AccessPermission;
use App\Models\Customer;
use App\Models\JobPosition;
use App\Models\PermissionGroup;
use App\Models\PhysicalArea;
use App\Models\Role;
use App\Models\Service;
use App\Models\SubService;
use App\Models\User;
use App\Models\UserHierarchyRelation;
use App\Models\UserInterns;
use App\Services\Authorization\PermissionAccessService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ReferenceDataCache
{
    private const ADMINISTRATION_KEY = 'reference-data:administration:v1';

    private const TIME_CONTROL_KEY = 'reference-data:time-control:v1';

    private const EMPLOYEE_SUGGESTIONS_KEY = 'reference-data:employee-suggestions:v1';

    private const ORGANIZATION_DASHBOARD_KEY = 'reference-data:organization-dashboard:v1';

    private const ORGANIZATION_DIRECTORY_KEY = 'reference-data:organization-directory:v2';

    private const MANAGEABLE_USERS_KEY = 'administration:manageable-users:v1';

    private const CUSTOMER_CATALOG_KEY = 'administration:customer-catalog:v1';

    private const ACTIVITY_CATALOG_KEY = 'administration:activity-catalog:v1';

    private const PERMISSION_CATALOG_KEY = 'administration:permission-catalog:v1';

    private const ASSIGNMENT_CATALOG_KEY = 'administration:assignment-catalog:v1';

    /** @return array<string, mixed> */
    public function administration(): array
    {
        return Cache::remember(self::ADMINISTRATION_KEY, now()->addMinutes(10), function (): array {
            $permissions = app(PermissionAccessService::class);

            return [
                'physicalAreas' => PhysicalArea::orderBy('name')->get(['id', 'name']),
                'jobPositions' => JobPosition::orderBy('name')->get(['id', 'name', 'payment_type']),
                'roles' => Role::orderBy('role')->get(['id', 'role']),
                'basePermissionProfiles' => collect([
                    'Administrador' => ['profile' => Role::PROFILE_ADMINISTRATOR, 'label' => 'Acceso administrativo'],
                    'Auxiliar' => ['profile' => Role::PROFILE_AUXILIARY, 'label' => 'Acceso operativo'],
                ])->map(function (array $definition) use ($permissions): array {
                    $profile = config('access-permissions.profiles.'.$definition['profile'], []);
                    $keys = $permissions->permissionKeysForProfile($definition['profile']);

                    return [
                        'label' => $definition['label'],
                        'description' => $profile['description'] ?? '',
                        'permissions' => AccessPermission::query()
                            ->active()
                            ->whereIn('key', $keys)
                            ->orderBy('sort_order')
                            ->pluck('name')
                            ->all(),
                    ];
                })->all(),
            ];
        });
    }

    /** @return array{customers: \Illuminate\Support\Collection<int, array{id: int, search_name: string}>, subServices: \Illuminate\Support\Collection<int, array{id: int, search_name: string}>} */
    public function timeControl(): array
    {
        return Cache::remember(self::TIME_CONTROL_KEY, now()->addMinutes(10), fn (): array => [
            'customers' => Customer::query()
                ->whereNull('deleted_at')
                ->orderBy('name')
                ->get(['id', 'name', 'last_name'])
                ->map(fn (Customer $customer): array => [
                    'id' => (int) $customer->id,
                    'search_name' => mb_strtolower(trim($customer->name.' '.$customer->last_name)),
                ]),
            'subServices' => SubService::query()
                ->orderBy('sub_service')
                ->get(['id', 'sub_service'])
                ->map(fn (SubService $subService): array => [
                    'id' => (int) $subService->id,
                    'search_name' => mb_strtolower(trim($subService->sub_service)),
                ]),
        ]);
    }

    public function employeeSuggestions(): mixed
    {
        return Cache::remember(self::EMPLOYEE_SUGGESTIONS_KEY, now()->addMinutes(10), fn () => DB::table('control_de_horas')
            ->select('employeeID')
            ->selectRaw('MAX(personName) as personName')
            ->whereNotNull('employeeID')
            ->where('employeeID', '<>', '')
            ->groupBy('employeeID')
            ->orderBy('employeeID')
            ->get());
    }

    /** @return array<string, mixed> */
    public function organizationDashboard(): array
    {
        return Cache::remember(self::ORGANIZATION_DASHBOARD_KEY, now()->addSeconds(30), fn (): array => [
            'onlineUserIds' => DB::table((string) config('session.table', 'sessions'))
                ->whereNotNull('user_id')
                ->where('last_activity', '>=', now()->subMinutes(2)->timestamp)
                ->distinct()
                ->pluck('user_id')
                ->map(static fn ($userId): int => (int) $userId),
            'recentUsers' => User::query()
                ->latest('created_at')
                ->limit(4)
                ->get(['id', 'name', 'last_name', 'created_at']),
            'areaUserCounts' => DB::table('user_organizational_profiles as profiles')
                ->join('physical_areas as areas', 'areas.id', '=', 'profiles.physical_area_id')
                ->where('profiles.is_active', true)
                ->select('areas.name', DB::raw('COUNT(DISTINCT profiles.user_id) as users_count'))
                ->groupBy('areas.id', 'areas.name')
                ->orderByDesc('users_count')
                ->limit(3)
                ->get(),
            'roleUserCounts' => Role::query()
                ->withCount('users')
                ->orderByDesc('users_count')
                ->orderBy('role')
                ->limit(4)
                ->get(['id', 'role']),
            'positionUserCounts' => DB::table('job_positions as positions')
                ->leftJoin('user_organizational_profiles as profiles', function ($join): void {
                    $join->on('profiles.job_position_id', '=', 'positions.id')
                        ->where('profiles.is_active', true);
                })
                ->select('positions.id', 'positions.name', DB::raw('COUNT(DISTINCT profiles.user_id) as users_count'))
                ->groupBy('positions.id', 'positions.name')
                ->orderByDesc('users_count')
                ->orderBy('positions.name')
                ->limit(4)
                ->get(),
            'customers' => DB::table('customers')
                ->whereNull('deleted_at')
                ->latest('created_at')
                ->limit(4)
                ->get(['id', 'name', 'last_name']),
            'customerCount' => DB::table('customers')->whereNull('deleted_at')->count(),
            'activities' => DB::table('sub_services')
                ->orderBy('sub_service')
                ->limit(4)
                ->get(['id', 'sub_service']),
            'activityCount' => DB::table('sub_services')->count(),
            'assignmentCounts' => [
                'relations' => UserHierarchyRelation::query()->count(),
                'interns' => UserInterns::query()->count(),
            ],
        ]);
    }

    /** @return array<string, array{label: string, description: string, items: array<int, array<string, mixed>>}> */
    public function organizationDirectory(): array
    {
        return Cache::remember(self::ORGANIZATION_DIRECTORY_KEY, now()->addMinutes(10), function (): array {
            $users = User::query()
                ->with([
                    'role:id,role',
                    'activeOrganizationalProfile.jobPosition:id,name,payment_type',
                    'activeOrganizationalProfile.physicalArea:id,name',
                ])
                ->orderBy('name')
                ->orderBy('last_name')
                ->get(['id', 'name', 'last_name', 'email', 'employee_id', 'role_id']);

            $roles = Role::query()
                ->with('permissionGroup:id,name')
                ->withCount('users')
                ->orderBy('role')
                ->get();

            $positions = JobPosition::query()
                ->withCount(['organizationalProfiles as active_users_count' => fn ($query) => $query->where('is_active', true)])
                ->orderBy('name')
                ->get();

            $areas = PhysicalArea::query()
                ->withCount([
                    'organizationalProfiles as active_users_count' => fn ($query) => $query->where('is_active', true),
                    'hierarchyRelations as relations_count',
                ])
                ->orderBy('name')
                ->get();

            $permissionGroups = PermissionGroup::query()
                ->withCount(['permissions', 'roles'])
                ->orderByDesc('is_system')
                ->orderBy('name')
                ->get();

            $customers = Customer::query()
                ->whereNull('deleted_at')
                ->with([
                    'accountants:id,name,last_name',
                    'interns:id,name,last_name',
                    'services:id,sub_service',
                ])
                ->orderBy('name')
                ->orderBy('last_name')
                ->get();

            $activities = SubService::query()
                ->with('service:id,service')
                ->orderBy('sub_service')
                ->get();

            $profileLabels = [
                Role::PROFILE_ADMINISTRATOR => 'ADMINISTRADOR',
                Role::PROFILE_AUXILIARY => 'AUXILIAR',
                Role::PROFILE_CUSTOM => 'PERSONALIZADO',
            ];

            return [
                'users' => [
                    'label' => 'Usuarios',
                    'description' => 'Directorio de colaboradores y su perfil organizacional.',
                    'items' => $users->map(function (User $user): array {
                        $profile = $user->activeOrganizationalProfile;

                        return [
                            'id' => (int) $user->id,
                            'title' => mb_strtoupper(trim($user->name.' '.$user->last_name)),
                            'subtitle' => mb_strtoupper((string) $user->email),
                            'details' => [
                                ['label' => 'Rol', 'value' => mb_strtoupper($user->role?->role ?? 'SIN ROL')],
                                ['label' => 'Puesto', 'value' => mb_strtoupper($profile?->jobPosition?->name ?? 'SIN PUESTO')],
                                ['label' => 'Área', 'value' => mb_strtoupper($profile?->physicalArea?->name ?? 'SIN ÁREA')],
                                ['label' => 'Reloj', 'value' => mb_strtoupper((string) ($user->employee_id ?: 'SIN VINCULAR'))],
                            ],
                        ];
                    })->values()->all(),
                ],
                'roles' => [
                    'label' => 'Roles',
                    'description' => 'Perfiles asignables a los colaboradores.',
                    'items' => $roles->map(fn (Role $role): array => [
                        'id' => (int) $role->id,
                        'title' => mb_strtoupper($role->role),
                        'subtitle' => mb_strtoupper((string) ($role->description ?: 'ROL ORGANIZACIONAL')),
                        'details' => [
                            ['label' => 'Usuarios', 'value' => (string) $role->users_count],
                            ['label' => 'Grupo de permisos', 'value' => mb_strtoupper($role->permissionGroup?->name ?? 'SIN GRUPO')],
                            ['label' => 'Perfil', 'value' => $profileLabels[$role->permission_profile] ?? 'PERSONALIZADO'],
                        ],
                    ])->values()->all(),
                ],
                'positions' => [
                    'label' => 'Puestos',
                    'description' => 'Posiciones operativas y modalidad de compensación.',
                    'items' => $positions->map(fn (JobPosition $position): array => [
                        'id' => (int) $position->id,
                        'title' => mb_strtoupper($position->name),
                        'subtitle' => $position->payment_type === JobPosition::PAYMENT_HOURLY ? 'PAGO POR HORA' : 'TIEMPO COMPLETO',
                        'details' => [
                            ['label' => 'Colaboradores activos', 'value' => (string) $position->active_users_count],
                            ['label' => 'Modalidad', 'value' => $position->payment_type === JobPosition::PAYMENT_HOURLY ? 'POR HORA' : 'TIEMPO COMPLETO'],
                        ],
                    ])->values()->all(),
                ],
                'areas' => [
                    'label' => 'Áreas',
                    'description' => 'Unidades y departamentos de la organización.',
                    'items' => $areas->map(fn (PhysicalArea $area): array => [
                        'id' => (int) $area->id,
                        'title' => mb_strtoupper($area->name),
                        'subtitle' => 'UNIDAD ORGANIZACIONAL',
                        'details' => [
                            ['label' => 'Colaboradores activos', 'value' => (string) $area->active_users_count],
                            ['label' => 'Relaciones', 'value' => (string) $area->relations_count],
                        ],
                    ])->values()->all(),
                ],
                'permissions' => [
                    'label' => 'Permisos',
                    'description' => 'Grupos de acceso disponibles para los roles.',
                    'items' => $permissionGroups->map(fn (PermissionGroup $group): array => [
                        'id' => (int) $group->id,
                        'title' => mb_strtoupper($group->name),
                        'subtitle' => mb_strtoupper((string) ($group->description ?: 'GRUPO DE PERMISOS')),
                        'details' => [
                            ['label' => 'Permisos', 'value' => (string) $group->permissions_count],
                            ['label' => 'Roles vinculados', 'value' => (string) $group->roles_count],
                            ['label' => 'Tipo', 'value' => $group->is_system ? 'DEL SISTEMA' : 'PERSONALIZADO'],
                        ],
                    ])->values()->all(),
                ],
                'customers' => [
                    'label' => 'Clientes',
                    'description' => 'Directorio comercial, responsables y servicios activos.',
                    'items' => $customers->map(fn (Customer $customer): array => [
                        'id' => (int) $customer->id,
                        'title' => mb_strtoupper(trim($customer->name.' '.$customer->last_name.' '.$customer->maternal_last_name)),
                        'subtitle' => mb_strtoupper((string) ($customer->email ?: 'SIN CORREO REGISTRADO')),
                        'details' => [
                            ['label' => 'RFC', 'value' => mb_strtoupper((string) ($customer->rfc ?: 'SIN RFC'))],
                            ['label' => 'Teléfono', 'value' => trim(($customer->codePhone ? '+'.$customer->codePhone.' ' : '').($customer->phone ?: 'SIN TELÉFONO'))],
                            ['label' => 'Responsables', 'value' => $customer->accountants->map(fn (User $user) => mb_strtoupper(trim($user->name.' '.$user->last_name)))->implode(', ') ?: 'SIN ASIGNAR'],
                            ['label' => 'Servicios', 'value' => (string) $customer->services->count()],
                        ],
                    ])->values()->all(),
                ],
                'assignments' => [
                    'label' => 'Asignaciones',
                    'description' => 'Responsables y auxiliares vinculados a cada cliente.',
                    'items' => $customers
                        ->filter(fn (Customer $customer): bool => $customer->accountants->isNotEmpty() || $customer->interns->isNotEmpty())
                        ->map(fn (Customer $customer): array => [
                            'id' => (int) $customer->id,
                            'title' => mb_strtoupper(trim($customer->name.' '.$customer->last_name.' '.$customer->maternal_last_name)),
                            'subtitle' => 'ASIGNACIÓN DE CLIENTE',
                            'details' => [
                                ['label' => 'Responsables', 'value' => $customer->accountants->map(fn (User $user) => mb_strtoupper(trim($user->name.' '.$user->last_name)))->implode(', ') ?: 'SIN RESPONSABLE'],
                                ['label' => 'Auxiliares', 'value' => $customer->interns->map(fn (User $user) => mb_strtoupper(trim($user->name.' '.$user->last_name)))->implode(', ') ?: 'SIN AUXILIARES'],
                                ['label' => 'Servicios', 'value' => (string) $customer->services->count()],
                            ],
                        ])->values()->all(),
                ],
                'activities' => [
                    'label' => 'Actividades',
                    'description' => 'Catálogo utilizado en el registro de horas.',
                    'items' => $activities->map(fn (SubService $activity): array => [
                        'id' => (int) $activity->id,
                        'title' => mb_strtoupper($activity->sub_service),
                        'subtitle' => mb_strtoupper((string) ($activity->description ?: 'ACTIVIDAD OPERATIVA')),
                        'details' => [
                            ['label' => 'Categoría', 'value' => mb_strtoupper($activity->service?->service ?? 'SIN CATEGORÍA')],
                            ['label' => 'Clave', 'value' => mb_strtoupper((string) ($activity->unique_key ?: 'SIN CLAVE'))],
                        ],
                    ])->values()->all(),
                ],
            ];
        });
    }

    public function manageableUsers(): mixed
    {
        return Cache::remember(self::MANAGEABLE_USERS_KEY, now()->addMinutes(10), fn () => User::query()
            ->orderBy('name')
            ->orderBy('last_name')
            ->get(['id', 'name', 'last_name', 'email']));
    }

    /** @return array<string, mixed> */
    public function customerCatalog(): array
    {
        return Cache::remember(self::CUSTOMER_CATALOG_KEY, now()->addMinutes(10), fn (): array => [
            'customers' => Customer::query()
                ->whereNull('deleted_at')
                ->with(['accountants:id,name,last_name'])
                ->orderBy('name')
                ->orderBy('last_name')
                ->get(),
            'accountants' => User::query()
                ->whereHas('role', fn ($query) => $query->whereIn('role', ['Coordinador', 'Contador']))
                ->orderBy('name')
                ->orderBy('last_name')
                ->get(['id', 'name', 'last_name', 'email']),
        ]);
    }

    /** @return array<string, mixed> */
    public function activityCatalog(): array
    {
        return Cache::remember(self::ACTIVITY_CATALOG_KEY, now()->addMinutes(10), fn (): array => [
            'activities' => SubService::query()
                ->with('service:id,service')
                ->orderBy('sub_service')
                ->get(),
            'services' => Service::query()->orderBy('service')->get(['id', 'service']),
        ]);
    }

    /** @return array<string, mixed> */
    public function permissionCatalog(): array
    {
        return Cache::remember(self::PERMISSION_CATALOG_KEY, now()->addMinutes(10), fn (): array => [
            'groups' => PermissionGroup::query()
                ->with(['permissions' => fn ($query) => $query->active()->orderBy('module')->orderBy('sort_order')])
                ->withCount(['permissions', 'roles'])
                ->orderByDesc('is_system')
                ->orderBy('name')
                ->get(),
            'permissions' => AccessPermission::query()
                ->active()
                ->orderBy('module')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
        ]);
    }

    /** @return array<string, mixed> */
    public function assignmentCatalog(): array
    {
        return Cache::remember(self::ASSIGNMENT_CATALOG_KEY, now()->addMinutes(10), fn (): array => [
            'customers' => Customer::query()->whereNull('deleted_at')->orderBy('name')->orderBy('last_name')->get(),
            'accountants' => User::query()->whereHas('role', fn ($query) => $query->whereIn('role', ['Coordinador', 'Contador']))->orderBy('name')->orderBy('last_name')->get(),
            'interns' => User::query()->whereHas('role', fn ($query) => $query->where('role', 'Auxiliar'))->orderBy('name')->orderBy('last_name')->get(),
        ]);
    }

    public function forgetAdministration(): void
    {
        Cache::forget(self::ADMINISTRATION_KEY);
        Cache::forget(self::ORGANIZATION_DIRECTORY_KEY);
        Cache::forget(self::CUSTOMER_CATALOG_KEY);
        Cache::forget(self::PERMISSION_CATALOG_KEY);
        Cache::forget(self::ASSIGNMENT_CATALOG_KEY);
    }

    public function forgetTimeControl(): void
    {
        Cache::forget(self::TIME_CONTROL_KEY);
        Cache::forget(self::ORGANIZATION_DIRECTORY_KEY);
        Cache::forget(self::ACTIVITY_CATALOG_KEY);
    }

    public function forgetOrganizationDashboard(): void
    {
        Cache::forget(self::ORGANIZATION_DASHBOARD_KEY);
        Cache::forget(self::ORGANIZATION_DIRECTORY_KEY);
    }

    public function forgetManageableUsers(): void
    {
        Cache::forget(self::MANAGEABLE_USERS_KEY);
        Cache::forget(self::ORGANIZATION_DIRECTORY_KEY);
    }

    public function forgetCustomerCatalog(): void
    {
        Cache::forget(self::CUSTOMER_CATALOG_KEY);
        Cache::forget(self::ASSIGNMENT_CATALOG_KEY);
        $this->forgetOrganizationDashboard();
    }

    public function forgetActivityCatalog(): void
    {
        Cache::forget(self::ACTIVITY_CATALOG_KEY);
        $this->forgetTimeControl();
        $this->forgetOrganizationDashboard();
    }

    public function forgetPermissionCatalog(): void
    {
        Cache::forget(self::PERMISSION_CATALOG_KEY);
        $this->forgetAdministration();
    }

    public function forgetAssignmentCatalog(): void
    {
        Cache::forget(self::ASSIGNMENT_CATALOG_KEY);
        $this->forgetOrganizationDashboard();
    }
}
