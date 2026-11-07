<?php

namespace App\Livewire\Home;

use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

/**
 * Personas en línea (actividad en los últimos minutos) y la actividad que tienen en curso.
 * Cada fila lleva al perfil de la persona.
 */
class OnlineUsers extends Component
{
    private const ONLINE_MINUTES = 3;

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
        return view('livewire.home.online-users', [
            'rows' => Cache::remember('home:online-users', self::CACHE_SECONDS, fn (): array => $this->rows()),
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    private function rows(): array
    {
        $users = User::query()
            ->where(function ($query): void {
                // El administrador (correos de support.always_online_emails) aparece siempre en línea.
                $query->where('last_seen_at', '>=', now()->subMinutes(self::ONLINE_MINUTES))
                    ->orWhereIn('email', (array) config('support.always_online_emails', []));
            })
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

        $rows = $users->map(function (User $user) use ($activities): array {
            $entry = $activities->get($user->id);

            return [
                'id' => (int) $user->id,
                'name' => trim($user->name.' '.($user->last_name ?? '')) ?: 'Usuario',
                'photo' => $user->profile_photo_path ? $user->profile_photo_url : null,
                'activity' => $entry
                    ? ($entry->subService?->sub_service ?? 'Actividad en curso')
                    : (in_array($user->email, (array) config('support.always_online_emails', []), true) ? 'Programando' : null),
                'customer' => $entry?->customer?->name,
                'section' => $user->last_seen_section,
                'url' => route('profiles.show', $user->id),
            ];
        })->sortBy(fn (array $row): string => ($row['activity'] ? '0' : '1').mb_strtolower($row['name']))->values()->all();

        // Sofía, la asistente virtual, está siempre disponible; su fila abre el asistente.
        $bot = (array) config('support.automated_user');
        array_unshift($rows, [
            'id' => 'sofia',
            'name' => trim(($bot['name'] ?? 'Sofia').' '.($bot['last_name'] ?? '')),
            'photo' => asset(ltrim((string) ($bot['avatar'] ?? 'img/support/sofia-avatar.svg'), '/')),
            'activity' => 'Asistente virtual',
            'customer' => null,
            'section' => 'Soporte',
            'url' => route('soporte.preguntas'),
        ]);

        return $rows;
    }
}
