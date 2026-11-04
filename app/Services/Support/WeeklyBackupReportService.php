<?php

namespace App\Services\Support;

use App\Mail\BackupReportMail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Envío automático semanal del Reporte de respaldos.
 *
 * Lee los mismos logs que la pantalla (perfil por perfil, solo lectura), genera el mismo PDF y lo manda
 * únicamente a los destinatarios de config('backup-reports.weekly.recipients'). No toca el envío manual
 * del componente Livewire; solo reutiliza sus servicios de lectura, PDF y correo.
 */
class WeeklyBackupReportService
{
    /** Misma clave que usa la pantalla para mostrar la última lectura guardada. */
    public const SNAPSHOT_KEY = 'backup-reports.last-snapshot';

    /** Resultado del último envío automático (lo muestra la pantalla). */
    public const LAST_RUN_KEY = 'backup-reports.weekly-last-run';

    public function __construct(
        private readonly BackupLogReaderService $reader,
        private readonly BackupReportPdfService $pdf,
    ) {}

    /** @return array<int, string> */
    public function recipients(): array
    {
        return collect(config('backup-reports.weekly.recipients', []))
            ->map(fn ($email): string => trim((string) $email))
            ->filter(fn (string $email): bool => filter_var($email, FILTER_VALIDATE_EMAIL) !== false)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Lee todos los perfiles, genera el PDF y lo envía. Con $send = false solo comprueba la lectura y el PDF.
     *
     * @return array{ok: bool, sent: bool, status: string, recipients: array<int, string>, profiles: int, errors: int}
     */
    public function run(bool $send = true): array
    {
        $recipients = $this->recipients();

        if ($send && $recipients === []) {
            return $this->finish(false, false, 'No hay destinatarios válidos configurados para el envío semanal.', $recipients, 0, 0);
        }

        if ($send && blank(config('mail.mailers.backup_reports.password'))) {
            return $this->finish(false, false, 'Falta configurar BACKUP_MAIL_PASSWORD en el archivo .env del servidor.', $recipients, 0, 0);
        }

        @set_time_limit(0);
        $servers = $this->readServers();
        $profiles = collect($servers)->flatMap(fn (array $server): array => $server['profiles']);
        $errors = $profiles->filter(fn (array $profile): bool => $profile['available'] && $this->pdf->isBad($profile))->count();

        $generatedAt = now(config('app.timezone'));
        $this->rememberSnapshot($servers, $generatedAt->toIso8601String());

        $filename = 'Reporte_de_Respaldos_'.$generatedAt->format('d-m-Y').'_'.random_int(100000, 999999).'.pdf';

        try {
            $contents = $this->pdf->generate($servers, $generatedAt, $filename, (string) config('backup-reports.responsible'));
        } catch (Throwable $exception) {
            Log::error('No se pudo generar el PDF del reporte semanal de respaldos.', ['message' => $exception->getMessage()]);

            return $this->finish(false, false, 'No se pudo generar el PDF del reporte.', $recipients, $profiles->count(), $errors);
        }

        if (! $send) {
            return $this->finish(true, false, 'Lectura y PDF correctos. No se envió ningún correo (modo de prueba).', $recipients, $profiles->count(), $errors, record: false);
        }

        try {
            Mail::mailer('backup_reports')->to($recipients)->send(new BackupReportMail(
                $contents,
                $filename,
                $generatedAt->translatedFormat('d \d\e F \d\e Y H:i'),
            ));
        } catch (Throwable $exception) {
            Log::error('No se pudo enviar el reporte semanal de respaldos por correo.', [
                'recipients' => $recipients,
                'message' => $exception->getMessage(),
            ]);

            return $this->finish(false, false, 'No se pudo enviar el correo. Verifica la contraseña y la conexión SMTP.', $recipients, $profiles->count(), $errors);
        }

        return $this->finish(true, true, 'Reporte enviado correctamente a '.implode(' y ', $recipients).'.', $recipients, $profiles->count(), $errors);
    }

    /**
     * Arma los servidores y perfiles igual que la pantalla y lee cada log.
     *
     * @return array<int, array<string, mixed>>
     */
    private function readServers(): array
    {
        $servers = collect(config('backup-reports.servers', []))->map(function (array $server, int $index): array {
            return [
                'key' => 'server-'.$index,
                'name' => $server['name'],
                'ip' => $server['ip'],
                'share' => $server['share'],
                'profiles' => collect($server['profiles'])->map(fn (array $profile): array => $this->freshProfile($profile))->all(),
            ];
        })->all();

        foreach ($servers as $serverIndex => $server) {
            foreach ($server['profiles'] as $profileIndex => $profile) {
                $path = rtrim((string) $server['share'], '\\/').DIRECTORY_SEPARATOR.$profile['log'].'.log.html';

                try {
                    $servers[$serverIndex]['profiles'][$profileIndex] = array_merge($profile, $this->reader->read($path, (string) $profile['name']));
                } catch (Throwable $exception) {
                    Log::warning('No se pudo leer un perfil de respaldo (envío semanal).', [
                        'server' => $server['name'],
                        'profile' => $profile['name'],
                        'message' => $exception->getMessage(),
                    ]);
                    $servers[$serverIndex]['profiles'][$profileIndex] = array_merge($profile, [
                        'available' => false,
                        'done' => true,
                        'failed' => false,
                        'message' => $exception->getMessage() ?: 'Log no disponible o sin permiso.',
                    ]);
                }
            }
        }

        return $servers;
    }

    /**
     * @param  array{name: string, log: string}  $profile
     * @return array<string, mixed>
     */
    private function freshProfile(array $profile): array
    {
        return [
            'name' => $profile['name'], 'log' => $profile['log'], 'available' => false, 'done' => false,
            'date' => '', 'errors' => [], 'failedFiles' => [], 'issues' => [], 'failed' => false,
            'message' => 'Pendiente', 'processed' => '', 'source' => '', 'destination' => '',
            'duration' => '', 'readBytes' => 0, 'totalBytes' => 0, 'readSeconds' => 0,
        ];
    }

    /** @param array<int, array<string, mixed>> $servers */
    private function rememberSnapshot(array $servers, string $generatedAt): void
    {
        try {
            Cache::forever(self::SNAPSHOT_KEY, ['servers' => $servers, 'generatedAt' => $generatedAt]);
        } catch (Throwable $exception) {
            Log::warning('No se pudo guardar la lectura del reporte semanal de respaldos.', ['message' => $exception->getMessage()]);
        }
    }

    /**
     * @param  array<int, string>  $recipients
     * @return array{ok: bool, sent: bool, status: string, recipients: array<int, string>, profiles: int, errors: int}
     */
    private function finish(bool $ok, bool $sent, string $status, array $recipients, int $profiles, int $errors, bool $record = true): array
    {
        if ($record) {
            try {
                Cache::forever(self::LAST_RUN_KEY, [
                    'at' => now(config('app.timezone'))->toIso8601String(),
                    'ok' => $ok,
                    'sent' => $sent,
                    'status' => $status,
                    'recipients' => $recipients,
                    'profiles' => $profiles,
                    'errors' => $errors,
                ]);
            } catch (Throwable) {
                // El resultado también queda en el log; no se interrumpe el comando por esto.
            }
        }

        return ['ok' => $ok, 'sent' => $sent, 'status' => $status, 'recipients' => $recipients, 'profiles' => $profiles, 'errors' => $errors];
    }
}
