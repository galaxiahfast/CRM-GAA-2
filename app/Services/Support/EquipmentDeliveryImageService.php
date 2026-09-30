<?php

namespace App\Services\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class EquipmentDeliveryImageService
{
    private const MAX_PIXELS = 40_000_000;

    private const MAX_DIMENSION = 2000;

    public function store(UploadedFile $upload): string
    {
        $payload = file_get_contents($upload->getRealPath());
        $imageInfo = @getimagesizefromstring((string) $payload);

        if (! $imageInfo || ($imageInfo[0] * $imageInfo[1]) > self::MAX_PIXELS) {
            throw new RuntimeException('La fotografía no es válida o tiene una resolución demasiado grande.');
        }

        $source = @imagecreatefromstring((string) $payload);
        if (! $source) {
            throw new RuntimeException('No fue posible leer la fotografía seleccionada.');
        }

        try {
            $source = $this->orientJpeg($source, $upload);
            $sourceWidth = imagesx($source);
            $sourceHeight = imagesy($source);
            $scale = min(1, self::MAX_DIMENSION / max($sourceWidth, $sourceHeight));
            $width = max(1, (int) round($sourceWidth * $scale));
            $height = max(1, (int) round($sourceHeight * $scale));
            $normalized = imagecreatetruecolor($width, $height);
            $white = imagecolorallocate($normalized, 255, 255, 255);
            imagefill($normalized, 0, 0, $white);
            imagecopyresampled($normalized, $source, 0, 0, 0, 0, $width, $height, $sourceWidth, $sourceHeight);

            ob_start();
            imagejpeg($normalized, null, 86);
            $jpeg = (string) ob_get_clean();
            imagedestroy($normalized);
        } finally {
            imagedestroy($source);
        }

        $path = 'delivery-notes/'.Str::uuid().'.jpg';
        if (! Storage::disk('local')->put($path, $jpeg)) {
            throw new RuntimeException('No fue posible guardar la fotografía.');
        }

        return $path;
    }

    public function delete(?string $path): void
    {
        if ($path) {
            Storage::disk('local')->delete($path);
        }
    }

    private function orientJpeg(\GdImage $image, UploadedFile $upload): \GdImage
    {
        if (! function_exists('exif_read_data') || $upload->getMimeType() !== 'image/jpeg') {
            return $image;
        }

        $exif = @exif_read_data($upload->getRealPath());
        $orientation = (int) ($exif['Orientation'] ?? 1);
        $rotated = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => false,
        };

        if ($rotated instanceof \GdImage) {
            imagedestroy($image);

            return $rotated;
        }

        return $image;
    }
}
