<?php

namespace Tests\Feature;

use App\Livewire\TimeControl\GeneralCharts;
use App\Models\Customer;
use App\Models\JobPosition;
use App\Models\PhysicalArea;
use App\Models\ManagerControlEmployeeLink;
use App\Models\Role;
use App\Models\Service;
use App\Models\SubService;
use App\Models\TimeEntry;
use App\Models\User;
use App\Services\ManagerControl\ReadOnlyConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class GeneralChartsTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, string $email): User
    {
        // El rol con id 1 cuenta como administrador: se reserva ese id antes de crear los demás
        Role::query()->firstOrCreate(['role' => 'Administrador']);
        $user = User::query()->create([
            'name' => 'Ana', 'last_name' => 'Gráficas', 'email' => $email,
            'password' => Hash::make('secret'), 'role_id' => Role::query()->firstOrCreate(['role' => $role])->id,
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();

        return $user;
    }

    private function fakeManagerControl(array $hours = []): void
    {
        $this->app->instance(ReadOnlyConnection::class, new class($hours) implements ReadOnlyConnection
        {
            public function __construct(private array $hours) {}

            public function select(string $sql, array $bindings = []): array
            {
                return str_contains($sql, 'AS work_date') ? $this->hours : [];
            }

            public function diagnostics(): array
            {
                return [];
            }
        });
    }

    private function entry(User $user, int $daysAgo, int $seconds, string $customer = 'Cliente Uno', string $activity = 'Contabilidad'): void
    {
        $service = Service::query()->firstOrCreate(['service' => 'General']);
        $sub = SubService::query()->firstOrCreate(['sub_service' => $activity], ['service_id' => $service->id, 'unique_key' => 'gc-'.md5($activity)]);
        $client = Customer::query()->firstOrCreate(['name' => $customer], ['rfc' => 'XA'.strtoupper(substr(md5($customer), 0, 10))]);
        $date = Carbon::now(config('app.timezone') === 'UTC' ? 'America/Mexico_City' : config('app.timezone'))->subDays($daysAgo)->setTime(9, 0);
        $entry = TimeEntry::query()->create([
            'user_id' => $user->id, 'role_id_snapshot' => $user->role_id, 'job_position_id_snapshot' => JobPosition::query()->firstOrCreate(['name' => 'Auxiliar'])->id, 'physical_area_id_snapshot' => PhysicalArea::query()->firstOrCreate(['name' => 'Contabilidad'])->id, 'customer_id' => $client->id, 'sub_service_id' => $sub->id,
            'entry_date' => $date->toDateString(), 'status' => TimeEntry::STATUS_PAUSED, 'total_duration_seconds' => $seconds,
        ]);
        $entry->intervals()->create(['started_at' => $date, 'ended_at' => $date->copy()->addSeconds($seconds)]);
    }

    public function test_the_page_shows_one_card_for_the_stopwatch_and_one_for_managercontrol(): void
    {
        $this->fakeManagerControl([
            ['record_id' => 1, 'work_date' => now()->toDateString(), 'customer' => 'Cliente MC', 'activity' => 'Nómina', 'description' => '', 'hours' => '1.5', 'customer_id' => 5, 'activity_id' => 6],
        ]);
        $user = $this->user('Auxiliar', 'graficas@datamid.test');
        ManagerControlEmployeeLink::query()->create(['user_id' => $user->id, 'legacy_employee_id' => 9, 'legacy_employee_name' => 'ANA GRAFICAS']);
        $this->entry($user, 1, 3600);
        $this->entry($user, 0, 1800, 'Cliente Dos', 'Impuestos');

        $html = Livewire::actingAs($user)->test(GeneralCharts::class)->assertOk()->html();

        $this->assertSame(1, substr_count($html, '<h1'));
        $this->assertStringContainsString('Gráficos Generales', $html);
        $this->assertLessThan(strpos($html, '>Manager Control</h2>'), strpos($html, '>Cronómetro</h2>'));
        // Totales del periodo con el formato del resto del módulo
        $this->assertStringContainsString('gc-cron-total', $html);
        $this->assertStringContainsString('ANA GRAFICAS', $html);
        // Sin zoom, sin descargar PDF ni imprimir
        $this->assertStringNotContainsString('dashboardZoomRange', $html);
        $this->assertStringNotContainsString('Imprimir', $html);
        $this->assertStringNotContainsString('downloadPdfBtn', $html);
    }

    public function test_the_stopwatch_charts_add_up_the_time_by_day_client_and_activity(): void
    {
        $this->fakeManagerControl();
        $user = $this->user('Auxiliar', 'datos@datamid.test');
        $this->entry($user, 1, 3600);
        $this->entry($user, 0, 1800, 'Cliente Dos', 'Impuestos');

        $component = Livewire::actingAs($user)->test(GeneralCharts::class);
        $cron = $component->viewData('cron');

        $this->assertSame(5400, $cron['total']);
        $this->assertCount(15, $cron['day']['values']);
        $this->assertSame([3600, 1800], array_slice($cron['day']['values'], -2));
        $this->assertSame(['Cliente Uno', 'Cliente Dos'], $cron['clients']['labels']);
        $this->assertSame(['Contabilidad', 'Impuestos'], $cron['activities']['labels']);

        // El filtro por cliente solo afecta a la gráfica por día
        $client = (string) Customer::query()->where('name', 'Cliente Dos')->value('id');
        $filtered = $component->set('cronClient', $client)->viewData('cron');
        $this->assertSame(1800, $filtered['day']['total']);
        $this->assertSame(5400, $filtered['total']);
    }

    public function test_managercontrol_card_explains_when_the_user_has_no_collaborator(): void
    {
        $this->fakeManagerControl();
        $user = $this->user('Auxiliar', 'sin-mc@datamid.test');

        Livewire::actingAs($user)->test(GeneralCharts::class)
            ->assertSee('Tu usuario no tiene un colaborador de ManagerControl asignado.');
    }

    public function test_non_admins_only_see_their_own_time(): void
    {
        $this->fakeManagerControl();
        $user = $this->user('Auxiliar', 'propio@datamid.test');
        $other = $this->user('Auxiliar', 'otro@datamid.test');
        $this->entry($other, 0, 7200);

        $component = Livewire::actingAs($user)->test(GeneralCharts::class)->set('userId', (string) $other->id);

        $this->assertSame(0, $component->viewData('cron')['total']);
    }

    public function test_the_route_opens_the_new_page(): void
    {
        $this->fakeManagerControl();
        $user = $this->user('Auxiliar', 'ruta@datamid.test');

        $this->actingAs($user)->get(route('time.dashboard'))->assertOk()->assertSeeLivewire(GeneralCharts::class);
    }
}
