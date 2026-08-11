<?php

namespace Tests\Feature;

use App\Livewire\Administracion\PanelAdministracion;
use App\Livewire\Administracion\Roles\Form as RoleForm;
use App\Livewire\Administracion\Roles\GestionRoles;
use App\Livewire\Administracion\Users\Form as UserForm;
use App\Models\AccessPermission;
use App\Models\JobPosition;
use App\Models\PhysicalArea;
use App\Models\Role;
use App\Models\User;
use App\Models\UserOrganizationalProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AdministrationModalFormsTest extends TestCase
{
    use RefreshDatabase;

    public function test_add_user_modal_preserves_the_existing_save_flow(): void
    {
        $administratorRole = Role::create([
            'role' => 'Administrador',
            'description' => 'Acceso administrativo',
        ]);
        $auxiliarRole = Role::create([
            'role' => 'Auxiliar',
            'description' => 'Acceso operativo',
        ]);
        $administrator = $this->createUser($administratorRole, 'admin-user-form@test.mx');
        $position = JobPosition::create([
            'name' => 'Auxiliar de auditoría',
            'payment_type' => JobPosition::PAYMENT_HOURLY,
        ]);
        $area = PhysicalArea::create(['name' => 'Auditoría']);
        DB::table('control_de_horas')->insert([
            [
                'employeeID' => 'BIO-900',
                'personName' => 'María Fernanda Usuario Nuevo',
                'authDateTime' => now()->subMinute(),
            ],
            [
                'employeeID' => 'BIO-900',
                'personName' => 'María Fernanda Usuario Nuevo',
                'authDateTime' => now(),
            ],
            [
                'employeeID' => 'BIO-901',
                'personName' => 'Carlos Segundo Colaborador',
                'authDateTime' => now(),
            ],
        ]);

        Livewire::actingAs($administrator)
            ->test(UserForm::class)
            ->assertSeeHtml('data-administration-modal="user-form"')
            ->assertSeeHtml('wire:click.self="cancel"')
            ->assertViewHas('employeeIdSuggestions', fn ($suggestions) => $suggestions->count() === 2)
            ->assertSeeHtml('id="create-employee-id-suggestions"')
            ->assertSeeHtml('data-employee-id="BIO-900"')
            ->assertSee('María Fernanda Usuario Nuevo')
            ->assertSeeHtml('role="listbox"')
            ->set('name', 'Usuario')
            ->set('last_name', 'Nuevo')
            ->set('email', 'usuario.nuevo@test.mx')
            ->set('password', 'password-seguro')
            ->set('password_confirmation', 'password-seguro')
            ->set('role_id', $auxiliarRole->id)
            ->set('job_position_id', $position->id)
            ->set('physical_area_id', $area->id)
            ->set('employee_id', 'BIO-900')
            ->set('hourly_rate', 125.50)
            ->set('food_allowance', 75)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect('/administracion/users');

        $createdUser = User::where('email', 'usuario.nuevo@test.mx')->firstOrFail();

        $this->assertSame('BIO-900', $createdUser->employee_id);
        $this->assertDatabaseHas('user_organizational_profiles', [
            'user_id' => $createdUser->id,
            'job_position_id' => $position->id,
            'physical_area_id' => $area->id,
            'is_active' => true,
            'hourly_rate' => 125.50,
            'food_allowance' => 75,
        ]);
    }

    public function test_administrator_can_create_job_positions_and_physical_areas_from_independent_modals(): void
    {
        $administratorRole = Role::create(['role' => 'Administrador']);
        $administrator = $this->createUser($administratorRole, 'admin-catalog-modals@test.mx');

        $component = Livewire::actingAs($administrator)
            ->test(PanelAdministracion::class)
            ->assertSet('showJobPositionModal', false)
            ->assertSet('showPhysicalAreaModal', false)
            ->assertSee('Agregar Puesto Operativo')
            ->assertSeeHtml('role="menu"')
            ->assertSeeHtml('Agregar &Aacute;rea')
            ->call('openJobPositionModal')
            ->assertSet('showJobPositionModal', true)
            ->assertSet('showPhysicalAreaModal', false)
            ->assertSeeHtml('data-administration-modal="job-position-form"')
            ->assertSeeHtml('@click.outside="$wire.closeJobPositionModal()"')
            ->set('newJobPositionName', '  Auditor   de Calidad  ')
            ->set('newJobPositionPaymentType', JobPosition::PAYMENT_HOURLY)
            ->call('saveJobPosition')
            ->assertHasNoErrors()
            ->assertSet('showJobPositionModal', false)
            ->assertSet('newJobPositionName', '');

        $this->assertDatabaseHas('job_positions', [
            'name' => 'Auditor de Calidad',
            'payment_type' => JobPosition::PAYMENT_HOURLY,
        ]);

        $component
            ->call('openPhysicalAreaModal')
            ->assertSet('showPhysicalAreaModal', true)
            ->assertSet('showJobPositionModal', false)
            ->assertSeeHtml('data-administration-modal="physical-area-form"')
            ->assertSeeHtml('@click.outside="$wire.closePhysicalAreaModal()"')
            ->set('newPhysicalAreaName', '  Control   Interno  ')
            ->call('savePhysicalArea')
            ->assertHasNoErrors()
            ->assertSet('showPhysicalAreaModal', false)
            ->assertSet('newPhysicalAreaName', '');

        $this->assertDatabaseHas('physical_areas', ['name' => 'Control Interno']);
    }

    public function test_hourly_compensation_depends_on_position_instead_of_role(): void
    {
        $administratorRole = Role::create(['role' => 'Administrador']);
        $accountantRole = Role::create(['role' => 'Contador']);
        $administrator = $this->createUser($administratorRole, 'admin-hourly-position@test.mx');
        $position = JobPosition::create([
            'name' => 'Consultor por hora',
            'payment_type' => JobPosition::PAYMENT_HOURLY,
        ]);
        $area = PhysicalArea::create(['name' => 'Consultoría']);

        Livewire::actingAs($administrator)
            ->test(UserForm::class)
            ->set('name', 'Consultor')
            ->set('email', 'consultor-hourly@test.mx')
            ->set('password', 'password-seguro')
            ->set('password_confirmation', 'password-seguro')
            ->set('role_id', $accountantRole->id)
            ->set('job_position_id', $position->id)
            ->assertSet('isHourlyPosition', true)
            ->set('physical_area_id', $area->id)
            ->set('hourly_rate', 200)
            ->set('food_allowance', 90)
            ->call('save')
            ->assertHasNoErrors();

        $user = User::where('email', 'consultor-hourly@test.mx')->firstOrFail();
        $this->assertDatabaseHas('user_organizational_profiles', [
            'user_id' => $user->id,
            'hourly_rate' => 200,
            'food_allowance' => 90,
        ]);
    }

    public function test_catalog_modals_reject_duplicates_and_are_restricted_to_administrators(): void
    {
        $administratorRole = Role::create(['role' => 'Administrador']);
        $coordinatorRole = Role::create(['role' => 'Coordinador']);
        $administrator = $this->createUser($administratorRole, 'admin-catalog-validation@test.mx');
        $coordinator = $this->createUser($coordinatorRole, 'coordinator-catalog-validation@test.mx');
        JobPosition::create(['name' => 'Auditor']);
        PhysicalArea::create(['name' => 'Fiscal']);

        Livewire::actingAs($administrator)
            ->test(PanelAdministracion::class)
            ->call('openJobPositionModal')
            ->set('newJobPositionName', 'Auditor')
            ->call('saveJobPosition')
            ->assertHasErrors(['newJobPositionName' => 'unique'])
            ->call('closeJobPositionModal')
            ->assertSet('newJobPositionName', '')
            ->call('openPhysicalAreaModal')
            ->set('newPhysicalAreaName', 'Fiscal')
            ->call('savePhysicalArea')
            ->assertHasErrors(['newPhysicalAreaName' => 'unique']);

        $this->assertSame(1, JobPosition::where('name', 'Auditor')->count());
        $this->assertSame(1, PhysicalArea::where('name', 'Fiscal')->count());

        Livewire::actingAs($coordinator)
            ->test(PanelAdministracion::class)
            ->call('openJobPositionModal')
            ->assertStatus(403);
    }

    public function test_catalog_tabs_update_records_and_detach_deleted_catalogs_from_profiles(): void
    {
        $administratorRole = Role::create(['role' => 'Administrador']);
        $administrator = $this->createUser($administratorRole, 'admin-catalog-tabs@test.mx');
        $collaborator = $this->createUser($administratorRole, 'collaborator-catalog-tabs@test.mx');
        $position = JobPosition::create(['name' => 'Analista Operativo']);
        $area = PhysicalArea::create(['name' => 'Control de Calidad']);

        UserOrganizationalProfile::create([
            'user_id' => $collaborator->id,
            'job_position_id' => $position->id,
            'physical_area_id' => $area->id,
            'hourly_rate' => 0,
            'food_allowance' => 0,
            'valid_from' => now()->toDateString(),
            'is_active' => true,
        ]);

        $component = Livewire::actingAs($administrator)
            ->test(PanelAdministracion::class)
            ->call('openJobPositionModal')
            ->assertSee('Crear')
            ->assertSee('Editar')
            ->assertSee('Eliminar')
            ->call('setJobPositionModalTab', 'editar')
            ->set('selectedJobPositionId', $position->id)
            ->set('editJobPositionName', 'Analista de Operaciones')
            ->call('updateJobPosition')
            ->assertHasNoErrors()
            ->call('setJobPositionModalTab', 'eliminar')
            ->set('selectedJobPositionId', $position->id)
            ->call('deleteJobPosition')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('job_positions', ['id' => $position->id]);
        $this->assertDatabaseHas('user_organizational_profiles', [
            'user_id' => $collaborator->id,
            'job_position_id' => null,
            'physical_area_id' => $area->id,
        ]);

        $component
            ->call('openPhysicalAreaModal')
            ->call('setPhysicalAreaModalTab', 'editar')
            ->set('selectedPhysicalAreaManagementId', $area->id)
            ->set('editPhysicalAreaName', 'Control Operativo')
            ->call('updatePhysicalArea')
            ->assertHasNoErrors()
            ->call('setPhysicalAreaModalTab', 'eliminar')
            ->set('selectedPhysicalAreaManagementId', $area->id)
            ->call('deletePhysicalArea')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('physical_areas', ['id' => $area->id]);
        $this->assertDatabaseHas('user_organizational_profiles', [
            'user_id' => $collaborator->id,
            'job_position_id' => null,
            'physical_area_id' => null,
        ]);
    }

    public function test_changing_a_position_to_full_time_clears_active_hourly_compensation(): void
    {
        $administratorRole = Role::create(['role' => 'Administrador']);
        $administrator = $this->createUser($administratorRole, 'admin-payment-type@test.mx');
        $collaborator = $this->createUser($administratorRole, 'hourly-collaborator@test.mx');
        $position = JobPosition::create([
            'name' => 'Capturista por hora',
            'payment_type' => JobPosition::PAYMENT_HOURLY,
        ]);
        $area = PhysicalArea::create(['name' => 'Captura']);
        UserOrganizationalProfile::create([
            'user_id' => $collaborator->id,
            'job_position_id' => $position->id,
            'physical_area_id' => $area->id,
            'hourly_rate' => 150,
            'food_allowance' => 80,
            'valid_from' => now()->toDateString(),
            'is_active' => true,
        ]);

        Livewire::actingAs($administrator)
            ->test(PanelAdministracion::class)
            ->call('openJobPositionModal')
            ->call('setJobPositionModalTab', 'editar')
            ->set('selectedJobPositionId', $position->id)
            ->assertSet('editJobPositionPaymentType', JobPosition::PAYMENT_HOURLY)
            ->set('editJobPositionPaymentType', JobPosition::PAYMENT_FULL_TIME)
            ->call('updateJobPosition')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('job_positions', [
            'id' => $position->id,
            'payment_type' => JobPosition::PAYMENT_FULL_TIME,
        ]);
        $this->assertDatabaseHas('user_organizational_profiles', [
            'user_id' => $collaborator->id,
            'hourly_rate' => 0,
            'food_allowance' => 0,
        ]);
    }

    public function test_user_modal_exposes_create_edit_and_delete_management_tabs(): void
    {
        $administratorRole = Role::create(['role' => 'Administrador']);
        $administrator = $this->createUser($administratorRole, 'admin-user-tabs@test.mx');
        $collaborator = $this->createUser($administratorRole, 'collaborator-user-tabs@test.mx');

        Livewire::actingAs($administrator)
            ->test(UserForm::class)
            ->assertSee('Crear')
            ->assertSee('Editar')
            ->assertSee('Eliminar')
            ->call('setManagementTab', 'editar')
            ->set('managementUserId', $collaborator->id)
            ->call('editManagedUser')
            ->assertRedirect(route('administracion.edit.users', $collaborator));
    }

    public function test_role_modal_preserves_create_and_edit_actions(): void
    {
        $administratorRole = Role::create(['role' => 'Administrador']);
        $administrator = $this->createUser($administratorRole, 'admin-role-form@test.mx');
        $permission = AccessPermission::create([
            'key' => 'administration.sample.manage',
            'name' => 'Permiso de prueba',
            'module' => 'Administración',
        ]);

        Livewire::actingAs($administrator)
            ->test(RoleForm::class)
            ->assertSeeHtml('data-administration-modal="role-form"')
            ->assertSeeHtml('wire:click.self="cancel"')
            ->assertSee('Crear Rol')
            ->assertSee('Editar Rol')
            ->assertSee('Eliminar Roles')
            ->set('role', 'Supervisor')
            ->set('description', 'Supervisa la operación')
            ->set('permissionIds', [$permission->id])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect('/administracion/roles');

        $role = Role::where('role', 'Supervisor')->firstOrFail();
        $this->assertDatabaseHas('role_access_permission', [
            'role_id' => $role->id,
            'access_permission_id' => $permission->id,
        ]);

        Livewire::actingAs($administrator)
            ->test(RoleForm::class, ['role' => $role])
            ->assertSee('Editar rol')
            ->assertSet('permissionIds', [$permission->id])
            ->set('role', 'Supervisor operativo')
            ->set('description', 'Supervisa la operación diaria')
            ->set('permissionProfile', Role::PROFILE_AUXILIARY)
            ->set('permissionIds', [])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect('/administracion/roles');

        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
            'role' => 'Supervisor operativo',
            'description' => 'Supervisa la operación diaria',
            'permission_profile' => Role::PROFILE_AUXILIARY,
        ]);
        $this->assertDatabaseMissing('role_access_permission', [
            'role_id' => $role->id,
            'access_permission_id' => $permission->id,
        ]);

        Livewire::actingAs($administrator)
            ->test(RoleForm::class, ['role' => $administratorRole])
            ->set('role', 'Administrador renombrado')
            ->set('description', 'Descripción actualizada sin cambiar el identificador')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('roles', [
            'id' => $administratorRole->id,
            'role' => 'Administrador',
            'description' => 'Descripción actualizada sin cambiar el identificador',
        ]);

        Livewire::actingAs($administrator)
            ->test(RoleForm::class)
            ->call('cancel')
            ->assertRedirect(route('administracion.index'));
    }

    public function test_roles_management_tab_reuses_existing_edit_and_delete_actions(): void
    {
        $administratorRole = Role::create(['role' => 'Administrador']);
        Role::create(['role' => 'Auxiliar']);
        $assignedRole = Role::create(['role' => 'Rol con usuario']);
        $temporaryRole = Role::create([
            'role' => 'Rol temporal',
            'description' => 'Se puede eliminar sin usuarios asociados',
        ]);
        $administrator = $this->createUser($administratorRole, 'admin-role-management@test.mx');
        $assignedUser = $this->createUser($assignedRole, 'assigned-role@test.mx');

        Livewire::actingAs($administrator)
            ->test(GestionRoles::class)
            ->call('deleteRole', $administratorRole->id);

        $this->assertDatabaseHas('roles', ['id' => $administratorRole->id]);
        $this->assertDatabaseHas('users', ['id' => $administrator->id]);

        Livewire::actingAs($administrator)
            ->test(GestionRoles::class)
            ->call('deleteRole', $assignedRole->id);

        $this->assertDatabaseHas('roles', ['id' => $assignedRole->id]);
        $this->assertDatabaseHas('users', ['id' => $assignedUser->id]);

        Livewire::actingAs($administrator)
            ->withQueryParams(['tab' => 'delete'])
            ->test(GestionRoles::class)
            ->assertSet('activeTab', 'delete')
            ->assertSeeHtml('data-administration-modal="roles-management"')
            ->assertSeeHtml('wire:click.self="cancel"')
            ->assertSee('Crear Rol')
            ->assertSee('Editar Rol')
            ->assertSee('Eliminar Roles')
            ->assertSee('Perfil de acceso vigente')
            ->call('deleteRole', $temporaryRole->id)
            ->assertRedirect(route('administracion.role', ['tab' => 'delete']));

        $this->assertDatabaseMissing('roles', ['id' => $temporaryRole->id]);

        Livewire::actingAs($administrator)
            ->withQueryParams(['tab' => 'edit'])
            ->test(GestionRoles::class)
            ->assertSet('activeTab', 'edit')
            ->assertSeeHtml(route('administracion.role.edit', $assignedRole))
            ->assertDontSeeHtml('wire:click="deleteRole('.$assignedRole->id.')"');

        Livewire::actingAs($administrator)
            ->withQueryParams(['tab' => 'invalid'])
            ->test(GestionRoles::class)
            ->assertSet('activeTab', 'edit');

        Livewire::actingAs($administrator)
            ->test(GestionRoles::class)
            ->call('cancel')
            ->assertRedirect(route('administracion.index'));
    }

    public function test_permissions_modal_only_presents_administrator_and_auxiliar_profiles(): void
    {
        $administratorRole = Role::create(['role' => 'Administrador']);
        Role::create(['role' => 'Auxiliar']);
        Role::create(['role' => 'Coordinador']);
        Role::create(['role' => 'Contador']);
        $administrator = $this->createUser($administratorRole, 'admin-permissions@test.mx');

        Livewire::actingAs($administrator)
            ->test(PanelAdministracion::class)
            ->assertSet('showPermissionsModal', false)
            ->call('openPermissionsModal')
            ->assertSet('showPermissionsModal', true)
            ->assertSeeHtml('data-administration-modal="permissions"')
            ->assertSeeHtml('wire:click.self="closePermissionsModal"')
            ->assertSeeHtml('data-permission-role="Administrador"')
            ->assertSeeHtml('data-permission-role="Auxiliar"')
            ->assertDontSeeHtml('data-permission-role="Coordinador"')
            ->assertDontSeeHtml('data-permission-role="Contador"')
            ->call('closePermissionsModal')
            ->assertSet('showPermissionsModal', false);
    }

    public function test_permissions_route_opens_the_same_modal_for_an_administrator(): void
    {
        $administratorRole = Role::create(['role' => 'Administrador']);
        Role::create(['role' => 'Auxiliar']);
        $administrator = $this->createUser($administratorRole, 'admin-permissions-route@test.mx');

        $this->actingAs($administrator)
            ->get(route('administracion.permissions'))
            ->assertOk()
            ->assertSee('data-administration-modal="permissions"', false);
    }

    private function createUser(Role $role, string $email): User
    {
        return User::create([
            'name' => 'Administrador',
            'email' => $email,
            'password' => Hash::make('secret-password'),
            'role_id' => $role->id,
        ]);
    }
}
