<?php

namespace App\Console\Commands;

use App\Models\EquipmentDeliveryReport;
use App\Models\ServiceOrder;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PDO;
use Throwable;

class ImportLegacyEquipmentDeliveryReports extends Command
{
    protected $signature = 'delivery-notes:import-legacy
        {database : Ruta al archivo datamid.sqlite3 del proyecto Python}
        {--uploads= : Ruta opcional a la carpeta instance/uploads}';

    protected $description = 'Importa de forma idempotente las hojas creadas por la aplicación Python';

    public function handle(): int
    {
        $database = (string) realpath((string) $this->argument('database'));
        if ($database === '' || ! is_file($database)) {
            $this->error('No se encontró la base SQLite indicada.');

            return self::FAILURE;
        }

        if (! extension_loaded('pdo_sqlite')) {
            $this->error('La extensión pdo_sqlite de PHP no está disponible.');

            return self::FAILURE;
        }

        $uploads = $this->option('uploads') ? realpath((string) $this->option('uploads')) : null;

        try {
            $legacy = new PDO('sqlite:'.$database, options: [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $rows = $legacy->query('SELECT * FROM reportes ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $exception) {
            $this->error('No fue posible leer la base anterior: '.$exception->getMessage());

            return self::FAILURE;
        }

        $imported = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $existing = EquipmentDeliveryReport::query()->where('folio', $row['folio'])->first();
            if ($existing) {
                $this->syncServiceOrder($existing);
                $skipped++;

                continue;
            }

            DB::transaction(function () use ($row, $uploads, &$imported): void {
                $photoPath = $this->importPhoto($row['foto_path'] ?? null, $uploads);
                $createdAt = Carbon::parse((string) $row['creado_en'])->utc();

                $report = new EquipmentDeliveryReport([
                    'folio' => $row['folio'],
                    'request_token' => (string) Str::uuid(),
                    'movement_type' => $row['tipo_movimiento'],
                    'customer_name' => $row['cliente_nombre'],
                    'customer_contact' => $row['cliente_contacto'] ?? '',
                    'equipment_type' => $row['tipo_equipo'],
                    'brand' => $row['marca'] ?? '',
                    'model' => $row['modelo'],
                    'serial_number' => $row['numero_serie'] ?: 'SIN SERIE',
                    'accessories' => $row['accesorios'] ?? '',
                    'physical_condition' => $row['estado_fisico'],
                    'observations' => $row['observaciones'] ?? '',
                    'photo_path' => $photoPath,
                    'created_by' => null,
                ]);
                $report->timestamps = false;
                $report->created_at = $createdAt;
                $report->updated_at = Carbon::parse((string) ($row['actualizado_en'] ?? $row['creado_en']))->utc();
                $report->save();
                $this->syncServiceOrder($report);

                if (preg_match('/^DM-(\d{8})-(\d{4})$/', (string) $row['folio'], $matches)) {
                    $date = Carbon::createFromFormat('Ymd', $matches[1])->toDateString();
                    $number = (int) $matches[2];
                    $current = DB::table('equipment_delivery_folio_counters')->where('date', $date)->value('last_number');
                    DB::table('equipment_delivery_folio_counters')->updateOrInsert(
                        ['date' => $date],
                        [
                            'last_number' => max((int) $current, $number),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }

                $imported++;
            });
        }

        $this->info("Importación terminada: {$imported} registros nuevos y {$skipped} existentes.");

        return self::SUCCESS;
    }

    private function importPhoto(?string $filename, string|false|null $uploads): ?string
    {
        if (! $filename || ! $uploads) {
            return null;
        }

        $source = $uploads.DIRECTORY_SEPARATOR.basename($filename);
        if (! is_file($source)) {
            return null;
        }

        $extension = strtolower(pathinfo($source, PATHINFO_EXTENSION)) ?: 'jpg';
        $path = 'delivery-notes/legacy-'.sha1_file($source).'.'.$extension;
        Storage::disk('local')->put($path, (string) file_get_contents($source));

        return $path;
    }

    private function syncServiceOrder(EquipmentDeliveryReport $report): void
    {
        if (! DB::getSchemaBuilder()->hasTable('ordenes_servicio')) {
            return;
        }

        $type = $report->movement_type;
        $status = match ($type) {
            'recepcion' => 'recibido',
            'entrega' => 'entregado',
            'prestamo' => 'prestado',
            'compra' => 'vendido',
            default => 'recibido',
        };
        $accessories = array_values(array_filter(array_map('trim', preg_split('/[,;\n]+/u', $report->accessories) ?: [])));

        $order = ServiceOrder::query()->where('legacy_report_id', $report->id)->first();

        if (! $order && $type === 'entrega') {
            $order = ServiceOrder::query()
                ->whereHas('movimientos', fn ($query) => $query
                    ->where('tipo_movimiento', 'entrega')
                    ->where('fecha', $report->created_at))
                ->first();
        }

        if (! $order && $type === 'entrega') {
            $pending = ServiceOrder::query()
                ->where('tipo', 'recepcion')
                ->whereNotIn('estado', ['entregado', 'cancelado']);
            $serial = trim((string) $report->serial_number);
            $order = $serial !== '' && mb_strtoupper($serial) !== 'SIN SERIE'
                ? (clone $pending)->where('equipo_serie', $serial)->latest('id')->first()
                : null;
            $order ??= $pending
                ->where('cliente_nombre', $report->customer_name)
                ->where('equipo_modelo', $report->model)
                ->latest('id')
                ->first();
        }

        if (! $order) {
            $order = ServiceOrder::query()->create([
                'legacy_report_id' => $report->id,
                'folio' => $report->folio,
                'request_token' => $report->request_token,
                'tipo' => $type,
                'cliente_id' => $report->customer_id,
                'cliente_nombre' => $report->customer_name,
                'contacto' => $report->customer_contact,
                'quien_entrega' => $report->delivered_by ?: 'Julián Emiliano Ortiz Rivero',
                'tipo_equipo' => $report->equipment_type,
                'equipo_marca' => $report->brand,
                'equipo_modelo' => $report->model,
                'equipo_serie' => $report->serial_number,
                'accesorios' => $accessories,
                'estado_fisico' => $report->physical_condition,
                'falla_reportada' => $type === 'recepcion' ? $report->observations : null,
                'reparacion_realizada' => $type === 'entrega' ? $report->observations : null,
                'estado' => $status,
                'fecha_recepcion' => $type === 'recepcion' ? $report->created_at : null,
                'fecha_entrega_real' => in_array($type, ['entrega', 'compra'], true) ? $report->created_at : null,
                'observaciones' => $report->observations,
                'foto_path' => $report->photo_path,
                'creado_por' => $report->created_by,
            ]);
        } elseif ($type === 'entrega') {
            $order->update([
                'estado' => 'entregado',
                'fecha_entrega_real' => $report->created_at,
                'reparacion_realizada' => $report->observations ?: $order->reparacion_realizada,
                'observaciones' => $report->observations ?: $order->observaciones,
                'foto_path' => $report->photo_path ?: $order->foto_path,
            ]);
        }

        $movementExists = $order->movimientos()
            ->where('tipo_movimiento', $type)
            ->where('fecha', $report->created_at)
            ->exists();

        if (! $movementExists) {
            $order->movimientos()->create([
                'tipo_movimiento' => $type,
                'fecha' => $report->created_at,
                'usuario_id' => $report->created_by,
                'notas' => $report->observations,
                'evidencia' => $report->photo_path ? [$report->photo_path] : null,
            ]);
        }
    }
}
