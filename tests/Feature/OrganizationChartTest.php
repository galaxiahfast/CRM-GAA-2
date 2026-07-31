<?php

namespace Tests\Feature;

use App\Models\JobPosition;
use App\Models\PhysicalArea;
use App\Models\Role;
use App\Models\User;
use App\Models\UserHierarchyRelation;
use App\Models\UserOrganizationalProfile;
use App\Services\Administracion\OrganizationChartService;
use App\Livewire\Administracion\IndexAdministracion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class OrganizationChartTest extends TestCase
{
    use RefreshDatabase;

    private function seedRoles(): array
    {
        $adminRole = Role::create(['role' => 'Administrador']);
        $contadorRole = Role::create(['role' => 'Contador']);
        $auxRole = Role::create(['role' => 'Auxiliar']);

        return compact('adminRole', 'contadorRole', 'auxRole');
    }

    private function createUser(Role $role, string $email, string $name): User
    {
        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make('secret'),
            'role_id' => $role->id,
        ]);
    }

    private function assignProfile(User $user, JobPosition $position, PhysicalArea $area): void
    {
        UserOrganizationalProfile::create([
            'user_id' => $user->id,
            'job_position_id' => $position->id,
            'physical_area_id' => $area->id,
            'valid_from' => now()->toDateString(),
            'is_active' => true,
        ]);
    }

    public function test_builds_contaduria_hierarchy_tree(): void
    {
        ['contadorRole' => $contadorRole, 'auxRole' => $auxRole] = $this->seedRoles();

        $contadorPos = JobPosition::create(['name' => 'Contador']);
        $auxPos = JobPosition::create(['name' => 'Auxiliar Contable']);
        $becarioPos = JobPosition::create(['name' => 'Becario']);
        $area = PhysicalArea::create(['name' => 'Contabilidad']);

        $contador = $this->createUser($contadorRole, 'contador@test.mx', 'Contador');
        $auxiliar = $this->createUser($auxRole, 'aux@test.mx', 'Auxiliar');
        $becario = $this->createUser($auxRole, 'becario@test.mx', 'Becario');

        $this->assignProfile($contador, $contadorPos, $area);
        $this->assignProfile($auxiliar, $auxPos, $area);
        $this->assignProfile($becario, $becarioPos, $area);

        $service = app(OrganizationChartService::class);
        $service->createRelation([
            'subordinate_id' => $auxiliar->id,
            'superior_id' => $contador->id,
            'job_position_id' => $auxPos->id,
            'physical_area_id' => $area->id,
        ]);
        $service->createRelation([
            'subordinate_id' => $becario->id,
            'superior_id' => $auxiliar->id,
            'job_position_id' => $becarioPos->id,
            'physical_area_id' => $area->id,
        ]);

        $data = $service->buildChartData($area->id);

        $this->assertCount(1, $data['tree']);
        $this->assertSame($contador->id, $data['tree'][0]['id']);
        $this->assertCount(1, $data['tree'][0]['children']);
        $this->assertSame($auxiliar->id, $data['tree'][0]['children'][0]['id']);
        $this->assertSame($becario->id, $data['tree'][0]['children'][0]['children'][0]['id']);
    }

    public function test_lists_unassigned_users_missing_superior_or_profile(): void
    {
        ['adminRole' => $adminRole, 'auxRole' => $auxRole] = $this->seedRoles();
        $area = PhysicalArea::create(['name' => 'Contabilidad']);
        $position = JobPosition::create(['name' => 'Contador']);

        $rootBoss = $this->createUser($adminRole, 'root@test.mx', 'Director');
        $this->assignProfile($rootBoss, $position, $area);

        $complete = $this->createUser($adminRole, 'complete@test.mx', 'Completo');
        $this->assignProfile($complete, $position, $area);

        $noProfile = $this->createUser($auxRole, 'noprofile@test.mx', 'Sin Perfil');
        $noBoss = $this->createUser($auxRole, 'noboss@test.mx', 'Sin Jefe');
        $this->assignProfile($noBoss, $position, $area);

        $service = app(OrganizationChartService::class);
        $service->createRelation([
            'subordinate_id' => $complete->id,
            'superior_id' => $rootBoss->id,
            'job_position_id' => $position->id,
            'physical_area_id' => $area->id,
        ]);

        $data = $service->buildChartData();

        $unassignedIds = collect($data['unassigned'])->pluck('id')->all();

        $this->assertContains($noProfile->id, $unassignedIds);
        $this->assertContains($noBoss->id, $unassignedIds);
        $this->assertNotContains($complete->id, $unassignedIds);
    }

    public function test_tree_render_is_safe_when_cycles_exist_in_database(): void
    {
        ['contadorRole' => $contadorRole, 'auxRole' => $auxRole] = $this->seedRoles();

        $userA = $this->createUser($contadorRole, 'cycle-a@test.mx', 'Usuario A');
        $userB = $this->createUser($auxRole, 'cycle-b@test.mx', 'Usuario B');

        UserHierarchyRelation::insert([
            [
                'subordinate_id' => $userB->id,
                'superior_id' => $userA->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'subordinate_id' => $userA->id,
                'superior_id' => $userB->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $service = app(OrganizationChartService::class);
        $data = $service->buildChartData();

        $this->assertGreaterThan(0, $data['stats']['cycles_detected']);
        $this->assertNotEmpty($data['tree']);
    }

    public function test_prevents_circular_hierarchy(): void
    {
        ['contadorRole' => $contadorRole, 'auxRole' => $auxRole] = $this->seedRoles();

        $userA = $this->createUser($contadorRole, 'a@test.mx', 'Usuario A');
        $userB = $this->createUser($auxRole, 'b@test.mx', 'Usuario B');

        $service = app(OrganizationChartService::class);
        $service->createRelation([
            'subordinate_id' => $userB->id,
            'superior_id' => $userA->id,
        ]);

        $this->expectException(ValidationException::class);
        $service->createRelation([
            'subordinate_id' => $userA->id,
            'superior_id' => $userB->id,
        ]);
    }

    public function test_deleting_superior_detaches_subordinates(): void
    {
        ['contadorRole' => $contadorRole, 'auxRole' => $auxRole] = $this->seedRoles();

        $boss = $this->createUser($contadorRole, 'boss@test.mx', 'Jefe');
        $subordinate = $this->createUser($auxRole, 'sub@test.mx', 'Subordinado');

        $service = app(OrganizationChartService::class);
        $service->createRelation([
            'subordinate_id' => $subordinate->id,
            'superior_id' => $boss->id,
        ]);

        $boss->delete();

        $this->assertDatabaseMissing('user_hierarchy_relations', [
            'subordinate_id' => $subordinate->id,
            'superior_id' => $boss->id,
        ]);

        $data = $service->buildChartData();
        $unassignedIds = collect($data['unassigned'])->pluck('id')->all();
        $this->assertContains($subordinate->id, $unassignedIds);
    }

    public function test_deactivating_superior_profile_detaches_subordinates(): void
    {
        ['contadorRole' => $contadorRole, 'auxRole' => $auxRole] = $this->seedRoles();
        $area = PhysicalArea::create(['name' => 'Contabilidad']);
        $position = JobPosition::create(['name' => 'Contador']);

        $boss = $this->createUser($contadorRole, 'boss2@test.mx', 'Jefe');
        $subordinate = $this->createUser($auxRole, 'sub2@test.mx', 'Subordinado');

        $bossProfile = UserOrganizationalProfile::create([
            'user_id' => $boss->id,
            'job_position_id' => $position->id,
            'physical_area_id' => $area->id,
            'valid_from' => now()->toDateString(),
            'is_active' => true,
        ]);

        UserOrganizationalProfile::create([
            'user_id' => $subordinate->id,
            'job_position_id' => $position->id,
            'physical_area_id' => $area->id,
            'valid_from' => now()->toDateString(),
            'is_active' => true,
        ]);

        $service = app(OrganizationChartService::class);
        $service->createRelation([
            'subordinate_id' => $subordinate->id,
            'superior_id' => $boss->id,
        ]);

        $bossProfile->update(['is_active' => false]);

        $this->assertSame(0, UserHierarchyRelation::count());
    }

    public function test_administracion_index_still_loads(): void
    {
        ['adminRole' => $adminRole] = $this->seedRoles();

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.mx',
            'password' => Hash::make('secret'),
            'role_id' => $adminRole->id,
        ]);

        $this->actingAs($admin)
            ->get('/administracion')
            ->assertOk();
    }

    public function test_user_edit_modal_lists_existing_attendance_ids_with_their_names(): void
    {
        ['adminRole' => $adminRole, 'auxRole' => $auxRole] = $this->seedRoles();

        $administrator = $this->createUser($adminRole, 'admin-attendance-id@test.mx', 'Administrador');
        $subject = $this->createUser($auxRole, 'subject-attendance-id@test.mx', 'Usuario Editado');

        DB::table('control_de_horas')->insert([
            'employeeID' => 'BIO-77',
            'personName' => 'Persona del Checador',
            'authDateTime' => now(),
        ]);

        Livewire::actingAs($administrator)
            ->test(IndexAdministracion::class)
            ->call('selectUser', $subject->id)
            ->call('beginEditingUser')
            ->assertSee('BIO-77')
            ->assertSee('Persona del Checador');
    }

    public function test_editing_hierarchy_lists_exclude_the_same_person_from_the_opposite_relation(): void
    {
        ['adminRole' => $adminRole, 'contadorRole' => $contadorRole, 'auxRole' => $auxRole] = $this->seedRoles();
        $area = PhysicalArea::create(['name' => 'Contabilidad']);
        $position = JobPosition::create(['name' => 'Contador']);

        $administrator = $this->createUser($adminRole, 'admin-hierarchy@test.mx', 'Administrador');
        $subject = $this->createUser($auxRole, 'subject@test.mx', 'Usuario Editado');
        $candidate = $this->createUser($contadorRole, 'candidate@test.mx', 'Candidato Organigrama');
        $member = $this->createUser($auxRole, 'member@test.mx', 'Miembro Organigrama');

        foreach ([$subject, $candidate, $member] as $user) {
            $this->assignProfile($user, $position, $area);
        }

        app(OrganizationChartService::class)->createRelation([
            'subordinate_id' => $member->id,
            'superior_id' => $candidate->id,
        ]);

        Livewire::actingAs($administrator)
            ->test(IndexAdministracion::class)
            ->call('selectUser', $subject->id)
            ->set('userForm.subordinate_ids', [$candidate->id])
            ->set('userForm.superior_ids', [$candidate->id])
            ->assertSet('userForm.superior_ids', [$candidate->id])
            ->assertSet('userForm.subordinate_ids', []);
    }

    public function test_editing_excludes_a_superiors_ancestors_from_subordinate_selection(): void
    {
        ['adminRole' => $adminRole, 'contadorRole' => $contadorRole, 'auxRole' => $auxRole] = $this->seedRoles();

        $administrator = $this->createUser($adminRole, 'admin-lineage@test.mx', 'Administrador');
        $subject = $this->createUser($auxRole, 'subject-lineage@test.mx', 'Usuario Editado');
        $director = $this->createUser($contadorRole, 'director@test.mx', 'Director');
        $manager = $this->createUser($contadorRole, 'manager@test.mx', 'Gerente');
        $member = $this->createUser($auxRole, 'member-lineage@test.mx', 'Miembro Organigrama');

        $service = app(OrganizationChartService::class);
        $service->createRelation([
            'subordinate_id' => $manager->id,
            'superior_id' => $director->id,
        ]);
        $service->createRelation([
            'subordinate_id' => $member->id,
            'superior_id' => $manager->id,
        ]);

        Livewire::actingAs($administrator)
            ->test(IndexAdministracion::class)
            ->call('selectUser', $subject->id)
            ->set('userForm.superior_ids', [$manager->id])
            ->set('userForm.subordinate_ids', [$director->id])
            ->assertSet('userForm.superior_ids', [$manager->id])
            ->assertSet('userForm.subordinate_ids', []);
    }

    public function test_editing_keeps_only_the_last_selected_superior(): void
    {
        ['adminRole' => $adminRole, 'contadorRole' => $contadorRole, 'auxRole' => $auxRole] = $this->seedRoles();

        $administrator = $this->createUser($adminRole, 'admin-multiple@test.mx', 'Administrador');
        $subject = $this->createUser($auxRole, 'subject-multiple@test.mx', 'Usuario Editado');
        $director = $this->createUser($contadorRole, 'director-multiple@test.mx', 'Director');
        $manager = $this->createUser($contadorRole, 'manager-multiple@test.mx', 'Gerente');
        $member = $this->createUser($auxRole, 'member-multiple@test.mx', 'Miembro Organigrama');

        $service = app(OrganizationChartService::class);
        $service->createRelation([
            'subordinate_id' => $manager->id,
            'superior_id' => $director->id,
        ]);
        $service->createRelation([
            'subordinate_id' => $member->id,
            'superior_id' => $manager->id,
        ]);

        Livewire::actingAs($administrator)
            ->test(IndexAdministracion::class)
            ->call('selectUser', $subject->id)
            ->set('userForm.superior_ids', [$manager->id, $director->id])
            ->assertSet('userForm.superior_ids', [$director->id]);
    }

    public function test_service_rejects_a_second_superior_for_the_same_subordinate(): void
    {
        ['contadorRole' => $contadorRole, 'auxRole' => $auxRole] = $this->seedRoles();

        $firstBoss = $this->createUser($contadorRole, 'first-boss@test.mx', 'Primer Jefe');
        $secondBoss = $this->createUser($contadorRole, 'second-boss@test.mx', 'Segundo Jefe');
        $subordinate = $this->createUser($auxRole, 'single-boss@test.mx', 'Subordinado');
        $service = app(OrganizationChartService::class);

        $service->createRelation([
            'subordinate_id' => $subordinate->id,
            'superior_id' => $firstBoss->id,
        ]);

        $this->expectException(ValidationException::class);

        $service->createRelation([
            'subordinate_id' => $subordinate->id,
            'superior_id' => $secondBoss->id,
        ]);
    }

    public function test_editing_does_not_offer_a_subordinate_assigned_to_another_boss(): void
    {
        ['adminRole' => $adminRole, 'contadorRole' => $contadorRole, 'auxRole' => $auxRole] = $this->seedRoles();

        $administrator = $this->createUser($adminRole, 'admin-candidates@test.mx', 'Administrador');
        $firstBoss = $this->createUser($contadorRole, 'assigned-boss@test.mx', 'Jefe Actual');
        $secondBoss = $this->createUser($contadorRole, 'editing-boss@test.mx', 'Jefe Editado');
        $assignedSubordinate = $this->createUser($auxRole, 'assigned-subordinate@test.mx', 'Subordinado Ocupado');
        $availableSubordinate = $this->createUser($auxRole, 'available-subordinate@test.mx', 'Subordinado Disponible');

        app(OrganizationChartService::class)->createRelation([
            'subordinate_id' => $assignedSubordinate->id,
            'superior_id' => $firstBoss->id,
        ]);

        Livewire::actingAs($administrator)
            ->test(IndexAdministracion::class)
            ->call('selectUser', $secondBoss->id)
            ->assertViewHas('subordinateCandidates', function ($candidates) use ($assignedSubordinate, $availableSubordinate): bool {
                return ! $candidates->contains('id', $assignedSubordinate->id)
                    && $candidates->contains('id', $availableSubordinate->id);
            });
    }

    public function test_editing_rejects_a_superior_who_is_not_part_of_the_organization_chart(): void
    {
        ['adminRole' => $adminRole, 'contadorRole' => $contadorRole, 'auxRole' => $auxRole] = $this->seedRoles();
        $area = PhysicalArea::create(['name' => 'Contabilidad']);
        $position = JobPosition::create(['name' => 'Contador']);

        $administrator = $this->createUser($adminRole, 'admin-validation@test.mx', 'Administrador');
        $subject = $this->createUser($auxRole, 'subject-validation@test.mx', 'Usuario Editado');
        $chartSuperior = $this->createUser($contadorRole, 'chart-superior@test.mx', 'Jefe Organigrama');
        $chartMember = $this->createUser($auxRole, 'chart-member@test.mx', 'Miembro Organigrama');
        $outsideUser = $this->createUser($contadorRole, 'outside@test.mx', 'Usuario Sin Organigrama');

        foreach ([$subject, $chartSuperior, $chartMember, $outsideUser] as $user) {
            $this->assignProfile($user, $position, $area);
        }

        app(OrganizationChartService::class)->createRelation([
            'subordinate_id' => $chartMember->id,
            'superior_id' => $chartSuperior->id,
        ]);

        Livewire::actingAs($administrator)
            ->test(IndexAdministracion::class)
            ->call('selectUser', $subject->id)
            ->set('userForm.superior_ids', [$outsideUser->id])
            ->call('saveSelectedUser')
            ->assertHasErrors('userForm.superior_ids.0');
    }
}
