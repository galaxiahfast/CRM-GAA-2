<?php

namespace App\Services\Notifications;

use App\Models\Role;
use App\Models\User;
use App\Notifications\SystemEventNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class SystemNotificationService
{
    public function sendToUser(User $user, array $payload): void
    {
        try {
            $user->notify(new SystemEventNotification($this->normalizePayload($payload)));
        } catch (Throwable $notificationError) {
            // Nunca se vuelve a reportar esta excepción: evita un ciclo si la BD
            // o el propio canal de notificaciones están indisponibles.
            try {
                Log::channel('single')->error('No fue posible guardar una notificación interna.', [
                    'user_id' => $user->getKey(),
                    'exception' => $notificationError::class,
                    'message' => $notificationError->getMessage(),
                ]);
            } catch (Throwable) {
                // El flujo principal tampoco depende de que el log esté disponible.
            }
        }
    }

    public function loginSucceeded(User $user, ?string $ipAddress = null, ?string $userAgent = null): void
    {
        $this->sendToUser($user, [
            'category' => 'auth',
            'severity' => 'success',
            'title' => 'Inicio de sesión exitoso',
            'message' => 'Se inició sesión en tu cuenta'.($ipAddress ? " desde {$ipAddress}" : '').'.',
            'context' => [
                'ip' => $ipAddress,
                'device' => $userAgent ? Str::limit($userAgent, 120) : null,
            ],
        ]);
    }

    public function loginFailed(User $user, ?string $ipAddress = null): void
    {
        $deduplicationKey = 'notification:failed-login:'.$user->getKey().':'.sha1((string) $ipAddress);

        if (! $this->claimOnce($deduplicationKey, now()->addMinutes(2))) {
            return;
        }

        $this->sendToUser($user, [
            'category' => 'security',
            'severity' => 'warning',
            'title' => 'Intento de acceso fallido',
            'message' => 'Se detectó un intento fallido de inicio de sesión'.($ipAddress ? " desde {$ipAddress}" : '').'.',
            'context' => ['ip' => $ipAddress],
        ]);
    }

    public function reportIncident(Throwable $exception, ?User $affectedUser = null, array $context = []): string
    {
        $reference = $this->referenceFor($exception);
        $fingerprint = sha1($exception::class.'|'.$exception->getMessage().'|'.($context['source'] ?? '').'|'.($affectedUser?->getKey() ?? 'guest'));

        if (! $this->claimOnce('notification:incident:'.$fingerprint, now()->addMinute())) {
            return $reference;
        }

        $administrators = $this->administrators();
        $administratorIds = $administrators->modelKeys();

        if ($affectedUser && ! in_array($affectedUser->getKey(), $administratorIds, true)) {
            $this->sendToUser($affectedUser, [
                'category' => 'system',
                'severity' => 'error',
                'title' => 'Incidente del sistema',
                'message' => "Una operación no pudo completarse. Referencia: {$reference}.",
                'reference' => $reference,
                'context' => $this->safeContext($context),
            ]);
        }

        foreach ($administrators as $administrator) {
            $this->sendToUser($administrator, [
                'category' => 'system',
                'severity' => 'error',
                'title' => 'Error del sistema',
                'message' => Str::limit(class_basename($exception).': '.$exception->getMessage(), 320),
                'reference' => $reference,
                'context' => $this->safeContext($context),
            ]);
        }

        return $reference;
    }

    public function referenceFor(Throwable $exception): string
    {
        return 'INC-'.strtoupper(substr(sha1($exception::class.'|'.$exception->getMessage().'|'.$exception->getFile().'|'.$exception->getLine()), 0, 10));
    }

    private function administrators(): Collection
    {
        try {
            return User::query()
                ->whereHas('role', function ($query) {
                    $query->where('permission_profile', Role::PROFILE_ADMINISTRATOR)
                        ->orWhere('role', 'Administrador');
                })
                ->get();
        } catch (Throwable) {
            return collect();
        }
    }

    private function normalizePayload(array $payload): array
    {
        return [
            'category' => $payload['category'] ?? 'operation',
            'severity' => $payload['severity'] ?? 'info',
            'title' => $payload['title'] ?? 'Aviso del sistema',
            'message' => $payload['message'] ?? '',
            'reference' => $payload['reference'] ?? null,
            'context' => $this->safeContext($payload['context'] ?? []),
            'action_url' => $payload['action_url'] ?? null,
        ];
    }

    private function safeContext(array $context): array
    {
        return collect($context)
            ->except(['password', 'password_confirmation', 'token', 'authorization', 'cookie', 'headers', 'trace'])
            ->map(fn ($value) => is_scalar($value) || $value === null ? $value : Str::limit(json_encode($value) ?: '[dato no serializable]', 250))
            ->all();
    }

    private function claimOnce(string $key, mixed $expiresAt): bool
    {
        try {
            return Cache::add($key, true, $expiresAt);
        } catch (Throwable) {
            // Si el caché falla, es preferible intentar guardar el aviso.
            return true;
        }
    }
}
