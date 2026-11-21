<?php

namespace App\Livewire\Home;

use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Throwable;

/**
 * Personas en línea (actividad en los últimos minutos) y la actividad que tienen en curso.
 * Cada fila lleva al perfil de la persona.
 */
class OnlineUsers extends Component
{
    private const LIMIT = 60;

    /** Segundos que se reutiliza la lista: todas las personas ven lo mismo, así que se calcula una vez por tick. */
    private const CACHE_SECONDS = 15;

    /** El inicio se dibuja al instante y esta lista llega justo después (carga diferida), sin frenar la entrada. */
    public function placeholder(): View
    {
        return view('livewire.home.online-users-placeholder');
    }

    public function render(): View
    {
        return view('livewire.home.online-users', ['rows' => $this->safeRows()]);
    }

    /**
     * Esta lista es un complemento del inicio: si algo falla (una migración pendiente, la caché sin tabla, etc.)
     * no debe tumbar la pantalla, así que en ese caso se muestra solo a la asistente virtual y se registra el motivo.
     *
     * @return array<int, array<string, mixed>>
     */
    private function safeRows(): array
    {
        try {
            return Cache::remember('home:online-users', self::CACHE_SECONDS, fn (): array => $this->rows());
        } catch (Throwable $exception) {
            report($exception);

            try {
                return $this->rows();
            } catch (Throwable) {
                return [];
            }
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function rows(): array
    {
        // Los agentes (bots) siempre; los demás solo si tienen la web abierta
        $users = User::query()
            ->online()
            ->orderBy('name')
            ->limit(self::LIMIT)
            ->get(['id', 'name', 'email', 'last_name', 'profile_photo_path', 'last_seen_at', 'last_seen_section']);

        $activities = $users->isEmpty()
            ? collect()
            : TimeEntry::query()
                ->where('status', TimeEntry::STATUS_IN_PROGRESS)
                ->whereIn('user_id', $users->modelKeys())
                ->with(['subService:id,sub_service', 'customer:id,name'])
                ->get()
                ->keyBy('user_id');

        // Los agentes (bots) muestran lo que hacen y van primero; su fila de soporte abre el asistente
        $bots = collect((array) config('support.bots', []))->keyBy('email');

        $rows = $users->map(function (User $user) use ($activities, $bots): array {
            $entry = $activities->get($user->id);
            $bot = $bots->get($user->email);

            if ($bot) {
                return [
                    'id' => (int) $user->id,
                    'name' => trim($user->name.' '.($user->last_name ?? '')),
                    'photo' => $user->profile_photo_path ? $user->profile_photo_url : asset($bot['photo']),
                    'activity' => $bot['activity'],
                    'customer' => null,
                    'section' => $bot['section'],
                    'url' => $user->email === config('support.automated_user.email') ? route('soporte.preguntas') : route('profiles.show', $user->id),
                    'bot' => true,
                ];
            }

            return [
                'id' => (int) $user->id,
                'name' => trim($user->name.' '.($user->last_name ?? '')) ?: 'Usuario',
                'photo' => $user->avatarUrl(),
                'activity' => $entry
                    ? ($entry->subService?->sub_service ?? 'Actividad en curso')
                    : null,
                'customer' => $entry?->customer?->name,
                'section' => $user->last_seen_section,
                'url' => route('profiles.show', $user->id),
            ];
        })->sortBy(fn (array $row): string => (! empty($row['bot']) ? '0' : ($row['activity'] ? '1' : '2')).mb_strtolower($row['name']))->values()->all();

        return $rows;
    }
}
