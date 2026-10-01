<?php

namespace App\Console\Commands;

use App\Models\ServiceOrder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class ExportDeliveryNotesHistory extends Command
{
    protected $signature = 'delivery-notes:export-history
        {--path= : Ruta de salida opcional; por defecto database/seeders/data/historial_ordenes.json}';

    protected $description = 'Exporta una instantánea portable del historial de órdenes de servicio';

    public function handle(): int
    {
        $target = $this->targetPath();
        $files = [];
        $orders = ServiceOrder::query()
            ->with(['movimientos.usuario:id,email', 'cliente:id,name,last_name,email,rfc', 'creator:id,email'])
            ->orderBy('id')
            ->get()
            ->map(function (ServiceOrder $order) use (&$files): array {
                $this->includeFile($order->foto_path, $files);

                $movements = $order->movimientos->map(function ($movement) use (&$files): array {
                    foreach ($movement->evidencia ?? [] as $path) {
                        $this->includeFile(is_string($path) ? $path : null, $files);
                    }

                    return [
                        'tipo_movimiento' => $movement->tipo_movimiento,
                        'fecha' => $movement->fecha?->toISOString(),
                        'usuario_id' => $movement->usuario_id,
                        'usuario_email' => $movement->usuario?->email,
                        'notas' => $movement->notas,
                        'evidencia' => $movement->evidencia,
                        'created_at' => $movement->created_at?->toISOString(),
                        'updated_at' => $movement->updated_at?->toISOString(),
                    ];
                })->all();

                return [
                    'folio' => $order->folio,
                    'request_token' => $order->request_token,
                    'tipo' => $order->tipo,
                    'cliente_id' => $order->cliente_id,
                    'cliente_referencia' => $order->cliente ? [
                        'nombre' => trim($order->cliente->name.' '.$order->cliente->last_name),
                        'rfc' => $order->cliente->rfc,
                        'email' => $order->cliente->email,
                    ] : null,
                    'contacto_id' => $order->contacto_id,
                    'cliente_nombre' => $order->cliente_nombre,
                    'contacto' => $order->contacto,
                    'quien_entrega' => $order->quien_entrega,
                    'tipo_equipo' => $order->tipo_equipo,
                    'equipo_marca' => $order->equipo_marca,
                    'equipo_modelo' => $order->equipo_modelo,
                    'equipo_serie' => $order->equipo_serie,
                    'accesorios' => $order->accesorios,
                    'estado_fisico' => $order->estado_fisico,
                    'falla_reportada' => $order->falla_reportada,
                    'diagnostico' => $order->diagnostico,
                    'reparacion_realizada' => $order->reparacion_realizada,
                    'estado' => $order->estado,
                    'fecha_recepcion' => $order->fecha_recepcion?->toISOString(),
                    'fecha_entrega_prometida' => $order->fecha_entrega_prometida?->toISOString(),
                    'fecha_entrega_real' => $order->fecha_entrega_real?->toISOString(),
                    'fecha_limite_devolucion' => $order->fecha_limite_devolucion?->toISOString(),
                    'fecha_devolucion' => $order->fecha_devolucion?->toISOString(),
                    'firma_recepcion' => $order->firma_recepcion,
                    'firma_entrega' => $order->firma_entrega,
                    'observaciones' => $order->observaciones,
                    'precio' => $order->precio,
                    'forma_pago' => $order->forma_pago,
                    'garantia' => $order->garantia,
                    'foto_path' => $order->foto_path,
                    'ocr_raw_text' => $order->ocr_raw_text,
                    'creado_por' => $order->creado_por,
                    'creado_por_email' => $order->creator?->email,
                    'legacy_report_id' => $order->legacy_report_id,
                    'created_at' => $order->created_at?->toISOString(),
                    'updated_at' => $order->updated_at?->toISOString(),
                    'movimientos' => $movements,
                ];
            })
            ->all();

        $payload = [
            'schema_version' => 1,
            'exported_at' => now()->toISOString(),
            'orders' => $orders,
            'files' => $files,
        ];
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        File::ensureDirectoryExists(dirname($target));
        File::put($target, $json.PHP_EOL);

        $this->info(sprintf('Historial exportado: %d órdenes, %d archivos.', count($orders), count($files)));
        $this->line($target);

        return self::SUCCESS;
    }

    /** @param array<string, array{sha256: string, contents_base64: string}> $files */
    private function includeFile(?string $path, array &$files): void
    {
        if (! $path || isset($files[$path]) || ! Storage::disk('local')->exists($path)) {
            return;
        }

        $contents = Storage::disk('local')->get($path);
        $files[$path] = [
            'sha256' => hash('sha256', $contents),
            'contents_base64' => base64_encode($contents),
        ];
    }

    private function targetPath(): string
    {
        $path = trim((string) $this->option('path'));
        if ($path === '') {
            return database_path('seeders/data/historial_ordenes.json');
        }

        $isAbsolute = str_starts_with($path, '/') || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1;

        return $isAbsolute ? $path : base_path($path);
    }
}
