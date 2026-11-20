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
        $this->assertSame('JULIÁN EMILIANO', $tree[0]['first_name']);
        $this->assertSame('ORTIZ RIVERO', $tree[0]['last_name']);

        // El árbol se arma después de abrir la pantalla (wire:init → loadOrgChartData)
        Livewire::actingAs($boss)->test(PanelAdministracion::class)
            ->set('orgChartPage', true)
            ->call('loadOrgChartData')
            ->assertSeeHtml('>JULIÁN EMILIANO</span>')
            ->assertSeeHtml('>ORTIZ RIVERO</span>')
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

    /** jefe → (ana, luis); ana → (eva). Más una persona sin asignar. */
    private function biggerChart(): array
    {
        ['boss' => $boss, 'member' => $ana] = $this->chart();
        $role = Role::first();
        $luis = User::create(['name' => 'Luis', 'last_name' => 'Gómez', 'email' => 'luis@datamid.test', 'password' => Hash::make('x'), 'role_id' => $role->id]);
        $eva = User::create(['name' => 'Eva', 'last_name' => 'Ruiz', 'email' => 'eva@datamid.test', 'password' => Hash::make('x'), 'role_id' => $role->id]);
        $nuevo = User::create(['name' => 'Nuevo', 'last_name' => 'Ingreso', 'email' => 'nuevo@datamid.test', 'password' => Hash::make('x'), 'role_id' => $role->id]);
        $service = app(OrganizationChartService::class);
        $service->createRelation(['subordinate_id' => $luis->id, 'superior_id' => $boss->id]);
        $service->createRelation(['subordinate_id' => $eva->id, 'superior_id' => $ana->id]);

        return compact('boss', 'ana', 'luis', 'eva', 'nuevo');
    }

    private function superiorOf(User $user): ?int
    {
        $id = \App\Models\UserHierarchyRelation::where('subordinate_id', $user->id)->value('superior_id');

        return $id === null ? null : (int) $id;
    }

    public function test_dragging_a_person_onto_another_moves_her_with_her_whole_team(): void
    {
        ['boss' => $boss, 'ana' => $ana, 'luis' => $luis, 'eva' => $eva] = $this->biggerChart();

        Livewire::actingAs($boss)->test(PanelAdministracion::class)
            ->set('orgChartPage', true)
            ->call('loadOrgChartData')
            ->call('moveUserInChart', $ana->id, $luis->id, $boss->id)
            ->assertDispatched('org-node-moved', id: $ana->id)
            ->assertDispatched('notify');

        $this->assertSame($luis->id, $this->superiorOf($ana));
        $this->assertSame($ana->id, $this->superiorOf($eva), 'su equipo se mueve con ella');
    }

    public function test_a_person_cannot_be_moved_under_someone_of_her_own_team(): void
    {
        ['boss' => $boss, 'ana' => $ana, 'eva' => $eva] = $this->biggerChart();

        Livewire::actingAs($boss)->test(PanelAdministracion::class)
            ->set('orgChartPage', true)
            ->call('moveUserInChart', $ana->id, $eva->id, $boss->id)
            ->assertDispatched('notify', type: 'error');

        $this->assertSame($boss->id, $this->superiorOf($ana));
    }

    public function test_a_move_made_on_an_outdated_screen_is_not_applied_and_the_chart_refreshes(): void
    {
        ['boss' => $boss, 'ana' => $ana, 'luis' => $luis, 'eva' => $eva] = $this->biggerChart();

        // La pantalla cree que Eva está bajo Luis, pero en realidad está bajo Ana (otra persona la movió)
        Livewire::actingAs($boss)->test(PanelAdministracion::class)
            ->set('orgChartPage', true)
            ->call('moveUserInChart', $eva->id, $boss->id, $luis->id)
            ->assertDispatched('notify', type: 'error')
            ->assertSet('orgChartLoaded', true);

        $this->assertSame($ana->id, $this->superiorOf($eva));
    }

    public function test_dragging_someone_from_unassigned_onto_a_boss_assigns_him(): void
    {
        ['boss' => $boss, 'luis' => $luis, 'nuevo' => $nuevo] = $this->biggerChart();

        Livewire::actingAs($boss)->test(PanelAdministracion::class)
            ->set('orgChartPage', true)
            ->call('moveUserInChart', $nuevo->id, $luis->id, null);

        $this->assertSame($luis->id, $this->superiorOf($nuevo));
    }

    public function test_dropping_a_person_on_unassigned_takes_her_out_and_her_team_goes_up_to_her_boss(): void
    {
        ['boss' => $boss, 'ana' => $ana, 'eva' => $eva] = $this->biggerChart();

        $component = Livewire::actingAs($boss)->test(PanelAdministracion::class)
            ->set('orgChartPage', true)
            ->call('removeUserFromChart', $ana->id, $boss->id)
            ->assertDispatched('notify');

        $this->assertNull($this->superiorOf($ana));
        $this->assertDatabaseMissing('user_hierarchy_relations', ['superior_id' => $ana->id]);
        $this->assertSame($boss->id, $this->superiorOf($eva));
        $this->assertDatabaseHas('users', ['id' => $ana->id]);
        $this->assertContains($ana->id, array_column($component->get('unassignedUsers'), 'id'));
    }

    public function test_the_delete_confirmation_can_only_take_the_person_out_of_the_chart(): void
    {
        ['boss' => $boss, 'ana' => $ana, 'eva' => $eva] = $this->biggerChart();

        Livewire::actingAs($boss)->test(PanelAdministracion::class)
            ->call('requestDeleteUser', $ana->id)
            ->assertSee('Solo quitar del organigrama')
            ->call('removeSelectedUserFromChart')
            ->assertSet('selectedUserDetails', null);

        $this->assertDatabaseHas('users', ['id' => $ana->id]);
        $this->assertSame($boss->id, $this->superiorOf($eva));
    }

    public function test_deleting_a_person_keeps_the_chart_connected(): void
    {
        ['boss' => $boss, 'ana' => $ana, 'eva' => $eva] = $this->biggerChart();

        Livewire::actingAs($boss)->test(PanelAdministracion::class)
            ->call('requestDeleteUser', $ana->id)
            ->set('deleteConfirmationName', 'Ana Pérez')
            ->call('deleteSelectedUser')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('users', ['id' => $ana->id]);
        $this->assertSame($boss->id, $this->superiorOf($eva), 'su equipo pasa a su jefe');
    }

    public function test_the_chart_only_redraws_when_someone_else_changed_it(): void
    {
        ['boss' => $boss, 'luis' => $luis, 'nuevo' => $nuevo] = $this->biggerChart();

        $component = Livewire::actingAs($boss)->test(PanelAdministracion::class)
            ->set('orgChartPage', true)
            ->call('loadOrgChartData');
        $signature = $component->get('orgChartSignature');

        $component->call('syncOrgChart')->assertNotDispatched('org-chart-synced');
        $this->assertSame($signature, $component->get('orgChartSignature'));

        // Otra persona asigna a «Nuevo» desde su pantalla
        app(OrganizationChartService::class)->moveUser($nuevo->id, $luis->id, null);

        $component->call('syncOrgChart')->assertDispatched('org-chart-synced');
        $this->assertNotSame($signature, $component->get('orgChartSignature'));
        $this->assertNotContains($nuevo->id, array_column($component->get('unassignedUsers'), 'id'));
    }
}
