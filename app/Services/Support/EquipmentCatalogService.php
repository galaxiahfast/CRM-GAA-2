<?php

namespace App\Services\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class EquipmentCatalogService
{
    /** @return array{brand?: string, model?: string, equipment_type?: string}|null */
    public function find(?string $barcode, string $recognizedText): ?array
    {
        if (! config('equipment-autofill.catalog.enabled', false)) {
            return null;
        }

        if ($barcode) {
            $result = $this->request(
                (string) config('equipment-autofill.catalog.lookup_endpoint'),
                ['upc' => $barcode],
                'barcode:'.$barcode,
            );

            if ($result) {
                return $result;
            }
        }

        if (! config('equipment-autofill.catalog.text_search', false)) {
            return null;
        }

        $query = trim(preg_replace('/\s+/u', ' ', $recognizedText) ?? '');
        if (mb_strlen($query) < 4) {
            return null;
        }

        return $this->request(
            (string) config('equipment-autofill.catalog.search_endpoint'),
            ['s' => mb_substr($query, 0, 120), 'type' => 'product'],
            'text:'.hash('sha256', $query),
        );
    }

    /** @param array<string, string> $query
     * @return array{brand?: string, model?: string, equipment_type?: string}|null
     */
    private function request(string $endpoint, array $query, string $cacheKey): ?array
    {
        $hours = max(1, (int) config('equipment-autofill.catalog.cache_hours', 168));

        return Cache::remember('equipment-catalog:'.$cacheKey, now()->addHours($hours), function () use ($endpoint, $query): ?array {
            try {
                $response = Http::acceptJson()
                    ->timeout((int) config('equipment-autofill.catalog.timeout', 8))
                    ->get($endpoint, $query);

                if (! $response->successful()) {
                    return null;
                }

                $item = collect($response->json('items', []))->first();
                if (! is_array($item)) {
                    return null;
                }

                $brand = trim((string) ($item['brand'] ?? ''));
                $model = trim((string) ($item['model'] ?? $item['title'] ?? ''));
                $category = trim(implode(' ', array_filter([
                    is_array($item['category'] ?? null) ? implode(' ', $item['category']) : ($item['category'] ?? ''),
                    $item['title'] ?? '',
                ])));

                return array_filter([
                    'brand' => $brand,
                    'model' => $model,
                    'equipment_type' => EquipmentAutofillService::inferEquipmentType($category),
                ]);
            } catch (Throwable $exception) {
                report($exception);

                return null;
            }
        });
    }
}
