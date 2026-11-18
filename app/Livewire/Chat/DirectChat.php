<?php

namespace App\Livewire\Chat;

use App\Models\DirectMessage;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Chat flotante abajo a la derecha (como el de Facebook): siempre visible en todas las pantallas, se minimiza y se expande, y
 * permite escribirse mensajes directos con cualquier colaborador. El estado (abierto o minimizado y la conversación activa) se
 * recuerda en la sesión para que no cambie al moverse entre pantallas. Minimizado muestra los mensajes nuevos y quién está
 * escribiendo; expandido, la lista de conversaciones con filas exactas (5) y la conversación activa.
 */
class DirectChat extends Component
{
    private const MAX_LENGTH = 1000;

    private const VISIBLE_MESSAGES = 60;

    public bool $expanded = false;

    public ?int $activeUserId = null;

    public string $message = '';

    public string $search = '';

    /** Cambia con cada intento de envío para que el aviso emergente de error vuelva a mostrarse. */
    public int $errorSeq = 0;

    public function mount(): void
    {
        $state = (array) session('direct_chat', []);
        $this->expanded = (bool) ($state['expanded'] ?? false);
        $active = isset($state['active']) ? (int) $state['active'] : null;
        $this->activeUserId = $active && $active !== (int) Auth::id() && User::query()->whereKey($active)->exists() ? $active : null;
    }

    public function toggle(): void
    {
        $this->expanded = ! $this->expanded;
        $this->remember();
    }

    public function minimize(): void
    {
        $this->expanded = false;
        $this->remember();
    }

    /** Abre la conversación con una persona (desde la lista, un aviso o el botón «Enviar mensaje» de su perfil). */
    #[On('open-direct-chat')]
    public function openConversation(int $userId): void
    {
        if ($userId === (int) Auth::id() || ! User::query()->whereKey($userId)->exists()) {
            return;
        }

        $this->forgetTyping();
        $this->activeUserId = $userId;
        $this->expanded = true;
        $this->search = '';
        $this->message = '';
        $this->remember();
        $this->dispatch('direct-chat-sent');
    }

    public function closeConversation(): void
    {
        $this->forgetTyping();
        $this->activeUserId = null;
        $this->message = '';
        $this->remember();
    }

    /** Mientras se escribe, la otra persona ve «está escribiendo» (dura unos segundos y se renueva con cada tecla). */
    public function updatedMessage(string $value): void
    {
        if ($this->activeUserId === null) {
            return;
        }

        if (trim($value) === '') {
            $this->forgetTyping();

            return;
        }

        Cache::put($this->typingKey((int) Auth::id(), $this->activeUserId), true, now()->addSeconds(6));
    }

    public function send(): void
    {
        $this->errorSeq++;
        $me = (int) Auth::id();
        $body = trim($this->message);

        if ($this->activeUserId === null || $this->activeUserId === $me) {
            return;
        }
        if ($body === '') {
            $this->addError('message', 'Escribe un mensaje antes de enviarlo.');

            return;
        }
        if (mb_strlen($body) > self::MAX_LENGTH) {
            $this->addError('message', 'El mensaje no puede superar los '.self::MAX_LENGTH.' caracteres.');

            return;
        }

        $rateKey = 'direct-chat:'.$me;
        if (RateLimiter::tooManyAttempts($rateKey, 30)) {
            $this->addError('message', 'Espera unos segundos antes de enviar más mensajes.');

            return;
        }
        RateLimiter::hit($rateKey, 60);

        DirectMessage::create(['sender_id' => $me, 'recipient_id' => $this->activeUserId, 'body' => $body]);

        $this->forgetTyping();
        $this->resetErrorBag();
        $this->message = '';
        $this->dispatch('direct-chat-sent');
    }

    private function typingKey(int $from, int $to): string
    {
        return 'direct-chat-typing:'.$from.':'.$to;
    }

    private function forgetTyping(): void
    {
        if ($this->activeUserId !== null) {
            Cache::forget($this->typingKey((int) Auth::id(), $this->activeUserId));
        }
    }

    private function remember(): void
    {
        session(['direct_chat' => ['expanded' => $this->expanded, 'active' => $this->activeUserId]]);
    }

    public function render()
    {
        $me = (int) Auth::id();
        $unreadTotal = DirectMessage::query()->where('recipient_id', $me)->whereNull('read_at')->count();

        $activeUser = null;
        $messages = collect();
        $people = collect();
        $previews = collect();
        $sessionsTable = (string) config('session.table', 'sessions');
        $recentLimit = now()->subMinutes(2)->timestamp;

        if ($this->activeUserId !== null) {
            $activeUser = User::query()->find($this->activeUserId);
            if ($activeUser === null) {
                $this->activeUserId = null;
            } else {
                // Al tener abierta (expandida) la conversación, lo recibido se marca como leído
                if ($this->expanded) {
                    DirectMessage::query()->where('sender_id', $activeUser->id)->where('recipient_id', $me)->whereNull('read_at')->update(['read_at' => now()]);
                    $unreadTotal = DirectMessage::query()->where('recipient_id', $me)->whereNull('read_at')->count();
                }
                $messages = DirectMessage::query()
                    ->where(fn ($query) => $query->between($me, $activeUser->id))
                    ->latest('id')->limit(self::VISIBLE_MESSAGES)->get()->reverse()->values();
            }
        }

        $onlineIds = DB::table($sessionsTable)->whereNotNull('user_id')->where('user_id', '!=', $me)
            ->where('last_activity', '>=', $recentLimit)->distinct()->pluck('user_id')->map(fn ($id): int => (int) $id)->all();
        $threads = $this->threads($me);

        // Avisos del chat minimizado: mensajes nuevos (uno por persona) y quién está escribiendo
        $previews = $threads
            ->filter(fn (array $thread): bool => $thread['unread'] > 0 || Cache::has($this->typingKey($thread['user']->id, $me)))
            ->take(3)->values()
            ->map(fn (array $thread): array => $thread + ['typing' => Cache::has($this->typingKey($thread['user']->id, $me))]);

        if ($activeUser === null) {
            $term = trim($this->search);

            if ($term !== '') {
                $like = '%'.$term.'%';
                $people = User::query()->whereKeyNot($me)
                    ->where(fn ($query) => $query->where('name', 'like', $like)->orWhere('last_name', 'like', $like)->orWhere('email', 'like', $like))
                    ->orderBy('name')->orderBy('last_name')->limit(25)->get()
                    ->map(fn (User $person): array => ['user' => $person, 'last' => (string) $person->email, 'at' => null, 'unread' => 0]);
            } else {
                // Conversaciones recientes y, después, las personas conectadas con quien aún no se ha hablado
                $threadIds = $threads->pluck('user.id')->all();
                $people = $threads->concat(
                    User::query()->whereKey($onlineIds)->whereNotIn('id', $threadIds)->orderBy('name')->limit(15)->get()
                        ->map(fn (User $person): array => ['user' => $person, 'last' => 'En línea', 'at' => null, 'unread' => 0])
                )->values();
            }

            $people = $people->map(fn (array $row): array => $row + [
                'online' => in_array($row['user']->id, $onlineIds, true),
                'typing' => Cache::has($this->typingKey($row['user']->id, $me)),
            ]);
        }

        $activeOnline = $activeUser !== null && in_array($activeUser->id, $onlineIds, true);
        $activeTyping = $activeUser !== null && Cache::has($this->typingKey($activeUser->id, $me));

        return view('livewire.chat.direct-chat', compact('unreadTotal', 'activeUser', 'messages', 'people', 'previews', 'activeOnline', 'activeTyping'));
    }

    /** Conversaciones recientes: la persona, el último mensaje y cuántos mensajes tiene sin leer. */
    private function threads(int $me)
    {
        $recent = DirectMessage::query()
            ->where(fn ($query) => $query->where('sender_id', $me)->orWhere('recipient_id', $me))
            ->latest('id')->limit(300)->get();

        $unread = DirectMessage::query()->where('recipient_id', $me)->whereNull('read_at')
            ->select('sender_id', DB::raw('count(*) as total'))->groupBy('sender_id')->pluck('total', 'sender_id');

        $latest = $recent->groupBy(fn (DirectMessage $item): int => $item->sender_id === $me ? $item->recipient_id : $item->sender_id)
            ->map(fn ($group) => $group->first())->take(15);

        $people = User::query()->whereKey($latest->keys())->get()->keyBy('id');

        return $latest->map(fn (DirectMessage $item, int $otherId): ?array => $people->has($otherId) ? [
            'user' => $people[$otherId],
            'last' => ($item->sender_id === $me ? 'Tú: ' : '').$item->body,
            'at' => $item->created_at,
            'unread' => (int) ($unread[$otherId] ?? 0),
        ] : null)->filter()->values();
    }
}
