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
        $bank = (array) config('support.questions', []);
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
            'signatureImage' => $this->dataUri(public_path('img/support/julian-emiliano-signature.png')),
        ])->setPaper('letter');

        $dompdf = $pdf->getDomPDF();
        $dompdf->add_info('Title', 'Guía de uso: '.$data['label'].' '.$data['serial']);
        $dompdf->add_info('Author', $data['responsible']);
        $dompdf->add_info('Subject', 'Guía del apartado '.$data['label']);

        return ['content' => $pdf->output(), 'filename' => $data['filename']];
    }

    private function pageDecorationDataUri(): ?string
    {
        $paths = [
            'header' => public_path('img/delivery-notes/datamid-header.png'),
            'logo' => public_path('img/delivery-notes/datamid-logo.png'),
            'footer' => public_path('img/delivery-notes/datamid-footer.png'),
        ];
        if (! function_exists('imagecreatetruecolor') || collect($paths)->contains(fn (string $path): bool => ! is_file($path))) {
            return null;
        }

        $canvas = imagecreatetruecolor(1224, 1584);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 255, 255, 255, 127);
        imagefilledrectangle($canvas, 0, 0, 1224, 1584, $transparent);
        imagealphablending($canvas, true);

        $placements = [
            ['path' => $paths['header'], 'x' => 0, 'y' => 0, 'width' => 216, 'height' => 214],
            ['path' => $paths['logo'], 'x' => 876, 'y' => 56, 'width' => 240, 'height' => 72],
            ['path' => $paths['footer'], 'x' => 0, 'y' => 1221, 'width' => 1224, 'height' => 363],
        ];

        foreach ($placements as $placement) {
            $source = imagecreatefrompng($placement['path']);
            if ($source === false) {
                imagedestroy($canvas);

                return null;
            }
            imagecopyresampled(
                $canvas,
                $source,
                $placement['x'],
                $placement['y'],
                0,
                0,
                $placement['width'],
                $placement['height'],
                imagesx($source),
                imagesy($source)
            );
            imagedestroy($source);
        }

        ob_start();
        imagepng($canvas);
        $content = ob_get_clean();
        imagedestroy($canvas);

        return is_string($content) ? 'data:image/png;base64,'.base64_encode($content) : null;
    }

    private function dataUri(string $path): ?string
    {
        if (! is_file($path)) {
            return null;
        }

        $mime = mime_content_type($path) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($path));
    }
}
