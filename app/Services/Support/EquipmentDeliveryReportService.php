<?php

namespace App\Services\Support;

use App\Models\EquipmentDeliveryReport;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class EquipmentDeliveryReportService
{
    /** @param array<string, mixed> $attributes */
    public function create(array $attributes, string $requestToken, ?int $createdBy): EquipmentDeliveryReport
    {
        return DB::transaction(function () use ($attributes, $requestToken, $createdBy): EquipmentDeliveryReport {
            $existing = EquipmentDeliveryReport::query()->where('request_token', $requestToken)->first();

            if ($existing) {
                return $existing;
            }

            $now = Carbon::now((string) config('support.timezone', 'America/Mexico_City'));
            $date = $now->toDateString();

            DB::table('equipment_delivery_folio_counters')->insertOrIgnore([
                'date' => $date,
                'last_number' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $counter = DB::table('equipment_delivery_folio_counters')
                ->where('date', $date)
                ->lockForUpdate()
                ->first();
            $next = ((int) ($counter?->last_number ?? 0)) + 1;

            if ($next > 9999) {
                throw new RuntimeException('Se agotaron los folios disponibles para el día.');
            }

            DB::table('equipment_delivery_folio_counters')
                ->where('date', $date)
                ->update(['last_number' => $next, 'updated_at' => now()]);

            return EquipmentDeliveryReport::query()->create($attributes + [
                'folio' => sprintf('DM-%s-%04d', $now->format('Ymd'), $next),
                'request_token' => $requestToken,
                'created_by' => $createdBy,
            ]);
        }, 3);
    }
}
