<?php

namespace App\Services\Support;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use thiagoalessio\TesseractOCR\TesseractOCR;
use Throwable;

class EquipmentOcrService
{
    public function extract(string $imagePath, string $preprocessing = 'default'): string
    {
        if (! is_file($imagePath) || ! is_readable($imagePath)) {
            throw new RuntimeException('No fue posible leer la fotografía temporal.');
        }

        $prepared = $this->preprocess($imagePath, $preprocessing);

        try {
            return match (config('equipment-autofill.ocr.driver', 'tesseract')) {
                'ocr_space' => $this->extractWithOcrSpace($prepared),
                default => $this->extractWithTesseract($prepared),
            };
        } finally {
            if ($prepared !== $imagePath) {
                @unlink($prepared);
            }
        }
    }

    private function extractWithTesseract(string $imagePath): string
    {
        try {
            $ocr = (new TesseractOCR($imagePath))
                ->executable((string) config('equipment-autofill.ocr.tesseract.executable', 'tesseract'))
                ->psm(6)
                ->threadLimit(1);

            $languages = array_values(array_filter(explode(
                '+',
                (string) config('equipment-autofill.ocr.tesseract.languages', 'eng+spa')
            )));

            if ($languages !== []) {
                $ocr->lang(...$languages);
            }

            $text = trim($ocr->run((int) config('equipment-autofill.ocr.tesseract.timeout', 25)));
        } catch (Throwable $exception) {
            report($exception);

            throw new RuntimeException(
                'El OCR local no está disponible. Instala Tesseract o escribe los datos de la etiqueta manualmente.',
                previous: $exception,
            );
        }

        if ($text === '') {
            throw new RuntimeException('No se encontró texto legible en la fotografía.');
        }

        return $text;
    }

    private function extractWithOcrSpace(string $imagePath): string
    {
        $apiKey = trim((string) config('equipment-autofill.ocr.ocr_space.api_key'));
        if ($apiKey === '') {
            throw new RuntimeException('Falta configurar OCR_SPACE_API_KEY.');
        }

        try {
            $response = Http::acceptJson()
                ->timeout((int) config('equipment-autofill.ocr.ocr_space.timeout', 25))
                ->attach('file', file_get_contents($imagePath), basename($imagePath))
                ->post((string) config('equipment-autofill.ocr.ocr_space.endpoint'), [
                    'apikey' => $apiKey,
                    'language' => 'auto',
                    'detectOrientation' => 'true',
                    'scale' => 'true',
                    'OCREngine' => '2',
                ]);

            $response->throw();
            $payload = $response->json();
            $text = collect($payload['ParsedResults'] ?? [])
                ->pluck('ParsedText')
                ->filter()
                ->implode("\n");

            if (($payload['IsErroredOnProcessing'] ?? false) || trim($text) === '') {
                throw new RuntimeException('OCR.space no encontró texto legible en la fotografía.');
            }

            return trim($text);
        } catch (RuntimeException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            throw new RuntimeException('No fue posible consultar OCR.space en este momento.', previous: $exception);
        }
    }

    private function preprocess(string $imagePath, string $mode): string
    {
        if ($mode === 'default') {
            return $imagePath;
        }
        if (! in_array($mode, ['grayscale', 'contrast', 'rotate'], true)) {
            throw new RuntimeException('El modo de preprocesamiento OCR no es válido.');
        }

        $source = @imagecreatefromstring((string) file_get_contents($imagePath));
        if (! $source) {
            throw new RuntimeException('No fue posible preparar la fotografía para un nuevo intento.');
        }

        try {
            if ($mode === 'grayscale') {
                imagefilter($source, IMG_FILTER_GRAYSCALE);
            } elseif ($mode === 'contrast') {
                imagefilter($source, IMG_FILTER_GRAYSCALE);
                imagefilter($source, IMG_FILTER_CONTRAST, -35);
            } else {
                $rotated = imagerotate($source, 90, 255);
                if ($rotated instanceof \GdImage) {
                    imagedestroy($source);
                    $source = $rotated;
                }
            }

            $temporary = tempnam(sys_get_temp_dir(), 'equipment-ocr-');
            if (! $temporary || ! imagejpeg($source, $temporary, 92)) {
                throw new RuntimeException('No fue posible crear la imagen temporal para OCR.');
            }

            return $temporary;
        } finally {
            imagedestroy($source);
        }
    }
}
