<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Service;
use App\Models\SubService;
use App\Services\ManagerControl\CatalogInsertConnection;
use App\Services\ManagerControl\CatalogSynchronizer;
use App\Services\ManagerControl\ReadOnlyConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManagerControlCatalogSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_does_not_write_and_apply_is_idempotent_with_separate_ids(): void
    {
        config(['managercontrol.company_id' => 1, 'managercontrol.exercise_id' => 3,
            'managercontrol.catalog_departments' => ['Contabilidad' => 41]]);
        SubService::query()->delete();
        Service::query()->delete();
        Customer::query()->delete();
        $customer = Customer::query()->create(['name' => 'Cliente del CRM', 'rfc' => 'ABC010101AB1']);
        $service = Service::query()->create(['service' => 'Contabilidad']);
        $activity = SubService::query()->create(['service_id' => $service->id, 'sub_service' => 'Actividad del CRM', 'unique_key' => 'activity-test-sync']);

        $remote = new class implements ReadOnlyConnection
        {
            public array $customers = [];

            public array $sections = [];

            public array $activities = [];

            public function select(string $sql, array $bindings = []): array
            {
                return match (true) {
                    str_contains($sql, '[Catalogos].[Departamentos]') => [['id' => 41]],
                    str_contains($sql, '[Catalogos].[Clientes]') => $this->customers,
                    str_contains($sql, '[Catalogos].[Secciones]') => $this->sections,
                    str_contains($sql, '[Catalogos].[Actividades]') => $this->activities,
                    default => [],
                };
            }

            public function diagnostics(): array
            {
                return [];
            }
        };
        $writer = new class($remote) implements CatalogInsertConnection
        {
            public int $writes = 0;

            public function __construct(private object $remote) {}

            public function assertReady(): void {}

            public function insert(string $kind, array $values): int
            {
                $id = 100 + (++$this->writes);
                $row = ['id' => $id, 'name' => $values['Nombre'], 'source_username' => 'CRM-GAA'];
                if ($kind === 'customers') {
                    $this->remote->customers[] = $row + ['rfc' => $values['RFC']];
                } elseif ($kind === 'sections') {
                    $this->remote->sections[] = $row + ['department_id' => $values['IDDepartamento']];
                } else {
                    $this->remote->activities[] = $row + ['section_id' => $values['IDSeccion']];
                }

                return $id;
            }
        };
        $this->app->instance(ReadOnlyConnection::class, $remote);
        $this->app->instance(CatalogInsertConnection::class, $writer);
        $sync = app(CatalogSynchronizer::class);

        $preview = $sync->run();
        foreach (['customers', 'sections', 'activities'] as $kind) {
            $this->assertSame(1, $preview['counts'][$kind]['would_create'] + $preview['counts'][$kind]['pending_section']);
        }
        $this->assertSame(0, $writer->writes);
        $this->assertDatabaseCount('managercontrol_catalog_links', 0);

        $applied = $sync->run(true);
        $this->assertSame(3, $writer->writes);
        $this->assertSame(1, $applied['counts']['customers']['created']);
        $this->assertSame(1, $applied['counts']['sections']['created']);
        $this->assertSame(1, $applied['counts']['activities']['created']);
        $this->assertDatabaseCount('managercontrol_catalog_links', 3);
        $this->assertDatabaseHas('managercontrol_catalog_links', ['kind' => 'customers', 'crm_id' => $customer->id, 'legacy_id' => 101, 'origin' => 'imported']);
        $this->assertDatabaseHas('managercontrol_catalog_links', ['kind' => 'sections', 'crm_id' => $service->id, 'legacy_id' => 102]);
        $this->assertDatabaseHas('managercontrol_catalog_links', ['kind' => 'activities', 'crm_id' => $activity->id, 'legacy_id' => 103]);
        $this->assertSame(102, $remote->activities[0]['section_id']);

        $second = $sync->run(true);
        $this->assertSame(3, $writer->writes);
        $this->assertSame(1, $second['counts']['customers']['linked']);
        $this->assertSame(1, $second['counts']['sections']['linked']);
        $this->assertSame(1, $second['counts']['activities']['linked']);
        $this->assertSame('crm', app(\App\Services\ManagerControl\ManagerControlRepository::class)->catalog('customers')[0]['source']);
    }

    public function test_same_customer_name_with_a_different_valid_rfc_is_not_imported_automatically(): void
    {
        SubService::query()->delete();
        Service::query()->delete();
        Customer::query()->delete();
        Customer::query()->create(['name' => 'Cliente compartido', 'rfc' => 'ABC010101AB1']);

        $remote = new class implements ReadOnlyConnection
        {
            public function select(string $sql, array $bindings = []): array
            {
                return str_contains($sql, '[Catalogos].[Clientes]')
                    ? [['id' => 50, 'name' => 'Cliente compartido', 'rfc' => 'DEF010101AB1', 'source_username' => 'LEGACY']]
                    : [];
            }

            public function diagnostics(): array
            {
                return [];
            }
        };
        $this->app->instance(ReadOnlyConnection::class, $remote);
        $result = app(CatalogSynchronizer::class)->run();

        $this->assertSame(1, $result['counts']['customers']['conflict']);
        $this->assertSame(0, $result['counts']['customers']['would_create']);
        $this->assertDatabaseCount('managercontrol_catalog_links', 0);
        $this->assertSame('managercontrol', app(\App\Services\ManagerControl\ManagerControlRepository::class)->catalog('customers')[0]['source']);
    }
}
