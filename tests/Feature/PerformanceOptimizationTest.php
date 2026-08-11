<?php

namespace Tests\Feature;

use App\Livewire\CustomerReport;
use App\Livewire\NotificationCenter;
use App\Models\Customer;
use App\Models\Service;
use App\Models\SubService;
use App\Models\User;
use App\Notifications\SystemEventNotification;
use App\Services\Authorization\PermissionAccessService;
use App\Services\ReferenceDataCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class PerformanceOptimizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_test_suite_keeps_an_isolated_in_memory_database_when_local_config_is_cached(): void
    {
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
    }

    public function test_permission_results_share_one_request_scoped_service(): void
    {
        $this->assertSame(
            app(PermissionAccessService::class),
            app(PermissionAccessService::class),
        );
    }

    public function test_time_control_reference_cache_is_reused_and_invalidated_by_model_changes(): void
    {
        Cache::flush();
        Customer::query()->create(['name' => 'Cliente inicial', 'rfc' => 'PERF010101AAA']);
        SubService::query()->create([
            'service_id' => \App\Models\Service::query()->create([
                'service' => 'Servicio de prueba',
            ])->id,
            'sub_service' => 'Actividad inicial',
            'unique_key' => 'actividad-inicial',
        ]);

        $references = app(ReferenceDataCache::class);
        $first = $references->timeControl();

        DB::enableQueryLog();
        $second = $references->timeControl();
        $cachedQueryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame($first, $second);
        $this->assertSame(0, $cachedQueryCount);

        Customer::query()->create(['name' => 'Cliente recién agregado', 'rfc' => 'PERF010101BBB']);
        $refreshed = $references->timeControl();

        $this->assertTrue(collect($refreshed['customers'])->contains(
            fn (array $customer): bool => str_contains($customer['search_name'], 'cliente recién agregado')
        ));
    }

    public function test_notification_center_defers_the_list_and_combines_counts_in_one_query(): void
    {
        $user = User::factory()->create();
        $user->notify(new SystemEventNotification([
            'category' => 'system',
            'severity' => 'info',
            'title' => 'Aviso diferido',
            'message' => 'Contenido que solo debe cargarse con el panel abierto.',
        ]));

        DB::enableQueryLog();

        $component = Livewire::actingAs($user)
            ->test(NotificationCenter::class)
            ->assertDontSee('Contenido que solo debe cargarse con el panel abierto.');

        $initialNotificationQueries = collect(DB::getQueryLog())
            ->pluck('query')
            ->filter(fn (string $query): bool => str_contains($query, 'notifications'));

        $this->assertCount(1, $initialNotificationQueries);
        $aggregateQuery = $initialNotificationQueries->first();

        $this->assertStringContainsString('count(*)', strtolower($aggregateQuery));
        $this->assertStringNotContainsString('order by', strtolower($aggregateQuery));

        DB::flushQueryLog();

        $component
            ->call('loadNotifications')
            ->assertSee('Contenido que solo debe cargarse con el panel abierto.');

        $openedNotificationQueries = collect(DB::getQueryLog())
            ->pluck('query')
            ->filter(fn (string $query): bool => str_contains($query, 'notifications'));

        DB::disableQueryLog();

        $this->assertCount(2, $openedNotificationQueries);
    }

    public function test_high_frequency_queries_have_composite_indexes(): void
    {
        $this->assertTrue(Schema::hasIndex('sessions', 'sessions_user_activity_index'));
        $this->assertTrue(Schema::hasIndex('notifications', 'notifications_notifiable_created_index'));
        $this->assertTrue(Schema::hasIndex('notifications', 'notifications_notifiable_read_index'));
        $this->assertTrue(Schema::hasIndex('time_entries', 'time_entries_user_date_index'));
        $this->assertTrue(Schema::hasIndex('control_de_horas', 'control_hours_employee_date_index'));
        $this->assertTrue(Schema::hasIndex('customer_accountants', 'customer_accountants_accountant_status_index'));
        $this->assertTrue(Schema::hasIndex('customer_accountants', 'customer_accountants_customer_status_index'));
        $this->assertTrue(Schema::hasIndex('customer_files', 'customer_files_period_service_index'));
        $this->assertTrue(Schema::hasIndex('customer_files', 'customer_files_declaration_type_index'));
    }

    public function test_annual_customer_report_loads_files_in_bulk_without_monthly_n_plus_one_queries(): void
    {
        $user = User::factory()->create();
        $customer = Customer::query()->create([
            'name' => 'Cliente anual',
            'rfc' => 'PERF010101CCC',
            'created_by' => $user->id,
        ]);
        $service = Service::query()->create(['service' => 'Servicio anual']);
        $subService = SubService::query()->create([
            'service_id' => $service->id,
            'sub_service' => 'Actividad anual',
            'unique_key' => 'actividad-anual',
        ]);
        $customer->services()->attach($subService->id);

        DB::flushQueryLog();
        DB::enableQueryLog();

        Livewire::actingAs($user)
            ->test(CustomerReport::class, ['customerId' => $customer->id])
            ->assertSee('Cliente anual');

        $fileQueries = collect(DB::getQueryLog())
            ->pluck('query')
            ->filter(fn (string $query): bool => str_contains(strtolower($query), 'customer_files'));

        DB::disableQueryLog();

        $this->assertCount(3, $fileQueries);
    }
}
