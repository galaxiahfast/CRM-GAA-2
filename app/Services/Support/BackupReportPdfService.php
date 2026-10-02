<?php

namespace App\Services\Support;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;

class BackupReportPdfService
{
    /** @param array<int, array<string, mixed>> $servers */
    public function generate(array $servers, Carbon $generatedAt, string $filename, string $responsible): string
    {
        $profiles = collect($servers)->flatMap(fn (array $server) => $server['profiles']);
        $available = $profiles->filter(fn (array $profile): bool => (bool) $profile['available']);
        $errors = $available->filter(fn (array $profile): bool => $this->isBad($profile));
        $affectedCompanies = $available
            ->flatMap(fn (array $profile): array => $profile['errors'] ?? [])
            ->map(fn ($company): string => mb_strtolower((string) $company))
            ->unique()
            ->count();

        $metrics = [
            'servers' => collect($servers)->filter(fn (array $server): bool => collect($server['profiles'])->contains('available', true))->count(),
            'profiles' => $available->count(),
            'errors' => $errors->count(),
            'companies' => $affectedCompanies,
            'totalProfiles' => $profiles->count(),
        ];

        return Pdf::loadView('pdf.server-backup-report', [
            'servers' => $servers,
            'generatedAt' => $generatedAt,
            'filename' => $filename,
            'responsible' => $responsible,
            'metrics' => $metrics,
            'logos' => $this->dataUriPages(public_path('img/delivery-notes/datamid-logo.png')),
            'headerDecorations' => $this->dataUriPages(public_path('img/delivery-notes/datamid-header.png')),
            'footerDecorations' => $this->dataUriPages(public_path('img/delivery-notes/datamid-footer.png')),
        ])->setPaper('a4')->output();
    }

    /** @param array<string, mixed> $profile */
    public function isBad(array $profile): bool
    {
        return (bool) ($profile['failed'] ?? false)
            || ($profile['errors'] ?? []) !== []
            || ($profile['issues'] ?? []) !== [];
    }

    /** @return array<int, string|null> */
    private function dataUriPages(string $path, int $pages = 20): array
    {
        if (! is_file($path)) {
            return array_fill(0, $pages, null);
        }

        $mime = mime_content_type($path) ?: 'image/png';
        $contents = (string) file_get_contents($path);

        return collect(range(1, $pages))
            ->map(fn (int $page): string => 'data:'.$mime.';base64,'.base64_encode($contents."DATAMID-PAGE-{$page}"))
            ->all();
    }
}
