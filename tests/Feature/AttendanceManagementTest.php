<?php

namespace Tests\Feature;

use App\Livewire\TimeControl\Admin\AttendanceManagement;
use App\Models\JobPosition;
use App\Models\PhysicalArea;
use App\Models\Role;
use App\Models\User;
use App\Models\UserOrganizationalProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AttendanceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_id_suggestions_are_loaded_only_when_the_editor_opens_and_then_cached(): void
    {
        Storage::fake('local');
        Cache::flush();

        $adminRole = Role::create(['role' => 'Administrador']);
        $admin = User::create([
            'name' => 'Admin', 'email' => 'admin-suggestions@test.mx',
            'password' => Hash::make('secret'), 'role_id' => $adminRole->id,
        ]);
        $collaborator = User::create([
            'name' => 'Ana', 'last_name' => 'Rápida', 'email' => 'ana-fast@test.mx',
            'password' => Hash::make('secret'), 'role_id' => $adminRole->id, 'employee_id' => 'EMP-OLD',
        ]);
        DB::table('control_de_horas')->insert([
            'employeeID' => 'EMP-AVAILABLE', 'personName' => 'Persona Disponible',
            'authDateTime' => '2026-08-07 09:00:00', 'authDate' => '2026-08-07',
            'authTime' => '09:00:00', 'direction' => 'IN', 'deviceName' => 'Prueba',
        ]);

        DB::enableQueryLog();
        $component = Livewire::actingAs($admin)->test(AttendanceManagement::class);
        $initialSuggestionQueries = collect(DB::getQueryLog())->pluck('query')->filter(
            fn (string $query): bool => str_contains(strtolower($query), 'control_de_horas')
                && str_contains(strtolower($query), 'max(')
        );
        $this->assertCount(0, $initialSuggestionQueries);
        $component->assertDontSeeHtml('wire:model.live="selectedReportUserIds"');
        $component->assertDontSee('Actualización automática');
        $component->assertDontSeeHtml('wire:click="selectAllReportUsers"');
        $component->assertSeeHtml('@click="selectAllUsers()"');
        $component->assertSeeHtml('@click="clearAllUsers()"');
        $component->assertSeeHtml('@change="syncSelection()"');

        DB::flushQueryLog();
        $component->call('openEmployeeIdModal', $collaborator->id)
            ->assertViewHas('employeeIdSuggestions', fn ($suggestions) => $suggestions->pluck('employeeID')->contains('EMP-AVAILABLE'));
        $firstOpenQueries = collect(DB::getQueryLog())->pluck('query')->filter(
            fn (string $query): bool => str_contains(strtolower($query), 'control_de_horas')
                && str_contains(strtolower($query), 'max(')
        );
        $this->assertCount(1, $firstOpenQueries);

        DB::flushQueryLog();
        $component->call('closeEmployeeIdModal')->call('openEmployeeIdModal', $collaborator->id);
        $cachedOpenQueries = collect(DB::getQueryLog())->pluck('query')->filter(
            fn (string $query): bool => str_contains(strtolower($query), 'control_de_horas')
                && str_contains(strtolower($query), 'max(')
        );
        DB::disableQueryLog();

        $this->assertCount(0, $cachedOpenQueries);
    }

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
            ->set('modalHourlyRate', 100)
            ->set('modalBonusAmount', 75.25)
            ->assertSet('modalCalculatedBasePay', 850.0)
            ->assertSet('modalCalculatedTotal', 925.25)
            ->call('saveDayAdjustment')
            ->assertHasErrors(['modalChangeComment' => 'required'])
            ->set('modalChangeComment', 'Se agregó la salida omitida por el dispositivo.')
            ->call('saveDayAdjustment')
            ->assertHasNoErrors()
            ->assertSet('showAttendanceModal', false)
            ->assertSet('payrollRows.0.estado', 'Corregido');

        // La tabla conserva el espejo crudo del biométrico; el ajuste se aplica
        // como una capa administrativa persistente sobre el informe.
        $this->assertSame(3, DB::table('control_de_horas')->where('employeeID', 'EMP-100')->where('authDate', '2026-08-07')->count());

        $settings = json_decode(Storage::disk('local')->get('checador_settings/EMP-100.json'), true);
        $override = $settings['day_overrides']['2026-08-07'];
        $this->assertEquals(100.0, $override['hourly_rate']);
        $this->assertArrayNotHasKey('daily_pay_amount', $override);
        $this->assertSame(75.25, $override['bonus_amount']);
        $this->assertSame('Se agregó la salida omitida por el dispositivo.', $override['comment']);
        $this->assertSame(['09:00:00', '13:00:00', '14:00:00', '18:30:15'], $override['marks']);
        $this->assertCount(1, $override['history']);

        // Simula otra sincronización del reloj: aunque los datos crudos sigan
        // incompletos, al volver a abrir el informe se conserva la corrección.
        Livewire::actingAs($admin)->test(AttendanceManagement::class)
            ->set('userId', $aux->id)
            ->set('from', '2026-08-07')
            ->set('to', '2026-08-07')
            ->call('searchAttendance')
            ->assertSet('payrollRows.0.estado', 'Corregido')
            ->assertSet('payrollRows.0.neto', '08h 30m 15s')
            ->call('editRow', '2026-08-07')
            ->assertSet('modalMarks', ['09:00:00', '13:00:00', '14:00:00', '18:30:15']);
    }

    public function test_weekends_never_receive_meal_bonus_and_pay_is_calculated_from_hours(): void
    {
        Storage::fake('local');
        $adminRole = Role::create(['role' => 'Administrador']);
        $auxRole = Role::create(['role' => 'Auxiliar']);
        $admin = User::create([
            'name' => 'Admin', 'email' => 'admin-weekend@test.mx',
            'password' => Hash::make('secret'), 'role_id' => $adminRole->id,
        ]);
        $aux = User::create([
            'name' => 'Fin', 'last_name' => 'Semana', 'email' => 'weekend@test.mx',
            'password' => Hash::make('secret'), 'role_id' => $auxRole->id, 'employee_id' => 'EMP-WEEKEND',
        ]);

        foreach (['2026-08-08', '2026-08-09', '2026-08-10'] as $date) {
            foreach (['09:00:00', '13:00:00'] as $index => $time) {
                DB::table('control_de_horas')->insert([
                    'employeeID' => $aux->employee_id,
                    'personName' => 'Fin Semana',
                    'authDateTime' => $date.' '.$time,
                    'authDate' => $date,
                    'authTime' => $time,
                    'direction' => $index === 0 ? 'IN' : 'OUT',
                    'deviceName' => 'Prueba',
                ]);
            }
        }

        $component = Livewire::actingAs($admin)->test(AttendanceManagement::class)
            ->set('userId', $aux->id)
            ->set('from', '2026-08-08')
            ->set('to', '2026-08-10')
            ->call('searchAttendance');

        $rows = collect($component->get('payrollRows'))->keyBy('fecha');
        $this->assertSame('$0.00', $rows['2026-08-08']['bono']);
        $this->assertSame('$0.00', $rows['2026-08-09']['bono']);
        $this->assertSame('$50.00', $rows['2026-08-10']['bono']);

        $component
            ->call('editRow', '2026-08-08')
            ->assertSet('selectedDateIsWeekend', true)
            ->set('modalHourlyRate', 125)
            ->set('modalBonusAmount', 99)
            ->set('modalChangeComment', 'Ajuste de tarifa por hora del sábado.')
            ->call('saveDayAdjustment')
            ->assertHasNoErrors();

        $rows = collect($component->get('payrollRows'))->keyBy('fecha');
        $this->assertSame('$500.00', $rows['2026-08-08']['pago_horas']);
        $this->assertSame('$0.00', $rows['2026-08-08']['bono']);
        $this->assertSame('$500.00', $rows['2026-08-08']['total']);

        $override = json_decode(Storage::disk('local')->get('checador_settings/EMP-WEEKEND.json'), true)['day_overrides']['2026-08-08'];
        $this->assertEquals(125.0, $override['hourly_rate']);
        $this->assertEquals(0.0, $override['bonus_amount']);
        $this->assertArrayNotHasKey('daily_pay_amount', $override);
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
        $hourlyPosition = JobPosition::create(['name' => 'Auxiliar sincronizado', 'payment_type' => JobPosition::PAYMENT_HOURLY]);
        $area = PhysicalArea::create(['name' => 'Operaciones sincronizadas']);
        UserOrganizationalProfile::create([
            'user_id' => $aux->id,
            'job_position_id' => $hourlyPosition->id,
            'physical_area_id' => $area->id,
            'hourly_rate' => 25,
            'food_allowance' => 50,
            'valid_from' => now()->toDateString(),
            'is_active' => true,
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
            ->set('editingHourlyRate', 37.50)
            ->set('editingFoodAllowance', 62.25)
            ->call('saveEmployeeId')
            ->assertHasNoErrors()
            ->assertSet('showEmployeeIdModal', false)
            ->assertSet('employeeId', 'EMP-NEW')
            ->assertSet('payrollRows.0.neto', '02h 00m 00s');

        $aux->refresh();
        $this->assertSame('EMP-NEW', $aux->employee_id);
        $this->assertSame('37.50', $aux->activeOrganizationalProfile->hourly_rate);
        $this->assertSame('62.25', $aux->activeOrganizationalProfile->food_allowance);
        $settings = json_decode(Storage::disk('local')->get('checador_settings/EMP-NEW.json'), true);
        $this->assertEquals(37.50, $settings['hourly_rate']);
        $this->assertEquals(62.25, $settings['bonus_amount']);
        $this->assertSame('Clara', $aux->name);
        $this->assertSame('Ríos', $aux->last_name);
        $this->assertSame('clara-id@test.mx', $aux->email);
        $this->assertSame($auxRole->id, $aux->role_id);
        $this->assertSame('EMP-TAKEN', $other->fresh()->employee_id);
        $this->assertSame(2, DB::table('control_de_horas')->where('employeeID', 'EMP-NEW')->count());
    }
}
