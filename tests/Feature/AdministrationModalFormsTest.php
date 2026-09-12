<?php

namespace Tests\Feature;

use App\Livewire\Administracion\PanelAdministracion;
use App\Livewire\Administracion\Permissions\CatalogManager as PermissionCatalogManager;
use App\Livewire\Administracion\Relationship\GestionRelacionesJerarquicas;
use App\Livewire\Administracion\Roles\Form as RoleForm;
use App\Livewire\Administracion\Roles\GestionRoles;
use App\Livewire\Administracion\Users\Form as UserForm;
use App\Models\AccessPermission;
use App\Models\Customer;
use App\Models\JobPosition;
use App\Models\PermissionGroup;
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
            ->assertSeeHtml('wire:click.self="closeJobPositionModal"')
            ->assertSeeHtml('@keydown.escape.window="visible = false; $wire.closeJobPositionModal()"')
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
            ->assertSeeHtml('wire:click.self="closePhysicalAreaModal"')
            ->assertSeeHtml('@keydown.escape.window="visible = false; $wire.closePhysicalAreaModal()"')
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
            ->set('deleteJobPositionConfirmation', 'Analista de Operaciones')
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
            ->set('deletePhysicalAreaConfirmation', 'Control Operativo')
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
        $permissionGroup = PermissionGroup::create([
            'name' => 'Supervisión administrativa',
            'description' => 'Accesos para supervisores.',
        ]);
        $permissionGroup->permissions()->sync([$permission->id]);

        Livewire::actingAs($administrator)
            ->test(RoleForm::class)
            ->assertSeeHtml('data-administration-modal="role-form"')
            ->assertSeeHtml('wire:click.self="cancel"')
            ->assertSee('Crear Rol')
            ->assertSee('Editar Rol')
            ->assertSee('Eliminar Roles')
            ->set('role', 'Supervisor')
            ->set('description', 'Supervisa la operación')
            ->call('selectPermissionGroup', $permissionGroup->id)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect('/administracion/roles');

        $role = Role::where('role', 'Supervisor')->firstOrFail();
        $this->assertDatabaseHas('role_access_permission', [
            'role_id' => $role->id,
            'access_permission_id' => $permission->id,
        ]);
        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
            'permission_group_id' => $permissionGroup->id,
        ]);

        Livewire::actingAs($administrator)
            ->test(RoleForm::class, ['role' => $role])
            ->assertSee('Editar rol')
            ->assertSet('permissionIds', [$permission->id])
            ->set('role', 'Supervisor operativo')
            ->set('description', 'Supervisa la operación diaria')
            ->call('selectPermissionGroup', $permissionGroup->id)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect('/administracion/roles');

        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
            'role' => 'Supervisor operativo',
            'description' => 'Supervisa la operación diaria',
            'permission_profile' => Role::PROFILE_CUSTOM,
            'permission_group_id' => $permissionGroup->id,
        ]);

        Livewire::actingAs($administrator)
            ->test(RoleForm::class, ['role' => $administratorRole])
            ->set('role', 'Administrador renombrado')
            ->set('description', 'Descripción actualizada sin cambiar el identificador')
            ->call('selectPermissionGroup', $permissionGroup->id)
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
            ->assertSee('Seleccionar rol')
            ->assertSee('Verificación')
            ->call('selectRole', $assignedRole->id)
            ->assertSet('editingRoleId', null)
            ->call('selectRole', $temporaryRole->id)
            ->call('deleteRole', $temporaryRole->id)
            ->assertHasErrors(['deleteConfirmationName'])
            ->set('deleteConfirmationName', mb_strtoupper($temporaryRole->role))
            ->call('deleteRole', $temporaryRole->id)
            ->assertHasErrors(['deleteConfirmationWord'])
            ->set('deleteConfirmationWord', 'ELIMINAR')
            ->call('deleteRole', $temporaryRole->id)
            ->assertRedirect(route('administracion.role', ['tab' => 'delete']));

        $this->assertDatabaseMissing('roles', ['id' => $temporaryRole->id]);

        Livewire::actingAs($administrator)
            ->withQueryParams(['tab' => 'edit'])
            ->test(GestionRoles::class)
            ->assertSet('activeTab', 'edit')
            ->assertSee('Buscar rol por nombre')
            ->assertSee('Vista previa de accesos')
            ->call('selectRole', $assignedRole->id)
            ->assertSet('editingRoleId', $assignedRole->id)
            ->assertSet('editingRoleName', 'Rol con usuario');

        Livewire::actingAs($administrator)
            ->withQueryParams(['tab' => 'invalid'])
            ->test(GestionRoles::class)
            ->assertSet('activeTab', 'edit');

        Livewire::actingAs($administrator)
            ->test(GestionRoles::class)
            ->call('cancel')
            ->assertRedirect(route('administracion.index'));
    }

    public function test_embedded_role_editor_updates_the_selected_role_without_mounting_a_second_livewire_form(): void
    {
        $administratorRole = Role::create(['role' => 'Administrador']);
        $targetRole = Role::create(['role' => 'Supervisor']);
        $administrator = $this->createUser($administratorRole, 'admin-inline-role-editor@test.mx');
        $permission = AccessPermission::create([
            'key' => 'customers.inline.manage',
            'name' => 'Administrar clientes en línea',
            'module' => 'Clientes',
        ]);
        $group = PermissionGroup::create([
            'name' => 'Supervisión de clientes',
            'description' => 'Permisos de supervisión.',
        ]);
        $group->permissions()->sync([$permission->id]);

        Livewire::actingAs($administrator)
            ->test(GestionRoles::class, ['embedded' => true, 'initialTab' => 'editar'])
            ->assertSet('editingRoleId', null)
            ->assertDontSeeHtml('embedded-role-editor-')
            ->call('selectRole', $targetRole->id)
            ->assertSet('editingRoleId', $targetRole->id)
            ->assertSet('editingRoleName', 'Supervisor')
            ->call('selectPermissionGroup', $group->id)
            ->assertSet('permissionIds', [$permission->id])
            ->set('editingRoleDescription', 'Supervisa el directorio de clientes')
            ->call('saveEditedRole')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('roles', [
            'id' => $targetRole->id,
            'description' => 'Supervisa el directorio de clientes',
            'permission_group_id' => $group->id,
        ]);
        $this->assertDatabaseHas('role_access_permission', [
            'role_id' => $targetRole->id,
            'access_permission_id' => $permission->id,
        ]);
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

    public function test_organization_role_actions_open_and_close_without_redirecting(): void
    {
        $administratorRole = Role::create(['role' => 'Administrador']);
        Role::create(['role' => 'Auxiliar']);
        $administrator = $this->createUser($administratorRole, 'admin-inline-roles@test.mx');

        Livewire::actingAs($administrator)
            ->test(PanelAdministracion::class)
            ->assertSeeHtml('wire:click="openRoleManagement(\'crear\')"')
            ->assertDontSeeHtml(route('administracion.role.create'))
            ->call('openRoleManagement', 'crear')
            ->assertSet('showRoleManagementModal', true)
            ->assertSeeHtml('data-administration-modal="role-form"')
            ->dispatch('role-management-closed')
            ->assertSet('showRoleManagementModal', false)
            ->call('openRoleManagement', 'eliminar')
            ->assertSeeHtml('data-administration-modal="roles-management"')
            ->dispatch('role-management-closed')
            ->assertSet('showRoleManagementModal', false);
    }

    public function test_organization_assignment_actions_use_an_inline_modal(): void
    {
        $administratorRole = Role::create(['role' => 'Administrador']);
        Role::create(['role' => 'Auxiliar']);
        $administrator = $this->createUser($administratorRole, 'admin-inline-assignments@test.mx');

        Livewire::actingAs($administrator)
            ->test(PanelAdministracion::class)
            ->assertSeeHtml('wire:click="openAssignmentModal(\'relationships\')"')
            ->assertDontSeeHtml('href="'.route('administracion.relationships').'"')
            ->call('openAssignmentModal', 'relationships')
            ->assertSet('showAssignmentModal', true)
            ->assertSet('assignmentModalTab', 'relationships')
            ->assertSeeHtml('data-administration-modal="assignment-management"')
            ->call('setAssignmentModalTab', 'interns')
            ->assertSet('assignmentModalTab', 'interns')
            ->call('closeAssignmentModal')
            ->assertSet('showAssignmentModal', false);
    }

    public function test_permission_catalog_can_reopen_and_manage_groups_without_opening_roles(): void
    {
        $administratorRole = Role::create(['role' => 'Administrador']);
        $catalogPermission = AccessPermission::firstOrCreate(['key' => 'administration.permissions.manage'], [
            'name' => 'Gestión de permisos',
            'module' => 'Administración',
            'is_active' => true,
        ]);
        $administrator = $this->createUser($administratorRole, 'admin-permission-catalog@test.mx');

        $component = Livewire::actingAs($administrator)
            ->test(PermissionCatalogManager::class, ['cardActions' => true])
            ->call('openModal', 'crear')
            ->assertSet('showModal', true)
            ->set('name', 'Auditoría avanzada')
            ->set('description', 'Permite consultar eventos de seguridad.')
            ->set('permissionIds', [$catalogPermission->id])
            ->call('save')
            ->assertHasNoErrors();

        $group = PermissionGroup::where('name', 'Auditoría avanzada')->firstOrFail();

        $component->call('closeModal')
            ->call('openModal', 'editar')
            ->set('selectedGroupId', $group->id)
            ->assertSet('name', 'Auditoría avanzada')
            ->set('name', 'Auditoría corporativa')
            ->call('save')
            ->assertHasNoErrors();

        $component->call('closeModal')
            ->call('openModal', 'eliminar')
            ->set('selectedGroupId', $group->id)
            ->set('deleteConfirmation', 'Auditoría corporativa')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showModal', true);

        $this->assertDatabaseMissing('permission_groups', ['id' => $group->id]);
    }

    public function test_assignment_form_saves_once_and_reopens_in_each_mode(): void
    {
        $administratorRole = Role::create(['role' => 'Administrador']);
        $accountantRole = Role::create(['role' => 'Contador']);
        $auxiliaryRole = Role::create(['role' => 'Auxiliar']);
        $administrator = $this->createUser($administratorRole, 'admin-assignment-form@test.mx');
        $accountant = $this->createUser($accountantRole, 'accountant-assignment-form@test.mx');
        $intern = $this->createUser($auxiliaryRole, 'intern-assignment-form@test.mx');
        $customer = Customer::create(['name' => 'Cliente Demo', 'rfc' => 'DEMO010101AA1']);

        $component = Livewire::actingAs($administrator)
            ->test(GestionRelacionesJerarquicas::class, ['cardActions' => true])
            ->call('openModal', 'crear')
            ->set('selectedCustomer', $customer->id)
            ->set('selectedAccountantId', $accountant->id)
            ->set('assignedInterns', [$intern->id])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('customer_accountants', ['customer_id' => $customer->id, 'accountant_id' => $accountant->id, 'status' => true]);
        $this->assertDatabaseHas('customer_interns', ['customer_id' => $customer->id, 'intern_id' => $intern->id]);

        $component->call('closeModal')->call('openModal', 'editar')->set('selectedCustomer', $customer->id)
            ->assertSet('selectedAccountantId', $accountant->id)
            ->assertSet('assignedInterns', [$intern->id])
            ->set('assignedInterns', [])->call('save')->assertHasNoErrors();
        $this->assertDatabaseMissing('customer_interns', ['customer_id' => $customer->id, 'intern_id' => $intern->id]);

        $component->call('closeModal')->call('openModal', 'eliminar')->set('selectedCustomer', $customer->id)
            ->set('deleteConfirmation', 'Cliente Demo')->call('save')->assertHasNoErrors();
        $this->assertDatabaseHas('customer_accountants', ['customer_id' => $customer->id, 'accountant_id' => $accountant->id, 'status' => false]);
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
