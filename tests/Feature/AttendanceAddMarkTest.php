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

/**
 * Reloj Checador (administrador): agregar una marca y elegir su hora o minuto.
 */
class AttendanceAddMarkTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $aux;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $adminRole = Role::create(['role' => 'Administrador']);
        $auxRole = Role::create(['role' => 'Auxiliar']);
        $this->admin = User::create([
            'name' => 'Admin', 'email' => 'admin-marks@test.mx',
            'password' => Hash::make('secret'), 'role_id' => $adminRole->id,
        ]);
        $this->aux = User::create([
            'name' => 'Aux', 'last_name' => 'Uno', 'email' => 'aux-marks@test.mx',
            'password' => Hash::make('secret'), 'role_id' => $auxRole->id, 'employee_id' => 'EMP-200',
        ]);

        foreach (['09:00:00', '13:00:00'] as $index => $time) {
            DB::table('control_de_horas')->insert([
                'employeeID' => $this->aux->employee_id,
                'personName' => 'Aux Uno',
                'authDateTime' => '2026-08-07 '.$time,
                'authDate' => '2026-08-07',
                'authTime' => $time,
                'direction' => $index % 2 === 0 ? 'IN' : 'OUT',
                'deviceName' => 'Prueba',
            ]);
        }
    }

    private function openDay()
    {
        return Livewire::actingAs($this->admin)->test(AttendanceManagement::class)
            ->set('userId', $this->aux->id)
            ->set('from', '2026-08-07')
            ->set('to', '2026-08-07')
            ->call('searchAttendance')
            ->call('editRow', '2026-08-07');
    }

    public function test_clicking_inside_the_time_picker_does_not_close_the_day_modal(): void
    {
        // El selector de hora se dibuja fuera del modal (teleport al body). Un clic dentro de él (en la hora, los
        // minutos o los segundos) no debe contar como "clic fuera" del modal: la regla debe ignorar ese selector.
        $this->openDay()
            ->assertSeeHtml('@click.away="if (! $event.target.closest(\'.attendance-time-popover\')) $wire.closeModal()"')
            ->assertSeeHtml('attendance-time-popover panel-surface panel-border fixed z-[100100]')
            ->assertSeeHtml('x-model="hour"')
            ->assertSeeHtml('x-model="minute"')
            ->assertSeeHtml('x-model="second"')
            ->assertSet('showAttendanceModal', true);
    }

    public function test_adding_a_mark_keeps_the_modal_open_and_shows_a_clear_empty_state(): void
    {
        $component = $this->openDay()
            ->assertSet('modalMarks', ['09:00:00', '13:00:00'])
            ->call('addAttendanceMark')
            ->assertSet('showAttendanceModal', true)
            ->assertSet('modalMarks', ['09:00:00', '13:00:00', ''])
            ->assertSee('Chequeo 3')
            ->assertSee('Las marcas impares requieren revisión.')
            ->assertSeeHtml("timePicker(\$wire.entangle('modalMarks.2').live, 'attendance-mark-2')");

        // Una marca vacía no se guarda y avisa en lugar de fallar en silencio
        $component->set('modalChangeComment', 'Se agrega la salida omitida.')
            ->call('saveDayAdjustment')
            ->assertHasErrors(['modalMarks.2'])
            ->assertSet('showAttendanceModal', true);
    }

    public function test_a_new_mark_can_be_picked_by_hour_and_minute_and_saved(): void
    {
        $this->openDay()
            ->call('addAttendanceMark')
            ->call('addAttendanceMark')
            // el selector envía la hora elegida (modalMarks.N) con wire:model live
            ->set('modalMarks.2', '14:30:00')
            ->set('modalMarks.3', '18:45:10')
            ->assertSet('showAttendanceModal', true)
            ->assertSet('modalMarks', ['09:00:00', '13:00:00', '14:30:00', '18:45:10'])
            ->assertDontSee('Las marcas impares requieren revisión.')
            ->set('modalChangeComment', 'Se agregaron la entrada y salida de la tarde.')
            ->call('saveDayAdjustment')
            ->assertHasNoErrors()
            ->assertSet('showAttendanceModal', false);

        $settings = json_decode(Storage::disk('local')->get('checador_settings/EMP-200.json'), true);
        $this->assertSame(
            ['09:00:00', '13:00:00', '14:30:00', '18:45:10'],
            $settings['day_overrides']['2026-08-07']['marks'],
        );
    }

    public function test_repeated_or_malformed_marks_are_rejected_without_closing_the_modal(): void
    {
        $this->openDay()
            ->call('addAttendanceMark')
            ->set('modalMarks.2', '09:00:00') // repetida
            ->set('modalChangeComment', 'Prueba de marca repetida.')
            ->call('saveDayAdjustment')
            ->assertHasErrors(['modalMarks.2'])
            ->assertSet('showAttendanceModal', true)
            ->set('modalMarks.2', '25:61:00') // formato inválido
            ->call('saveDayAdjustment')
            ->assertHasErrors(['modalMarks.2'])
            ->assertSet('showAttendanceModal', true);
    }

    public function test_a_mark_can_be_removed_and_the_remaining_ones_keep_their_order(): void
    {
        $this->openDay()
            ->call('addAttendanceMark')
            ->set('modalMarks.2', '15:00:00')
            ->call('removeAttendanceMark', 0)
            ->assertSet('modalMarks', ['13:00:00', '15:00:00'])
            ->assertSet('showAttendanceModal', true);
    }

    public function test_only_administrators_can_edit_marks(): void
    {
        Livewire::actingAs($this->aux)->test(AttendanceManagement::class)->assertForbidden();
    }
}
