<?php

namespace App\Services;

use App\Models\AccessPermission;
use App\Models\Customer;
use App\Models\JobPosition;
use App\Models\PhysicalArea;
use App\Models\Role;
use App\Models\SubService;
use App\Services\Authorization\PermissionAccessService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ReferenceDataCache
{
    private const ADMINISTRATION_KEY = 'reference-data:administration:v1';

    private const TIME_CONTROL_KEY = 'reference-data:time-control:v1';

    private const EMPLOYEE_SUGGESTIONS_KEY = 'reference-data:employee-suggestions:v1';

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
        return Cache::remember(self::EMPLOYEE_SUGGESTIONS_KEY, now()->addMinute(), fn () => DB::table('control_de_horas')
            ->select('employeeID')
            ->selectRaw('MAX(personName) as personName')
            ->whereNotNull('employeeID')
            ->where('employeeID', '<>', '')
            ->groupBy('employeeID')
            ->orderBy('employeeID')
            ->get());
    }

    public function forgetAdministration(): void
    {
        Cache::forget(self::ADMINISTRATION_KEY);
    }

    public function forgetTimeControl(): void
    {
        Cache::forget(self::TIME_CONTROL_KEY);
    }
}
