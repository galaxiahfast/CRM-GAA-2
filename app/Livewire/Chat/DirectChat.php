<?php

namespace App\Livewire\Chat;

use App\Models\DirectMessage;
use App\Models\User;
use App\Services\Notifications\SystemNotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
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
        // Desde una notificación de mensaje nuevo (/…?chat=ID) se abre directamente esa conversación
        $fromNotification = (int) request()->query('chat', 0);
        if ($fromNotification > 0 && $fromNotification !== (int) Auth::id() && User::query()->whereKey($fromNotification)->exists()) {
            $this->activeUserId = $fromNotification;
            $this->expanded = true;
            $this->remember();

            return;
        }

        $state = (array) session('direct_chat', []);
        $this->expanded = (bool) ($state['expanded'] ?? false);
        $active = isset($state['active']) ? (int) $state['active'] : null;
        $this->activeUserId = $active && $active !== (int) Auth::id() && User::query()->whereKey($active)->exists() ? $active : null;

        // Al cargar o recargar la página, lo que ya estaba pendiente no se vuelve a mostrar como aviso: solo lo nuevo
        if (! $this->expanded) {
            $this->dismissPreviews();
        }
    }

    /** El navegador abre y minimiza al instante; aquí solo se recuerda para que al recargar quede igual. */
    public function setExpanded(bool $expanded): void
    {
        $this->expanded = $expanded;
        $this->remember();

        if (! $expanded) {
            $this->dismissPreviews();
        }
    }

    /** Al minimizar, los avisos que ya se vieron se ocultan; solo vuelven a salir cuando llega algo nuevo. */
    private function dismissPreviews(): void
    {
        $me = (int) Auth::id();
        session(['direct_chat_dismissed' => $this->previewSignature($this->previewsFor($this->threads($me), $me))]);
    }

    private function previewsFor($threads, int $me)
    {
        return $threads
            ->filter(fn (array $thread): bool => $thread['unread'] > 0 || Cache::has($this->typingKey($thread['user']->id, $me)))
            ->take(3)->values()
            ->map(fn (array $thread): array => $thread + ['typing' => Cache::has($this->typingKey($thread['user']->id, $me))]);
    }

    private function previewSignature($previews): string
    {
        return $previews->map(fn (array $p): string => $p['user']->id.':'.$p['unread'].':'.(int) $p['typing'].':'.($p['last'] ?? ''))->implode('|');
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
        $this->dismissPreviews();
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
        $this->dispatch('direct-chat-opened');
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

        // Una notificación por conversación mientras la otra persona no lea: así no se llena de avisos
        $alreadyNotified = DirectMessage::query()->where('sender_id', $me)->where('recipient_id', $this->activeUserId)->whereNull('read_at')->exists();

        DirectMessage::create(['sender_id' => $me, 'recipient_id' => $this->activeUserId, 'body' => $body]);

        if (! $alreadyNotified && ($recipient = User::query()->find($this->activeUserId))) {
            $sender = Auth::user();
            app(SystemNotificationService::class)->sendToUser($recipient, [
                'category' => 'chat',
                'severity' => 'info',
                'title' => 'Nuevo mensaje de '.trim($sender->name.' '.$sender->last_name),
                'message' => Str::limit($body, 120),
                'action_url' => route('inicio', ['chat' => $me]),
                'context' => ['sender_id' => $me],
            ]);
        }

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

        // Mismo criterio que «En línea ahora» de Inicio: presencia reciente (users.last_seen_at), sesiones activas o cuentas siempre en línea
        $onlineIds = collect(DB::table($sessionsTable)->whereNotNull('user_id')->where('user_id', '!=', $me)
            ->where('last_activity', '>=', $recentLimit)->distinct()->pluck('user_id')->all())
            ->merge(User::query()->whereKeyNot($me)->where(fn ($query) => $query
                ->where('last_seen_at', '>=', now()->subMinutes(3))
                ->orWhereIn('email', (array) config('support.always_online_emails', [])))->pluck('id')->all())
            ->map(fn ($id): int => (int) $id)->unique()->values()->all();
        $threads = $this->threads($me);

        // Avisos del chat minimizado: mensajes nuevos (uno por persona) y quién está escribiendo
        $previews = $this->previewsFor($threads, $me);
        if ($previews->isNotEmpty() && $this->previewSignature($previews) === session('direct_chat_dismissed')) {
            $previews = collect();
        }

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
