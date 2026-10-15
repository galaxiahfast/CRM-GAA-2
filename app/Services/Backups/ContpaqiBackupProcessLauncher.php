<?php

namespace App\Services\Backups;

use Illuminate\Support\Str;

class ContpaqiBackupProcessLauncher
{
    public function launch(string $exportId): bool
    {
        if (! Str::isUuid($exportId) || ! function_exists('exec')) {
            return false;
        }

        return PHP_OS_FAMILY === 'Windows'
            ? $this->launchOnWindows($exportId)
            : $this->launchOnUnix($exportId);
    }

    private function launchOnWindows(string $exportId): bool
    {
        $quote = static fn (string $value): string => "'".str_replace("'", "''", $value)."'";
        $arguments = '"'.base_path('artisan').'" backups:copy-contpaqi '.$exportId.' --no-interaction';
        $script = '$ErrorActionPreference = \'Stop\'; ';
        $script .= 'Start-Process -FilePath '.$quote(PHP_BINARY);
        $script .= ' -ArgumentList '.$quote($arguments);
        $script .= ' -WorkingDirectory '.$quote(base_path()).' -WindowStyle Hidden';
        $encoded = base64_encode(mb_convert_encoding($script, 'UTF-16LE', 'UTF-8'));
        $output = [];
        $exitCode = 1;

        @exec('powershell.exe -NoProfile -NonInteractive -EncodedCommand '.$encoded, $output, $exitCode);

        return $exitCode === 0;
    }

    private function launchOnUnix(string $exportId): bool
    {
        $log = storage_path('logs/contpaqi-backups.log');
        $command = sprintf(
            'nohup %s %s backups:copy-contpaqi %s --no-interaction >> %s 2>&1 &',
            escapeshellarg(PHP_BINARY),
            escapeshellarg(base_path('artisan')),
            escapeshellarg($exportId),
            escapeshellarg($log),
        );
        $output = [];
        $exitCode = 1;
        @exec($command, $output, $exitCode);

        return $exitCode === 0;
    }
}
