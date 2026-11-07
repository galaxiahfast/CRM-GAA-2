<?php

namespace Tests\Feature;

use App\Livewire\Administracion\PanelAdministracion;
use App\Models\JobPosition;
use App\Models\PhysicalArea;
use App\Models\Role;
use App\Models\User;
use App\Models\UserOrganizationalProfile;
use App\Services\Administracion\OrganizationChartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class OrganigramaNodeActionsTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{boss: User, member: User} */
    private function chart(): array
    {
        $admin = Role::create(['role' => 'Administrador']);
        $aux = Role::create(['role' => 'Auxiliar']);
        $position = JobPosition::create(['name' => 'Director']);
        $area = PhysicalArea::create(['name' => 'Contabilidad']);

        $boss = User::create(['name' => 'Julián Emiliano', 'last_name' => 'Ortiz Rivero', 'email' => 'jefe@datamid.test', 'password' => Hash::make('x'), 'role_id' => $admin->id]);
        $member = User::create(['name' => 'Ana', 'last_name' => 'Pérez', 'email' => 'ana@datamid.test', 'password' => Hash::make('x'), 'role_id' => $aux->id]);

        foreach ([$boss, $member] as $user) {
            UserOrganizationalProfile::create(['user_id' => $user->id, 'job_position_id' => $position->id, 'physical_area_id' => $area->id, 'valid_from' => now()->toDateString(), 'is_active' => true]);
        }
        app(OrganizationChartService::class)->createRelation([
            'subordinate_id' => $member->id, 'superior_id' => $boss->id, 'job_position_id' => $position->id, 'physical_area_id' => $area->id,
        ]);

        return ['boss' => $boss, 'member' => $member];
    }

    public function test_each_node_shows_first_name_and_last_name_on_separate_lines_with_a_delete_button(): void
    {
        ['boss' => $boss, 'member' => $member] = $this->chart();

        $tree = app(OrganizationChartService::class)->buildChartData(null)['tree'];
        $this->assertSame('Julián Emiliano', $tree[0]['first_name']);
        $this->assertSame('Ortiz Rivero', $tree[0]['last_name']);

        // El árbol se arma después de abrir la pantalla (wire:init → loadOrgChartData)
        Livewire::actingAs($boss)->test(PanelAdministracion::class)
            ->set('orgChartPage', true)
            ->call('loadOrgChartData')
            ->assertSeeHtml('>Julián Emiliano</span>')
            ->assertSeeHtml('>Ortiz Rivero</span>')
            ->assertSeeHtml('wire:click="requestDeleteUser('.$member->id.')"');
    }

    public function test_the_x_of_a_node_opens_the_delete_confirmation_without_deleting_anyone(): void
    {
        ['boss' => $boss, 'member' => $member] = $this->chart();

        Livewire::actingAs($boss)->test(PanelAdministracion::class)
            ->call('requestDeleteUser', $member->id)
            ->assertSet('selectedUserId', $member->id)
            ->assertSet('activeTab', 'eliminar')
            ->assertSee('¿Estás seguro de eliminar este usuario?')
            ->assertDontSeeHtml('aria-label="Secciones del usuario"');

        $this->assertDatabaseHas('users', ['id' => $member->id]);
    }

    public function test_opening_a_user_normally_or_closing_goes_back_to_the_data_view(): void
    {
        ['boss' => $boss, 'member' => $member] = $this->chart();

        Livewire::actingAs($boss)->test(PanelAdministracion::class)
            ->call('requestDeleteUser', $member->id)
            ->call('closeUserDetails')
            ->assertSet('activeTab', 'datos')
            ->call('requestDeleteUser', $member->id)
            ->call('selectUser', $boss->id)
            ->assertSet('activeTab', 'datos')
            ->assertDontSee('¿Estás seguro de eliminar este usuario?');
    }

    public function test_deleting_from_the_node_still_requires_the_full_name(): void
    {
        ['boss' => $boss, 'member' => $member] = $this->chart();

        Livewire::actingAs($boss)->test(PanelAdministracion::class)
            ->call('requestDeleteUser', $member->id)
            ->set('deleteConfirmationName', 'Otra Persona')
            ->call('deleteSelectedUser')
            ->assertHasErrors('deleteConfirmationName');

        $this->assertDatabaseHas('users', ['id' => $member->id]);
    }
}
