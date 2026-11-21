<?php

namespace Tests\Feature;

use App\Livewire\Administracion\PanelAdministracion;
use App\Models\JobPosition;
use App\Models\PhysicalArea;
use App\Models\Role;
use App\Models\User;
use App\Models\UserHierarchyRelation;
use App\Models\UserOrganizationalProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use App\Services\Administracion\OrganizationChartService;
use Livewire\Livewire;
use Tests\TestCase;

class UserFormsFlowTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{admin: User, member: User, position: JobPosition, area: PhysicalArea, roles: array<string, Role>} */
    private function setUpData(): array
    {
        $roles = [
            'Administrador' => Role::create(['role' => 'Administrador']),
            'Contador' => Role::create(['role' => 'Contador']),
        ];
        $position = JobPosition::create(['name' => 'Contador general']);
        $area = PhysicalArea::create(['name' => 'Contabilidad']);

        $admin = User::create(['name' => 'Admin', 'last_name' => 'Prueba', 'email' => 'admin@datamid.test', 'password' => Hash::make('secret-password'), 'role_id' => $roles['Administrador']->id]);
        $member = User::create(['name' => 'Ana', 'last_name' => 'Pérez', 'email' => 'ana@datamid.test', 'password' => Hash::make('secret-password'), 'role_id' => $roles['Contador']->id, 'employee_id' => 'BIO-1']);
        foreach ([$admin, $member] as $user) {
            UserOrganizationalProfile::create(['user_id' => $user->id, 'job_position_id' => $position->id, 'physical_area_id' => $area->id, 'hourly_rate' => 0, 'food_allowance' => 0, 'valid_from' => now()->toDateString(), 'is_active' => true]);
        }

        return compact('admin', 'member', 'position', 'area', 'roles');
    }

    public function test_adding_a_user_does_not_need_the_clock_id(): void
    {
        ['admin' => $admin, 'position' => $position, 'area' => $area, 'roles' => $roles] = $this->setUpData();

        // Crear usa la misma ficha que editar
        Livewire::actingAs($admin)->test(PanelAdministracion::class)
            ->call('openUserManagement', 'crear')
            ->assertSet('isCreatingUser', true)
            ->assertSet('isEditingUser', true)
            ->set('userForm.name', 'Nueva')
            ->set('userForm.last_name', 'Persona')
            ->set('userForm.email', 'nueva@datamid.test')
            ->set('userForm.password', 'password-seguro')
            ->set('userForm.password_confirmation', 'password-seguro')
            ->set('userForm.role_id', $roles['Contador']->id)
            ->set('userForm.job_position_id', $position->id)
            ->set('userForm.physical_area_id', $area->id)
            ->set('userForm.employee_id', '')
            ->call('saveSelectedUser')
            ->assertHasNoErrors()
            ->assertDispatched('notify', message: 'Usuario agregado.', type: 'success')
            ->assertSet('selectedUserDetails', null)
            ->assertSet('isCreatingUser', false);

        $created = User::where('email', 'nueva@datamid.test')->firstOrFail();
        $this->assertNull($created->employee_id);
        $this->assertSame($position->id, $created->activeOrganizationalProfile->job_position_id);
    }

    public function test_editing_a_user_without_clock_id_says_updated_and_leaves_the_form(): void
    {
        ['admin' => $admin, 'member' => $member] = $this->setUpData();

        // «Editar» desde Administración abre la ficha de esa persona ya en modo edición
        Livewire::actingAs($admin)->test(PanelAdministracion::class)
            ->call('openUserManagement', 'editar', $member->id)
            ->assertSet('selectedUserId', $member->id)
            ->assertSet('isEditingUser', true)
            ->assertSet('userForm.employee_id', 'BIO-1')
            ->set('userForm.employee_id', '')
            ->set('userForm.name', 'Ana María')
            ->call('saveSelectedUser')
            ->assertHasNoErrors()
            ->assertDispatched('notify', message: 'Usuario actualizado.', type: 'success')
            ->assertSet('selectedUserDetails', null);

        $member->refresh();
        $this->assertSame('ANA MARÍA', $member->name);
        $this->assertNull($member->employee_id);
    }

    public function test_editing_from_the_organigrama_card_says_updated_and_closes_the_form(): void
    {
        ['admin' => $admin, 'member' => $member] = $this->setUpData();

        Livewire::actingAs($admin)->test(PanelAdministracion::class)
            ->call('selectUser', $member->id)
            ->call('beginEditingUser')
            ->set('userForm.name', 'Ana María')
            ->set('userForm.employee_id', '')
            ->call('saveSelectedUser')
            ->assertHasNoErrors()
            ->assertDispatched('notify', message: 'Usuario actualizado.', type: 'success')
            ->assertSet('selectedUserId', null)
            ->assertSet('selectedUserDetails', null)
            ->assertSet('isEditingUser', false);

        $member->refresh();
        $this->assertSame('ANA MARÍA', $member->name);
        $this->assertNull($member->employee_id);
    }

    public function test_the_clock_id_can_be_repeated_when_adding_or_editing(): void
    {
        ['admin' => $admin, 'member' => $member, 'position' => $position, 'area' => $area, 'roles' => $roles] = $this->setUpData();

        Livewire::actingAs($admin)->test(PanelAdministracion::class)
            ->call('openUserManagement', 'crear')
            ->set('userForm.name', 'Otra')
            ->set('userForm.last_name', 'Persona')
            ->set('userForm.email', 'otra@datamid.test')
            ->set('userForm.password', 'password-seguro')
            ->set('userForm.password_confirmation', 'password-seguro')
            ->set('userForm.role_id', $roles['Contador']->id)
            ->set('userForm.job_position_id', $position->id)
            ->set('userForm.physical_area_id', $area->id)
            ->set('userForm.employee_id', 'BIO-1')
            ->call('saveSelectedUser')
            ->assertHasNoErrors();

        $this->assertSame(2, User::where('employee_id', 'BIO-1')->count());

        Livewire::actingAs($admin)->test(PanelAdministracion::class)
            ->call('selectUser', $admin->id)
            ->call('beginEditingUser')
            ->set('userForm.employee_id', 'BIO-1')
            ->call('saveSelectedUser')
            ->assertHasNoErrors();

        $this->assertSame(3, User::where('employee_id', 'BIO-1')->count());
    }

    public function test_a_user_without_position_or_area_can_still_be_updated_from_the_organigrama(): void
    {
        ['admin' => $admin, 'roles' => $roles] = $this->setUpData();
        $unassigned = User::create(['name' => 'Dionicio', 'last_name' => 'Farfán', 'email' => 'dionicio@datamid.test', 'password' => Hash::make('x'), 'role_id' => $roles['Contador']->id]);

        Livewire::actingAs($admin)->test(PanelAdministracion::class)
            ->call('selectUser', $unassigned->id)
            ->call('beginEditingUser')
            ->set('userForm.name', 'Dionicio Alberto')
            ->call('saveSelectedUser')
            ->assertHasNoErrors()
            ->assertDispatched('notify', message: 'Usuario actualizado.', type: 'success')
            ->assertSet('selectedUserId', null);

        $this->assertSame('DIONICIO ALBERTO', $unassigned->fresh()->name);
    }

    public function test_a_failed_save_always_shows_an_error_notice_and_the_error_summary(): void
    {
        ['admin' => $admin, 'member' => $member] = $this->setUpData();

        Livewire::actingAs($admin)->test(PanelAdministracion::class)
            ->call('selectUser', $member->id)
            ->call('beginEditingUser')
            ->set('userForm.name', '')
            ->call('saveSelectedUser')
            ->assertHasErrors('userForm.name')
            ->assertDispatched('notify', message: 'No se pudo guardar: El campo nombre es obligatorio.', type: 'error')
            ->assertSee('No se pudo guardar. Revisa lo siguiente:')
            ->assertSee('El campo nombre es obligatorio.');
    }

    public function test_a_user_without_position_or_area_can_be_updated_from_administration(): void
    {
        ['admin' => $admin, 'roles' => $roles] = $this->setUpData();
        $unassigned = User::create(['name' => 'Dionicio', 'last_name' => 'Farfán', 'email' => 'dionicio@datamid.test', 'password' => Hash::make('x'), 'role_id' => $roles['Contador']->id]);

        Livewire::actingAs($admin)->test(PanelAdministracion::class)
            ->call('openUserManagement', 'editar', $unassigned->id)
            ->set('userForm.name', 'Dionicio Alberto')
            ->call('saveSelectedUser')
            ->assertHasNoErrors()
            ->assertDispatched('notify', message: 'Usuario actualizado.', type: 'success');

        $this->assertSame('DIONICIO ALBERTO', $unassigned->fresh()->name);
    }

    public function test_birth_date_and_sex_are_saved_shown_and_validated(): void
    {
        ['admin' => $admin, 'member' => $member] = $this->setUpData();

        Livewire::actingAs($admin)->test(PanelAdministracion::class)
            ->call('openUserManagement', 'editar', $member->id)
            ->set('userForm.birth_date', '1995-03-14')
            ->set('userForm.gender', 'mujer')
            ->call('saveSelectedUser')
            ->assertHasNoErrors();

        $member->refresh();
        $this->assertSame('1995-03-14', $member->birth_date->format('Y-m-d'));
        $this->assertSame(1995, (int) $member->birth_year);
        $this->assertSame('mujer', $member->gender);

        // La ficha (modo ver) muestra la fecha con la edad y el sexo
        Livewire::actingAs($admin)->test(PanelAdministracion::class)
            ->call('selectUser', $member->id)
            ->assertSee('14 de marzo de 1995')
            ->assertSee('Mujer');

        // Una fecha futura o un sexo que no existe no se guardan
        Livewire::actingAs($admin)->test(PanelAdministracion::class)
            ->call('openUserManagement', 'editar', $member->id)
            ->set('userForm.birth_date', now()->addYear()->format('Y-m-d'))
            ->call('saveSelectedUser')
            ->assertHasErrors('userForm.birth_date');
    }

    public function test_edit_and_delete_without_a_person_first_ask_who_in_the_same_form(): void
    {
        ['admin' => $admin, 'member' => $member] = $this->setUpData();

        Livewire::actingAs($admin)->test(PanelAdministracion::class)
            ->call('openUserManagement', 'editar')
            ->assertSet('userPickerAction', 'editar')
            ->assertSee('Elige a la persona que quieres editar.')
            ->assertSee('ana@datamid.test')
            ->call('pickUserForAction', $member->id)
            ->assertSet('selectedUserId', $member->id)
            ->assertSet('isEditingUser', true)
            ->assertSet('userPickerAction', null);

        Livewire::actingAs($admin)->test(PanelAdministracion::class)
            ->call('openUserManagement', 'eliminar')
            ->call('pickUserForAction', $member->id)
            ->assertSet('selectedUserId', $member->id)
            ->assertSet('activeTab', 'eliminar');
    }

    public function test_the_old_create_and_edit_pages_open_the_single_user_form(): void
    {
        ['admin' => $admin, 'member' => $member] = $this->setUpData();

        $this->actingAs($admin)->get(route('administracion.create.users'))
            ->assertRedirect(route('administracion.index', ['usuario' => 'nuevo']));
        $this->actingAs($admin)->get(route('administracion.edit.users', $member))
            ->assertRedirect(route('administracion.index', ['usuario' => $member->id]));

        $this->actingAs($admin)->get(route('administracion.index', ['usuario' => $member->id]))
            ->assertOk()
            ->assertSee('Actualiza los datos de acceso y el perfil organizacional.');
        $this->actingAs($admin)->get(route('administracion.index', ['usuario' => 'nuevo']))
            ->assertOk()
            ->assertSee('Captura los datos de la nueva persona.');
    }

    public function test_the_suggested_password_uses_the_first_surname_and_the_year(): void
    {
        ['admin' => $admin] = $this->setUpData();

        Livewire::actingAs($admin)->test(PanelAdministracion::class)
            ->call('openUserManagement', 'crear')
            ->set('userForm.last_name', 'Ortiz Rivero')
            ->call('suggestUserPassword')
            ->assertSet('userForm.password', 'Ortiz'.now()->year.'.')
            ->assertSet('userForm.password_confirmation', 'Ortiz'.now()->year.'.')
            ->set('userForm.last_name', '')
            ->call('suggestUserPassword')
            ->assertSet('userForm.password', '');
    }

    public function test_the_password_can_be_changed_and_a_blank_one_keeps_the_current(): void
    {
        ['admin' => $admin, 'member' => $member] = $this->setUpData();
        $before = $member->password;

        Livewire::actingAs($admin)->test(PanelAdministracion::class)
            ->call('selectUser', $member->id)
            ->call('beginEditingUser')
            ->set('userForm.name', 'Ana María')
            ->call('saveSelectedUser')
            ->assertHasNoErrors();
        $this->assertSame($before, $member->fresh()->password);

        Livewire::actingAs($admin)->test(PanelAdministracion::class)
            ->call('selectUser', $member->id)
            ->call('beginEditingUser')
            ->set('userForm.password', 'nueva-clave-123')
            ->set('userForm.password_confirmation', 'nueva-clave-123')
            ->call('saveSelectedUser')
            ->assertHasNoErrors()
            ->assertDispatched('notify', message: 'Usuario actualizado.', type: 'success');

        $this->assertTrue(Hash::check('nueva-clave-123', $member->fresh()->password));
    }

    public function test_a_short_or_unconfirmed_password_is_rejected_with_a_visible_notice(): void
    {
        ['admin' => $admin, 'member' => $member] = $this->setUpData();
        $before = $member->password;

        Livewire::actingAs($admin)->test(PanelAdministracion::class)
            ->call('selectUser', $member->id)
            ->call('beginEditingUser')
            ->set('userForm.password', 'corta')
            ->set('userForm.password_confirmation', 'corta')
            ->call('saveSelectedUser')
            ->assertHasErrors('userForm.password')
            ->assertDispatched('notify', type: 'error');

        Livewire::actingAs($admin)->test(PanelAdministracion::class)
            ->call('selectUser', $member->id)
            ->call('beginEditingUser')
            ->set('userForm.password', 'clave-larga-123')
            ->set('userForm.password_confirmation', 'otra-distinta-1')
            ->call('saveSelectedUser')
            ->assertHasErrors('userForm.password')
            ->assertDispatched('notify', type: 'error');

        $this->assertSame($before, $member->fresh()->password);
    }

    /** @return array{admin: User, member: User, boss: User, free: User} */
    private function hierarchyData(): array
    {
        ['admin' => $admin, 'member' => $member, 'roles' => $roles, 'position' => $position, 'area' => $area] = $this->setUpData();
        $boss = User::create(['name' => 'Jefa', 'last_name' => 'Principal', 'email' => 'jefa@datamid.test', 'password' => Hash::make('x'), 'role_id' => $roles['Contador']->id]);
        $free = User::create(['name' => 'Libre', 'last_name' => 'Sin Jefe', 'email' => 'libre@datamid.test', 'password' => Hash::make('x'), 'role_id' => $roles['Contador']->id]);
        foreach ([$boss, $free] as $user) {
            UserOrganizationalProfile::create(['user_id' => $user->id, 'job_position_id' => $position->id, 'physical_area_id' => $area->id, 'hourly_rate' => 0, 'food_allowance' => 0, 'valid_from' => now()->toDateString(), 'is_active' => true]);
        }
        // Ana ya reporta a la jefa, así ambas forman parte del organigrama.
        app(OrganizationChartService::class)->createRelation(['subordinate_id' => $member->id, 'superior_id' => $boss->id]);

        return compact('admin', 'member', 'boss', 'free');
    }

    public function test_choosing_a_boss_and_subordinates_saves_the_relations_and_clearing_removes_them(): void
    {
        ['admin' => $admin, 'member' => $member, 'boss' => $boss, 'free' => $free] = $this->hierarchyData();

        // La persona libre se une al organigrama como subordinada de Ana y Ana sigue reportando a la jefa.
        Livewire::actingAs($admin)->test(PanelAdministracion::class)
            ->call('selectUser', $member->id)
            ->call('beginEditingUser')
            ->call('selectSuperior', $boss->id)
            ->set('userForm.subordinate_ids', [$free->id])
            ->call('saveSelectedUser')
            ->assertHasNoErrors()
            ->assertDispatched('notify', message: 'Usuario actualizado.', type: 'success');

        $this->assertTrue(UserHierarchyRelation::where('subordinate_id', $member->id)->where('superior_id', $boss->id)->exists());
        $this->assertTrue(UserHierarchyRelation::where('subordinate_id', $free->id)->where('superior_id', $member->id)->exists());

        // Quitar el jefe y los subordinados borra solo las relaciones de esa persona.
        Livewire::actingAs($admin)->test(PanelAdministracion::class)
            ->call('selectUser', $member->id)
            ->call('beginEditingUser')
            ->call('clearSuperiorSelection')
            ->set('userForm.subordinate_ids', [])
            ->call('saveSelectedUser')
            ->assertHasNoErrors();

        $this->assertSame(0, UserHierarchyRelation::where('subordinate_id', $member->id)->orWhere('superior_id', $member->id)->count());
    }

    public function test_reversing_a_relation_swaps_it_without_creating_a_cycle(): void
    {
        ['admin' => $admin, 'member' => $member, 'boss' => $boss] = $this->hierarchyData();

        // La jefa pasa a reportar a Ana, que hasta ahora le reportaba: la relación se invierte, no se duplica ni forma un ciclo.
        Livewire::actingAs($admin)->test(PanelAdministracion::class)
            ->call('selectUser', $boss->id)
            ->call('beginEditingUser')
            ->set('userForm.superior_ids', [$member->id])
            ->assertSet('userForm.subordinate_ids', [])
            ->call('saveSelectedUser')
            ->assertHasNoErrors()
            ->assertDispatched('notify', message: 'Usuario actualizado.', type: 'success');

        $this->assertTrue(UserHierarchyRelation::where('subordinate_id', $boss->id)->where('superior_id', $member->id)->exists());
        $this->assertFalse(UserHierarchyRelation::where('subordinate_id', $member->id)->where('superior_id', $boss->id)->exists());
        $this->assertSame(1, UserHierarchyRelation::count());
    }

    public function test_the_boss_checkbox_selects_one_person_at_a_time_and_unchecks_on_second_click(): void
    {
        ['admin' => $admin, 'member' => $member, 'boss' => $boss, 'free' => $free] = $this->hierarchyData();

        Livewire::actingAs($admin)->test(PanelAdministracion::class)
            ->call('selectUser', $member->id)
            ->call('beginEditingUser')
            ->assertSet('userForm.superior_ids', [$boss->id])
            ->call('toggleSuperior', $free->id)
            ->assertSet('userForm.superior_ids', [$free->id])
            ->call('toggleSuperior', $boss->id)
            ->assertSet('userForm.superior_ids', [$boss->id])
            ->call('toggleSuperior', $boss->id)
            ->assertSet('userForm.superior_ids', []);
    }

    public function test_a_failure_while_saving_keeps_the_form_open_and_warns(): void
    {
        ['admin' => $admin, 'member' => $member] = $this->setUpData();

        // El correo ya lo usa otra persona: no se guarda nada y el aviso sale arriba.
        Livewire::actingAs($admin)->test(PanelAdministracion::class)
            ->call('selectUser', $member->id)
            ->call('beginEditingUser')
            ->set('userForm.name', 'Nombre que no debe guardarse')
            ->set('userForm.email', 'admin@datamid.test')
            ->call('saveSelectedUser')
            ->assertHasErrors('userForm.email')
            ->assertDispatched('notify', type: 'error')
            ->assertSet('selectedUserId', $member->id)
            ->assertSet('isEditingUser', true);

        $this->assertSame('ANA', $member->fresh()->name);
    }
}
