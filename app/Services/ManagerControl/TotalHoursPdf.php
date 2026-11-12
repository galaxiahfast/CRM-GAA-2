<?php

namespace App\Services\ManagerControl;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;

/**
 * Réplica del reporte «Total de Horas Laboradas» de ManagerControl con el formato de los reportes del CRM
 * (carta, franja y logo de DataMID, secciones numeradas «01 / …» y pie con número de página).
 * Sin caché: son datos sensibles.
 */
class TotalHoursPdf
{
    private const PAGE_HEIGHT = 560; // alto útil del contenido, en puntos (con margen de seguridad)

    private const FIRST_PAGE_INTRO = 150; // alto del encabezado de la primera página

    /** @param array{id: int, name: string} $employee */
    public function html(array $employee, string $from, string $to, HoursSummary $summary): string
    {
        $pages = $this->paginate($summary);

        return view('pdf.managercontrol-total-hours', [
            'employee' => $employee,
            'from' => Carbon::parse($from)->format('d/m/Y'),
            'to' => Carbon::parse($to)->format('d/m/Y'),
            'summary' => $summary,
            'pages' => $pages,
            'filename' => 'total-horas-laboradas_'.$employee['id'].'_'.$from.'_'.$to,
            'logos' => $this->dataUriPages(public_path('img/delivery-notes/datamid-logo.png'), count($pages)),
            'headerDecorations' => $this->dataUriPages(public_path('img/delivery-notes/datamid-header.png'), count($pages)),
            'footerDecorations' => $this->dataUriPages(public_path('img/delivery-notes/datamid-footer.png'), count($pages)),
            'generatedAt' => Carbon::now(),
        ])->render();
    }

    /** @param array{id: int, name: string} $employee */
    public function render(array $employee, string $from, string $to, HoursSummary $summary): string
    {
        return Pdf::loadHTML($this->html($employee, $from, $to, $summary))
            ->setPaper('letter', 'portrait')
            ->setOptions([
                'defaultFont' => 'DejaVu Sans Mono',
                'isRemoteEnabled' => false,
                'isPhpEnabled' => false,
                'isJavascriptEnabled' => false,
                'isFontSubsettingEnabled' => false,
            ])
            ->output();
    }

    /**
     * Reporte con el formato original de ManagerControl (el que genera su Crystal Report «Total de Horas Laboradas»).
     *
     * @param  array{id: int, name: string}  $employee
     */
    public function originalHtml(array $employee, ?string $department, string $from, string $to, HoursSummary $summary): string
    {
        $logo = public_path('img/managercontrol/gaa-logo.png');
        $rows = $summary->rows;
        usort($rows, fn (array $a, array $b): int => strcmp($a['date'], $b['date']));

        return view('pdf.managercontrol-original', [
            'employee' => $employee,
            'department' => $department,
            'from' => Carbon::parse($from)->format('d/m/Y'),
            'to' => Carbon::parse($to)->format('d/m/Y'),
            'rows' => $rows,
            'totalSeconds' => $summary->totalSeconds,
            'logo' => is_file($logo) ? 'data:image/png;base64,'.base64_encode((string) file_get_contents($logo)) : null,
            'generatedAt' => Carbon::now(config('app.timezone') === 'UTC' ? 'America/Mexico_City' : config('app.timezone'))->locale('es')->translatedFormat('d-M-Y'),
        ])->render();
    }

    /** @param array{id: int, name: string} $employee */
    public function renderOriginal(array $employee, ?string $department, string $from, string $to, HoursSummary $summary): string
    {
        return Pdf::loadHTML($this->originalHtml($employee, $department, $from, $to, $summary))
            ->setPaper('letter', 'portrait')
            ->setOptions(['defaultFont' => 'Helvetica', 'isRemoteEnabled' => false, 'isPhpEnabled' => false, 'isJavascriptEnabled' => false])
            ->output();
    }

    /**
     * Reparte el contenido en páginas de alto fijo (el diseño es de página completa, sin cortes automáticos).
     *
     * @return list<list<array<string, mixed>>>
     */
    public function paginate(HoursSummary $summary): array
    {
        $items = [];
        $section = function (string $title, bool $divider = true) use (&$items): void {
            if ($divider && $items !== []) {
                $items[] = ['type' => 'divider', 'h' => 27];
            }
            $items[] = ['type' => 'section', 'title' => $title, 'h' => 24];
        };

        foreach ([['01 / Distribución por cliente', $summary->byCustomer, 'Sin clientes en el periodo.'], ['02 / Distribución por actividad', $summary->byActivity, 'Sin actividades en el periodo.']] as [$title, $data, $empty]) {
            $section($title);
            if ($data === []) {
                $items[] = ['type' => 'empty', 'text' => $empty, 'h' => 24];
            }
            foreach ($data as $name => $seconds) {
                $items[] = [
                    'type' => 'srow', 'h' => 12 + 13.2 * $this->lines((string) $name, 40),
                    'cells' => [(string) $name, HoursSummary::format($seconds), ($summary->totalSeconds > 0 ? round($seconds * 100 / $summary->totalSeconds) : 0).' %'],
                ];
            }
        }

        $section('03 / Detalle por día');
        if ($summary->byDay === []) {
            $items[] = ['type' => 'empty', 'text' => 'Sin horas laboradas en el periodo.', 'h' => 24];
        }
        foreach ($summary->byDay as $date => $rows) {
            $items[] = ['type' => 'day', 'h' => 28, 'date' => Carbon::parse($date)->format('d/m/Y'), 'total' => HoursSummary::format(array_sum(array_column($rows, 'seconds'))), 'continued' => false];
            foreach ($rows as $row) {
                $lines = max($this->lines($row['customer'], 24), $this->lines($row['activity'], 22), $this->lines($row['description'] !== '' ? $row['description'] : '—', 24));
                $items[] = [
                    'type' => 'row', 'h' => 12 + 13.2 * $lines, 'date' => $date,
                    'cells' => [$row['customer'], $row['activity'], $row['description'] !== '' ? $row['description'] : '—', HoursSummary::format($row['seconds'])],
                ];
            }
        }
        $items[] = ['type' => 'total', 'h' => 30, 'text' => 'Total acumulado: '.HoursSummary::format($summary->totalSeconds)];

        // Reparto en páginas: un título nunca queda solo al final de la página.
        $pages = [[]];
        $used = 0;
        $capacity = self::PAGE_HEIGHT - self::FIRST_PAGE_INTRO;
        $currentDay = null;
        foreach ($items as $index => $item) {
            $needs = $item['h'];
            // Un título viaja con lo que sigue (y un título de sección con el día y su primera fila).
            if (in_array($item['type'], ['section', 'day'], true)) {
                for ($next = $index + 1, $chain = 0; isset($items[$next]) && $chain < 2; $next++, $chain++) {
                    $needs += $items[$next]['h'];
                    if (! in_array($items[$next]['type'], ['section', 'day'], true)) {
                        break;
                    }
                }
            }
            if ($used + $needs > $capacity && $pages[array_key_last($pages)] !== []) {
                $pages[] = [];
                $used = 0;
                $capacity = self::PAGE_HEIGHT;
                if ($item['type'] === 'row' && $currentDay !== null) {
                    $heading = $currentDay;
                    $heading['continued'] = true;
                    $pages[array_key_last($pages)][] = $heading;
                    $used += $heading['h'];
                }
            }
            if ($item['type'] === 'day') {
                $currentDay = $item;
            }
            $pages[array_key_last($pages)][] = $item;
            $used += $item['h'];
        }

        return $pages;
    }

    private function lines(string $text, int $charsPerLine): int
    {
        return max(1, (int) ceil(mb_strlen($text) / $charsPerLine));
    }

    /** @return array<int, string|null> */
    private function dataUriPages(string $path, int $pages): array
    {
        if (! is_file($path)) {
            return array_fill(0, $pages, null);
        }

        [$mime, $contents] = $this->flattened($path);

        // Cada página lleva su propia copia (igual que en el reporte de respaldos) para que Dompdf no reutilice la imagen.
        return collect(range(1, $pages))
            ->map(fn (int $page): string => 'data:'.$mime.';base64,'.base64_encode($contents."DATAMID-PAGE-{$page}"))
            ->all();
    }

    /**
     * Copia JPEG sobre fondo blanco (se guarda una vez en storage). Las PNG con transparencia hacían que Dompdf
     * procesara el canal alfa en cada página: un PDF de 7 páginas tardaba más de 5 segundos.
     *
     * @return array{0: string, 1: string}
     */
    private function flattened(string $path): array
    {
        if (! function_exists('imagecreatefrompng') || ! str_ends_with(strtolower($path), '.png')) {
            return [mime_content_type($path) ?: 'image/png', (string) file_get_contents($path)];
        }

        $cache = storage_path('app/pdf-assets/'.pathinfo($path, PATHINFO_FILENAME).'-'.filemtime($path).'.jpg');
        if (! is_file($cache)) {
            $source = @imagecreatefrompng($path);
            if ($source === false) {
                return ['image/png', (string) file_get_contents($path)];
            }
            $canvas = imagecreatetruecolor(imagesx($source), imagesy($source));
            imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
            imagecopy($canvas, $source, 0, 0, 0, 0, imagesx($source), imagesy($source));
            if (! is_dir(dirname($cache))) {
                mkdir(dirname($cache), 0755, true);
            }
            imagejpeg($canvas, $cache, 92);
            imagedestroy($source);
            imagedestroy($canvas);
        }

        return ['image/jpeg', (string) file_get_contents($cache)];
    }
}
