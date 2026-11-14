<?php

namespace Tests\Feature;

use App\Livewire\Testing\ManagerControlOwnHours;
use App\Models\Customer;
use App\Models\JobPosition;
use App\Models\ManagerControlEmployeeLink;
use App\Models\PhysicalArea;
use App\Models\Role;
use App\Models\Service;
use App\Models\SubService;
use App\Models\TimeEntry;
use App\Models\User;
use App\Models\UserOrganizationalProfile;
use App\Services\ManagerControl\ClockBalancer;
use App\Services\ManagerControl\ManagerControlException;
use App\Services\ManagerControl\ReadOnlyConnection;
use App\Services\ManagerControl\ReadOnlyGuard;
use App\Services\ManagerControl\TimerSync;
use App\Services\ManagerControl\WriteConnection;
use App\Services\TimeControl\TimerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Cronómetro: se pausa al cerrar la página o la sesión, pero no al cambiar de apartado.
 * Cronómetro → ManagerControl (solo en ese sentido) y cuadre con el reloj checador.
 * Todo lo de ManagerControl va con conexiones simuladas: nunca se escribe en la base real.
 */
class TimerPresenceAndManagerControlSyncTest extends TestCase
{
    use RefreshDatabase;

    /** Registros de ManagerControl que devuelve la conexión simulada (consulta de horas trabajadas). */
    private array $mcRows = [];

    private object $writes;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'managercontrol.write.enabled' => true, 'managercontrol.write.username' => 'crm_captura', 'managercontrol.write.password' => 'x',
            'managercontrol.company_id' => 1, 'managercontrol.exercise_id' => 3,
            'managercontrol.timer_sync.enabled' => true, 'managercontrol.timer_sync.from' => '2026-11-12',
        ]);

        $test = $this;
        $this->app->instance(ReadOnlyConnection::class, new class($test) implements ReadOnlyConnection
        {
            public function __construct(private readonly TimerPresenceAndManagerControlSyncTest $test) {}

            public function select(string $sql, array $bindings = []): array
            {
                ReadOnlyGuard::assertReadOnly($sql);

                return match (true) {
                    str_contains($sql, 'AS username') => [['username' => 'ANA.PEREZ', 'rate' => 120.0]],
                    str_contains($sql, 'AS work_date') => $this->test->mcRowsFor($bindings),
                    str_contains($sql, '[Secciones]') => [['id' => 5, 'name' => 'Cuenta A']],
                    str_contains($sql, '[Clientes]') => [['id' => 10, 'name' => 'Cliente A'], ['id' => 11, 'name' => 'Cliente B']],
                    str_contains($sql, '[Actividades]') => [['id' => 20, 'name' => 'Contabilidad', 'section_id' => 5], ['id' => 21, 'name' => 'Nóminas', 'section_id' => 5]],
                    str_contains($sql, 'AS customer_id') => [['customer_id' => 10, 'activity_id' => 20]],
                    str_contains($sql, 'COUNT(*)') => [['id' => 7, 'total' => 1]],
                    default => [['id' => 7, 'name' => 'Ana Pérez']],
                };
            }

            public function diagnostics(): array
            {
                return ['engine' => 'Simulado'];
            }
        });

        $this->writes = new class implements WriteConnection
        {
            public array $calls = [];

            public int $nextId = 9001;

            public function insert(string $sql, array $bindings): int
            {
                $this->calls[] = ['insert', $sql, $bindings];

                return $this->nextId++;
            }

            public function update(string $sql, array $bindings): int
            {
                $this->calls[] = ['update', $sql, $bindings];

                return 1;
            }

            public function diagnostics(): array
            {
                return ['login' => 'crm_captura'];
            }
        };
        $this->app->instance(WriteConnection::class, $this->writes);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** @param list<mixed> $bindings [colaborador, desde, hasta, …] */
    public function mcRowsFor(array $bindings): array
    {
        [, $from, $to] = $bindings;

        return array_values(array_filter($this->mcRows, fn (array $row): bool => $row['work_date'] >= $from && $row['work_date'] <= $to));
    }

    private function mcRow(int $id, string $date, float $hours, int $customer = 10, int $activity = 20, string $description = 'Pólizas'): array
    {
        return ['record_id' => $id, 'work_date' => $date, 'customer' => 'Cliente A', 'activity' => $activity === 20 ? 'Contabilidad' : 'Nóminas',
            'description' => $description, 'hours' => (string) $hours, 'customer_id' => $customer, 'activity_id' => $activity];
    }

    private function seedData(): array
    {
        Role::create(['role' => 'Administrador']); // el rol con id 1 es administrador
        $aux = User::create(['name' => 'Ana', 'last_name' => 'Pérez', 'email' => 'ana@test.mx', 'password' => Hash::make('secret'), 'role_id' => Role::create(['role' => 'Auxiliar'])->id]);
        $aux->forceFill(['email_verified_at' => now()])->save();
        UserOrganizationalProfile::create([
            'user_id' => $aux->id, 'job_position_id' => JobPosition::create(['name' => 'Auxiliar'])->id,
            'physical_area_id' => PhysicalArea::create(['name' => 'Contabilidad'])->id, 'valid_from' => '2026-01-01', 'is_active' => true,
        ]);
        $customer = Customer::create(['name' => 'Cliente A', 'rfc' => 'XAXX010101000', 'created_by' => $aux->id]);
        $sub = new SubService(['sub_service' => 'Contabilidad', 'service_id' => Service::create(['service' => 'Fiscal'])->id]);
        $sub->unique_key = 'CONTA';
        $sub->save();

        return [$aux, $customer, $sub];
    }

    private function linkAll(User $user, Customer $customer, SubService $sub): void
    {
        ManagerControlEmployeeLink::query()->create(['user_id' => $user->id, 'legacy_employee_id' => 7, 'legacy_employee_name' => 'Ana Pérez']);
        foreach ([['customers', $customer->id, 10], ['activities', $sub->id, 20]] as [$kind, $crmId, $legacyId]) {
            DB::table('managercontrol_catalog_links')->insert(['kind' => $kind, 'crm_id' => $crmId, 'company_id' => 1, 'exercise_id' => 3, 'legacy_id' => $legacyId, 'origin' => 'matched', 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    /** Actividad del Cronómetro con su tiempo, en hora local de México. */
    private function entry(User $user, Customer $customer, SubService $sub, string $date, string $start, string $end, int $status = TimeEntry::STATUS_PAUSED): TimeEntry
    {
        $profile = $user->activeOrganizationalProfile;
        $entry = TimeEntry::create([
            'user_id' => $user->id, 'customer_id' => $customer->id, 'sub_service_id' => $sub->id, 'entry_date' => $date, 'status' => $status, 'sort_order' => 0, 'total_duration_seconds' => 0,
            'role_id_snapshot' => $user->role_id, 'job_position_id_snapshot' => $profile->job_position_id, 'physical_area_id_snapshot' => $profile->physical_area_id,
        ]);
        $entry->intervals()->create(['started_at' => "{$date} {$start}", 'ended_at' => $status === TimeEntry::STATUS_IN_PROGRESS ? null : "{$date} {$end}"]);

        return $entry;
    }

    // ===== Cronómetro: pausa al salir =====

    public function test_closing_the_page_pauses_the_activity_at_the_last_time_the_user_was_seen(): void
    {
        [$aux, $customer, $sub] = $this->seedData();
        $timer = app(TimerService::class);

        Carbon::setTestNow('2026-11-14 15:00:00'); // 09:00 en México
        $entry = $timer->start($aux, $customer->id, $sub->id);
        // Tuvo el CRM abierto (cambiando de apartados) hasta las 10:30 y luego cerró el navegador
        $aux->forceFill(['last_seen_at' => Carbon::parse('2026-11-14 16:30:00')])->save();

        Carbon::setTestNow('2026-11-14 16:32:00'); // dos minutos después: todavía dentro del margen
        $this->assertSame(0, $timer->pauseAbandoned());
        $this->assertSame(TimeEntry::STATUS_IN_PROGRESS, $entry->fresh()->status);

        Carbon::setTestNow('2026-11-14 16:59:00'); // el programador corre cada minuto
        $this->artisan('time:pause-abandoned')->assertSuccessful();
        Carbon::setTestNow('2026-11-14 17:00:00'); // ya pasaron más de 3 minutos sin el CRM abierto
        $this->artisan('time:pause-abandoned')->assertSuccessful();

        $entry->refresh();
        $this->assertSame(TimeEntry::STATUS_PAUSED, $entry->status);
        // Cuenta de 09:00 a 10:30 (hora de México): no se cuenta el tiempo en que ya no estaba
        $this->assertSame(90 * 60, $entry->total_duration_seconds);
    }

    public function test_after_a_power_cut_only_those_who_are_gone_are_paused_at_the_moment_they_left(): void
    {
        [$ana, $customer, $sub] = $this->seedData();
        $beto = User::create(['name' => 'Beto', 'last_name' => 'Díaz', 'email' => 'beto@test.mx', 'password' => Hash::make('secret'), 'role_id' => $ana->role_id]);
        UserOrganizationalProfile::create(['user_id' => $beto->id, 'job_position_id' => $ana->activeOrganizationalProfile->job_position_id,
            'physical_area_id' => $ana->activeOrganizationalProfile->physical_area_id, 'valid_from' => '2026-01-01', 'is_active' => true]);
        $timer = app(TimerService::class);

        // 09:00 en México: los dos empiezan y el programador está corriendo
        Carbon::setTestNow('2026-11-14 15:00:00');
        $anaEntry = $timer->start($ana, $customer->id, $sub->id);
        $betoEntry = $timer->start($beto, $customer->id, $sub->id);
        foreach ([$ana, $beto] as $user) {
            $user->forceFill(['last_seen_at' => now()])->save();
        }
        $this->artisan('time:pause-abandoned')->assertSuccessful();

        // Se va la luz de 09:00 a 11:00: el servidor no corre nada. Al volver, Ana sigue con el CRM abierto; Beto no.
        Carbon::setTestNow('2026-11-14 17:00:00');
        $this->artisan('time:pause-abandoned')->assertSuccessful(); // primera vuelta tras el apagón: no pausa a nadie
        $this->assertSame(TimeEntry::STATUS_IN_PROGRESS, $anaEntry->fresh()->status);
        $this->assertSame(TimeEntry::STATUS_IN_PROGRESS, $betoEntry->fresh()->status);

        Carbon::setTestNow('2026-11-14 17:00:30');
        $ana->forceFill(['last_seen_at' => now()])->save(); // el navegador de Ana vuelve a avisar
        Carbon::setTestNow('2026-11-14 17:01:00');
        $this->artisan('time:pause-abandoned')->assertSuccessful();

        $this->assertSame(TimeEntry::STATUS_IN_PROGRESS, $anaEntry->fresh()->status);
        $betoEntry->refresh();
        $this->assertSame(TimeEntry::STATUS_PAUSED, $betoEntry->status);
        $this->assertSame(0, $betoEntry->total_duration_seconds); // Beto se pausa a las 09:00, cuando se le vio por última vez
    }

    public function test_changing_sections_keeps_the_activity_running(): void
    {
        [$aux, $customer, $sub] = $this->seedData();
        $timer = app(TimerService::class);

        Carbon::setTestNow('2026-11-14 15:00:00');
        $entry = $timer->start($aux, $customer->id, $sub->id);
        // Mientras navega por otros apartados el navegador sigue avisando y last_seen_at se mantiene al día
        foreach (['15:20:00', '15:40:00', '16:00:00'] as $time) {
            Carbon::setTestNow("2026-11-14 {$time}");
            $aux->forceFill(['last_seen_at' => now()->subSeconds(30)])->save();
            $timer->pauseAbandoned();
        }

        $this->assertSame(TimeEntry::STATUS_IN_PROGRESS, $entry->fresh()->status);
    }

    public function test_logging_out_pauses_the_running_activity(): void
    {
        [$aux, $customer, $sub] = $this->seedData();
        Carbon::setTestNow('2026-11-14 15:00:00');
        $entry = app(TimerService::class)->start($aux, $customer->id, $sub->id);

        Carbon::setTestNow('2026-11-14 15:45:00');
        Auth::login($aux);
        Auth::logout();

        $entry->refresh();
        $this->assertSame(TimeEntry::STATUS_PAUSED, $entry->status);
        $this->assertSame(45 * 60, $entry->total_duration_seconds);
    }

    // ===== Cronómetro → ManagerControl =====

    public function test_the_timer_sends_each_day_customer_and_activity_once_and_never_reads_back(): void
    {
        [$aux, $customer, $sub] = $this->seedData();
        $this->linkAll($aux, $customer, $sub);
        Carbon::setTestNow('2026-11-14 20:00:00');
        $this->entry($aux, $customer, $sub, '2026-11-11', '09:00:00', '12:00:00'); // antes del 12: no se envía
        $this->entry($aux, $customer, $sub, '2026-11-12', '09:00:00', '11:30:00'); // 2.5 h
        $this->entry($aux, $customer, $sub, '2026-11-13', '09:00:00', '10:00:00');
        $this->entry($aux, $customer, $sub, '2026-11-13', '15:00:00', '15:15:00'); // mismo día, cliente y actividad: se suman (1.25 h)
        $this->entry($aux, $customer, $sub, '2026-11-14', '09:00:00', '', TimeEntry::STATUS_IN_PROGRESS); // en progreso: espera

        // Sin --apply solo se revisa: no se escribe nada
        $this->artisan('managercontrol:sync-timer')->assertSuccessful();
        $this->assertSame([], $this->writes->calls);

        $result = app(TimerSync::class)->run(null, null, true);
        $this->assertSame(2, $result['created']);
        $inserts = array_values(array_filter($this->writes->calls, fn (array $call): bool => $call[0] === 'insert'));
        $this->assertCount(2, $inserts);
        // colaborador 7, actividad 20, cliente 10, horas en décimas, fecha
        $this->assertSame([7, 20, 10, 2.5], [$inserts[0][2][3], $inserts[0][2][4], $inserts[0][2][5], $inserts[0][2][6]]);
        $this->assertSame('2026-11-12', $inserts[0][2][8]);
        $this->assertSame(1.3, $inserts[1][2][6]); // 1:15 → 1.25 h → 1.3 en décimas, como el escritorio
        $this->assertDatabaseHas('managercontrol_timer_syncs', ['work_date' => '2026-11-13', 'legacy_record_id' => 9002, 'status' => 'synced']);

        // Otra vuelta sin cambios no duplica nada
        $this->writes->calls = [];
        $this->mcRows = [
            $this->mcRow(9001, '2026-11-12', 2.5, description: 'Registrado desde el Cronómetro del CRM'),
            $this->mcRow(9002, '2026-11-13', 1.3, description: 'Registrado desde el Cronómetro del CRM'),
        ];
        $this->assertSame(2, app(TimerSync::class)->run(null, null, true)['unchanged']);
        $this->assertSame([], $this->writes->calls);
    }

    public function test_a_timer_change_updates_its_record_unless_it_was_edited_in_managercontrol(): void
    {
        [$aux, $customer, $sub] = $this->seedData();
        $this->linkAll($aux, $customer, $sub);
        Carbon::setTestNow('2026-11-14 20:00:00');
        $this->entry($aux, $customer, $sub, '2026-11-12', '09:00:00', '11:00:00');
        $other = $this->entry($aux, $customer, $sub, '2026-11-13', '09:00:00', '11:00:00');
        app(TimerSync::class)->run(null, null, true); // 9001 (día 12) y 9002 (día 13), 2.0 h cada uno

        // El día 12 sigue como lo dejó el CRM; el 13 lo editaron en ManagerControl (3.0 h)
        $this->mcRows = [
            $this->mcRow(9001, '2026-11-12', 2.0, description: 'Registrado desde el Cronómetro del CRM'),
            $this->mcRow(9002, '2026-11-13', 3.0, description: 'Registrado desde el Cronómetro del CRM'),
        ];
        // En el Cronómetro ambos días crecen media hora
        TimeEntry::query()->whereDate('entry_date', '2026-11-12')->first()->intervals()->create(['started_at' => '2026-11-12 12:00:00', 'ended_at' => '2026-11-12 12:30:00']);
        $other->intervals()->create(['started_at' => '2026-11-13 12:00:00', 'ended_at' => '2026-11-13 12:30:00']);
        $this->writes->calls = [];

        $result = app(TimerSync::class)->run(null, null, true);

        $this->assertSame(1, $result['updated']);
        $this->assertSame(1, $result['edited']);
        $this->assertCount(1, $this->writes->calls);
        [$kind, $sql, $bindings] = $this->writes->calls[0];
        $this->assertSame('update', $kind);
        $this->assertSame(2.5, $bindings[2]); // nuevas horas del día 12
        $this->assertContains(9001, $bindings);
        $this->assertDatabaseHas('managercontrol_timer_syncs', ['work_date' => '2026-11-13', 'status' => 'edited']);

        // Lo editado en ManagerControl no se vuelve a tocar
        $this->writes->calls = [];
        app(TimerSync::class)->run(null, null, true);
        $this->assertSame([], $this->writes->calls);
    }

    public function test_an_activity_deleted_from_the_timer_is_retired_in_managercontrol_only_if_untouched(): void
    {
        [$aux, $customer, $sub] = $this->seedData();
        $this->linkAll($aux, $customer, $sub);
        Carbon::setTestNow('2026-11-14 20:00:00');
        $first = $this->entry($aux, $customer, $sub, '2026-11-12', '09:00:00', '11:00:00');
        $second = $this->entry($aux, $customer, $sub, '2026-11-13', '09:00:00', '11:00:00');
        app(TimerSync::class)->run(null, null, true); // 9001 y 9002

        // Se borran las dos del Cronómetro; en ManagerControl el 9001 sigue igual y el 9002 lo editaron
        $this->mcRows = [
            $this->mcRow(9001, '2026-11-12', 2.0, description: 'Registrado desde el Cronómetro del CRM'),
            $this->mcRow(9002, '2026-11-13', 2.0, description: 'Lo corrigió el supervisor'),
        ];
        $first->delete();
        $second->delete();
        $this->writes->calls = [];

        $result = app(TimerSync::class)->run(null, null, true);

        $this->assertSame(1, $result['deleted']);
        $this->assertSame(1, $result['edited']);
        $this->assertCount(1, $this->writes->calls);
        $this->assertStringContainsString('SET [Baja] = 1', $this->writes->calls[0][1]); // baja lógica, nunca DELETE
        $this->assertSame(9001, $this->writes->calls[0][2][0]);
        $this->assertDatabaseHas('managercontrol_timer_syncs', ['work_date' => '2026-11-12', 'status' => 'deleted']);
        $this->assertDatabaseHas('managercontrol_timer_syncs', ['work_date' => '2026-11-13', 'status' => 'edited']);
    }

    public function test_unlinked_users_customers_or_activities_are_reported_and_not_sent(): void
    {
        [$aux, $customer, $sub] = $this->seedData();
        Carbon::setTestNow('2026-11-14 20:00:00');
        $this->entry($aux, $customer, $sub, '2026-11-12', '09:00:00', '10:00:00');

        $result = app(TimerSync::class)->run(null, null, true);

        $this->assertSame(1, $result['skipped']);
        $this->assertStringContainsString('no tiene colaborador de ManagerControl', $result['rows'][0]['message']);
        $this->assertSame([], $this->writes->calls);
    }

    public function test_nothing_is_sent_while_the_sync_is_turned_off(): void
    {
        config(['managercontrol.timer_sync.enabled' => false]);
        [$aux, $customer, $sub] = $this->seedData();
        $this->linkAll($aux, $customer, $sub);
        Carbon::setTestNow('2026-11-14 20:00:00');
        $this->entry($aux, $customer, $sub, '2026-11-12', '09:00:00', '10:00:00');

        $this->artisan('managercontrol:sync-timer --apply')->assertSuccessful();

        $this->assertSame([], $this->writes->calls);
        $this->assertDatabaseCount('managercontrol_timer_syncs', 0);
    }

    // ===== Cuadre con el reloj checador =====

    public function test_the_difference_is_spread_in_proportion_and_in_tenths(): void
    {
        // Reloj 8:27 → 8.5 h (85 décimas); capturado 1.0 + 0.5 + 0.3 = 1.8 h
        $changes = ClockBalancer::plan([['id' => 1, 'hours' => 1.0], ['id' => 2, 'hours' => 0.5], ['id' => 3, 'hours' => 0.3]], ClockBalancer::dayTarget(8 * 3600 + 27 * 60));
        $this->assertEqualsWithDelta(8.5, array_sum($changes), 0.001);
        $this->assertTrue($changes[1] > $changes[2] && $changes[2] > $changes[3]); // se respeta la proporción
        foreach ($changes as $hours) {
            $this->assertEqualsWithDelta(round($hours, 1), $hours, 0.0001); // siempre en décimas
        }

        // Si ya cuadra no cambia nada; si hay que bajar, ninguna actividad queda en menos de 0.1 h
        $this->assertSame([], ClockBalancer::plan([['id' => 1, 'hours' => 2.0], ['id' => 2, 'hours' => 1.0]], 30));
        $down = ClockBalancer::plan([['id' => 1, 'hours' => 5.0], ['id' => 2, 'hours' => 0.1]], 20);
        $this->assertEqualsWithDelta(2.0, ($down[1] ?? 5.0) + ($down[2] ?? 0.1), 0.001);
        $this->assertGreaterThanOrEqual(0.1, $down[2] ?? 0.1);

        $this->expectException(ManagerControlException::class);
        ClockBalancer::plan([['id' => 1, 'hours' => 1.0], ['id' => 2, 'hours' => 1.0], ['id' => 3, 'hours' => 1.0]], 2);
    }

    public function test_a_period_matches_the_clock_total_in_decimal_hours(): void
    {
        // 8:27, 8:33 y 3:57 → 8.45 + 8.55 + 3.95 = 20.95 h; redondeando cada día por separado darían 8.5 + 8.6 + 4.0 = 21.1
        $days = ['2026-11-10' => 8 * 3600 + 27 * 60, '2026-11-11' => 8 * 3600 + 33 * 60, '2026-11-12' => 3 * 3600 + 57 * 60];
        $targets = ClockBalancer::periodTargets($days);

        $this->assertSame((int) round(array_sum($days) / 360), array_sum($targets)); // 210 décimas = 21.0 h, el total del periodo
        foreach ($targets as $date => $target) {
            $this->assertLessThanOrEqual(1, abs($target - $days[$date] / 360)); // cada día a menos de 0.1 h de su tiempo real
        }
    }

    public function test_the_balloon_button_previews_and_balances_the_day_with_the_clock(): void
    {
        [$aux] = $this->seedData();
        ManagerControlEmployeeLink::query()->create(['user_id' => $aux->id, 'legacy_employee_id' => 7, 'legacy_employee_name' => 'Ana Pérez']);
        $aux->forceFill(['employee_id' => '88'])->save();
        Carbon::setTestNow('2026-11-12 18:00:00');
        // Reloj: 08:00 a 16:30 = 8.5 h; ManagerControl: 1.0 + 0.5 = 1.5 h
        foreach (['08:00:00' => 'IN', '16:30:00' => 'OUT'] as $time => $direction) {
            DB::table('control_de_horas')->insert(['employeeID' => '88', 'personName' => 'Ana', 'authDateTime' => "2026-11-10 {$time}", 'authDate' => '2026-11-10', 'authTime' => $time, 'direction' => $direction]);
        }
        $this->mcRows = [$this->mcRow(555, '2026-11-10', 1.0), $this->mcRow(556, '2026-11-10', 0.5, activity: 21, description: 'Nómina')];

        $component = Livewire::actingAs($aux)->test(ManagerControlOwnHours::class)
            ->assertSeeHtml('data-testid="clock-balance"')->assertSee('Cuadrar con el reloj checador')
            ->call('askBalance', '2026-11-10')
            ->assertSet('dialog.kind', 'balance')->assertSee('ManagerControl después de cuadrar')->assertSeeText('8.5 h');
        $this->assertSame([], $this->writes->calls); // la vista previa no escribe nada

        $component->call('confirmBalance')->assertSet('dialog', null)->assertSee('El día quedó cuadrado con el reloj checador.');

        $updates = array_values(array_filter($this->writes->calls, fn (array $call): bool => $call[0] === 'update'));
        $this->assertCount(2, $updates);
        $this->assertEqualsWithDelta(8.5, $updates[0][2][2] + $updates[1][2][2], 0.001); // las horas nuevas suman lo del reloj
        // cada cambio exige que el registro siga como se leyó (si lo cambiaron en el escritorio, no se sobrescribe)
        $this->assertStringContainsString('AND [HoraLaborada] = ?', $updates[0][1]);
    }
}
