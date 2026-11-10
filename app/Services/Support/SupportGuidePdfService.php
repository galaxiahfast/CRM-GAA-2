<?php

namespace App\Services\Support;

use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Guía en PDF de un apartado del menú, con el mismo formato membretado
 * que usan los Reportes de soporte técnico.
 */
class SupportGuidePdfService
{
    /**
     * @return array<string, mixed>|null
     */
    public function data(string $categoryKey, ?CarbonImmutable $now = null): ?array
    {
        $bank = array_merge(
            (array) config('support.questions', []),
            (array) config('section-guides', [])
        );
        $category = $bank[$categoryKey] ?? null;
        if (! is_array($category)) {
            return null;
        }

        $position = (int) array_search($categoryKey, array_keys($bank), true) + 1;
        $issuedAt = ($now ?? CarbonImmutable::now())
            ->timezone((string) config('support.timezone', 'America/Mexico_City'))
            ->locale('es');

        $items = collect($category['items'] ?? [])
            ->map(fn (array $item): array => [
                'question' => (string) ($item['question'] ?? ''),
                'answer' => (string) ($item['answer'] ?? ''),
                'steps' => array_values(array_filter(array_map('strval', (array) ($item['steps'] ?? [])))),
            ])
            ->values()
            ->all();

        $serial = 'GU-'.$issuedAt->format('Ymd').'-'.str_pad((string) $position, 2, '0', STR_PAD_LEFT);
        $label = (string) ($category['label'] ?? $categoryKey);

        return [
            'serial' => $serial,
            'issuedAt' => $issuedAt,
            'issuedAtLong' => ucfirst($issuedAt->isoFormat('dddd, D [de] MMMM [de] YYYY, HH:mm:ss')).' '.$issuedAt->format('T'),
            'responsible' => 'Julian Emiliano Ortiz Rivero',
            'title' => 'Guía de uso',
            'addressee' => 'Atención: Personal usuario del CRM',
            'label' => $label,
            'menu' => (string) ($category['menu'] ?? $label),
            'summary' => (string) ($category['summary'] ?? ''),
            'items' => $items,
            'filename' => $serial.'-'.Str::slug($label).'.pdf',
        ];
    }

    /**
     * @return array{content: string, filename: string}|null
     */
    public function generate(string $categoryKey, ?CarbonImmutable $now = null): ?array
    {
        $data = $this->data($categoryKey, $now);
        if ($data === null) {
            return null;
        }

        File::ensureDirectoryExists(storage_path('fonts'));

        $pdf = Pdf::loadView('pdf.support-guide', [
            ...$data,
            'pageDecoration' => $this->pageDecorationDataUri(),
            'signatureImage' => $this->signatureDataUri(),
        ])->setPaper('letter');

        $dompdf = $pdf->getDomPDF();
        $dompdf->add_info('Title', 'Guía de uso: '.$data['label'].' '.$data['serial']);
        $dompdf->add_info('Author', $data['responsible']);
        $dompdf->add_info('Subject', 'Guía del apartado '.$data['label']);

        return ['content' => $pdf->output(), 'filename' => $data['filename']];
    }

    /**
     * Las imágenes de la guía se preparan una sola vez y se guardan en disco: componerlas y que DomPDF
     * procese PNG con transparencia en cada descarga tardaba entre 7 y 19 segundos.
     */
    private function pageDecorationDataUri(): ?string
    {
        $paths = [
            public_path('img/delivery-notes/datamid-header.png'),
            public_path('img/delivery-notes/datamid-logo.png'),
            public_path('img/delivery-notes/datamid-footer.png'),
        ];

        return $this->preparedAsset('page-decoration', $paths, fn (): ?string => $this->composePageDecoration());
    }

    private function signatureDataUri(): ?string
    {
        $path = public_path('img/support/julian-emiliano-signature.png');

        return $this->preparedAsset('signature', [$path], fn (): ?string => $this->flattenedJpegDataUri($path, 600));
    }

    /**
     * @param  array<int, string>  $sources
     * @param  callable(): ?string  $build
     */
    private function preparedAsset(string $name, array $sources, callable $build): ?string
    {
        if (collect($sources)->contains(fn (string $path): bool => ! is_file($path))) {
            return null;
        }

        $fingerprint = md5($name.'|v2|'.collect($sources)->map(fn (string $path): string => $path.filemtime($path).filesize($path))->implode('|'));
        $cache = storage_path('framework/cache/guide-asset-'.$fingerprint.'.txt');

        if (is_file($cache)) {
            $stored = (string) file_get_contents($cache);

            if ($stored !== '') {
                return $stored;
            }
        }

        $built = $build();

        if ($built !== null) {
            File::ensureDirectoryExists(dirname($cache));
            file_put_contents($cache, $built);
        }

        return $built;
    }

    /** Lienzo carta sobre fondo blanco opaco (sin canal alfa) con el membrete y el pie de página. */
    private function composePageDecoration(): ?string
    {
        if (! function_exists('imagecreatetruecolor') || ! function_exists('imagejpeg')) {
            return null;
        }

        $canvas = imagecreatetruecolor(1224, 1584);
        imagefilledrectangle($canvas, 0, 0, 1224, 1584, imagecolorallocate($canvas, 255, 255, 255));
        imagealphablending($canvas, true);

        $placements = [
            ['path' => public_path('img/delivery-notes/datamid-header.png'), 'x' => 0, 'y' => 0, 'width' => 216, 'height' => 214],
            ['path' => public_path('img/delivery-notes/datamid-logo.png'), 'x' => 876, 'y' => 56, 'width' => 240, 'height' => 72],
            ['path' => public_path('img/delivery-notes/datamid-footer.png'), 'x' => 0, 'y' => 1221, 'width' => 1224, 'height' => 363],
        ];

        foreach ($placements as $placement) {
            $source = imagecreatefrompng($placement['path']);

            if ($source === false) {
                imagedestroy($canvas);

                return null;
            }

            imagecopyresampled($canvas, $source, $placement['x'], $placement['y'], 0, 0, $placement['width'], $placement['height'], imagesx($source), imagesy($source));
            imagedestroy($source);
        }

        ob_start();
        imagejpeg($canvas, null, 90);
        $content = ob_get_clean();
        imagedestroy($canvas);

        return is_string($content) ? 'data:image/jpeg;base64,'.base64_encode($content) : null;
    }

    /** Reduce la imagen al ancho indicado y la aplana sobre blanco (JPEG): pesa una fracción y DomPDF la procesa al instante. */
    private function flattenedJpegDataUri(string $path, int $maxWidth): ?string
    {
        if (! function_exists('imagecreatefrompng') || ! function_exists('imagejpeg')) {
            return null;
        }

        $source = imagecreatefrompng($path);

        if ($source === false) {
            return null;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $targetWidth = min($maxWidth, $width);
        $targetHeight = (int) round($height * $targetWidth / $width);

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
        imagefilledrectangle($canvas, 0, 0, $targetWidth, $targetHeight, imagecolorallocate($canvas, 255, 255, 255));
        imagealphablending($canvas, true);
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);
        imagedestroy($source);

        ob_start();
        imagejpeg($canvas, null, 90);
        $content = ob_get_clean();
        imagedestroy($canvas);

        return is_string($content) ? 'data:image/jpeg;base64,'.base64_encode($content) : null;
    }
}
