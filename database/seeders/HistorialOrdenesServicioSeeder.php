<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\EquipmentDeliveryReport;
use App\Models\ServiceOrder;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class HistorialOrdenesServicioSeeder extends Seeder
{
    public function __construct(private readonly ?string $sourcePath = null) {}

    public function run(): void
    {
        $path = $this->sourcePath ?: database_path('seeders/data/historial_ordenes.json');
        if (! File::isFile($path)) {
            throw new RuntimeException("No existe el historial exportado: {$path}");
        }

        $payload = json_decode(File::get($path), true, flags: JSON_THROW_ON_ERROR);
        if (($payload['schema_version'] ?? null) !== 1 || ! is_array($payload['orders'] ?? null)) {
            throw new RuntimeException('El archivo de historial no tiene una estructura compatible.');
        }

        DB::transaction(function () use ($payload): void {
            $this->restoreFiles($payload['files'] ?? []);

            foreach ($payload['orders'] as $data) {
                $this->seedOrder($data);
            }
        });
    }

    /** @param array<string, mixed> $data */
    private function seedOrder(array $data): void
    {
        $folio = trim((string) ($data['folio'] ?? ''));
        if ($folio === '') {
            throw new RuntimeException('Se encontró una orden sin folio en el historial.');
        }

        $existing = ServiceOrder::query()->where('folio', $folio)->first();
        $requestToken = (string) ($data['request_token'] ?? '');
        if ($requestToken === '' || ServiceOrder::query()
            ->where('request_token', $requestToken)
            ->when($existing, fn ($query) => $query->whereKeyNot($existing->id))
            ->exists()) {
            $requestToken = $existing?->request_token ?: (string) Str::uuid();
        }

        $legacyId = $this->existingLegacyId($data['legacy_report_id'] ?? null, $folio, $existing);
        $attributes = [
            'request_token' => $requestToken,
            'tipo' => (string) ($data['tipo'] ?? 'recepcion'),
            'cliente_id' => $this->resolveCustomerId($data),
            'contacto_id' => $data['contacto_id'] ?? null,
            'cliente_nombre' => (string) ($data['cliente_nombre'] ?? ''),
            'contacto' => (string) ($data['contacto'] ?? ''),
            'quien_entrega' => (string) ($data['quien_entrega'] ?? 'Julián Emiliano Ortiz Rivero'),
            'tipo_equipo' => (string) ($data['tipo_equipo'] ?? ''),
            'equipo_marca' => (string) ($data['equipo_marca'] ?? ''),
            'equipo_modelo' => (string) ($data['equipo_modelo'] ?? ''),
            'equipo_serie' => (string) ($data['equipo_serie'] ?? 'SIN SERIE'),
            'accesorios' => is_array($data['accesorios'] ?? null) ? $data['accesorios'] : [],
            'estado_fisico' => (string) ($data['estado_fisico'] ?? 'bueno'),
            'falla_reportada' => $data['falla_reportada'] ?? null,
            'diagnostico' => $data['diagnostico'] ?? null,
            'reparacion_realizada' => $data['reparacion_realizada'] ?? null,
            'estado' => (string) ($data['estado'] ?? 'recibido'),
            'fecha_recepcion' => $data['fecha_recepcion'] ?? null,
            'fecha_entrega_prometida' => $data['fecha_entrega_prometida'] ?? null,
            'fecha_entrega_real' => $data['fecha_entrega_real'] ?? null,
            'fecha_limite_devolucion' => $data['fecha_limite_devolucion'] ?? null,
            'fecha_devolucion' => $data['fecha_devolucion'] ?? null,
            'firma_recepcion' => (bool) ($data['firma_recepcion'] ?? false),
            'firma_entrega' => (bool) ($data['firma_entrega'] ?? false),
            'observaciones' => $data['observaciones'] ?? null,
            'precio' => $data['precio'] ?? null,
            'forma_pago' => $data['forma_pago'] ?? null,
            'garantia' => $data['garantia'] ?? null,
            'foto_path' => $data['foto_path'] ?? null,
            'ocr_raw_text' => $data['ocr_raw_text'] ?? null,
            'creado_por' => $this->resolveUserId($data['creado_por'] ?? null, $data['creado_por_email'] ?? null),
            'legacy_report_id' => $legacyId,
        ];

        $order = ServiceOrder::query()->updateOrCreate(['folio' => $folio], $attributes);
        $this->preserveTimestamps($order, $data);

        foreach ($data['movimientos'] ?? [] as $movementData) {
            $date = $movementData['fecha'] ?? null;
            if (! $date || empty($movementData['tipo_movimiento'])) {
                continue;
            }

            $movement = $order->movimientos()->updateOrCreate(
                [
                    'tipo_movimiento' => (string) $movementData['tipo_movimiento'],
                    'fecha' => Carbon::parse($date),
                ],
                [
                    'usuario_id' => $this->resolveUserId($movementData['usuario_id'] ?? null, $movementData['usuario_email'] ?? null),
                    'notas' => $movementData['notas'] ?? null,
                    'evidencia' => is_array($movementData['evidencia'] ?? null) ? $movementData['evidencia'] : null,
                ],
            );
            $this->preserveTimestamps($movement, $movementData);
        }
    }

    /** @param array<string, mixed> $files */
    private function restoreFiles(array $files): void
    {
        foreach ($files as $path => $file) {
            if (! is_string($path) || ! str_starts_with($path, 'delivery-notes/') || ! is_array($file)) {
                continue;
            }

            $contents = base64_decode((string) ($file['contents_base64'] ?? ''), true);
            if ($contents === false || ! hash_equals((string) ($file['sha256'] ?? ''), hash('sha256', $contents))) {
                throw new RuntimeException("La evidencia {$path} está dañada o incompleta.");
            }

            Storage::disk('local')->put($path, $contents);
        }
    }

    /** @param array<string, mixed> $data */
    private function resolveCustomerId(array $data): ?int
    {
        $reference = is_array($data['cliente_referencia'] ?? null) ? $data['cliente_referencia'] : [];
        $rfc = trim((string) ($reference['rfc'] ?? ''));
        $email = trim((string) ($reference['email'] ?? ''));

        if ($rfc !== '' && ($customer = Customer::query()->where('rfc', $rfc)->first())) {
            return $customer->id;
        }
        if ($email !== '' && ($customer = Customer::query()->where('email', $email)->first())) {
            return $customer->id;
        }

        return is_numeric($data['cliente_id'] ?? null)
            && Customer::query()->whereKey((int) $data['cliente_id'])->exists()
                ? (int) $data['cliente_id']
                : null;
    }

    private function resolveUserId(mixed $id, mixed $email): ?int
    {
        $email = trim((string) $email);
        if ($email !== '' && ($user = User::query()->where('email', $email)->first())) {
            return $user->id;
        }

        return is_numeric($id) && User::query()->whereKey((int) $id)->exists() ? (int) $id : null;
    }

    private function existingLegacyId(mixed $id, string $folio, ?ServiceOrder $existing): ?int
    {
        if (! is_numeric($id) || ! EquipmentDeliveryReport::query()->whereKey((int) $id)->where('folio', $folio)->exists()) {
            return null;
        }

        $used = ServiceOrder::query()
            ->where('legacy_report_id', (int) $id)
            ->when($existing, fn ($query) => $query->whereKeyNot($existing->id))
            ->exists();

        return $used ? null : (int) $id;
    }

    /** @param array<string, mixed> $data */
    private function preserveTimestamps($model, array $data): void
    {
        $model->timestamps = false;
        $model->forceFill([
            'created_at' => isset($data['created_at']) ? Carbon::parse($data['created_at']) : $model->created_at,
            'updated_at' => isset($data['updated_at']) ? Carbon::parse($data['updated_at']) : $model->updated_at,
        ])->save();
        $model->timestamps = true;
    }
}
