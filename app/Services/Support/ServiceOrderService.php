<?php

namespace App\Services\Support;

use App\Models\ServiceOrder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ServiceOrderService
{
    /** @param array<string, mixed> $attributes */
    public function create(array $attributes, string $requestToken, ?int $createdBy): ServiceOrder
    {
        return DB::transaction(function () use ($attributes, $requestToken, $createdBy): ServiceOrder {
            $existing = ServiceOrder::query()->where('request_token', $requestToken)->first();
            if ($existing) {
                return $existing;
            }

            $now = Carbon::now((string) config('support.timezone', 'America/Mexico_City'));
            $type = (string) $attributes['tipo'];
            $status = match ($type) {
                'recepcion' => 'recibido',
                'prestamo' => 'prestado',
                'compra' => 'vendido',
                default => 'entregado',
            };

            $order = ServiceOrder::query()->create($attributes + [
                'folio' => $this->nextFolio($now),
                'request_token' => $requestToken,
                'estado' => $status,
                'fecha_recepcion' => $type === 'recepcion' ? $now : null,
                'fecha_entrega_real' => in_array($type, ['entrega', 'compra'], true) ? $now : null,
                'creado_por' => $createdBy,
            ]);

            $this->movement($order, $type, $createdBy, $attributes['observaciones'] ?? null, $attributes['foto_path'] ?? null, $now);

            return $order;
        }, 3);
    }

    /** @param array<string, mixed> $attributes */
    public function deliver(ServiceOrder $order, array $attributes, ?int $userId): ServiceOrder
    {
        return DB::transaction(function () use ($order, $attributes, $userId): ServiceOrder {
            $now = Carbon::now((string) config('support.timezone', 'America/Mexico_City'));
            $order->update([
                'estado' => 'entregado',
                'fecha_entrega_real' => $now,
                'firma_entrega' => (bool) ($attributes['firma_entrega'] ?? false),
                'diagnostico' => $attributes['diagnostico'] ?? $order->diagnostico,
                'reparacion_realizada' => $attributes['reparacion_realizada'] ?? $order->reparacion_realizada,
                'estado_fisico' => $attributes['estado_fisico'] ?? $order->estado_fisico,
                'accesorios' => $attributes['accesorios'] ?? $order->accesorios,
                'observaciones' => $attributes['observaciones'] ?? $order->observaciones,
                'foto_path' => $attributes['foto_path'] ?? $order->foto_path,
                'ocr_raw_text' => $attributes['ocr_raw_text'] ?? $order->ocr_raw_text,
                'quien_entrega' => $attributes['quien_entrega'] ?? $order->quien_entrega,
            ]);
            $this->movement($order, 'entrega', $userId, $attributes['observaciones'] ?? null, $attributes['foto_path'] ?? null, $now);

            return $order->refresh();
        }, 3);
    }

    /** @param array<string, mixed> $attributes */
    public function returnLoan(ServiceOrder $order, array $attributes, ?int $userId): ServiceOrder
    {
        return DB::transaction(function () use ($order, $attributes, $userId): ServiceOrder {
            $now = Carbon::now((string) config('support.timezone', 'America/Mexico_City'));
            $order->update([
                'estado' => 'devuelto',
                'fecha_devolucion' => $now,
                'firma_entrega' => (bool) ($attributes['firma_entrega'] ?? false),
                'estado_fisico' => $attributes['estado_fisico'] ?? $order->estado_fisico,
                'observaciones' => $attributes['observaciones'] ?? $order->observaciones,
                'foto_path' => $attributes['foto_path'] ?? $order->foto_path,
                'ocr_raw_text' => $attributes['ocr_raw_text'] ?? $order->ocr_raw_text,
            ]);
            $this->movement($order, 'devolucion', $userId, $attributes['observaciones'] ?? null, $attributes['foto_path'] ?? null, $now);

            return $order->refresh();
        }, 3);
    }

    private function nextFolio(Carbon $now): string
    {
        $date = $now->toDateString();
        DB::table('equipment_delivery_folio_counters')->insertOrIgnore([
            'date' => $date, 'last_number' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $counter = DB::table('equipment_delivery_folio_counters')->where('date', $date)->lockForUpdate()->first();
        $next = ((int) ($counter?->last_number ?? 0)) + 1;
        if ($next > 9999) {
            throw new RuntimeException('Se agotaron los folios disponibles para el día.');
        }
        DB::table('equipment_delivery_folio_counters')->where('date', $date)->update([
            'last_number' => $next, 'updated_at' => now(),
        ]);

        return sprintf('DM-%s-%04d', $now->format('Ymd'), $next);
    }

    private function movement(ServiceOrder $order, string $type, ?int $userId, ?string $notes, ?string $photo, Carbon $date): void
    {
        $order->movimientos()->create([
            'tipo_movimiento' => $type,
            'fecha' => $date,
            'usuario_id' => $userId,
            'notas' => $notes,
            'evidencia' => $photo ? [$photo] : null,
        ]);
    }
}
