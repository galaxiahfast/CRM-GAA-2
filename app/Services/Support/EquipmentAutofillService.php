<?php

namespace App\Services\Support;

use Illuminate\Support\Str;

class EquipmentAutofillService
{
    /** @var array<string, string> */
    private const BRANDS = [
        'hewlett packard' => 'HP',
        'western digital' => 'Western Digital',
        'tp-link' => 'TP-Link',
        'ubiquiti' => 'Ubiquiti',
        'microsoft' => 'Microsoft',
        'motorola' => 'Motorola',
        'logitech' => 'Logitech',
        'kingston' => 'Kingston',
        'samsung' => 'Samsung',
        'seagate' => 'Seagate',
        'lenovo' => 'Lenovo',
        'brother' => 'Brother',
        'huawei' => 'Huawei',
        'xiaomi' => 'Xiaomi',
        'epson' => 'Epson',
        'canon' => 'Canon',
        'apple' => 'Apple',
        'dell' => 'Dell',
        'acer' => 'Acer',
        'asus' => 'Asus',
        'hp' => 'HP',
    ];

    public function __construct(
        private readonly EquipmentOcrService $ocr,
        private readonly EquipmentCatalogService $catalog,
    ) {}

    /** @return array{fields: array<string, string>, recognized_text: string, catalog_used: bool} */
    public function fromImage(string $imagePath, string $preprocessing = 'default'): array
    {
        $text = $preprocessing === 'default'
            ? $this->ocr->extract($imagePath)
            : $this->ocr->extract($imagePath, $preprocessing);

        return $this->fromText($text);
    }

    /** @return array{fields: array<string, string>, recognized_text: string, catalog_used: bool} */
    public function fromText(string $text): array
    {
        $text = $this->normalize($text);
        $barcode = $this->extractBarcode($text);
        $fields = array_filter([
            'brand' => $this->extractBrand($text),
            'model' => $this->extractModel($text),
            'serial_number' => $this->extractSerial($text),
            'equipment_type' => self::inferEquipmentType($text),
            'accessories' => $this->extractAccessories($text),
        ], fn (?string $value): bool => filled($value));

        $catalogFields = $this->catalog->find($barcode, $text) ?? [];
        foreach ($catalogFields as $key => $value) {
            if (! isset($fields[$key]) && filled($value)) {
                $fields[$key] = trim((string) $value);
            }
        }

        return [
            'fields' => $fields,
            'recognized_text' => $text,
            'catalog_used' => $catalogFields !== [],
        ];
    }

    public static function inferEquipmentType(string $text): ?string
    {
        $normalized = Str::lower(Str::ascii($text));
        $types = [
            'Laptop' => ['laptop', 'notebook', 'thinkpad', 'probook', 'elitebook', 'latitude', 'macbook'],
            'PC de escritorio' => ['desktop', 'optiplex', 'workstation', 'computadora de escritorio', 'pc de escritorio'],
            'Impresora' => ['printer', 'impresora', 'laserjet', 'deskjet', 'ecotank', 'multifuncional'],
            'Monitor' => ['monitor', 'display', 'pantalla led', 'lcd monitor'],
            'Servidor' => ['server', 'servidor', 'poweredge', 'proliant'],
            'Tablet' => ['tablet', 'ipad', 'galaxy tab'],
            'Teléfono' => ['telefono', 'smartphone', 'iphone', 'galaxy phone', 'moto g'],
        ];

        foreach ($types as $type => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($normalized, $keyword)) {
                    return $type;
                }
            }
        }

        return null;
    }

    private function normalize(string $text): string
    {
        $text = str_replace(["\r\n", "\r", "\0"], ["\n", "\n", ''], trim($text));
        $lines = array_map(
            fn (string $line): string => trim(preg_replace('/[ \t]+/u', ' ', $line) ?? ''),
            explode("\n", $text),
        );

        return implode("\n", array_values(array_filter($lines)));
    }

    private function extractBrand(string $text): ?string
    {
        foreach (self::BRANDS as $needle => $brand) {
            if (preg_match('/(?<![\pL\pN])'.preg_quote($needle, '/').'(?![\pL\pN])/iu', $text)) {
                return $brand;
            }
        }

        if (preg_match('/(?:marca|brand)\s*[:#-]?\s*([\pL][\pL\pN .-]{1,30})/iu', $text, $matches)) {
            return $this->cleanCandidate($matches[1], 100);
        }

        return null;
    }

    private function extractModel(string $text): ?string
    {
        if (preg_match('/(?:modelo|model|product name|machine type(?: model)?|mtm)\s*(?:no\.?|number)?\s*[:#-]?\s*([^\n]{2,60})/iu', $text, $matches)) {
            return $this->cleanCandidate($matches[1], 100, true);
        }

        $brand = $this->extractBrand($text);
        $serial = $this->extractSerial($text);
        foreach (explode("\n", $text) as $line) {
            $line = preg_replace('/\s+(?:serial|s\/?n|service tag|numero de serie|número de serie|upc|ean|gtin)\s*[:#-]?.*$/iu', '', $line) ?? $line;
            if (trim($line) === '' || preg_match('/^(?:serial|s\/?n|service tag|numero de serie|número de serie|upc|ean|gtin)/iu', $line)) {
                continue;
            }

            $candidate = $line;
            if ($brand) {
                $candidate = preg_replace('/'.preg_quote($brand, '/').'/iu', '', $candidate) ?? $candidate;
                if ($brand === 'HP') {
                    $candidate = preg_replace('/hewlett[ -]?packard/iu', '', $candidate) ?? $candidate;
                }
            }
            if ($serial) {
                $candidate = str_ireplace($serial, '', $candidate);
            }
            $candidate = preg_replace('/\b(?:laptop|notebook|desktop|computer|computadora|printer|impresora|monitor|server|servidor)\b/iu', '', $candidate) ?? $candidate;
            $candidate = $this->cleanCandidate($candidate, 100, true);

            if ($candidate && preg_match('/[\pL]/u', $candidate) && preg_match('/\d/u', $candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function extractSerial(string $text): ?string
    {
        if (preg_match('/(?:numero de serie|número de serie|serial(?: number| no\.?| #)?|s\/?n|service tag)\s*[:#-]?\s*([A-Z0-9][A-Z0-9._-]{4,39})/iu', $text, $matches)) {
            return mb_strtoupper($this->cleanCandidate($matches[1], 100) ?? '');
        }

        $singleLine = trim(str_replace("\n", ' ', $text));
        $tokens = preg_split('/\s+/u', $singleLine) ?: [];
        $last = trim((string) end($tokens), " \t\n\r\0\x0B,.;:()[]");
        if (strlen($last) >= 6 && strlen($last) <= 40 && preg_match('/[A-Z]/i', $last) && preg_match('/\d/', $last)) {
            return mb_strtoupper($last);
        }

        return null;
    }

    private function extractBarcode(string $text): ?string
    {
        if (! preg_match_all('/(?<!\d)(\d{8}|\d{12,14})(?!\d)/', $text, $matches)) {
            return null;
        }

        foreach ($matches[1] as $candidate) {
            if ($this->hasValidGtinChecksum($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function hasValidGtinChecksum(string $digits): bool
    {
        $numbers = array_map('intval', str_split($digits));
        $check = array_pop($numbers);
        $sum = 0;

        foreach (array_reverse($numbers) as $index => $number) {
            $sum += $number * ($index % 2 === 0 ? 3 : 1);
        }

        return (10 - ($sum % 10)) % 10 === $check;
    }

    private function extractAccessories(string $text): ?string
    {
        $accessories = [
            'cargador' => 'Cargador',
            'adaptador' => 'Adaptador',
            'cable usb' => 'Cable USB',
            'cable hdmi' => 'Cable HDMI',
            'cable de corriente' => 'Cable de corriente',
            'funda' => 'Funda',
            'mouse' => 'Mouse',
            'teclado' => 'Teclado',
            'base' => 'Base',
            'stylus' => 'Stylus',
            'pluma' => 'Pluma',
            'bateria' => 'Batería',
            'batería' => 'Batería',
        ];
        $found = [];

        foreach ($accessories as $needle => $label) {
            if (str_contains(Str::lower($text), $needle)) {
                $found[$label] = $label;
            }
        }

        return $found === [] ? null : implode(', ', array_values($found));
    }

    private function cleanCandidate(string $value, int $limit, bool $stripLabels = false): ?string
    {
        if ($stripLabels) {
            $value = preg_split('/\s+(?:serial|s\/?n|service tag|upc|ean|gtin|p\/?n)\s*[:#-]?/iu', $value)[0] ?? $value;
        }

        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? '', " \t\n\r\0\x0B,.;:|-");

        return $value === '' ? null : mb_substr($value, 0, $limit);
    }
}
