<?php

namespace App\Http\Controllers;

use App\Services\Backups\ContpaqiBackupCatalog;
use Illuminate\Support\Facades\Gate;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ContpaqiBackupDownloadController extends Controller
{
    public function __invoke(string $kind, string $token, ContpaqiBackupCatalog $catalog): BinaryFileResponse
    {
        Gate::authorize('access-system-backups');

        try {
            $path = $catalog->resolveDownload($kind, $token);
        } catch (RuntimeException $exception) {
            abort(404, $exception->getMessage());
        }

        return response()->download($path, basename($path), [
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
