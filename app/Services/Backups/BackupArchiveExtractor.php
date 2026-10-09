<?php

namespace App\Services\Backups;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class BackupArchiveExtractor
{
    /**
     * @return array<int, array{customer:string, category:string, name:string, path:string, size:int}>
     */
    public function extract(FilesystemAdapter $disk, string $archivePath, string $site, string $uploadId): array
    {
        $absoluteArchive = $disk->path($archivePath);
        $entries = class_exists(ZipArchive::class)
            ? $this->zipEntries($absoluteArchive)
            : $this->tarEntries($absoluteArchive);

        if (count($entries) > 10000) {
            throw new RuntimeException('El ZIP contiene demasiados elementos.');
        }

        $manifest = [];
        $totalExtracted = 0;
        $maxExtracted = (int) config('backup-storage.max_extracted_size', config('backup-storage.max_file_size'));

        foreach ($entries as $entry) {
            $backup = $this->backupMetadata($entry);
            if ($backup === null) {
                continue;
            }

            $customerSlug = Str::slug($backup['customer']) ?: 'cliente';
            $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $backup['name']) ?: 'respaldo.'.$backup['category'];
            $path = sprintf(
                'backups/%s/%s/%s/%s_%d_%s',
                $site,
                $customerSlug,
                ucfirst($backup['category']),
                $uploadId,
                count($manifest),
                $safeName,
            );

            $disk->makeDirectory(dirname($path));
            $size = class_exists(ZipArchive::class)
                ? $this->extractWithZip($absoluteArchive, $entry, $disk->path($path))
                : $this->extractWithTar($absoluteArchive, $entry, $disk->path($path));

            $totalExtracted += $size;
            if ($totalExtracted > $maxExtracted) {
                $disk->delete($path);
                throw new RuntimeException('El contenido descomprimido supera el límite permitido.');
            }

            $manifest[] = $backup + ['path' => $path, 'size' => $size];
        }

        if ($manifest === []) {
            throw new RuntimeException('El ZIP no contiene archivos con la estructura Cliente/Index/*.index o Cliente/Bak/*.bak.');
        }

        return $manifest;
    }

    /** @return array<int, string> */
    private function zipEntries(string $archive): array
    {
        $zip = new ZipArchive;
        if ($zip->open($archive) !== true) {
            throw new RuntimeException('El archivo ZIP está dañado o no se puede abrir.');
        }

        try {
            $entries = [];
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $name = $zip->getNameIndex($index);
                if (is_string($name)) {
                    $this->assertSafeEntry($name);
                    $entries[] = $name;
                }
            }

            return $entries;
        } finally {
            $zip->close();
        }
    }

    /** @return array<int, string> */
    private function tarEntries(string $archive): array
    {
        [$exitCode, $output, $error] = $this->run(['tar', '-tf', $archive]);
        if ($exitCode !== 0) {
            throw new RuntimeException('El archivo ZIP está dañado o no se puede abrir. '.$error);
        }

        $entries = preg_split('/\r\n|\r|\n/', trim($output)) ?: [];
        foreach ($entries as $entry) {
            $this->assertSafeEntry($entry);
        }

        return array_values(array_filter($entries, fn (string $entry) => $entry !== ''));
    }

    private function extractWithZip(string $archive, string $entry, string $destination): int
    {
        $zip = new ZipArchive;
        if ($zip->open($archive) !== true) {
            throw new RuntimeException('No fue posible abrir el ZIP durante la extracción.');
        }

        $source = $zip->getStream($entry);
        $target = fopen($destination, 'wb');
        if ($source === false || $target === false) {
            $zip->close();
            throw new RuntimeException('No fue posible extraer uno de los archivos del ZIP.');
        }

        stream_copy_to_stream($source, $target);
        fclose($source);
        fclose($target);
        $zip->close();

        return (int) filesize($destination);
    }

    private function extractWithTar(string $archive, string $entry, string $destination): int
    {
        $target = fopen($destination, 'wb');
        if ($target === false) {
            throw new RuntimeException('No fue posible crear el archivo extraído.');
        }

        $pipes = [];
        $process = proc_open(
            ['tar', '-xOf', $archive, $entry],
            [0 => ['pipe', 'r'], 1 => $target, 2 => ['pipe', 'w']],
            $pipes,
        );
        if (! is_resource($process)) {
            fclose($target);
            throw new RuntimeException('El servidor no dispone de un extractor ZIP.');
        }

        fclose($pipes[0]);
        $error = stream_get_contents($pipes[2]) ?: '';
        fclose($pipes[2]);
        $exitCode = proc_close($process);
        fclose($target);

        if ($exitCode !== 0) {
            @unlink($destination);
            throw new RuntimeException('No fue posible extraer un archivo del ZIP. '.$error);
        }

        return (int) filesize($destination);
    }

    /** @return array{customer:string, category:string, name:string}|null */
    private function backupMetadata(string $entry): ?array
    {
        $normalized = str_replace('\\', '/', trim($entry, '/'));
        $parts = array_values(array_filter(explode('/', $normalized), fn (string $part) => $part !== ''));
        if (count($parts) < 3 || str_ends_with($entry, '/')) {
            return null;
        }

        $name = $parts[array_key_last($parts)];
        $category = mb_strtolower($parts[count($parts) - 2]);
        $customer = trim($parts[count($parts) - 3]);
        $extension = mb_strtolower((string) pathinfo($name, PATHINFO_EXTENSION));

        if (! in_array($category, ['index', 'bak'], true) || $extension !== $category || $customer === '') {
            return null;
        }

        return compact('customer', 'category', 'name');
    }

    private function assertSafeEntry(string $entry): void
    {
        $normalized = str_replace('\\', '/', $entry);
        $parts = explode('/', $normalized);
        if ($entry === '' || str_contains($entry, "\0") || str_starts_with($normalized, '/') || preg_match('/^[A-Za-z]:\//', $normalized) || in_array('..', $parts, true)) {
            throw new RuntimeException('El ZIP contiene una ruta no permitida.');
        }
    }

    /** @return array{int, string, string} */
    private function run(array $command): array
    {
        $pipes = [];
        $process = proc_open($command, [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ], $pipes);
        if (! is_resource($process)) {
            throw new RuntimeException('El servidor no dispone de un extractor ZIP.');
        }

        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]) ?: '';
        $error = stream_get_contents($pipes[2]) ?: '';
        fclose($pipes[1]);
        fclose($pipes[2]);

        return [proc_close($process), $output, trim($error)];
    }
}
