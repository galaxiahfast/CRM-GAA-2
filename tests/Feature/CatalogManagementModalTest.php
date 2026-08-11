<?php

namespace Tests\Feature;

use App\Livewire\Administracion\PanelAdministracion;
use App\Livewire\Customer\CatalogManager as CustomerCatalogManager;
use App\Livewire\Customer\CrearCliente;
use App\Livewire\Customer\GestionClientes;
use App\Livewire\Dashboard;
use App\Livewire\TimeControl\ActivityCatalogManager;
use App\Livewire\TimeControl\RegistroActividades;
use App\Models\AccessPermission;
use App\Models\Customer;
use App\Models\JobPosition;
use App\Models\PhysicalArea;
use App\Models\Role;
use App\Models\Service;
use App\Models\SubService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class CatalogManagementModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_modal_creates_updates_and_logically_deletes_without_losing_assignments(): void
    {
        $administratorRole = Role::create(['role' => 'Administrador']);
        $accountantRole = Role::create(['role' => 'Contador']);
        $administrator = $this->createUser($administratorRole, 'admin-customers@test.mx');
        $accountant = $this->createUser($accountantRole, 'accountant-customers@test.mx', 'Contadora');

        $component = Livewire::actingAs($administrator)
            ->test(CustomerCatalogManager::class)
            ->assertSee('Agregar Cliente')
            ->call('openModal')
            ->assertSet('showModal', true)
            ->assertSeeHtml('data-administration-modal="customer-catalog-management"')
            ->assertSeeHtml('data-catalog-search="accountant"')
            ->assertSee('Crear')
            ->assertSee('Editar')
            ->assertSee('Eliminar')
            ->set('name', '  Consultoría   del Centro  ')
            ->set('rfc', ' cdc010101aa1 ')
            ->set('email', 'CONTACTO@CENTRO.TEST')
            ->call('createCustomer')
            ->assertHasErrors(['principalAccountantId'])
            ->set('principalAccountantId', $accountant->id)
            ->call('createCustomer')
            ->assertHasNoErrors()
            ->assertDispatched('customer-catalog-updated');

        $customer = Customer::query()->where('rfc', 'CDC010101AA1')->firstOrFail();
        $this->assertSame('Consultoría del Centro', $customer->name);
        $this->assertSame('contacto@centro.test', $customer->email);
        $this->assertSame($administrator->id, $customer->created_by);
        $this->assertDatabaseHas('customer_accountants', [
            'customer_id' => $customer->id,
            'accountant_id' => $accountant->id,
            'status' => true,
        ]);

        $component
            ->call('setActiveTab', 'editar')
            ->assertSeeHtml('data-catalog-search="customer"')
            ->set('selectedCustomerId', $customer->id)
            ->assertSet('name', 'Consultoría del Centro')
            ->set('name', 'Consultoría Centro Actualizada')
            ->call('updateCustomer')
            ->assertHasNoErrors()
            ->assertDispatched('customer-catalog-updated');

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Consultoría Centro Actualizada',
            'created_by' => $administrator->id,
        ]);
        $this->assertDatabaseHas('customer_accountants', [
            'customer_id' => $customer->id,
            'accountant_id' => $accountant->id,
            'status' => true,
        ]);

        $component
            ->call('setActiveTab', 'eliminar')
            ->set('selectedCustomerId', $customer->id)
            ->call('deleteCustomer')
            ->assertHasNoErrors()
            ->assertDispatched('customer-catalog-updated');

        $this->assertNotNull($customer->fresh()->deleted_at);
        $this->assertDatabaseHas('customer_accountants', [
            'customer_id' => $customer->id,
            'accountant_id' => $accountant->id,
        ]);
    }

    public function test_activity_modal_preserves_the_internal_key_and_deletes_only_safe_links(): void
    {
        $administratorRole = Role::create(['role' => 'Administrador']);
        $administrator = $this->createUser($administratorRole, 'admin-activities@test.mx');
        $service = Service::create(['service' => 'Fiscal', 'description' => 'Servicios fiscales']);

        $component = Livewire::actingAs($administrator)
            ->test(ActivityCatalogManager::class)
            ->assertSee('Agregar Actividad')
            ->call('openModal')
            ->assertSet('showModal', true)
            ->assertSeeHtml('data-administration-modal="activity-catalog-management"')
            ->assertSeeHtml('data-catalog-search="service"')
            ->assertSee('Crear')
            ->assertSee('Editar')
            ->assertSee('Eliminar')
            ->set('name', '  Revisión   mensual  ')
            ->set('serviceId', $service->id)
            ->set('description', 'Revisión inicial')
            ->call('createActivity')
            ->assertHasNoErrors()
            ->assertDispatched('activity-catalog-updated');

        $activity = SubService::query()->where('sub_service', 'Revisión mensual')->firstOrFail();
        $originalKey = $activity->unique_key;
        $this->assertStringStartsWith('activity_', $originalKey);

        $component
            ->call('setActiveTab', 'editar')
            ->assertSeeHtml('data-catalog-search="activity"')
            ->set('selectedActivityId', $activity->id)
            ->set('name', 'Revisión fiscal mensual')
            ->set('description', 'Descripción actualizada')
            ->call('updateActivity')
            ->assertHasNoErrors()
            ->assertDispatched('activity-catalog-updated');

        $activity->refresh();
        $this->assertSame('Revisión fiscal mensual', $activity->sub_service);
        $this->assertSame($originalKey, $activity->unique_key);

        $customer = Customer::create([
            'name' => 'Cliente de actividad',
            'rfc' => 'CAA010101AA1',
            'created_by' => $administrator->id,
        ]);
        $customer->services()->attach($activity->id);

        $component
            ->call('setActiveTab', 'eliminar')
            ->set('selectedActivityId', $activity->id)
            ->call('deleteActivity')
            ->assertHasNoErrors()
            ->assertDispatched('activity-catalog-updated');

        $this->assertDatabaseMissing('sub_services', ['id' => $activity->id]);
        $this->assertDatabaseMissing('customer_services', [
            'customer_id' => $customer->id,
            'sub_service_id' => $activity->id,
        ]);
    }

    public function test_creation_never_ignores_a_publicly_supplied_catalog_selection(): void
    {
        $administratorRole = Role::create(['role' => 'Administrador']);
        $accountantRole = Role::create(['role' => 'Contador']);
        $administrator = $this->createUser($administratorRole, 'admin-unique-catalog@test.mx');
        $accountant = $this->createUser($accountantRole, 'accountant-unique-catalog@test.mx');
        $service = Service::create(['service' => 'Catálogo único']);

        $customer = Customer::create([
            'name' => 'Cliente existente',
            'rfc' => 'UNI010101AA1',
            'created_by' => $administrator->id,
        ]);
        $activity = SubService::create([
            'sub_service' => 'Actividad existente',
            'service_id' => $service->id,
            'unique_key' => 'EXISTING-ACTIVITY',
        ]);

        Livewire::actingAs($administrator)
            ->test(CustomerCatalogManager::class)
            ->set('selectedCustomerId', $customer->id)
            ->set('name', 'Otro cliente')
            ->set('rfc', $customer->rfc)
            ->set('principalAccountantId', $accountant->id)
            ->call('createCustomer')
            ->assertHasErrors(['rfc']);

        Livewire::actingAs($administrator)
            ->test(ActivityCatalogManager::class)
            ->set('selectedActivityId', $activity->id)
            ->set('name', $activity->sub_service)
            ->set('serviceId', $service->id)
            ->call('createActivity')
            ->assertHasErrors(['name']);

        $this->assertSame(1, Customer::query()->where('rfc', $customer->rfc)->count());
        $this->assertSame(1, SubService::query()->where('sub_service', $activity->sub_service)->count());
    }

    public function test_system_base_activities_cannot_change_category_or_be_deleted(): void
    {
        $administratorRole = Role::create(['role' => 'Administrador']);
        $administrator = $this->createUser($administratorRole, 'admin-protected-activity@test.mx');
        $originalService = Service::create(['service' => 'Servicio base']);
        $otherService = Service::create(['service' => 'Servicio alterno']);
        $activity = SubService::create([
            'sub_service' => 'Declaraciones',
            'service_id' => $originalService->id,
            'description' => 'Actividad protegida',
            'unique_key' => 'subservice_DECL_6',
        ]);

        $component = Livewire::actingAs($administrator)
            ->test(ActivityCatalogManager::class)
            ->call('setActiveTab', 'editar')
            ->set('selectedActivityId', $activity->id)
            ->set('serviceId', $otherService->id)
            ->call('updateActivity')
            ->assertHasErrors(['serviceId']);

        $this->assertSame($originalService->id, $activity->fresh()->service_id);

        $component
            ->call('setActiveTab', 'eliminar')
            ->set('selectedActivityId', $activity->id)
            ->call('deleteActivity')
            ->assertHasErrors(['selectedActivityId']);

        $this->assertDatabaseHas('sub_services', ['id' => $activity->id]);
    }

    public function test_activity_with_time_history_cannot_be_deleted(): void
    {
        [$administrator, $administratorRole, $activity, $customer] = $this->activityDeletionFixture('history');
        $position = JobPosition::create(['name' => 'Supervisor de horas']);
        $area = PhysicalArea::create(['name' => 'Operaciones']);

        DB::table('time_entries')->insert([
            'user_id' => $administrator->id,
            'customer_id' => $customer->id,
            'sub_service_id' => $activity->id,
            'role_id_snapshot' => $administratorRole->id,
            'job_position_id_snapshot' => $position->id,
            'physical_area_id_snapshot' => $area->id,
            'entry_date' => now()->toDateString(),
            'status' => 2,
            'total_duration_seconds' => 3600,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Livewire::actingAs($administrator)
            ->test(ActivityCatalogManager::class)
            ->call('setActiveTab', 'eliminar')
            ->set('selectedActivityId', $activity->id)
            ->call('deleteActivity')
            ->assertHasErrors(['selectedActivityId']);

        $this->assertDatabaseHas('sub_services', ['id' => $activity->id]);
    }

    public function test_activity_with_customer_files_cannot_be_deleted(): void
    {
        [$administrator, , $activity, $customer] = $this->activityDeletionFixture('files');

        DB::table('customer_files')->insert([
            'customer_id' => $customer->id,
            'user_id' => $administrator->id,
            'sub_service_id' => $activity->id,
            'file_path' => 'customers/test/document.pdf',
            'original_name' => 'document.pdf',
            'file_type' => true,
            'declaration_type' => false,
            'upload_period' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Livewire::actingAs($administrator)
            ->test(ActivityCatalogManager::class)
            ->call('setActiveTab', 'eliminar')
            ->set('selectedActivityId', $activity->id)
            ->call('deleteActivity')
            ->assertHasErrors(['selectedActivityId']);

        $this->assertDatabaseHas('sub_services', ['id' => $activity->id]);
        $this->assertDatabaseHas('customer_files', ['sub_service_id' => $activity->id]);
    }

    public function test_catalog_modals_enforce_permissions_on_the_server(): void
    {
        Role::create(['role' => 'Administrador']);
        $role = Role::create(['role' => 'Sin gestión']);
        $role->accessPermissions()->attach(
            AccessPermission::query()
                ->whereIn('key', [
                    'administration.organization.manage',
                    'customers.manage',
                    'activities.manage',
                ])
                ->pluck('id')
                ->all(),
        );
        $user = $this->createUser($role, 'without-catalog-permissions@test.mx');

        Livewire::actingAs($user)
            ->test(CustomerCatalogManager::class)
            ->assertForbidden();

        Livewire::actingAs($user)
            ->test(ActivityCatalogManager::class)
            ->assertForbidden();

        Livewire::actingAs($user)
            ->test(CrearCliente::class)
            ->assertForbidden();
    }

    public function test_catalog_managers_only_appear_in_administration_and_operational_selectors_exclude_deleted_records(): void
    {
        $administratorRole = Role::create(['role' => 'Administrador']);
        $administrator = $this->createUser($administratorRole, 'admin-catalog-sections@test.mx');
        $role = Role::create(['role' => 'Gestor de catálogos']);
        $role->accessPermissions()->attach(
            AccessPermission::query()
                ->whereIn('key', ['customers.manage', 'activities.manage'])
                ->pluck('id')
                ->all(),
        );
        $manager = $this->createUser($role, 'catalog-manager@test.mx');
        $service = Service::create(['service' => 'Operación']);
        $activity = SubService::create([
            'sub_service' => 'Actividad visible',
            'service_id' => $service->id,
            'description' => null,
            'unique_key' => 'VISIBLE-ACTIVITY',
        ]);
        $activeCustomer = Customer::create([
            'name' => 'Cliente visible',
            'rfc' => 'VIS010101AA1',
            'created_by' => $manager->id,
        ]);
        $activeCustomer->accountants()->attach($manager->id, ['status' => true]);
        Customer::create([
            'name' => 'Cliente eliminado',
            'rfc' => 'DEL010101AA1',
            'created_by' => $manager->id,
            'deleted_at' => now(),
        ]);

        Livewire::actingAs($manager)
            ->test(GestionClientes::class)
            ->assertDontSee('Agregar Cliente')
            ->assertDontSee('Agregar Actividad')
            ->assertSee('Cliente visible')
            ->assertDontSee('Cliente eliminado');

        Livewire::actingAs($manager)
            ->test(RegistroActividades::class)
            ->assertDontSee('Agregar Cliente')
            ->assertDontSee('Agregar Actividad')
            ->assertViewHas('customers', fn ($customers) => $customers->contains('id', $activeCustomer->id))
            ->assertViewHas('customers', fn ($customers) => ! $customers->contains('search_name', 'cliente eliminado'))
            ->assertViewHas('subServices', fn ($activities) => $activities->contains('id', $activity->id));

        Livewire::actingAs($administrator)
            ->test(PanelAdministracion::class)
            ->assertSee('Agregar Cliente')
            ->assertSee('Agregar Actividad');

        $activeCustomer->update(['deleted_at' => now()]);
        $activity->delete();

        Livewire::actingAs($manager)
            ->test(GestionClientes::class)
            ->assertDontSee('Agregar Cliente');

        Livewire::actingAs($manager)
            ->test(RegistroActividades::class)
            ->assertViewHas('customers', fn ($customers) => ! $customers->contains('id', $activeCustomer->id))
            ->assertViewHas('subServices', fn ($activities) => ! $activities->contains('id', $activity->id));
    }

    public function test_logically_deleted_customers_are_excluded_from_dashboard_collections(): void
    {
        $administratorRole = Role::create(['role' => 'Administrador']);
        $administrator = $this->createUser($administratorRole, 'admin-dashboard-catalog@test.mx');
        $activeCustomer = Customer::create([
            'name' => 'Cliente activo del dashboard',
            'rfc' => 'ACT010101AA1',
            'created_by' => $administrator->id,
        ]);
        $deletedCustomer = Customer::create([
            'name' => 'Cliente eliminado del dashboard',
            'rfc' => 'DEL020202AA2',
            'created_by' => $administrator->id,
            'deleted_at' => now(),
        ]);

        Livewire::actingAs($administrator)
            ->test(Dashboard::class)
            ->assertSet('customerIds', fn ($ids): bool => collect($ids)->contains($activeCustomer->id)
                && ! collect($ids)->contains($deletedCustomer->id))
            ->assertSet('customers', fn ($customers): bool => collect($customers)->contains('id', $activeCustomer->id)
                && ! collect($customers)->contains('id', $deletedCustomer->id))
            ->assertViewHas('customersPaginate', fn ($customers): bool => $customers->contains('id', $activeCustomer->id)
                && ! $customers->contains('id', $deletedCustomer->id));
    }

    private function activityDeletionFixture(string $suffix): array
    {
        $administratorRole = Role::create(['role' => 'Administrador']);
        $administrator = $this->createUser($administratorRole, "admin-activity-{$suffix}@test.mx");
        $service = Service::create(['service' => "Servicio {$suffix}"]);
        $activity = SubService::create([
            'sub_service' => "Actividad {$suffix}",
            'service_id' => $service->id,
            'description' => null,
            'unique_key' => "ACTIVITY-{$suffix}",
        ]);
        $customer = Customer::create([
            'name' => "Cliente {$suffix}",
            'rfc' => 'RFC'.strtoupper(substr($suffix, 0, 3)).'010101',
            'created_by' => $administrator->id,
        ]);

        return [$administrator, $administratorRole, $activity, $customer];
    }

    private function createUser(Role $role, string $email, string $name = 'Administrador'): User
    {
        return User::create([
            'name' => $name,
            'last_name' => 'Pruebas',
            'email' => $email,
            'password' => Hash::make('secret-password'),
            'role_id' => $role->id,
        ]);
    }
}
