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

    public function test_attendance_management_waits_for_the_report_selection(): void
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
            ->assertSet('userId', null)
            ->assertSet('selectedReportUserIds', [])
            ->assertSet('activeReportUserId', null)
            ->assertSet('searched', false)
            ->assertSee('Ana Asistencia');
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

    public function test_selection_loads_one_person_and_allows_switching_in_group_reports(): void
    {
        Storage::fake('local');
        $adminRole = Role::create(['role' => 'Administrador']);
        $auxRole = Role::create(['role' => 'Auxiliar']);
        $admin = User::create([
            'name' => 'Admin', 'email' => 'admin-reports@test.mx',
            'password' => Hash::make('secret'), 'role_id' => $adminRole->id,
        ]);
        $ana = User::create([
            'name' => 'Ana', 'last_name' => 'Uno', 'email' => 'ana-reports@test.mx',
            'password' => Hash::make('secret'), 'role_id' => $auxRole->id, 'employee_id' => 'EMP-R01',
        ]);
        $beto = User::create([
            'name' => 'Beto', 'last_name' => 'Dos', 'email' => 'beto-reports@test.mx',
            'password' => Hash::make('secret'), 'role_id' => $auxRole->id, 'employee_id' => 'EMP-R02',
        ]);

        foreach ([$ana, $beto] as $user) {
            foreach (['09:00:00', '18:00:00'] as $index => $time) {
                DB::table('control_de_horas')->insert([
                    'employeeID' => $user->employee_id,
                    'personName' => trim($user->name.' '.$user->last_name),
                    'authDateTime' => '2026-08-07 '.$time,
                    'authDate' => '2026-08-07',
                    'authTime' => $time,
                    'direction' => $index === 0 ? 'IN' : 'OUT',
                    'deviceName' => 'Prueba',
                ]);
            }
        }

        Livewire::actingAs($admin)->test(AttendanceManagement::class)
            ->set('from', '2026-08-07')
            ->set('to', '2026-08-07')
            ->set('selectedReportUserIds', [$ana->id])
            ->call('generateSelectionReport')
            ->assertSet('selectionReportIsCurrent', true)
            ->assertSet('activeReportUserId', $ana->id)
            ->assertSet('employeeId', 'EMP-R01')
            ->assertSet('selectedEmployeeName', 'Ana Uno')
            ->assertSet('payrollRows.0.neto', '09h 00m 00s');

        $groupComponent = Livewire::actingAs($admin)->test(AttendanceManagement::class)
            ->set('from', '2026-08-07')
            ->set('to', '2026-08-07')
            ->set('selectedReportUserIds', [$ana->id, $beto->id])
            ->call('generateSelectionReport')
            ->assertSet('reportedUserIds', [$ana->id, $beto->id])
            ->assertSet('activeReportUserId', $ana->id)
            ->call('selectReportUser', $beto->id)
            ->assertSet('activeReportUserId', $beto->id)
            ->assertSet('employeeId', 'EMP-R02')
            ->assertSet('selectedEmployeeName', 'Beto Dos');

        foreach (['group' => 'grupal', 'general' => 'general'] as $mode => $filenameMode) {
            $groupComponent
                ->call('exportSelectionReport', $mode)
                ->assertFileDownloaded("reloj-checador-{$filenameMode}_2026-08-07_2026-08-07.pdf");
        }
    }

    public function test_admin_can_change_only_the_related_employee_id_and_active_hours_are_synchronized(): void
    {
        Storage::fake('local');
        $adminRole = Role::create(['role' => 'Administrador']);
        $auxRole = Role::create(['role' => 'Auxiliar']);
        $admin = User::create([
            'name' => 'Admin', 'email' => 'admin-id@test.mx',
            'password' => Hash::make('secret'), 'role_id' => $adminRole->id,
        ]);
        $aux = User::create([
            'name' => 'Clara', 'last_name' => 'Ríos', 'email' => 'clara-id@test.mx',
            'password' => Hash::make('secret'), 'role_id' => $auxRole->id, 'employee_id' => 'EMP-OLD',
        ]);
        $other = User::create([
            'name' => 'Mario', 'last_name' => 'Luna', 'email' => 'mario-id@test.mx',
            'password' => Hash::make('secret'), 'role_id' => $auxRole->id, 'employee_id' => 'EMP-TAKEN',
        ]);

        foreach (['10:00:00', '12:00:00'] as $index => $time) {
            DB::table('control_de_horas')->insert([
                'employeeID' => 'EMP-NEW',
                'personName' => 'Clara Ríos',
                'authDateTime' => '2026-08-07 '.$time,
                'authDate' => '2026-08-07',
                'authTime' => $time,
                'direction' => $index === 0 ? 'IN' : 'OUT',
                'deviceName' => 'Prueba',
            ]);
        }
        DB::table('control_de_horas')->insert([
            'employeeID' => 'EMP-TAKEN', 'personName' => 'Mario Luna',
            'authDateTime' => '2026-08-07 09:00:00', 'authDate' => '2026-08-07',
            'authTime' => '09:00:00', 'direction' => 'IN', 'deviceName' => 'Prueba',
        ]);

        $component = Livewire::actingAs($admin)->test(AttendanceManagement::class)
            ->set('from', '2026-08-07')
            ->set('to', '2026-08-07')
            ->set('selectedReportUserIds', [$aux->id])
            ->call('generateSelectionReport')
            ->call('openEmployeeIdModal', $aux->id)
            ->assertSet('showEmployeeIdModal', true)
            ->assertSet('editingEmployeeId', 'EMP-OLD')
            ->assertViewHas('employeeIdSuggestions', fn ($suggestions) => $suggestions->pluck('employeeID')->contains('EMP-NEW')
                && ! $suggestions->pluck('employeeID')->contains('EMP-TAKEN'))
            ->set('editingEmployeeId', 'EMP-NOT-REGISTERED')
            ->call('saveEmployeeId')
            ->assertHasErrors(['editingEmployeeId' => 'exists'])
            ->set('editingEmployeeId', 'EMP-TAKEN')
            ->call('saveEmployeeId')
            ->assertHasErrors(['editingEmployeeId' => 'unique'])
            ->set('editingEmployeeId', 'EMP-NEW')
            ->call('saveEmployeeId')
            ->assertHasNoErrors()
            ->assertSet('showEmployeeIdModal', false)
            ->assertSet('employeeId', 'EMP-NEW')
            ->assertSet('payrollRows.0.neto', '02h 00m 00s');

        $aux->refresh();
        $this->assertSame('EMP-NEW', $aux->employee_id);
        $this->assertSame('Clara', $aux->name);
        $this->assertSame('Ríos', $aux->last_name);
        $this->assertSame('clara-id@test.mx', $aux->email);
        $this->assertSame($auxRole->id, $aux->role_id);
        $this->assertSame('EMP-TAKEN', $other->fresh()->employee_id);
        $this->assertSame(2, DB::table('control_de_horas')->where('employeeID', 'EMP-NEW')->count());
    }
}
