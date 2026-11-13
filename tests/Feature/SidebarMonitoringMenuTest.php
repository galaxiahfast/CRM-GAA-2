<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Menú lateral: la sección "Monitoreo" reúne dos apartados desplegables.
 * Primero Control de Horas (Reloj Checador) y después Control de Actividades
 * (Cronómetro, Productividad y Panel de Control, en ese orden).
 */
class SidebarMonitoringMenuTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $roleName, string $email): User
    {
        $role = Role::query()->firstOrCreate(['role' => $roleName]);
        $user = User::query()->create([
            'name' => str($roleName)->before(' ')->value(),
            'last_name' => 'Prueba',
            'email' => $email,
            'password' => Hash::make('secret'),
            'role_id' => $role->id,
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();

        return $user;
    }

    private function sidebar(User $user): string
    {
        return $this->flushSession()->actingAs($user)->get(route('profile.show'))->assertOk()->getContent();
    }

    public function test_the_section_is_called_monitoring_and_the_old_name_is_gone(): void
    {
        $html = $this->sidebar($this->userWithRole('Auxiliar', 'aux-menu@datamid.test'));

        $this->assertStringContainsString('>Monitoreo<', $html);
        $this->assertStringNotContainsString('>Actividades<', $html);
    }

    public function test_the_groups_use_capitalized_names(): void
    {
        $html = $this->sidebar($this->userWithRole('Auxiliar', 'aux-names@datamid.test'));

        $this->assertStringContainsString('>Control de Horas<', $html);
        $this->assertStringContainsString('>Control de Actividades<', $html);
        $this->assertStringNotContainsString('>Control de horas<', $html);
        $this->assertStringNotContainsString('>Control de actividades<', $html);
    }

    public function test_regular_users_get_hours_first_and_then_activities_with_their_items_in_order(): void
    {
        $html = $this->sidebar($this->userWithRole('Auxiliar', 'aux-groups@datamid.test'));

        $monitoring = strpos($html, '>Monitoreo<');
        $hours = strpos($html, '>Control de Horas<');
        $activities = strpos($html, '>Control de Actividades<');
        $this->assertNotFalse($monitoring);
        $this->assertNotFalse($hours);
        $this->assertNotFalse($activities);

        // Primero Control de Horas y después Control de Actividades
        $this->assertGreaterThan($monitoring, $hours);
        $this->assertGreaterThan($hours, $activities);

        // Control de Horas: Reloj Checador
        $clock = strpos($html, '>Reloj Checador<', $hours);
        $this->assertNotFalse($clock);
        $this->assertLessThan($activities, $clock);

        // Control de Actividades: Cronómetro y Productividad (una sola página) y después Panel de Control
        $stopwatch = strpos($html, '>Cronómetro<', $activities);
        $panel = strpos($html, '>Panel de Control<', $activities);
        $this->assertNotFalse($stopwatch);
        $this->assertNotFalse($panel);
        $this->assertLessThan($panel, $stopwatch);
        $this->assertStringNotContainsString('>Productividad<', $html);

        // Cada apartado se abre y se cierra por separado y recuerda su estado
        $this->assertStringContainsString("sidebarOpenGet('time-hours')", $html);
        $this->assertStringContainsString("sidebarOpenGet('time-activities')", $html);
    }

    public function test_administrators_keep_their_items_split_between_the_two_groups(): void
    {
        $html = $this->sidebar($this->userWithRole('Administrador', 'admin-groups@datamid.test'));

        $hours = strpos($html, '>Control de Horas<');
        $activities = strpos($html, '>Control de Actividades<');
        $this->assertNotFalse($hours);
        $this->assertNotFalse($activities);
        $this->assertLessThan($activities, $hours);

        $clock = strpos($html, '>Reloj Checador<', $hours);
        $supervision = strpos($html, '>Supervisión de Horas<', $hours);
        $panel = strpos($html, '>Panel de Control<', $activities);
        $this->assertNotFalse($clock);
        $this->assertNotFalse($supervision);
        $this->assertLessThan($activities, $clock);
        $this->assertLessThan($activities, $supervision);
        $this->assertNotFalse($panel);
    }

    public function test_the_backup_management_link_is_only_for_administrators(): void
    {
        $this->assertStringContainsString('>Gestión de Respaldos<', $this->sidebar($this->userWithRole('Administrador', 'admin-backup-menu@datamid.test')));

        foreach (['Coordinador', 'Contador', 'Auxiliar', 'Becario', 'Becario Senior'] as $index => $roleName) {
            $html = $this->sidebar($this->userWithRole($roleName, 'menu-'.$index.'@datamid.test'));
            $this->assertStringNotContainsString('>Gestión de Respaldos<', $html, $roleName);
        }
    }
}
