<?php

namespace Tests\Feature;

use App\Livewire\Support\EquipmentDeliveryNotes;
use App\Models\Customer;
use App\Models\EquipmentCatalogItem;
use App\Models\EquipmentDeliveryReport;
use App\Models\Role;
use App\Models\User;
use App\Services\Support\EquipmentAutofillService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PDO;
use Tests\TestCase;

class EquipmentDeliveryNotesTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_only_administrators_can_open_delivery_notes(): void
    {
        $admin = $this->userWithRole('Administrador', 'admin-delivery@datamid.test');
        $auxiliary = $this->userWithRole('Auxiliar', 'aux-delivery@datamid.test');

        $this->get(route('soporte.hoja-entrega'))->assertRedirect(route('login'));
        $this->actingAs($auxiliary)->get(route('soporte.hoja-entrega'))->assertForbidden();
        $this->flushSession();
        $this->actingAs($admin)
            ->get(route('soporte.hoja-entrega'))
            ->assertOk()
            ->assertSeeText('Hojas de entrega y recepción')
            ->assertSeeText('Nueva hoja')
            ->assertSeeText('Historial');
    }

    public function test_client_search_filters_suggestions(): void
    {
        $admin = $this->userWithRole('Administrador', 'admin-client-search@datamid.test');
        $customer = Customer::query()->create([
            'name' => 'Grupo Acme',
            'last_name' => 'Norte',
            'email' => 'compras@acme.test',
            'phone' => '9991234567',
            'codePhone' => 52,
            'rfc' => 'ACM010101AAA',
        ]);
        Customer::query()->create([
            'name' => 'Servicios Delta',
            'rfc' => 'DEL010101BBB',
        ]);

        Livewire::actingAs($admin)->test(EquipmentDeliveryNotes::class)
            ->set('customerName', 'Acme')
            ->assertSet('showClientDropdown', true)
            ->assertSet('customerSuggestions.0.id', $customer->id)
            ->assertSet('customerSuggestions.0.name', 'Grupo Acme Norte')
            ->assertSeeText('ACM010101AAA')
            ->assertDontSeeText('Servicios Delta');
    }

    public function test_focusing_client_search_opens_all_available_suggestions(): void
    {
        $admin = $this->userWithRole('Administrador', 'admin-client-focus@datamid.test');
        Customer::query()->create(['name' => 'Cliente Alfa', 'rfc' => 'ALF010101AAA']);
        Customer::query()->create(['name' => 'Cliente Beta', 'rfc' => 'BET010101BBB']);

        Livewire::actingAs($admin)->test(EquipmentDeliveryNotes::class)
            ->call('openClientSuggestions')
            ->assertSet('showClientDropdown', true)
            ->assertCount('customerSuggestions', 2)
            ->assertSeeText('Cliente Alfa')
            ->assertSeeText('Cliente Beta');
    }

    public function test_local_equipment_catalog_suggests_and_selects_models_without_history(): void
    {
        $admin = $this->userWithRole('Administrador', 'admin-equipment-catalog@datamid.test');
        $catalogItem = EquipmentCatalogItem::query()
            ->where('brand', 'Lenovo')
            ->where('model', 'ThinkPad T14')
            ->firstOrFail();

        Livewire::actingAs($admin)->test(EquipmentDeliveryNotes::class)
            ->call('openEquipmentSuggestions')
            ->assertSet('showEquipmentDropdown', true)
            ->set('autofillInput', 'ThinkPad T14')
            ->assertSet('equipmentSuggestions.0.source', 'catalog')
            ->assertSeeText('Lenovo ThinkPad T14')
            ->call('selectEquipmentSuggestion', 'catalog', $catalogItem->id)
            ->assertSet('equipmentType', 'Laptop')
            ->assertSet('brand', 'Lenovo')
            ->assertSet('model', 'ThinkPad T14');
    }

    public function test_equipment_fields_show_all_suggestions_on_focus_and_filter_while_typing(): void
    {
        $admin = $this->userWithRole('Administrador', 'admin-equipment-fields@datamid.test');

        Livewire::actingAs($admin)->test(EquipmentDeliveryNotes::class)
            ->call('openEquipmentFieldSuggestions', 'brand')
            ->assertSet('showBrandDropdown', true)
            ->assertSet('brandSuggestions', fn (array $suggestions): bool => count($suggestions) === 8)
            ->set('brand', 'Len')
            ->assertSet('brandSuggestions', ['Lenovo'])
            ->call('selectEquipmentFieldSuggestion', 'brand', 0)
            ->assertSet('brand', 'Lenovo')
            ->assertSet('showBrandDropdown', false);
    }

    public function test_selecting_a_client_assigns_its_id_and_loads_its_contacts(): void
    {
        $admin = $this->userWithRole('Administrador', 'admin-client-select@datamid.test');
        $customer = Customer::query()->create([
            'name' => 'Corporativo Peninsular',
            'email' => 'compras@peninsular.test',
            'phone' => '9991234567',
            'codePhone' => 52,
            'rfc' => 'CPE010101AAA',
        ]);

        Livewire::actingAs($admin)->test(EquipmentDeliveryNotes::class)
            ->call('selectCustomer', $customer->id)
            ->assertSet('customerId', $customer->id)
            ->assertSet('customerName', 'Corporativo Peninsular')
            ->assertSet('contactSuggestions.0', 'compras@peninsular.test')
            ->call('selectContactSuggestion', 0)
            ->assertSet('customerContact', 'compras@peninsular.test');
    }

    public function test_deliverer_only_accepts_the_two_fixed_options(): void
    {
        $admin = $this->userWithRole('Administrador', 'admin-deliverer@datamid.test');

        $component = Livewire::actingAs($admin)->test(EquipmentDeliveryNotes::class)
            ->set('customerName', 'Cliente libre')
            ->set('equipmentType', 'Laptop')
            ->set('model', 'Latitude 5420')
            ->set('serialNumber', 'DELIVERER-1')
            ->set('deliveredBy', 'Persona no autorizada')
            ->call('save')
            ->assertHasErrors(['deliveredBy' => 'in']);

        $component
            ->set('deliveredBy', 'Abigail Ku Vera')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('equipment_delivery_reports', [
            'serial_number' => 'DELIVERER-1',
            'delivered_by' => 'Abigail Ku Vera',
        ]);
    }

    public function test_form_fields_are_rendered_in_the_requested_order(): void
    {
        $admin = $this->userWithRole('Administrador', 'admin-form-order@datamid.test');

        $this->actingAs($admin)
            ->get(route('soporte.hoja-entrega'))
            ->assertOk()
            ->assertSeeInOrder([
                'data-form-order="movement"',
                'wire:model.live.debounce.300ms="customerName"',
                'wire:model.live.debounce.300ms="customerContact"',
                'wire:model="deliveredBy"',
                'data-form-order="autofill"',
                'data-form-order="equipment"',
                'data-form-order="conditions"',
            ], false);
    }

    public function test_administrator_can_create_a_report_with_photo_and_daily_folio(): void
    {
        Storage::fake('local');
        Carbon::setTestNow(Carbon::parse('2026-09-30 10:30:00', 'America/Mexico_City'));
        $admin = $this->userWithRole('Administrador', 'admin-create-delivery@datamid.test');

        $component = Livewire::actingAs($admin)->test(EquipmentDeliveryNotes::class)
            ->set('movementType', 'recepcion')
            ->set('customerName', 'Grupo Inopack')
            ->set('customerContact', 'contacto@inopack.test')
            ->set('equipmentType', 'Laptop')
            ->set('brand', 'Lenovo')
            ->set('model', 'ThinkPad T14')
            ->set('serialNumber', 'PF-TEST-2026')
            ->set('physicalCondition', 'con_detalles')
            ->set('observations', 'Rayón superficial en la tapa.')
            ->set('accessories', 'Cargador USB-C')
            ->set('photo', UploadedFile::fake()->image('equipo.png', 900, 600))
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('activeView', 'detail')
            ->assertSeeText('DM-20260930-0001');

        $report = EquipmentDeliveryReport::query()->firstOrFail();
        $this->assertSame('DM-20260930-0001', $report->folio);
        $this->assertSame($admin->id, $report->created_by);
        $this->assertSame('PF-TEST-2026', $report->serial_number);
        $this->assertNotNull($report->photo_path);
        Storage::disk('local')->assertExists($report->photo_path);

        $component->call('showCreate')
            ->set('movementType', 'entrega')
            ->set('customerName', 'Grupo Inopack')
            ->set('equipmentType', 'Laptop')
            ->set('model', 'ThinkPad T14')
            ->set('withoutSerial', true)
            ->set('physicalCondition', 'bueno')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('equipment_delivery_reports', [
            'folio' => 'DM-20260930-0002',
            'serial_number' => 'SIN SERIE',
        ]);
    }

    public function test_manual_label_text_autocompletes_equipment_fields(): void
    {
        $admin = $this->userWithRole('Administrador', 'admin-autofill-text@datamid.test');
        $autofill = \Mockery::mock(EquipmentAutofillService::class);
        $autofill->shouldReceive('fromText')
            ->once()
            ->with('Lenovo ThinkPad T14 S/N PF3ABC123 cargador')
            ->andReturn([
                'fields' => [
                    'brand' => 'Lenovo',
                    'model' => 'ThinkPad T14',
                    'serial_number' => 'PF3ABC123',
                    'equipment_type' => 'Laptop',
                    'accessories' => 'Cargador',
                ],
                'recognized_text' => 'Lenovo ThinkPad T14 S/N PF3ABC123 cargador',
                'catalog_used' => false,
            ]);
        $this->app->instance(EquipmentAutofillService::class, $autofill);

        Livewire::actingAs($admin)->test(EquipmentDeliveryNotes::class)
            ->set('autofillInput', 'Lenovo ThinkPad T14 S/N PF3ABC123 cargador')
            ->call('autofillFromText')
            ->assertHasNoErrors()
            ->assertSet('brand', 'Lenovo')
            ->assertSet('model', 'ThinkPad T14')
            ->assertSet('serialNumber', 'PF3ABC123')
            ->assertSet('equipmentType', 'Laptop')
            ->assertSet('accessories', 'Cargador')
            ->assertSet('withoutSerial', false)
            ->assertSet('autofillMessage', 'Se completaron 5 campos desde el texto escrito. Revisa los datos antes de guardar.');
    }

    public function test_uploaded_label_photo_is_analyzed_and_autocompletes_fields(): void
    {
        Storage::fake('local');
        $admin = $this->userWithRole('Administrador', 'admin-autofill-photo@datamid.test');
        $autofill = \Mockery::mock(EquipmentAutofillService::class);
        $autofill->shouldReceive('fromImage')
            ->once()
            ->with(\Mockery::on(fn (string $path): bool => is_file($path)))
            ->andReturn([
                'fields' => [
                    'brand' => 'HP',
                    'model' => 'ProBook 450 G8',
                    'serial_number' => '5CD1234ABC',
                    'equipment_type' => 'Laptop',
                ],
                'recognized_text' => "HP ProBook 450 G8\nS/N 5CD1234ABC",
                'catalog_used' => false,
            ]);
        $this->app->instance(EquipmentAutofillService::class, $autofill);

        Livewire::actingAs($admin)->test(EquipmentDeliveryNotes::class)
            ->set('photo', UploadedFile::fake()->image('etiqueta.jpg', 800, 600))
            ->assertHasNoErrors('photo')
            ->assertSet('brand', 'HP')
            ->assertSet('model', 'ProBook 450 G8')
            ->assertSet('serialNumber', '5CD1234ABC')
            ->assertSet('equipmentType', 'Laptop')
            ->assertSet('autofillRecognizedText', 'HP ProBook 450 G8 S/N 5CD1234ABC');
    }

    public function test_ocr_failure_keeps_the_photo_and_allows_manual_capture(): void
    {
        Storage::fake('local');
        $admin = $this->userWithRole('Administrador', 'admin-autofill-failure@datamid.test');
        $autofill = \Mockery::mock(EquipmentAutofillService::class);
        $autofill->shouldReceive('fromImage')
            ->once()
            ->andThrow(new \RuntimeException('El OCR local no está disponible.'));
        $this->app->instance(EquipmentAutofillService::class, $autofill);

        Livewire::actingAs($admin)->test(EquipmentDeliveryNotes::class)
            ->set('photo', UploadedFile::fake()->image('etiqueta.jpg', 800, 600))
            ->assertHasNoErrors('photo')
            ->assertSet('autofillWarning', 'El OCR local no está disponible.')
            ->assertSet('brand', '')
            ->assertSet('model', '');
    }

    public function test_damaged_equipment_requires_observations_and_pdf_is_private(): void
    {
        Storage::fake('local');
        $admin = $this->userWithRole('Administrador', 'admin-pdf-delivery@datamid.test');
        $auxiliary = $this->userWithRole('Auxiliar', 'aux-pdf-delivery@datamid.test');

        Livewire::actingAs($admin)->test(EquipmentDeliveryNotes::class)
            ->set('customerName', 'Cliente prueba')
            ->set('equipmentType', 'Monitor')
            ->set('model', 'P24h')
            ->set('serialNumber', 'MON-100')
            ->set('physicalCondition', 'danado')
            ->call('save')
            ->assertHasErrors(['observations' => 'required'])
            ->set('observations', 'Pantalla quebrada en la esquina derecha.')
            ->call('save')
            ->assertHasNoErrors();

        $report = EquipmentDeliveryReport::query()->firstOrFail();
        $this->actingAs($auxiliary)->get(route('soporte.hoja-entrega.pdf', $report))->assertForbidden();
        $this->flushSession();

        $response = $this->actingAs($admin)->get(route('soporte.hoja-entrega.pdf', [
            'report' => $report,
            'copy' => 'both',
        ]));
        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertStringContainsString($report->folio.'_doble.pdf', (string) $response->headers->get('content-disposition'));
    }

    public function test_legacy_python_history_can_be_imported_without_duplicates(): void
    {
        $legacyPath = tempnam(sys_get_temp_dir(), 'delivery-notes-legacy-');
        $legacy = new PDO('sqlite:'.$legacyPath);
        $legacy->exec('CREATE TABLE reportes (
            id INTEGER PRIMARY KEY,
            folio TEXT,
            tipo_movimiento TEXT,
            cliente_nombre TEXT,
            cliente_contacto TEXT,
            tipo_equipo TEXT,
            marca TEXT,
            modelo TEXT,
            numero_serie TEXT,
            accesorios TEXT,
            estado_fisico TEXT,
            observaciones TEXT,
            foto_path TEXT,
            creado_en TEXT,
            actualizado_en TEXT
        )');
        $statement = $legacy->prepare('INSERT INTO reportes VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $statement->execute([
            1,
            'DM-20260922-0003',
            'recepcion',
            'Grupo Inopack',
            '',
            'Laptop',
            'Lenovo',
            'ThinkPad',
            'SERIE-1',
            'Cargador',
            'bueno',
            '',
            null,
            '2026-09-22T12:59:20-06:00',
            '2026-09-22T12:59:20-06:00',
        ]);

        try {
            $this->assertSame(0, Artisan::call('delivery-notes:import-legacy', ['database' => $legacyPath]));
            $this->assertSame(0, Artisan::call('delivery-notes:import-legacy', ['database' => $legacyPath]));
            $this->assertSame(1, EquipmentDeliveryReport::query()->count());
            $report = EquipmentDeliveryReport::query()->firstOrFail();
            $this->assertSame('DM-20260922-0003', $report->folio);
            $this->assertSame('12:59', $report->created_at->timezone('America/Mexico_City')->format('H:i'));
            $this->assertDatabaseHas('equipment_delivery_folio_counters', [
                'date' => '2026-09-22',
                'last_number' => 3,
            ]);
        } finally {
            unset($legacy);
            @unlink($legacyPath);
        }
    }

    private function userWithRole(string $roleName, string $email): User
    {
        $role = Role::query()->firstOrCreate(['role' => $roleName]);

        $user = User::query()->create([
            'name' => str($roleName)->before(' ')->value(),
            'last_name' => 'Prueba',
            'email' => $email,
            'password' => Hash::make('secret'),
            'role_id' => $role->id,
        ]);

        $user->forceFill(['email_verified_at' => now()])->save();

        return $user;
    }
}
