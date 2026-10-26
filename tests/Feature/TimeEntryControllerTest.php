<?php

namespace Tests\Feature;

use App\Models\JobPosition;
use App\Models\PhysicalArea;
use App\Models\Role;
use App\Models\User;
use App\Models\UserOrganizationalProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TimeEntryControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_shared_checker_id_uses_the_authenticated_users_profile(): void
    {
        Storage::fake('local');
        [$administrator, $firstUser, $secondUser] = $this->sharedCheckerUsers();

        $this->actingAs($secondUser)
            ->postJson(route('control-horas.consultar'), [
                'employee_id' => 'EMP-SHARED',
                'inicio' => '2026-10-01',
                'fin' => '2026-10-02',
            ])
            ->assertOk()
            ->assertJsonPath('settings.hourly_rate', 80);

        $this->actingAs($administrator)
            ->postJson(route('control-horas.consultar'), [
                'employee_id' => 'EMP-SHARED',
                'inicio' => '2026-10-01',
                'fin' => '2026-10-02',
            ])
            ->assertStatus(422);

        $this->actingAs($administrator)
            ->postJson(route('control-horas.consultar'), [
                'employee_id' => 'EMP-SHARED',
                'user_id' => $firstUser->id,
                'inicio' => '2026-10-01',
                'fin' => '2026-10-02',
            ])
            ->assertOk()
            ->assertJsonPath('settings.hourly_rate', 25);
    }

    /** @return array{User, User, User} */
    private function sharedCheckerUsers(): array
    {
        $administratorRole = Role::create(['role' => 'Administrador']);
        $auxiliarRole = Role::create(['role' => 'Auxiliar']);
        $position = JobPosition::create(['name' => 'Auxiliar compartido', 'payment_type' => JobPosition::PAYMENT_HOURLY]);
        $area = PhysicalArea::create(['name' => 'Operación compartida']);

        $administrator = User::create([
            'name' => 'Administrador',
            'email' => 'admin-shared-checker@test.mx',
            'email_verified_at' => now(),
            'password' => Hash::make('secret'),
            'role_id' => $administratorRole->id,
        ]);
        $firstUser = User::create([
            'name' => 'Primera',
            'email' => 'first-shared-checker@test.mx',
            'email_verified_at' => now(),
            'password' => Hash::make('secret'),
            'role_id' => $auxiliarRole->id,
            'employee_id' => 'EMP-SHARED',
        ]);
        $secondUser = User::create([
            'name' => 'Segunda',
            'email' => 'second-shared-checker@test.mx',
            'email_verified_at' => now(),
            'password' => Hash::make('secret'),
            'role_id' => $auxiliarRole->id,
            'employee_id' => 'EMP-SHARED',
        ]);

        foreach ([[$firstUser, 25], [$secondUser, 80]] as [$user, $hourlyRate]) {
            UserOrganizationalProfile::create([
                'user_id' => $user->id,
                'job_position_id' => $position->id,
                'physical_area_id' => $area->id,
                'hourly_rate' => $hourlyRate,
                'food_allowance' => 50,
                'valid_from' => now()->toDateString(),
                'is_active' => true,
            ]);
        }

        return [$administrator, $firstUser, $secondUser];
    }
}
