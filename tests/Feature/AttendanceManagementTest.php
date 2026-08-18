<?php

namespace Tests\Feature;

use App\Livewire\TimeControl\Admin\AttendanceManagement;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AttendanceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_collaborator_with_employee_id_is_loaded_by_default(): void
    {
        Storage::fake('local');
        $adminRole = Role::create(['role' => 'Administrador']);
        $admin = User::create([
            'name' => 'Admin', 'email' => 'admin-default@test.mx',
            'password' => Hash::make('secret'), 'role_id' => $adminRole->id,
        ]);
        $collaborator = User::create([
            'name' => 'Ana', 'last_name' => 'Asistencia', 'email' => 'ana@test.mx',
            'password' => Hash::make('secret'), 'role_id' => $adminRole->id, 'employee_id' => 'EMP-DEFAULT',
        ]);

        Livewire::actingAs($admin)
            ->test(AttendanceManagement::class)
            ->assertSet('userId', $collaborator->id)
            ->assertSet('searchCollaborator', 'Ana Asistencia')
            ->assertSet('employeeId', 'EMP-DEFAULT')
            ->assertSet('searched', true);
    }

    public function test_admin_can_correct_daily_marks_pay_and_bonus_with_comment(): void
    {
        Storage::fake('local');

        $adminRole = Role::create(['role' => 'Administrador']);
        $auxRole = Role::create(['role' => 'Auxiliar']);
        $admin = User::create([
            'name' => 'Admin', 'email' => 'admin-attendance@test.mx',
            'password' => Hash::make('secret'), 'role_id' => $adminRole->id,
        ]);
        $aux = User::create([
            'name' => 'Aux', 'last_name' => 'Uno', 'email' => 'aux-attendance@test.mx',
            'password' => Hash::make('secret'), 'role_id' => $auxRole->id, 'employee_id' => 'EMP-100',
        ]);

        foreach (['09:00:00', '13:00:00', '14:00:00'] as $index => $time) {
            DB::table('control_de_horas')->insert([
                'employeeID' => $aux->employee_id,
                'personName' => 'Aux Uno',
                'authDateTime' => '2026-08-07 '.$time,
                'authDate' => '2026-08-07',
                'authTime' => $time,
                'direction' => $index % 2 === 0 ? 'IN' : 'OUT',
                'deviceName' => 'Prueba',
            ]);
        }

        Livewire::actingAs($admin)->test(AttendanceManagement::class)
            ->set('userId', $aux->id)
            ->set('from', '2026-08-07')
            ->set('to', '2026-08-07')
            ->call('searchAttendance')
            ->call('editRow', '2026-08-07')
            ->assertSet('showAttendanceModal', true)
            ->set('modalMarks', ['09:00:00', '13:00:00', '14:00:00', '18:30:15'])
            ->set('modalDailyPay', 850.50)
            ->set('modalBonusAmount', 75.25)
            ->call('saveDayAdjustment')
            ->assertHasErrors(['modalChangeComment' => 'required'])
            ->set('modalChangeComment', 'Se agregó la salida omitida por el dispositivo.')
            ->call('saveDayAdjustment')
            ->assertHasNoErrors()
            ->assertSet('showAttendanceModal', false)
            ->assertSet('payrollRows.0.estado', 'Corregido');

        $this->assertSame(4, DB::table('control_de_horas')->where('employeeID', 'EMP-100')->where('authDate', '2026-08-07')->count());
        $this->assertDatabaseHas('control_de_horas', [
            'employeeID' => 'EMP-100',
            'authDateTime' => '2026-08-07 18:30:15',
            'direction' => 'OUT',
        ]);

        $settings = json_decode(Storage::disk('local')->get('checador_settings/EMP-100.json'), true);
        $override = $settings['day_overrides']['2026-08-07'];
        $this->assertSame(850.5, $override['daily_pay_amount']);
        $this->assertSame(75.25, $override['bonus_amount']);
        $this->assertSame('Se agregó la salida omitida por el dispositivo.', $override['comment']);
        $this->assertCount(1, $override['history']);
    }
}
