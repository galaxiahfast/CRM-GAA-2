<?php

namespace App\Livewire\Support;

use App\Mail\BackupReportMail;
use App\Services\Support\BackupLogReaderService;
use App\Services\Support\BackupReportPdfService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Attributes\Renderless;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ServerBackupReports extends Component
{
    /** @var array<int, array<string, mixed>> */
    public array $servers = [];

    /** @var array<int, array{server: int, profile: int}> */
    public array $pendingProfiles = [];

    public bool $reading = false;

    public bool $stopped = false;

    public bool $initialScanStarted = false;

    public int $processedProfiles = 0;

    public int $totalProfiles = 0;

    public string $statusMessage = 'Listo para consultar los registros.';

    public string $generatedAt = '';

    public string $responsible = '';

    public string $emailStatus = '';

    public bool $emailSent = false;

    /** @var array<int, string> */
    public array $emailRecipients = [];

    public bool $showProfileEditor = false;

    public bool $showServerEditor = false;

    public ?int $editingServer = null;

    public ?int $editingProfile = null;

    /** @var array<string, mixed> */
    public array $profileForm = [];

    /** @var array<string, string> */
    public array $serverForm = ['name' => '', 'ip' => '', 'share' => ''];

    public function mount(): void
    {
        Gate::authorize('manage-backup-reports');
        $this->responsible = (string) config('backup-reports.responsible');
        $this->emailRecipients = array_values(config('backup-reports.email.recipients', []));
        $this->resetReport();
    }

    public function startReading(): void
    {
        Gate::authorize('manage-backup-reports');
        if ($this->reading) {
            return;
        }

        $this->initialScanStarted = true;
        $this->emailStatus = '';
        $this->emailSent = false;
        $this->resetReport();
        $this->reading = true;
        $this->stopped = false;
        $this->statusMessage = 'Consultando perfiles por separado. Puedes detener la lectura cuando termine el perfil actual.';
        $this->dispatchScanProgress();
        $this->dispatch('backup-read-next');
    }

    public function readNext(BackupLogReaderService $reader): void
    {
        Gate::authorize('manage-backup-reports');
        if (! $this->reading || $this->pendingProfiles === []) {
            $this->finishReading();

            return;
        }

        @set_time_limit(0);
        $next = array_shift($this->pendingProfiles);
        $serverIndex = $next['server'];
        $profileIndex = $next['profile'];
        $server = $this->servers[$serverIndex];
        $profile = $server['profiles'][$profileIndex];
        $path = rtrim((string) $server['share'], '\\/').DIRECTORY_SEPARATOR.$profile['log'].'.log.html';

        try {
            $result = $reader->read($path, (string) $profile['name']);
            $this->servers[$serverIndex]['profiles'][$profileIndex] = array_merge($profile, $result);
        } catch (Throwable $exception) {
            Log::warning('No se pudo leer un perfil de respaldo.', [
                'server' => $server['name'],
                'profile' => $profile['name'],
                'message' => $exception->getMessage(),
            ]);
            $this->servers[$serverIndex]['profiles'][$profileIndex] = array_merge($profile, [
                'available' => false,
                'done' => true,
                'failed' => false,
                'message' => $exception->getMessage() ?: 'Log no disponible o sin permiso.',
            ]);
        }

        $this->processedProfiles++;
        $this->generatedAt = now(config('app.timezone'))->toIso8601String();

        if ($this->pendingProfiles === [] || ! $this->reading) {
            $this->finishReading();
        } else {
            $this->dispatchScanProgress();
            $this->dispatch('backup-read-next');
            $this->skipRender();
        }
    }

    public function stopReading(): void
    {
        $this->reading = false;
        $this->stopped = true;
        $this->pendingProfiles = [];
        $this->statusMessage = 'Lectura detenida. Se conservaron los resultados disponibles.';
        $this->dispatchScanProgress();
    }

    public function openProfile(int $serverIndex, int $profileIndex): void
    {
        $profile = $this->servers[$serverIndex]['profiles'][$profileIndex] ?? null;
        abort_unless(is_array($profile), 404);
        $this->editingServer = $serverIndex;
        $this->editingProfile = $profileIndex;
        $this->profileForm = [
            'name' => (string) $profile['name'],
            'log' => (string) $profile['log'],
            'date' => (string) $profile['date'],
            'processed' => (string) $profile['processed'],
            'errors' => implode("\n", $profile['errors'] ?? []),
            'issues' => implode("\n", $profile['issues'] ?? []),
            'available' => (bool) $profile['available'],
            'source' => (string) $profile['source'],
            'destination' => (string) $profile['destination'],
            'duration' => (string) $profile['duration'],
        ];
        $this->showProfileEditor = true;
    }

    public function addProfile(int $serverIndex): void
    {
        abort_unless(isset($this->servers[$serverIndex]), 404);
        $this->editingServer = $serverIndex;
        $this->editingProfile = null;
        $this->profileForm = [
            'name' => '', 'log' => '', 'date' => '', 'processed' => '', 'errors' => '', 'issues' => '',
            'available' => true, 'source' => '', 'destination' => '', 'duration' => '',
        ];
        $this->showProfileEditor = true;
    }

    public function saveProfile(): void
    {
        $this->validate([
            'profileForm.name' => ['required', 'string', 'max:100'],
            'profileForm.log' => ['nullable', 'string', 'max:100'],
            'profileForm.date' => ['nullable', 'string', 'max:100'],
            'profileForm.processed' => ['nullable', 'string', 'max:30'],
            'profileForm.errors' => ['nullable', 'string', 'max:10000'],
            'profileForm.issues' => ['nullable', 'string', 'max:10000'],
            'profileForm.available' => ['boolean'],
        ]);

        $errors = $this->lines((string) $this->profileForm['errors']);
        $issues = $this->lines((string) $this->profileForm['issues']);
        $profile = $this->freshProfile([
            'name' => trim((string) $this->profileForm['name']),
            'log' => trim((string) ($this->profileForm['log'] ?: $this->profileForm['name'])),
        ]);
        $profile = array_merge($profile, [
            'available' => (bool) $this->profileForm['available'],
            'done' => true,
            'date' => trim((string) $this->profileForm['date']),
            'processed' => trim((string) $this->profileForm['processed']),
            'errors' => $errors,
            'issues' => $issues,
            'failed' => $errors !== [] || $issues !== [],
            'message' => (bool) $this->profileForm['available'] ? 'Ajustado por el responsable.' : 'Excluido por el responsable.',
            'source' => trim((string) ($this->profileForm['source'] ?? '')),
            'destination' => trim((string) ($this->profileForm['destination'] ?? '')),
            'duration' => trim((string) ($this->profileForm['duration'] ?? '')),
        ]);

        if ($this->editingProfile === null) {
            $this->servers[$this->editingServer]['profiles'][] = $profile;
            $this->totalProfiles++;
            $this->processedProfiles++;
        } else {
            $existing = $this->servers[$this->editingServer]['profiles'][$this->editingProfile];
            $this->servers[$this->editingServer]['profiles'][$this->editingProfile] = array_merge($existing, $profile);
        }

        $this->showProfileEditor = false;
        $this->generatedAt = now(config('app.timezone'))->toIso8601String();
    }

    public function deleteProfile(): void
    {
        if ($this->editingServer === null || $this->editingProfile === null) {
            return;
        }
        array_splice($this->servers[$this->editingServer]['profiles'], $this->editingProfile, 1);
        $this->totalProfiles = max(0, $this->totalProfiles - 1);
        $this->processedProfiles = min($this->processedProfiles, $this->totalProfiles);
        $this->showProfileEditor = false;
    }

    public function openServer(?int $serverIndex = null): void
    {
        $this->editingServer = $serverIndex;
        $server = $serverIndex === null ? null : ($this->servers[$serverIndex] ?? null);
        $this->serverForm = [
            'name' => (string) ($server['name'] ?? ''),
            'ip' => (string) ($server['ip'] ?? ''),
            'share' => (string) ($server['share'] ?? ''),
        ];
        $this->showServerEditor = true;
    }

    public function saveServer(): void
    {
        $this->validate([
            'serverForm.name' => ['required', 'string', 'max:100'],
            'serverForm.ip' => ['nullable', 'string', 'max:100'],
            'serverForm.share' => ['nullable', 'string', 'max:500'],
        ]);

        if ($this->editingServer === null) {
            $this->servers[] = [
                'key' => 'manual-'.Str::uuid(),
                'name' => trim($this->serverForm['name']),
                'ip' => trim($this->serverForm['ip']),
                'share' => trim($this->serverForm['share']),
                'profiles' => [],
            ];
        } else {
            foreach (['name', 'ip', 'share'] as $field) {
                $this->servers[$this->editingServer][$field] = trim($this->serverForm[$field]);
            }
        }
        $this->showServerEditor = false;
    }

    public function deleteServer(): void
    {
        if ($this->editingServer === null) {
            return;
        }
        $removed = $this->servers[$this->editingServer];
        $count = count($removed['profiles']);
        array_splice($this->servers, $this->editingServer, 1);
        $this->totalProfiles = max(0, $this->totalProfiles - $count);
        $this->processedProfiles = min($this->processedProfiles, $this->totalProfiles);
        $this->showServerEditor = false;
    }

    public function downloadPdf(BackupReportPdfService $pdfService): StreamedResponse
    {
        Gate::authorize('manage-backup-reports');
        $generatedAt = now(config('app.timezone'));
        $filename = 'Reporte_de_Respaldos_'.$generatedAt->format('d-m-Y').'_'.random_int(100000, 999999).'.pdf';
        $contents = $pdfService->generate($this->servers, $generatedAt, $filename, $this->responsible);

        return response()->streamDownload(static function () use ($contents): void {
            echo $contents;
        }, $filename, ['Content-Type' => 'application/pdf']);
    }

    /** @return array{sent: bool, status: string} */
    #[Renderless]
    public function sendEmail(BackupReportPdfService $pdfService): array
    {
        Gate::authorize('manage-backup-reports');
        $this->emailSent = false;

        if ($this->reading) {
            $this->emailStatus = 'Espera a que termine la lectura antes de enviar el reporte.';

            return $this->emailActionResult();
        }

        if ($this->totalProfiles === 0 || $this->processedProfiles < $this->totalProfiles) {
            $this->emailStatus = 'Primero debes revisar todos los servidores antes de enviar el reporte.';

            return $this->emailActionResult();
        }

        if (blank(config('mail.mailers.backup_reports.password'))) {
            $this->emailStatus = 'Falta configurar BACKUP_MAIL_PASSWORD en el archivo .env del servidor.';

            return $this->emailActionResult();
        }

        $allowedRecipients = array_keys(config('backup-reports.email.recipient_options', []));
        $recipients = collect($this->emailRecipients)
            ->intersect($allowedRecipients)
            ->filter(fn ($email): bool => filter_var($email, FILTER_VALIDATE_EMAIL) !== false)
            ->unique()
            ->values()
            ->all();

        if ($recipients === []) {
            $this->emailStatus = 'No hay destinatarios válidos configurados para el reporte.';

            return $this->emailActionResult();
        }

        $generatedAt = now(config('app.timezone'));
        $filename = 'Reporte_de_Respaldos_'.$generatedAt->format('d-m-Y').'_'.random_int(100000, 999999).'.pdf';
        $contents = $pdfService->generate($this->servers, $generatedAt, $filename, $this->responsible);

        try {
            Mail::mailer('backup_reports')->to($recipients)->send(new BackupReportMail(
                $contents,
                $filename,
                $generatedAt->translatedFormat('d \d\e F \d\e Y H:i'),
            ));
            $this->emailSent = true;
            $this->emailStatus = 'Reporte enviado correctamente a '.implode(' y ', $recipients).'.';
        } catch (Throwable $exception) {
            Log::error('No se pudo enviar el reporte de respaldos por correo.', [
                'recipients' => $recipients,
                'message' => $exception->getMessage(),
            ]);
            $this->emailStatus = 'No se pudo enviar el correo. Verifica la contraseña y la conexión SMTP.';
        }

        return $this->emailActionResult();
    }

    /** @return array{servers: int, profiles: int, errors: int, companies: int, pending: int} */
    public function summary(): array
    {
        $profiles = collect($this->servers)->flatMap(fn (array $server) => $server['profiles']);
        $available = $profiles->filter(fn (array $profile): bool => (bool) $profile['available']);
        $bad = $available->filter(fn (array $profile): bool => $this->isBad($profile));

        return [
            'servers' => collect($this->servers)->filter(fn (array $server): bool => collect($server['profiles'])->contains('available', true))->count(),
            'profiles' => $available->count(),
            'errors' => $bad->count(),
            'companies' => $bad->flatMap(fn (array $profile) => $profile['errors'])->map(fn ($value) => mb_strtolower((string) $value))->unique()->count(),
            'pending' => $profiles->filter(fn (array $profile): bool => ! $profile['available'])->count(),
        ];
    }

    /** @return array<string, string> */
    public function recipientOptions(): array
    {
        return config('backup-reports.email.recipient_options', []);
    }

    public function render(): View
    {
        Gate::authorize('manage-backup-reports');

        return view('livewire.support.server-backup-reports', [
            'summary' => $this->summary(),
            'recipientOptions' => $this->recipientOptions(),
        ])->layout('layouts.app');
    }

    private function resetReport(): void
    {
        $this->servers = collect(config('backup-reports.servers', []))->map(function (array $server, int $index): array {
            return [
                'key' => 'server-'.$index,
                'name' => $server['name'],
                'ip' => $server['ip'],
                'share' => $server['share'],
                'profiles' => collect($server['profiles'])->map(fn (array $profile): array => $this->freshProfile($profile))->all(),
            ];
        })->all();
        $this->pendingProfiles = [];
        foreach ($this->servers as $serverIndex => $server) {
            foreach ($server['profiles'] as $profileIndex => $_profile) {
                $this->pendingProfiles[] = ['server' => $serverIndex, 'profile' => $profileIndex];
            }
        }
        $this->totalProfiles = count($this->pendingProfiles);
        $this->processedProfiles = 0;
        $this->reading = false;
        $this->stopped = false;
        $this->generatedAt = now(config('app.timezone'))->toIso8601String();
    }

    /** @param array{name: string, log: string} $profile
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

    private function finishReading(): void
    {
        $this->reading = false;
        if (! $this->stopped) {
            $this->statusMessage = 'Lectura terminada. Puedes revisar, editar y descargar el informe.';
        }
        $this->dispatchScanProgress();
    }

    private function dispatchScanProgress(): void
    {
        $this->dispatch(
            'backup-scan-progress',
            processed: $this->processedProfiles,
            total: $this->totalProfiles,
            reading: $this->reading,
            message: $this->statusMessage,
        );
    }

    /** @return array{sent: bool, status: string} */
    private function emailActionResult(): array
    {
        $this->skipRender();

        return [
            'sent' => $this->emailSent,
            'status' => $this->emailStatus,
        ];
    }

    /** @return array<int, string> */
    private function lines(string $value): array
    {
        return collect(preg_split('/[\r\n;]+/', $value) ?: [])->map(fn ($line) => trim((string) $line))->filter()->unique()->values()->all();
    }

    /** @param array<string, mixed> $profile */
    private function isBad(array $profile): bool
    {
        return (bool) ($profile['failed'] ?? false) || ($profile['errors'] ?? []) !== [] || ($profile['issues'] ?? []) !== [];
    }
}
