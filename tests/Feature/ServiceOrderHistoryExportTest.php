<?php

namespace Tests\Feature;

use App\Models\ServiceOrder;
use Database\Seeders\HistorialOrdenesServicioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ServiceOrderHistoryExportTest extends TestCase
{
    use RefreshDatabase;

    private array $temporaryFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $path) {
            File::delete($path);
        }

        parent::tearDown();
    }

    public function test_export_command_creates_the_expected_json_structure(): void
    {
        Storage::fake('local');
        $order = $this->createOrder();
        Storage::disk('local')->put('delivery-notes/export-test.jpg', 'evidencia-prueba');
        $order->update(['foto_path' => 'delivery-notes/export-test.jpg']);
        $order->movimientos()->create([
            'tipo_movimiento' => 'recepcion',
            'fecha' => '2026-10-01 09:00:00',
            'notas' => 'Equipo recibido.',
            'evidencia' => ['delivery-notes/export-test.jpg'],
        ]);
        $path = $this->temporaryPath('export-command.json');

        $this->assertSame(0, Artisan::call('delivery-notes:export-history', ['--path' => $path]));
        $this->assertFileExists($path);

        $payload = json_decode(File::get($path), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame(1, $payload['schema_version']);
        $this->assertCount(1, $payload['orders']);
        $this->assertSame('DM-20261001-9001', $payload['orders'][0]['folio']);
        $this->assertSame('recepcion', $payload['orders'][0]['movimientos'][0]['tipo_movimiento']);
        $this->assertArrayHasKey('delivery-notes/export-test.jpg', $payload['files']);
        $this->assertSame(
            hash('sha256', 'evidencia-prueba'),
            $payload['files']['delivery-notes/export-test.jpg']['sha256'],
        );
    }

    public function test_history_seeder_is_idempotent(): void
    {
        Storage::fake('local');
        $order = $this->createOrder();
        $order->movimientos()->create([
            'tipo_movimiento' => 'recepcion',
            'fecha' => '2026-10-01 09:00:00',
            'notas' => 'Equipo recibido.',
        ]);
        $path = $this->temporaryPath('idempotent-seeder.json');
        Artisan::call('delivery-notes:export-history', ['--path' => $path]);
        $order->delete();

        (new HistorialOrdenesServicioSeeder($path))->run();
        (new HistorialOrdenesServicioSeeder($path))->run();

        $this->assertSame(1, ServiceOrder::query()->count());
        $restored = ServiceOrder::query()->firstOrFail();
        $this->assertSame('DM-20261001-9001', $restored->folio);
        $this->assertSame(1, $restored->movimientos()->count());
        $this->assertSame('Equipo recibido.', $restored->movimientos()->firstOrFail()->notas);
    }

    private function createOrder(): ServiceOrder
    {
        return ServiceOrder::query()->create([
            'folio' => 'DM-20261001-9001',
            'request_token' => (string) Str::uuid(),
            'tipo' => 'recepcion',
            'cliente_nombre' => 'Cliente exportación',
            'contacto' => 'cliente@datamid.test',
            'quien_entrega' => 'Julián Emiliano Ortiz Rivero',
            'tipo_equipo' => 'Laptop',
            'equipo_marca' => 'Lenovo',
            'equipo_modelo' => 'ThinkPad T14',
            'equipo_serie' => 'EXPORT-100',
            'accesorios' => ['Cargador'],
            'estado_fisico' => 'bueno',
            'falla_reportada' => 'No enciende.',
            'estado' => 'recibido',
            'fecha_recepcion' => '2026-10-01 09:00:00',
        ]);
    }

    private function temporaryPath(string $name): string
    {
        $path = storage_path('framework/testing/'.$name);
        File::ensureDirectoryExists(dirname($path));
        $this->temporaryFiles[] = $path;

        return $path;
    }
}
