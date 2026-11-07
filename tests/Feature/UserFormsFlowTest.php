<?php

namespace Tests\Feature;

use App\Livewire\Administracion\PanelAdministracion;
use App\Livewire\Administracion\Users\Form as UserForm;
use App\Models\JobPosition;
use App\Models\PhysicalArea;
use App\Models\Role;
use App\Models\User;
use App\Models\UserOrganizationalProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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

        Livewire::actingAs($admin)->test(UserForm::class, ['embedded' => true])
            ->set('name', 'Nueva')
            ->set('last_name', 'Persona')
            ->set('email', 'nueva@datamid.test')
            ->set('password', 'password-seguro')
            ->set('password_confirmation', 'password-seguro')
            ->set('role_id', $roles['Contador']->id)
            ->set('job_position_id', $position->id)
            ->set('physical_area_id', $area->id)
            ->set('employee_id', '')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('notify', message: 'Usuario agregado.', type: 'success')
            ->assertDispatched('user-management-closed');

        $created = User::where('email', 'nueva@datamid.test')->firstOrFail();
        $this->assertNull($created->employee_id);
    }

    public function test_editing_a_user_without_clock_id_says_updated_and_leaves_the_form(): void
    {
        ['admin' => $admin, 'member' => $member] = $this->setUpData();

        Livewire::actingAs($admin)->test(UserForm::class, ['embedded' => true, 'initialTab' => 'editar', 'initialUserId' => $member->id])
            ->assertSet('employee_id', 'BIO-1')
            ->set('employee_id', '')
            ->set('name', 'Ana María')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('notify', message: 'Usuario actualizado.', type: 'success')
            ->assertDispatched('user-management-closed');

        $member->refresh();
        $this->assertSame('Ana María', $member->name);
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
        $this->assertSame('Ana María', $member->name);
        $this->assertNull($member->employee_id);
    }

    public function test_the_clock_id_can_be_repeated_when_adding_or_editing(): void
    {
        ['admin' => $admin, 'member' => $member, 'position' => $position, 'area' => $area, 'roles' => $roles] = $this->setUpData();

        Livewire::actingAs($admin)->test(UserForm::class, ['embedded' => true])
            ->set('name', 'Otra')
            ->set('last_name', 'Persona')
            ->set('email', 'otra@datamid.test')
            ->set('password', 'password-seguro')
            ->set('password_confirmation', 'password-seguro')
            ->set('role_id', $roles['Contador']->id)
            ->set('job_position_id', $position->id)
            ->set('physical_area_id', $area->id)
            ->set('employee_id', 'BIO-1')
            ->call('save')
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

        $this->assertSame('Dionicio Alberto', $unassigned->fresh()->name);
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

        Livewire::actingAs($admin)->test(UserForm::class, ['embedded' => true, 'initialTab' => 'editar', 'initialUserId' => $unassigned->id])
            ->set('name', 'Dionicio Alberto')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('notify', message: 'Usuario actualizado.', type: 'success');

        $this->assertSame('Dionicio Alberto', $unassigned->fresh()->name);
    }
}
