<?php

namespace Tests\Feature;

use App\Models\AccessPermission;
use App\Models\JobPosition;
use App\Models\PhysicalArea;
use App\Models\Role;
use App\Models\User;
use App\Models\UserHierarchyRelation;
use App\Models\UserOrganizationalProfile;
use App\Services\Administracion\OrganizationChartService;
use App\Services\Authorization\PermissionAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RolePermissionLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_a_permission_revokes_visibility_and_direct_access_immediately(): void
    {
        $role = Role::create(['role' => 'Rol dinámico']);
        $user = $this->createUser($role, 'dynamic-access@test.mx');
        $permission = AccessPermission::create([
            'key' => 'reports.dynamic.view',
            'name' => 'Ver reporte dinámico',
            'module' => 'Reportes',
        ]);
        $role->accessPermissions()->attach($permission->id);

        Route::middleware(['web', 'auth', 'access.permission:reports.dynamic.view'])
            ->get('/_permission-access-probe', fn () => 'Acceso vigente');

        $access = app(PermissionAccessService::class);

        $this->assertTrue($access->allows($user, 'reports.dynamic.view'));

        $this->actingAs($user)
            ->get('/_permission-access-probe')
            ->assertOk()
            ->assertSee('Acceso vigente');

        $this->assertStringContainsString(
            'Módulo visible',
            Blade::render("@rolePermission('reports.dynamic.view') Módulo visible @endrolePermission")
        );

        $access->deletePermission($permission);

        $this->assertDatabaseMissing('access_permissions', ['id' => $permission->id]);
        $this->assertDatabaseMissing('role_access_permission', [
            'role_id' => $role->id,
            'access_permission_id' => $permission->id,
        ]);
        $this->assertFalse($access->allows($user, 'reports.dynamic.view'));
        $this->assertStringNotContainsString(
            'Módulo visible',
            Blade::render("@rolePermission('reports.dynamic.view') Módulo visible @endrolePermission")
        );

        $this->get('/_permission-access-probe')->assertForbidden();
    }

    public function test_deleting_a_permission_preserves_users_roles_profiles_and_hierarchy(): void
    {
        $superiorRole = Role::create(['role' => 'Superior']);
        $collaboratorRole = Role::create(['role' => 'Colaborador']);
        $superior = $this->createUser($superiorRole, 'superior@test.mx');
        $collaborator = $this->createUser($collaboratorRole, 'collaborator@test.mx');
        $position = JobPosition::create(['name' => 'Auxiliar de auditoría']);
        $area = PhysicalArea::create(['name' => 'Auditoría']);
        $profile = UserOrganizationalProfile::create([
            'user_id' => $collaborator->id,
            'job_position_id' => $position->id,
            'physical_area_id' => $area->id,
            'valid_from' => now()->toDateString(),
            'is_active' => true,
        ]);
        $relation = UserHierarchyRelation::create([
            'subordinate_id' => $collaborator->id,
            'superior_id' => $superior->id,
            'job_position_id' => $position->id,
            'physical_area_id' => $area->id,
        ]);
        $permission = AccessPermission::create([
            'key' => 'organization.sample.view',
            'name' => 'Consulta organizacional',
        ]);
        $collaboratorRole->accessPermissions()->attach($permission->id);

        $before = app(OrganizationChartService::class)->buildChartData();
        $this->assertNotContains($collaborator->id, array_column($before['unassigned'], 'id'));

        app(PermissionAccessService::class)->deletePermission($permission);

        $this->assertDatabaseHas('users', [
            'id' => $collaborator->id,
            'role_id' => $collaboratorRole->id,
        ]);
        $this->assertDatabaseHas('roles', ['id' => $collaboratorRole->id]);
        $this->assertDatabaseHas('user_organizational_profiles', [
            'id' => $profile->id,
            'user_id' => $collaborator->id,
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('user_hierarchy_relations', [
            'id' => $relation->id,
            'subordinate_id' => $collaborator->id,
            'superior_id' => $superior->id,
        ]);

        $after = app(OrganizationChartService::class)->buildChartData();
        $this->assertNotContains($collaborator->id, array_column($after['unassigned'], 'id'));
    }

    public function test_syncing_role_permissions_changes_only_the_role_permission_pivot(): void
    {
        $role = Role::create(['role' => 'Rol sincronizable']);
        $user = $this->createUser($role, 'sync-permissions@test.mx');
        $firstPermission = AccessPermission::create([
            'key' => 'module.first.view',
            'name' => 'Primer módulo',
        ]);
        $secondPermission = AccessPermission::create([
            'key' => 'module.second.view',
            'name' => 'Segundo módulo',
        ]);
        $role->accessPermissions()->attach($firstPermission->id);

        $access = app(PermissionAccessService::class);
        $access->syncRolePermissions($role, [$secondPermission->id]);

        $this->assertFalse($access->allows($user, $firstPermission->key));
        $this->assertTrue($access->allows($user, $secondPermission->key));
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role_id' => $role->id,
        ]);
        $this->assertDatabaseHas('roles', ['id' => $role->id]);
        $this->assertDatabaseCount('role_access_permission', 1);
    }

    private function createUser(Role $role, string $email): User
    {
        return User::create([
            'name' => 'Usuario',
            'email' => $email,
            'password' => Hash::make('secret-password'),
            'role_id' => $role->id,
        ]);
    }
}
