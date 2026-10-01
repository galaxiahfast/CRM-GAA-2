<?php

namespace App\Console\Commands;

use App\Models\EquipmentCatalogIndex;
use App\Models\EquipmentCatalogItem;
use App\Models\EquipmentDeliveryReport;
use App\Models\ServiceOrder;
use App\Services\Support\EquipmentCatalogService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RebuildEquipmentCatalogIndex extends Command
{
    protected $signature = 'equipment:rebuild-index';

    protected $description = 'Reconstruye el índice difuso de equipos desde el catálogo y las órdenes de servicio';

    public function handle(EquipmentCatalogService $catalog): int
    {
        $items = [];

        EquipmentCatalogItem::query()->orderBy('id')->each(function (EquipmentCatalogItem $item) use (&$items, $catalog): void {
            $key = str_replace(' ', '', $catalog->normalize($item->brand.' '.$item->model));
            $items[$key] = [
                'normalized_key' => $key,
                'brand' => $item->brand,
                'model' => $item->model,
                'equipment_type' => $item->equipment_type,
                'serial_number' => null,
                'typical_accessories' => [],
                'aliases' => array_values(array_filter([$item->keywords])),
                'usage_count' => 0,
            ];
        });

        $importedLegacyIds = ServiceOrder::query()
            ->whereNotNull('legacy_report_id')
            ->pluck('legacy_report_id');
        EquipmentDeliveryReport::query()
            ->whereNotIn('id', $importedLegacyIds)
            ->orderBy('id')
            ->each(function (EquipmentDeliveryReport $report) use (&$items, $catalog): void {
                $key = str_replace(' ', '', $catalog->normalize($report->brand.' '.$report->model));
                if ($key === '') {
                    return;
                }

                $current = $items[$key] ?? [
                    'normalized_key' => $key,
                    'brand' => $report->brand,
                    'model' => $report->model,
                    'equipment_type' => $report->equipment_type ?: 'Otro',
                    'serial_number' => null,
                    'typical_accessories' => [],
                    'aliases' => [],
                    'usage_count' => 0,
                ];
                if (filled($report->serial_number)) {
                    $current['serial_number'] = trim((string) $report->serial_number);
                }
                $accessories = array_values(array_filter(array_map(
                    'trim',
                    preg_split('/[,;\n]+/u', (string) $report->accessories) ?: [],
                )));
                $current['typical_accessories'] = array_values(array_unique([
                    ...$current['typical_accessories'],
                    ...$accessories,
                ]));
                $current['usage_count']++;
                $items[$key] = $current;
            });

        ServiceOrder::query()->orderBy('id')->each(function (ServiceOrder $order) use (&$items, $catalog): void {
            $key = str_replace(' ', '', $catalog->normalize($order->equipo_marca.' '.$order->equipo_modelo));
            if ($key === '') {
                return;
            }

            $current = $items[$key] ?? [
                'normalized_key' => $key,
                'brand' => $order->equipo_marca,
                'model' => $order->equipo_modelo,
                'equipment_type' => $order->tipo_equipo ?: 'Otro',
                'serial_number' => null,
                'typical_accessories' => [],
                'aliases' => [],
                'usage_count' => 0,
            ];
            $serial = trim((string) $order->equipo_serie);
            if ($serial !== '' && mb_strtoupper($serial) !== 'SIN SERIE') {
                $current['serial_number'] = $serial;
            }
            $current['typical_accessories'] = array_values(array_unique([
                ...$current['typical_accessories'],
                ...($order->accesorios ?? []),
            ]));
            $current['usage_count']++;
            $items[$key] = $current;
        });

        DB::transaction(function () use ($items, $catalog): void {
            $keys = array_keys($items);
            foreach ($items as $item) {
                $item['search_text'] = $catalog->normalize(implode(' ', [
                    $item['brand'],
                    $item['model'],
                    $item['equipment_type'],
                    ...$item['aliases'],
                ]));
                EquipmentCatalogIndex::query()->updateOrCreate(
                    ['normalized_key' => $item['normalized_key']],
                    $item,
                );
            }

            EquipmentCatalogIndex::query()->whereNotIn('normalized_key', $keys)->delete();
        });

        $this->info('Índice reconstruido: '.count($items).' equipos disponibles.');

        return self::SUCCESS;
    }
}
