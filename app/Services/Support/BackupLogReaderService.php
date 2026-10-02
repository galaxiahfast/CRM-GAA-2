<?php

namespace App\Services\Support;

use RuntimeException;

class BackupLogReaderService
{
    private const INITIAL_WINDOW = 131072;

    /** @return array<string, mixed> */
    public function read(string $path, string $profile): array
    {
        $startedAt = microtime(true);
        $latest = $this->readLatestExecution($path);
        $html = $latest['html'];

        if (! preg_match('/<h2\s+id="(?<id>cs_\d+)"/i', $html, $heading)) {
            throw new RuntimeException('El formato del log no pudo interpretarse.');
        }

        $target = preg_quote($heading['id'], '/');
        if (! preg_match('/<h2\s+id="'.$target.'"[^>]*>(?<date>.*?)<\/h2>(?<body>.*?)(?=<h2\s+id=|<\/body>)/is', $html, $block)) {
            throw new RuntimeException('La última ejecución no está completa en el log.');
        }

        $date = $this->plain($block['date']);
        $body = $block['body'];
        $tablePosition = stripos($body, '<table');
        $summary = $tablePosition === false ? $body : substr($body, 0, $tablePosition);
        $issues = [];

        foreach (preg_split('/<br\s*\/?\s*>|<\/p>|<\/div>/i', $summary) ?: [] as $line) {
            $plain = $this->plain($line);
            if (preg_match('/configuraci[oó]n inv[aá]lida|directorio (destino|origen) inv[aá]lido/iu', $plain)) {
                $issues[] = $plain;
            }
        }

        $failedRows = 0;
        $companies = [];
        $failedFiles = [];
        $seenFiles = [];

        preg_match_all('/<tr[^>]*>(?<row>.*?)<\/tr>/is', $body, $rows);
        foreach ($rows['row'] ?? [] as $row) {
            preg_match_all('/<td[^>]*>(?<cell>.*?)<\/td>/is', $row, $matches);
            $cells = array_map(fn (string $cell): string => $this->plain($cell), $matches['cell'] ?? []);
            if (count($cells) < 5 || ! preg_match('/^(Fall|Error)/iu', $cells[0])) {
                continue;
            }

            $failedRows++;
            $company = $this->companyFromFile($cells[4]);
            if ($company === null) {
                continue;
            }

            $companies[mb_strtolower($company)] = $company;
            if (! isset($seenFiles[mb_strtolower($cells[4])])) {
                $seenFiles[mb_strtolower($cells[4])] = true;
                $failedFiles[] = ['path' => $cells[4], 'company' => $company];
            }
        }

        if ($failedRows > 0 && $companies === [] && $issues === []) {
            $issues[] = 'Se registraron errores en archivos internos del respaldo.';
        }

        $plainSummary = $this->plain(preg_replace('/<br\s*\/?\s*>/i', ' | ', $summary) ?? $summary);
        preg_match('/Hecho:\s*(?<done>\d+)\/(?<total>\d+)/iu', $plainSummary, $counts);

        return [
            'profile' => $profile,
            'available' => true,
            'done' => true,
            'date' => $date,
            'errors' => array_values($companies),
            'failedFiles' => $failedFiles,
            'issues' => array_values(array_unique($issues)),
            'failed' => $failedRows > 0 || $issues !== [],
            'message' => '',
            'processed' => isset($counts['done']) ? $counts['done'].'/'.$counts['total'] : '',
            'source' => $this->summaryValue($plainSummary, 'Izquierda'),
            'destination' => $this->summaryValue($plainSummary, 'Derecha'),
            'duration' => $this->summaryValue($plainSummary, 'Tiempo Transcurrido'),
            'readBytes' => $latest['readBytes'],
            'totalBytes' => $latest['totalBytes'],
            'readSeconds' => round(microtime(true) - $startedAt, 3),
        ];
    }

    /** @return array{html: string, readBytes: int, totalBytes: int} */
    private function readLatestExecution(string $path): array
    {
        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('Log no disponible o sin permiso.');
        }

        try {
            $stat = fstat($handle);
            $length = (int) ($stat['size'] ?? 0);
            if ($length < 1) {
                throw new RuntimeException('El log está vacío.');
            }

            $headLength = min(16384, $length);
            $head = (string) fread($handle, $headLength);
            $readBytes = strlen($head);
            if (! preg_match('/href="#(?<id>cs_\d+)">Ir al m[^<]+reciente/iu', $head, $link)) {
                throw new RuntimeException('No se encontró el enlace a la última ejecución.');
            }

            $marker = '<h2 id="'.$link['id'].'"';
            $window = min(self::INITIAL_WINDOW, $length);
            while (true) {
                $offset = max(0, $length - $window);
                fseek($handle, $offset);
                $tail = stream_get_contents($handle, $window) ?: '';
                $readBytes += strlen($tail);
                $position = stripos($tail, $marker);
                if ($position !== false) {
                    return [
                        'html' => substr($tail, $position),
                        'readBytes' => $readBytes,
                        'totalBytes' => $length,
                    ];
                }

                if ($window >= $length) {
                    throw new RuntimeException('La última ejecución no está completa en el log.');
                }
                $window = min($length, $window * 2);
            }
        } finally {
            fclose($handle);
        }
    }

    private function plain(string $value): string
    {
        $decoded = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim((string) preg_replace('/\s+/u', ' ', $decoded));
    }

    private function summaryValue(string $summary, string $label): string
    {
        preg_match('/'.preg_quote($label, '/').':\s*(?<value>[^|]+)/iu', $summary, $match);

        return trim($match['value'] ?? '');
    }

    private function companyFromFile(string $file): ?string
    {
        $name = basename(str_replace('\\', '/', ltrim($file, '\\')));
        if (! preg_match('/\.(mdf|ldf)$/iu', $name) || ! preg_match('/^(cc_|gub_|ct)/iu', $name)) {
            return null;
        }

        $name = preg_replace('/_log\.ldf$/iu', '', $name) ?? $name;
        $name = preg_replace('/\.(mdf|ldf)$/iu', '', $name) ?? $name;
        $name = preg_replace('/^cc_[^_]+_CFDI_/iu', '', $name) ?? $name;
        $name = preg_replace('/^gub_[^_]+_\d{4}_/iu', '', $name) ?? $name;
        $name = preg_replace('/^ct(?=[A-Z0-9])/u', '', $name) ?? $name;
        $ignored = ['add_catalogos', 'appmanager', 'db_directory', 'generalessql', 'master', 'mastlog', 'model', 'modellog', 'msdbdata', 'msdblog', 'tempdb', 'templog'];

        return in_array(mb_strtolower($name), $ignored, true) ? null : $name;
    }
}
