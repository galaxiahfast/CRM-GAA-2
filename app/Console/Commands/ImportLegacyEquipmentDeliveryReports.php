<?php

namespace App\Console\Commands;

use App\Models\EquipmentDeliveryReport;
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
            if (EquipmentDeliveryReport::query()->where('folio', $row['folio'])->exists()) {
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
}
