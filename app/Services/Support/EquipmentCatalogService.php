<?php

namespace App\Services\Support;

use App\Models\EquipmentCatalogIndex;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class EquipmentCatalogService
{
    /** @return array{brand?: string, model?: string, equipment_type?: string}|null */
    public function find(?string $barcode, string $recognizedText): ?array
    {
        $local = $this->findBestLocal($recognizedText);
        if ($local) {
            return $local;
        }

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

    /** @return array<int, array<string, mixed>> */
    public function fuzzySearch(string $query, int $limit = 10): array
    {
        $needle = $this->normalize($query);
        if ($needle === '') {
            return EquipmentCatalogIndex::query()
                ->orderByDesc('usage_count')
                ->orderBy('brand')
                ->orderBy('model')
                ->limit($limit)
                ->get()
                ->map(fn (EquipmentCatalogIndex $item): array => $this->result($item, 1))
                ->all();
        }

        return EquipmentCatalogIndex::query()
            ->get()
            ->map(function (EquipmentCatalogIndex $item) use ($needle): ?array {
                $score = $this->score($needle, $item);

                return $score === 0 ? null : $this->result($item, $score);
            })
            ->filter()
            ->sort(function (array $left, array $right): int {
                if ($left['score'] !== $right['score']) {
                    return $right['score'] <=> $left['score'];
                }

                if ($left['usage_count'] !== $right['usage_count']) {
                    return $right['usage_count'] <=> $left['usage_count'];
                }

                return strnatcasecmp($left['title'], $right['title']);
            })
            ->take($limit)
            ->values()
            ->all();
    }

    /** @return array<string, string>|null */
    public function findBestLocal(string $text): ?array
    {
        $queries = collect(preg_split('/\R+/u', $text) ?: [])
            ->push($text)
            ->map(fn (string $line): string => trim($line))
            ->filter(fn (string $line): bool => mb_strlen($line) >= 4)
            ->unique();

        $best = $queries
            ->flatMap(fn (string $query): array => $this->fuzzySearch($query, 3))
            ->sortByDesc(fn (array $item): array => [$item['score'], $item['usage_count']])
            ->first();

        if (! is_array($best)) {
            return null;
        }

        return array_filter([
            'brand' => $best['brand'],
            'model' => $best['model'],
            'equipment_type' => $best['equipment_type'],
            'serial_number' => $best['serial_number'],
            'accessories' => implode(', ', $best['typical_accessories']),
        ], fn ($value): bool => filled($value));
    }

    /** @param array<int, string> $accessories
     * @param  array<int, string>  $aliases
     */
    public function register(
        string $brand,
        string $model,
        string $equipmentType = 'Otro',
        ?string $serialNumber = null,
        array $accessories = [],
        array $aliases = [],
    ): EquipmentCatalogIndex {
        $brand = trim($brand);
        $model = trim($model);
        $key = $this->normalize($brand.' '.$model);

        return EquipmentCatalogIndex::query()->updateOrCreate(
            ['normalized_key' => str_replace(' ', '', $key)],
            [
                'brand' => $brand,
                'model' => $model,
                'equipment_type' => $equipmentType ?: 'Otro',
                'serial_number' => filled($serialNumber) ? trim((string) $serialNumber) : null,
                'typical_accessories' => array_values(array_unique(array_filter(array_map('trim', $accessories)))),
                'aliases' => array_values(array_unique(array_filter(array_map('trim', $aliases)))),
                'search_text' => $this->normalize(implode(' ', [$brand, $model, $equipmentType, ...$aliases])),
            ],
        );
    }

    public function normalize(string $value): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', ' ', Str::lower(Str::ascii($value))) ?? '');
    }

    private function score(string $needle, EquipmentCatalogIndex $item): int
    {
        $haystack = $this->normalize($item->search_text.' '.implode(' ', $item->aliases ?? []));
        $needleCompact = str_replace(' ', '', $needle);
        $haystackCompact = str_replace(' ', '', $haystack);
        $identity = $this->normalize($item->brand.' '.$item->model);

        if ($needle === $identity || $needleCompact === str_replace(' ', '', $identity)) {
            return 300;
        }
        if (str_contains($haystack, $needle) || str_contains($haystackCompact, $needleCompact)) {
            return 200;
        }

        $candidates = collect(preg_split('/\s+/', $haystack) ?: [])
            ->push(str_replace(' ', '', $this->normalize($item->model)))
            ->push(str_replace(' ', '', $identity))
            ->merge(collect($item->aliases ?? [])->map(
                fn (string $alias): string => str_replace(' ', '', $this->normalize($alias)),
            ))
            ->push($haystackCompact)
            ->filter()
            ->unique();
        $bestDistance = $candidates->min(fn (string $candidate): int => levenshtein($needleCompact, $candidate));
        $allowed = max(2, (int) ceil(mb_strlen($needleCompact) * .2));

        return $bestDistance <= $allowed ? max(100, 150 - ($bestDistance * 10)) : 0;
    }

    /** @return array<string, mixed> */
    private function result(EquipmentCatalogIndex $item, int $score): array
    {
        return [
            'id' => $item->id,
            'source' => 'index',
            'title' => trim($item->brand.' '.$item->model),
            'brand' => $item->brand,
            'model' => $item->model,
            'serial_number' => $item->serial_number ?: '',
            'equipment_type' => $item->equipment_type,
            'typical_accessories' => $item->typical_accessories ?? [],
            'usage_count' => $item->usage_count,
            'score' => $score,
        ];
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
