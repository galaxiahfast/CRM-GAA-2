<?php

namespace Tests\Feature;

use App\Livewire\Testing\ManagerControlHours;
use App\Livewire\Testing\ManagerControlOwnHours;
use App\Models\ManagerControlEmployeeLink;
use App\Models\Role;
use App\Models\User;
use App\Services\ManagerControl\ManagerControlException;
use App\Services\ManagerControl\ManagerControlWriter;
use App\Services\ManagerControl\ReadOnlyConnection;
use App\Services\ManagerControl\ReadOnlyGuard;
use App\Services\ManagerControl\ReadOnlyViolation;
use App\Services\ManagerControl\SqlServerWriteConnection;
use App\Services\ManagerControl\WriteConnection;
use App\Services\ManagerControl\WriteDisabled;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

/** Escritura en ManagerControl: SIEMPRE con conexiones simuladas; nunca se escribe en la base real. */
class ManagerControlWriteTest extends TestCase
{
    use RefreshDatabase;

    private object $writes;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(ReadOnlyConnection::class, new class implements ReadOnlyConnection
        {
            public function select(string $sql, array $bindings = []): array
            {
                ReadOnlyGuard::assertReadOnly($sql);

                return match (true) {
                    str_contains($sql, 'AS username') => [['username' => 'ANA.PEREZ', 'rate' => 120.0]],
                    str_contains($sql, '[Clientes]') && ! str_contains($sql, 'AS work_date') => [['id' => 10, 'name' => 'Cliente A'], ['id' => 11, 'name' => 'Cliente B']],
                    str_contains($sql, '[Actividades]') && ! str_contains($sql, 'AS work_date') => [['id' => 20, 'name' => 'Contabilidad']],
                    str_contains($sql, 'AS work_date') => [['record_id' => 555, 'work_date' => '2026-11-10', 'customer' => 'Cliente A', 'activity' => 'Contabilidad', 'description' => 'Pólizas', 'hours' => '1.5', 'customer_id' => 10, 'activity_id' => 20]],
                    str_contains($sql, 'COUNT(*)') => [['id' => 7, 'total' => 1]],
                    default => [['id' => 7, 'name' => 'Ana Pérez'], ['id' => 8, 'name' => 'Beto Díaz']],
                };
            }

            public function diagnostics(): array
            {
                return ['engine' => 'Microsoft SQL Server', 'version' => 'Simulado', 'database' => 'Prueba', 'login' => 'ro', 'can_write' => false, 'write_roles' => []];
            }
        });

        $this->writes = new class implements WriteConnection
        {
            public array $calls = [];

            public function insert(string $sql, array $bindings): int
            {
                $this->calls[] = ['insert', $sql, $bindings];

                return 9001;
            }

            public function update(string $sql, array $bindings): int
            {
                $this->calls[] = ['update', $sql, $bindings];

                return 1;
            }
        };
        $this->app->instance(WriteConnection::class, $this->writes);
    }

    private function enableWrites(): void
    {
        config(['managercontrol.write.enabled' => true, 'managercontrol.write.username' => 'crm_captura', 'managercontrol.write.password' => 'x', 'managercontrol.company_id' => 1, 'managercontrol.exercise_id' => 3]);
    }

    private function user(string $roleName, string $email): User
    {
        $role = Role::query()->firstOrCreate(['role' => $roleName]);
        $user = User::query()->create(['name' => 'Usuario', 'last_name' => 'Prueba', 'email' => $email, 'password' => Hash::make('secret'), 'role_id' => $role->id]);
        $user->forceFill(['email_verified_at' => now()])->save();

        return $user;
    }

    private function data(array $overrides = []): array
    {
        return array_merge(['date' => '2026-11-10', 'customer_id' => '10', 'activity_id' => '20', 'hours' => '1.5', 'description' => 'Pólizas'], $overrides);
    }

    public function test_writes_are_off_by_default_and_nothing_reaches_the_database(): void
    {
        $writer = app(ManagerControlWriter::class);
        $this->assertFalse($writer->enabled());

        foreach ([fn () => $writer->create(7, $this->data()), fn () => $writer->update(555, 7, $this->data()), fn () => $writer->delete(555, 7)] as $attempt) {
            try {
                $attempt();
                $this->fail('Debió rechazar la escritura.');
            } catch (WriteDisabled) {
                $this->addToAssertionCount(1);
            }
        }
        $this->assertSame([], $this->writes->calls);
        $this->assertDatabaseCount('managercontrol_audits', 0);
    }

    public function test_create_fills_the_same_columns_as_managercontrol_and_is_audited(): void
    {
        $this->enableWrites();
        $actor = $this->user('Administrador', 'admin-w@datamid.test');

        $id = app(ManagerControlWriter::class)->create(7, $this->data(), $actor);

        $this->assertSame(9001, $id);
        [$kind, $sql, $bindings] = $this->writes->calls[0];
        $this->assertSame('insert', $kind);
        $this->assertStringStartsWith('INSERT INTO [ControlTiempos].[RegistroDiario] ([IDEmpresa], [IDEjercicio], [NombreUsuario], [IDEmpleado]', $sql);
        $this->assertStringContainsString('OUTPUT INSERTED.[IDRegistroDiario]', $sql);
        $this->assertStringContainsString('GETDATE(), 0, ?, ?, 0)', $sql);
        // empresa, ejercicio, usuario del colaborador, colaborador, actividad, cliente, horas, descripción, fecha, precio y subtotal
        $this->assertSame([1, 3, 'ANA.PEREZ', 7, 20, 10, 1.5, 'Pólizas', '2026-11-10', 120.0, 180.0], $bindings);
        $this->assertDatabaseHas('managercontrol_audits', ['action' => 'create', 'legacy_record_id' => 9001, 'legacy_employee_id' => 7, 'user_id' => $actor->id]);
    }

    public function test_update_and_delete_only_touch_the_owners_active_record(): void
    {
        $this->enableWrites();
        $writer = app(ManagerControlWriter::class);

        $writer->update(555, 7, $this->data(['hours' => '2']));
        $writer->delete(555, 7);

        [, $updateSql, $updateBindings] = $this->writes->calls[0];
        $this->assertStringStartsWith('UPDATE [ControlTiempos].[RegistroDiario] SET', $updateSql);
        $this->assertStringContainsString('[SubTotal] = ROUND([Precio] * ?, 1)', $updateSql);
        $this->assertStringEndsWith('WHERE [IDRegistroDiario] = ? AND [IDEmpresa] = ? AND [IDEjercicio] = ? AND [IDEmpleado] = ? AND ISNULL([Baja], 0) = 0', $updateSql);
        $this->assertSame([555, 1, 3, 7], array_slice($updateBindings, -4));

        [, $deleteSql, $deleteBindings] = $this->writes->calls[1];
        $this->assertStringStartsWith('UPDATE [ControlTiempos].[RegistroDiario] SET [Baja] = 1 WHERE', $deleteSql);
        $this->assertStringNotContainsString('DELETE', $deleteSql);
        $this->assertSame([555, 1, 3, 7], $deleteBindings);
        $this->assertDatabaseCount('managercontrol_audits', 2);
    }

    public function test_invalid_data_is_rejected_with_a_clear_message(): void
    {
        $this->enableWrites();
        $writer = app(ManagerControlWriter::class);

        foreach ([['hours' => '0'], ['hours' => '25'], ['customer_id' => '999'], ['activity_id' => '999'], ['date' => '10/11/2026']] as $bad) {
            try {
                $writer->create(7, $this->data($bad));
                $this->fail('Debió rechazar: '.json_encode($bad));
            } catch (ManagerControlException $e) {
                $this->assertNotSame('', $e->getMessage());
            }
        }
        $this->assertSame([], $this->writes->calls);
    }

    public function test_the_write_connection_only_accepts_insert_or_update_on_the_hours_table(): void
    {
        $this->enableWrites();
        $connection = app(SqlServerWriteConnection::class);

        foreach (['DELETE FROM [ControlTiempos].[RegistroDiario]', 'UPDATE [Catalogos].[Empleados] SET [Precio] = 0', 'INSERT INTO [ControlTiempos].[RegistroDiario] (a) VALUES (1); DROP TABLE x'] as $sql) {
            try {
                str_starts_with($sql, 'INSERT') ? $connection->insert($sql, []) : $connection->update($sql, []);
                $this->fail("Debió rechazar: {$sql}");
            } catch (ReadOnlyViolation) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_admin_can_edit_anyone_and_users_only_their_own(): void
    {
        $admin = $this->user('Administrador', 'admin-gate@datamid.test');
        $aux = $this->user('Auxiliar', 'aux-gate@datamid.test');
        ManagerControlEmployeeLink::query()->create(['user_id' => $aux->id, 'legacy_employee_id' => 7, 'legacy_employee_name' => 'Ana Pérez']);

        $this->assertTrue(Gate::forUser($admin)->allows('edit-legacy-hours', 8));
        $this->assertTrue(Gate::forUser($aux)->allows('edit-legacy-hours', 7));
        $this->assertFalse(Gate::forUser($aux)->allows('edit-legacy-hours', 8));
    }

    public function test_screen_shows_read_only_controls_while_writes_are_off(): void
    {
        $admin = $this->user('Administrador', 'admin-ro@datamid.test');

        Livewire::actingAs($admin)->test(ManagerControlHours::class)
            ->assertSee('Agregar actividad')->assertSee('Solo lectura: agregar, editar y dar de baja')
            ->assertDontSeeHtml('wire:click="openCreate"')
            ->call('openCreate')
            ->assertSet('formOpen', false)
            ->assertSee('ManagerControl está en modo de solo lectura');
        $this->assertSame([], $this->writes->calls);
    }

    public function test_with_writes_on_a_user_adds_an_activity_and_the_tables_refresh(): void
    {
        $this->enableWrites();
        $this->user('Administrador', 'first-admin@datamid.test'); // el rol con id 1 es administrador en la app
        $aux = $this->user('Auxiliar', 'aux-cap@datamid.test');
        ManagerControlEmployeeLink::query()->create(['user_id' => $aux->id, 'legacy_employee_id' => 7, 'legacy_employee_name' => 'Ana Pérez']);

        Livewire::actingAs($aux)->test(ManagerControlOwnHours::class)
            ->assertSeeHtml('wire:click="openCreate"')
            ->call('openCreate')->assertSet('formOpen', true)
            ->set('formCustomer', '10')->set('formActivity', '20')->set('formHours', '1.5')->set('formDescription', 'Pólizas')
            ->call('saveRecord')
            ->assertSet('formOpen', false)
            ->assertSee('Actividad agregada en ManagerControl.');

        $this->assertSame('insert', $this->writes->calls[0][0]);
        $this->assertSame(7, $this->writes->calls[0][2][3]);
    }
}
