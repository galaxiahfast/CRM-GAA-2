<?php

namespace Tests\Feature;

use App\Livewire\Testing\ManagerControlHours;
use App\Models\Role;
use App\Models\User;
use App\Services\ManagerControl\ConfigFileParser;
use App\Services\ManagerControl\HoursSummary;
use App\Services\ManagerControl\ManagerControlException;
use App\Services\ManagerControl\ManagerControlRepository;
use App\Services\ManagerControl\ReadOnlyConnection;
use App\Services\ManagerControl\ReadOnlyGuard;
use App\Services\ManagerControl\ReadOnlyViolation;
use App\Services\ManagerControl\TotalHoursPdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

/** Todo con una conexión SIMULADA: nunca se toca la base real de ManagerControl. */
class ManagerControlReadOnlyTest extends TestCase
{
    use RefreshDatabase;

    private const XML = <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<configuration>
  <connectionStrings>
    <add name="DefaultConnectionString" connectionString="Data Source=SRV-PRUEBA\INSTANCIA;Initial Catalog=BaseDePrueba;User ID=usuario_falso;password=clave-falsa-123" providerName="System.Data.SqlClient"/>
    <!--<add name="DefaultConnectionString" connectionString="Data Source=COMENTADO\X;Initial Catalog=Otra;User ID=a;password=b" providerName="System.Data.SqlClient" />-->
  </connectionStrings>
</configuration>
XML;

    private function fakeConnection(array $employees = [], array $hours = []): ReadOnlyConnection
    {
        return new class($employees, $hours) implements ReadOnlyConnection
        {
            public array $queries = [];

            public function __construct(private array $employees, private array $hours) {}

            public function select(string $sql, array $bindings = []): array
            {
                ReadOnlyGuard::assertReadOnly($sql);
                $this->queries[] = [$sql, $bindings];

                return str_contains($sql, 'AS work_date') ? $this->hours : $this->employees;
            }

            public function diagnostics(): array
            {
                return ['engine' => 'Microsoft SQL Server', 'version' => 'Simulado', 'database' => 'Prueba', 'login' => 'ro', 'can_write' => false, 'write_roles' => []];
            }
        };
    }

    private function user(string $roleName, string $email): User
    {
        $role = Role::query()->firstOrCreate(['role' => $roleName]);
        $user = User::query()->create([
            'name' => 'Prueba', 'last_name' => 'Legacy', 'email' => $email,
            'password' => Hash::make('secret'), 'role_id' => $role->id,
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();

        return $user;
    }

    private function sampleHours(): array
    {
        return [
            ['work_date' => '2026-11-02', 'customer' => 'Cliente A', 'activity' => 'Contabilidad', 'description' => 'Pólizas', 'hours' => '1.5'],
            ['work_date' => '2026-11-02', 'customer' => 'Cliente B', 'activity' => 'Contabilidad', 'description' => '', 'hours' => '0.5'],
            ['work_date' => '2026-11-03', 'customer' => 'Cliente A', 'activity' => 'Impuestos', 'description' => 'IVA', 'hours' => '2'],
        ];
    }

    public function test_parser_extracts_server_instance_and_database_without_exposing_credentials(): void
    {
        $info = (new ConfigFileParser)->parseXml(self::XML);

        $this->assertSame('SRV-PRUEBA', $info['host']);
        $this->assertSame('INSTANCIA', $info['instance']);
        $this->assertSame('BaseDePrueba', $info['database']);
        $this->assertTrue($info['file_has_credentials']);
        $this->assertStringNotContainsString('clave-falsa-123', json_encode($info));
        $this->assertStringNotContainsString('usuario_falso', json_encode($info));
    }

    public function test_parser_handles_port_and_rejects_dtd_and_missing_connection(): void
    {
        $parsed = (new ConfigFileParser)->parseConnectionString('Data Source=tcp:10.0.0.5,1433;Initial Catalog=X');
        $this->assertSame(['10.0.0.5', null, 1433], [$parsed['host'], $parsed['instance'], $parsed['port']]);

        $this->expectException(ManagerControlException::class);
        (new ConfigFileParser)->parseXml('<!DOCTYPE foo [<!ENTITY x SYSTEM "file:///etc/passwd">]><configuration/>');
    }

    public function test_parser_fails_loudly_when_the_named_connection_is_missing(): void
    {
        $this->expectException(ManagerControlException::class);
        (new ConfigFileParser)->parseXml(self::XML, 'NoExiste');
    }

    public function test_guard_accepts_select_and_rejects_every_kind_of_write(): void
    {
        ReadOnlyGuard::assertReadOnly('SELECT a FROM [dbo].[T] WHERE b = ?');
        ReadOnlyGuard::assertReadOnly("SELECT 'update' AS texto FROM T");

        foreach ([
            'INSERT INTO T VALUES (1)', 'UPDATE T SET a = 1', 'DELETE FROM T', 'DROP TABLE T', 'TRUNCATE TABLE T',
            'EXEC sp_who', 'SELECT * INTO copia FROM T', 'SELECT 1; DELETE FROM T', '/* x */ UPDATE T SET a = 1',
            "SELECT 1 -- ok\nUPDATE T SET a = 1", 'WITH x AS (SELECT 1) DELETE FROM T', 'SELECT * FROM OPENROWSET(\'a\')', '',
        ] as $sql) {
            try {
                ReadOnlyGuard::assertReadOnly($sql);
                $this->fail("Debió rechazar: {$sql}");
            } catch (ReadOnlyViolation) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_repository_only_sends_selects_with_bound_parameters(): void
    {
        $db = $this->fakeConnection([['id' => 7, 'name' => 'Ana']], $this->sampleHours());
        $repository = new ManagerControlRepository($db);

        $rows = $repository->workedHours(7, '2026-11-01', '2026-11-15');

        $this->assertCount(3, $rows);
        $this->assertSame(5400, $rows[0]['seconds']);
        [$sql, $bindings] = $db->queries[0];
        $this->assertSame([7, '2026-11-01', '2026-11-15', 1, 3], $bindings); // empleado, periodo, empresa y ejercicio
        $this->assertStringNotContainsString('2026-11', $sql);
    }

    public function test_repository_rejects_unsafe_schema_identifiers(): void
    {
        config(['managercontrol.schema.hours.table' => 'ControlHoras; DROP TABLE x']);

        $this->expectException(ManagerControlException::class);
        (new ManagerControlRepository($this->fakeConnection()))->workedHours(1, '2026-11-01', '2026-11-02');
    }

    public function test_hour_values_are_converted_to_seconds(): void
    {
        $this->assertSame(5400, ManagerControlRepository::toSeconds('1.5'));
        $this->assertSame(3720, ManagerControlRepository::toSeconds('01:02'));
        config(['managercontrol.hours_unit' => 'minutes']);
        $this->assertSame(90, ManagerControlRepository::toSeconds('1.5'));
    }

    public function test_only_administrators_can_open_the_screen_and_the_pdf(): void
    {
        $this->app->instance(ReadOnlyConnection::class, $this->fakeConnection([['id' => 7, 'name' => 'Ana']], $this->sampleHours()));
        $admin = $this->user('Administrador', 'admin-mc@datamid.test');
        $aux = $this->user('Auxiliar', 'aux-mc@datamid.test');
        $pdfUrl = route('testing.managercontrol.pdf', ['employee' => 7, 'from' => '2026-11-01', 'to' => '2026-11-15']);

        $this->actingAs($admin)->get(route('testing.managercontrol.index'))->assertOk()->assertSee('Registro de horas');
        $this->actingAs($admin)->get($pdfUrl)->assertOk()->assertHeader('Content-Type', 'application/pdf');

        $this->flushSession();
        $this->actingAs($aux)->get(route('testing.managercontrol.index'))->assertForbidden();
        $this->actingAs($aux)->get($pdfUrl)->assertForbidden();
        $this->app['auth']->forgetGuards();
        $this->flushSession();
        $this->get(route('testing.managercontrol.index'))->assertRedirect();
    }

    public function test_screen_shows_distributions_and_surfaces_connection_errors(): void
    {
        $this->app->instance(ReadOnlyConnection::class, $this->fakeConnection([['id' => 7, 'name' => 'Ana']], $this->sampleHours()));

        Livewire::actingAs($this->user('Administrador', 'admin-lw@datamid.test'))
            ->test(ManagerControlHours::class)
            ->assertSet('to', now(config('app.timezone') === 'UTC' ? 'America/Mexico_City' : config('app.timezone'))->toDateString())
            ->set('employeeId', '7')
            ->call('applyFilters')
            ->call('applyDetailFilters')
            ->assertSee('Cliente A')->assertSee('Impuestos')->assertSee('03:30:00')
            ->assertSeeHtml('prod-day-head')->assertSee('Registro de horas por día');

        \Illuminate\Support\Facades\Cache::flush();
        $failing = new class implements ReadOnlyConnection
        {
            public function select(string $sql, array $bindings = []): array
            {
                throw new ManagerControlException('Sin conexión simulada.');
            }

            public function diagnostics(): array
            {
                throw new ManagerControlException('Sin conexión simulada.');
            }
        };
        $this->app->instance(ReadOnlyConnection::class, $failing);

        Livewire::actingAs($this->user('Administrador', 'admin-lw2@datamid.test'))
            ->test(ManagerControlHours::class)
            ->assertSee('Sin conexión simulada.');
    }

    public function test_choosing_an_employee_refreshes_both_tables_without_pressing_consult(): void
    {
        $this->app->instance(ReadOnlyConnection::class, $this->fakeConnection([['id' => 7, 'name' => 'Ana']], $this->sampleHours()));

        Livewire::actingAs($this->user('Administrador', 'admin-auto@datamid.test'))
            ->test(ManagerControlHours::class)
            ->assertSet('employeeId', '7')->assertSet('loaded', true)->assertSee('Cliente A')
            ->set('employeeId', '')
            ->assertSet('loaded', false)->assertDontSee('Cliente A')
            ->set('employeeId', '7')
            ->assertSet('loaded', true)->assertSet('detailLoaded', true)
            ->assertSee('Cliente A')->assertSee('Impuestos')
            ->set('employeeId', '')
            ->assertSet('loaded', false)->assertSet('error', null);
    }

    public function test_admin_screen_opens_with_the_busiest_employee_already_loaded(): void
    {
        $db = $this->fakeConnection([['id' => 7, 'name' => 'Ana'], ['id' => 9, 'name' => 'Beto']], $this->sampleHours());
        $this->app->instance(ReadOnlyConnection::class, $db);

        Livewire::actingAs($this->user('Administrador', 'admin-top@datamid.test'))->test(ManagerControlHours::class)
            ->assertSet('employeeId', '7')->assertSet('detailLoaded', true)->assertSee('Impuestos');

        $top = array_values(array_filter($db->queries, fn ($q) => str_contains($q[0], 'COUNT(*)')));
        $this->assertCount(1, $top);
        $this->assertStringContainsString('GROUP BY', $top[0][0]);
        $today = \Carbon\Carbon::now('America/Mexico_City');
        $this->assertSame([$today->copy()->subDays(14)->toDateString(), $today->toDateString(), 1, 3], $top[0][1]);
    }

    public function test_default_period_is_fifteen_days_inclusive(): void
    {
        $this->app->instance(ReadOnlyConnection::class, $this->fakeConnection());

        $component = Livewire::actingAs($this->user('Administrador', 'admin-per@datamid.test'))->test(ManagerControlHours::class);

        $this->assertSame(14, (int) \Illuminate\Support\Carbon::parse($component->get('from'))->diffInDays($component->get('to')));
    }

    public function test_pdf_replicates_the_total_hours_report_layout(): void
    {
        $summary = HoursSummary::fromRows(array_map(fn ($r) => [
            'date' => $r['work_date'], 'customer' => $r['customer'], 'activity' => $r['activity'],
            'description' => $r['description'], 'seconds' => ManagerControlRepository::toSeconds($r['hours']),
        ], $this->sampleHours()));
        $pdf = new TotalHoursPdf;
        $html = $pdf->html(['id' => 7, 'name' => 'Ana Pérez'], '2026-11-01', '2026-11-15', $summary);

        foreach (['Total de Horas Laboradas', 'Del 01/11/2026 Al 15/11/2026', 'Ana Pérez', 'Cliente A', 'Impuestos', 'Total acumulado: 04:00:00', 'data:image/'] as $expected) {
            $this->assertStringContainsString($expected, $html);
        }
        $this->assertStringStartsWith('%PDF', $pdf->render(['id' => 7, 'name' => 'Ana Pérez'], '2026-11-01', '2026-11-15', $summary));
    }

    public function test_test_connection_command_reports_read_only_session_without_secrets(): void
    {
        $this->app->instance(ReadOnlyConnection::class, $this->fakeConnection());
        config(['managercontrol.host' => 'srv.local', 'managercontrol.database' => 'Base', 'managercontrol.username' => 'ro_user', 'managercontrol.password' => 'super-secreta-xyz']);

        $this->artisan('managercontrol:test-connection')
            ->expectsOutputToContain('Microsoft SQL Server')
            ->expectsOutputToContain('Conexión correcta y de solo lectura.')
            ->doesntExpectOutputToContain('super-secreta-xyz')
            ->assertSuccessful();
    }

    public function test_name_matcher_requires_seventy_percent_and_a_unique_match(): void
    {
        $matcher = new \App\Services\ManagerControl\NameMatcher;
        $employees = [
            ['id' => 1, 'name' => 'Juan Carlos Pérez López'],
            ['id' => 2, 'name' => 'María Fernanda Gómez Ruiz'],
            ['id' => 3, 'name' => 'Luis Hernández Soto'],
        ];

        $this->assertSame(1, $matcher->bestMatch('Juan Pérez', $employees)['employee']['id']);
        $this->assertSame(2, $matcher->bestMatch('Maria Fernanda Gomez', $employees)['employee']['id']);
        $this->assertSame('none', $matcher->bestMatch('Pedro Ramírez Cruz', $employees)['status']);

        $twins = [['id' => 1, 'name' => 'Ana López Díaz'], ['id' => 2, 'name' => 'Ana López Díez']];
        $this->assertSame('ambiguous', $matcher->bestMatch('Ana López', $twins)['status']);
        $this->assertSame(1, $matcher->bestMatch('Ana López Díaz', $twins)['employee']['id']);
    }

    public function test_own_screen_shows_only_the_matched_employee_and_ignores_client_supplied_ids(): void
    {
        $db = $this->fakeConnection([['id' => 7, 'name' => 'Prueba Becario Legacy'], ['id' => 8, 'name' => 'Otra Persona Distinta']], $this->sampleHours());
        $this->app->instance(ReadOnlyConnection::class, $db);
        $user = $this->user('Auxiliar', 'bec-mc@datamid.test');
        $user->forceFill(['name' => 'Prueba', 'last_name' => 'Becario Legacy'])->save();

        Livewire::actingAs($user)->test(\App\Livewire\Testing\ManagerControlOwnHours::class)
            ->set('employeeId', '8')
            ->assertSee('Control Gerencial')->assertDontSee('Colaborador: Prueba Becario Legacy')->assertSee('Cliente A');

        $hoursQueries = array_values(array_filter($db->queries, fn ($q) => str_contains($q[0], 'AS work_date')));
        $this->assertNotEmpty($hoursQueries);
        foreach ($hoursQueries as [, $bindings]) {
            $this->assertSame(7, $bindings[0]);
        }
    }

    public function test_own_screen_has_no_warning_banner_when_there_is_no_match(): void
    {
        $this->app->instance(ReadOnlyConnection::class, $this->fakeConnection([['id' => 8, 'name' => 'Otra Persona Distinta']], $this->sampleHours()));

        Livewire::actingAs($this->user('Auxiliar', 'sin-match@datamid.test'))
            ->test(\App\Livewire\Testing\ManagerControlOwnHours::class)
            ->assertDontSee('No se pudo consultar ManagerControl')
            ->assertDontSee('No se encontró en ManagerControl')
            ->assertDontSee('sin asignar')
            ->assertDontSee('Cliente de ejemplo');
    }

    public function test_admin_gets_name_suggestions_and_can_assign_them_all_at_once(): void
    {
        $db = $this->fakeConnection([['id' => 7, 'name' => 'Persona Uno'], ['id' => 8, 'name' => 'Persona Dos']], $this->sampleHours());
        $this->app->instance(ReadOnlyConnection::class, $db);
        $admin = $this->user('Administrador', 'admin-sug@datamid.test');
        $uno = User::query()->create(['name' => 'Persona', 'last_name' => 'Uno', 'email' => 'uno-sug@datamid.test', 'password' => Hash::make('secret'), 'role_id' => $admin->role_id]);
        $dos = User::query()->create(['name' => 'Persona', 'last_name' => 'Dos', 'email' => 'dos-sug@datamid.test', 'password' => Hash::make('secret'), 'role_id' => $admin->role_id]);

        // Cada usuario sin colaborador muestra su coincidencia de nombre y hay un botón para asignarlas todas.
        Livewire::actingAs($admin)->test(ManagerControlHours::class)
            ->assertSeeHtml('data-testid="assign-suggested"')->assertSeeHtml('data-testid="accept-suggestion"')
            ->assertSee('Asignar sugeridos (2)')
            ->call('assignSuggested')
            ->assertSee('Se asignaron 2 usuarios con su coincidencia de nombre.')
            ->assertDontSeeHtml('data-testid="assign-suggested"');

        $this->assertDatabaseHas('managercontrol_employee_links', ['user_id' => $uno->id, 'legacy_employee_id' => 7]);
        $this->assertDatabaseHas('managercontrol_employee_links', ['user_id' => $dos->id, 'legacy_employee_id' => 8]);
    }

    public function test_admin_can_assign_and_remove_a_crm_user_and_the_assignment_wins_over_the_name(): void
    {
        $db = $this->fakeConnection([['id' => 7, 'name' => 'Persona Uno'], ['id' => 8, 'name' => 'Persona Dos']], $this->sampleHours());
        $this->app->instance(ReadOnlyConnection::class, $db);
        $admin = $this->user('Administrador', 'admin-link@datamid.test');
        $becario = $this->user('Auxiliar', 'bec-link@datamid.test');
        $otro = $this->user('Auxiliar', 'otro-link@datamid.test');

        Livewire::actingAs($admin)->test(ManagerControlHours::class)
            ->assertSee('Asignar usuario del CRM')
            ->set("links.{$becario->id}", '8')->call('saveLink', $becario->id)
            ->assertSee('colaboradores en ManagerControl')->assertDontSee('usuarios del CRM ·')->assertDontSee('Asignado:')
            // el mismo colaborador SÍ puede estar en varias cuentas
            ->set("links.{$otro->id}", '8')->call('saveLink', $otro->id)
            ->assertDontSee('ya está asignado')
            ->set("links.{$otro->id}", '999')->call('saveLink', $otro->id)
            ->assertSee('no existe en ManagerControl');

        $this->assertDatabaseHas('managercontrol_employee_links', ['user_id' => $becario->id, 'legacy_employee_id' => 8, 'legacy_employee_name' => 'Persona Dos']);
        $this->assertDatabaseHas('managercontrol_employee_links', ['user_id' => $otro->id, 'legacy_employee_id' => 8]);

        $db->queries = [];
        Livewire::actingAs($becario)->test(\App\Livewire\Testing\ManagerControlOwnHours::class)->assertSee('Cliente A')->assertDontSee('Colaborador: Persona Dos');
        foreach (array_filter($db->queries, fn ($q) => str_contains($q[0], 'AS work_date')) as [, $bindings]) {
            $this->assertSame(8, $bindings[0]);
        }

        Livewire::actingAs($admin)->test(ManagerControlHours::class)->call('removeLink', $becario->id)->assertDontSee('Asignación quitada.');
        $this->assertDatabaseMissing('managercontrol_employee_links', ['user_id' => $becario->id]);
    }

    public function test_assignment_block_filters_by_assigned_unassigned_and_search(): void
    {
        $this->app->instance(ReadOnlyConnection::class, $this->fakeConnection([['id' => 7, 'name' => 'Persona Uno']]));
        $admin = $this->user('Administrador', 'admin-filter@datamid.test');
        $asignado = $this->user('Auxiliar', 'asignado-f@datamid.test');
        $asignado->forceFill(['name' => 'Zeta', 'last_name' => 'Asignada'])->save();
        $libre = $this->user('Auxiliar', 'libre-f@datamid.test');
        $libre->forceFill(['name' => 'Omega', 'last_name' => 'Libre'])->save();
        \App\Models\ManagerControlEmployeeLink::query()->create(['user_id' => $asignado->id, 'legacy_employee_id' => 7, 'legacy_employee_name' => 'Persona Uno']);

        Livewire::actingAs($admin)->test(ManagerControlHours::class)
            ->call('setLinkFilter', 'assigned')->assertSee('Zeta Asignada')->assertDontSee('Omega Libre')
            ->call('setLinkFilter', 'unassigned')->assertSee('Omega Libre')->assertDontSee('Zeta Asignada')
            ->call('setLinkFilter', 'all')->set('linkSearch', 'omega')->assertSee('Omega Libre')->assertDontSee('Zeta Asignada');
    }

    public function test_searching_assignments_reuses_hours_but_consulting_refreshes_them(): void
    {
        $db = $this->fakeConnection([['id' => 7, 'name' => 'Persona Uno']], $this->sampleHours());
        $this->app->instance(ReadOnlyConnection::class, $db);
        $admin = $this->user('Administrador', 'admin-cache@datamid.test');

        $screen = Livewire::actingAs($admin)->test(ManagerControlHours::class);
        $db->queries = [];

        $screen->set('linkSearch', 'persona')->assertSee('Asignar usuario del CRM');
        $this->assertCount(0, array_filter($db->queries, fn (array $query): bool => str_contains($query[0], 'AS work_date')));

        $screen->call('applyFilters');
        $this->assertCount(1, array_filter($db->queries, fn (array $query): bool => str_contains($query[0], 'AS work_date')));
    }

    public function test_control_gerencial_menu_is_for_everyone_except_administrators(): void
    {
        $this->app->instance(ReadOnlyConnection::class, $this->fakeConnection([['id' => 8, 'name' => 'Persona Dos']]));
        $admin = $this->user('Administrador', 'adm-menu@datamid.test');
        $intern = $this->user('Auxiliar', 'int-menu@datamid.test');

        $this->assertTrue(\Illuminate\Support\Facades\Gate::forUser($intern)->allows('view-legacy-own-hours'));

        $this->actingAs($intern)->get(route('profile.show'))->assertOk()->assertSee('>Control Gerencial<', false);
        $this->flushSession();
        $this->actingAs($admin)->get(route('profile.show'))->assertOk()->assertSee('>Control Gerencial<', false)
            ->assertSee(route('testing.managercontrol.index'), false)->assertDontSee(route('testing.own-hours.index'), false);
        $this->flushSession();
    }

    public function test_daily_report_uses_the_original_managercontrol_format(): void
    {
        $this->app->instance(ReadOnlyConnection::class, $this->fakeConnection([['id' => 7, 'name' => 'Ana Pérez']], $this->sampleHours()));
        $summary = HoursSummary::fromRows(array_map(fn ($r) => [
            'date' => $r['work_date'], 'customer' => $r['customer'], 'activity' => $r['activity'],
            'description' => $r['description'], 'seconds' => ManagerControlRepository::toSeconds($r['hours']),
        ], $this->sampleHours()));

        $html = (new TotalHoursPdf)->originalHtml(['id' => 7, 'name' => 'Ana Pérez'], 'Contabilidad', '2026-11-01', '2026-11-15', $summary);
        foreach (['GONZÁLEZ, ALONZO Y ASOCIADOS S.C.P', 'Total de Horas Laboradas', '01/11/2026', '15/11/2026', 'Ana Pérez', 'Contabilidad', 'Supervisó', 'Revisó', '4.00', '02/11/2026'] as $expected) {
            $this->assertStringContainsString($expected, $html);
        }

        $admin = $this->user('Administrador', 'admin-orig@datamid.test');
        $this->actingAs($admin)
            ->get(route('testing.managercontrol.pdf', ['employee' => 7, 'from' => '2026-11-01', 'to' => '2026-11-15', 'formato' => 'managercontrol']))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->actingAs($admin)
            ->get(route('testing.managercontrol.pdf', ['employee' => 7, 'from' => '2026-11-01', 'to' => '2026-11-15', 'formato' => 'otro']))
            ->assertSessionHasErrors('formato');
    }

    public function test_managercontrol_guides_download_and_respect_permissions(): void
    {
        $admin = $this->user('Administrador', 'admin-guide@datamid.test');
        $intern = $this->user('Auxiliar', 'int-guide@datamid.test');

        foreach (['managercontrol', 'managercontrol-detalle', 'managercontrol-asignar'] as $section) {
            $this->actingAs($admin)->get(route('section.guide', $section))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        }

        $this->flushSession();
        $this->actingAs($intern)->get(route('section.guide', 'managercontrol'))->assertOk();
        $this->actingAs($intern)->get(route('section.guide', 'managercontrol-asignar'))->assertForbidden();
    }

    public function test_non_admins_cannot_manage_assignments(): void
    {
        $this->app->instance(ReadOnlyConnection::class, $this->fakeConnection([['id' => 8, 'name' => 'Persona Dos']]));
        $becario = $this->user('Auxiliar', 'bec-forbid@datamid.test');

        Livewire::actingAs($becario)->test(\App\Livewire\Testing\ManagerControlOwnHours::class)
            ->set('links', [$becario->id => '8'])->call('saveLink', $becario->id)->assertForbidden();
        $this->assertDatabaseCount('managercontrol_employee_links', 0);
    }
}
