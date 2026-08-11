<?php

namespace App\Livewire\Support;

use App\Models\Role;
use App\Models\SupportChatMessage;
use App\Models\User;
use App\Services\Notifications\SystemNotificationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class TicketChat extends Component
{
    public string $message = '';

    /** @var array<int, array<string, mixed>> */
    public array $messages = [];

    /** @var array<int, array{id: int, type: string, name: string, email: string, handle: string, initials: string, photo_url: ?string}> */
    public array $mentionSuggestions = [];

    /** @var array<int, array{id: int, name: string, email: string, initials: string, photo_url: ?string, is_current: bool}> */
    public array $onlineUsers = [];

    public string $todayLabel = '';

    public ?int $automatedUserId = null;

    /** @var array<int, int> */
    public array $alwaysOnlineUserIds = [];

    public bool $canDeleteAllMessages = false;

    public function mount(): void
    {
        abort_unless(auth()->check(), 403);

        [$start] = $this->dayBounds();
        SupportChatMessage::withTrashed()->where('created_at', '<', $start)->forceDelete();

        $now = Carbon::now($this->timezone());
        $this->todayLabel = $now->locale('es')->translatedFormat('l d \d\e F \d\e Y');
        $this->automatedUserId = (int) $this->automatedUser()->id;
        $this->canDeleteAllMessages = $this->isSupportAdministrator(auth()->user()->loadMissing('role'));
        $this->alwaysOnlineUserIds = User::query()
            ->whereIn('email', (array) config('support.always_online_emails', []))
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
        $this->refreshMessages();
    }

    public function updatedMessage(string $value): void
    {
        if (! preg_match('/(?:^|\s)@([\pL\pN._-]*)$/u', $value, $matches)) {
            $this->mentionSuggestions = [];

            return;
        }

        $this->mentionSuggestions = $this->findMentionSuggestions($matches[1] ?? '');
    }

    public function selectMention(int $userId): void
    {
        if ($userId === 0 && preg_match('/(?:^|\s)@[\pL\pN._-]*$/u', $this->message)) {
            $this->message = preg_replace('/@([\pL\pN._-]*)$/u', '@todos ', $this->message, 1) ?? $this->message;
            $this->mentionSuggestions = [];

            return;
        }

        $user = User::query()
            ->select(['id', 'name', 'last_name', 'email', 'profile_photo_path'])
            ->whereKey($userId)
            ->whereKeyNot(auth()->id())
            ->whereKeyNot($this->automatedUserId)
            ->first();

        if (! $user || ! preg_match('/(?:^|\s)@[\pL\pN._-]*$/u', $this->message)) {
            $this->mentionSuggestions = [];

            return;
        }

        $this->message = preg_replace(
            '/@([\pL\pN._-]*)$/u',
            '@'.$this->mentionHandle($user).' ',
            $this->message,
            1
        ) ?? $this->message;
        $this->mentionSuggestions = [];
    }

    public function sendMessage(): void
    {
        $user = auth()->user();
        abort_unless($user, 403);

        $validated = $this->validate([
            'message' => ['required', 'string', 'max:1000'],
        ], [
            'message.required' => 'Escribe un mensaje antes de enviarlo.',
            'message.max' => 'El mensaje no puede superar los 1000 caracteres.',
        ]);

        $rateKey = 'support-chat:'.$user->getAuthIdentifier();
        if (RateLimiter::tooManyAttempts($rateKey, 12)) {
            $this->addError('message', 'Espera unos segundos antes de enviar más mensajes.');

            return;
        }

        RateLimiter::hit($rateKey, 60);

        $message = SupportChatMessage::create([
            'user_id' => $user->getAuthIdentifier(),
            'message' => trim($validated['message']),
        ]);

        $this->notifyMentionedUsers($message, $user);

        $this->reset('message');
        $this->mentionSuggestions = [];
        $this->refreshMessages();
        $this->dispatch('support-message-sent');
    }

    public function refreshMessages(): void
    {
        $this->ensureCurrentGreeting();
        $this->messages = $this->todayMessages();
        $this->refreshOnlineUsers();
        $this->dispatch('support-messages-refreshed');
    }

    public function deleteMessage(int $messageId): void
    {
        $message = SupportChatMessage::query()->findOrFail($messageId);
        $user = User::query()->with('role')->findOrFail((int) auth()->id());

        abort_unless(
            (int) $message->user_id === (int) $user->id || $this->isSupportAdministrator($user),
            403
        );

        $message->delete();
        $this->refreshMessages();
    }

    public function downloadPdf(): StreamedResponse
    {
        abort_unless(auth()->check(), 403);

        $messages = $this->todayMessages(includePdfPhotos: true);
        $date = Carbon::now($this->timezone());
        $pdf = Pdf::loadView('pdf.support-ticket-conversation', [
            'messages' => $messages,
            'dateLabel' => $date->locale('es')->translatedFormat('l d \d\e F \d\e Y'),
            'generatedAt' => $date->format('d/m/Y H:i'),
            'generatedBy' => trim((auth()->user()->name ?? '').' '.(auth()->user()->last_name ?? '')),
        ])->setPaper('a4');

        return response()->streamDownload(
            static fn () => print $pdf->output(),
            'conversacion-soporte-'.$date->format('Y-m-d').'.pdf',
            ['Content-Type' => 'application/pdf']
        );
    }

    /** @return array<int, array<string, mixed>> */
    private function todayMessages(bool $includePdfPhotos = false): array
    {
        [$start, $end] = $this->dayBounds();
        $currentUserId = (int) auth()->id();

        return SupportChatMessage::query()
            ->withTrashed()
            ->with('user:id,name,last_name,email,profile_photo_path')
            ->whereBetween('created_at', [$start, $end])
            ->oldest('created_at')
            ->oldest('id')
            ->get()
            ->map(fn (SupportChatMessage $message): array => [
                'id' => $message->id,
                'name' => trim(($message->user?->name ?? 'Usuario').' '.($message->user?->last_name ?? '')),
                'email' => $message->user?->email ?? 'Correo no disponible',
                'message' => $message->trashed() ? '' : $message->message,
                'automatic_key' => $message->automatic_key,
                'photo_url' => $message->user ? $this->profilePhotoUrl($message->user) : null,
                'pdf_photo_data' => $includePdfPhotos && $message->user ? $this->profilePhotoDataUri($message->user) : null,
                'time' => $message->created_at->timezone($this->timezone())->format('H:i'),
                'is_mine' => (int) $message->user_id === $currentUserId,
                'is_deleted' => $message->trashed(),
                'can_delete' => ! $message->trashed()
                    && ((int) $message->user_id === $currentUserId || $this->canDeleteAllMessages),
            ])
            ->all();
    }

    /** @return array<int, array{id: int, type: string, name: string, email: string, handle: string, initials: string, photo_url: ?string}> */
    private function findMentionSuggestions(string $query): array
    {
        $needle = Str::lower(trim($query));
        $searchTerm = collect(preg_split('/[-._\s]+/', $needle))
            ->filter()
            ->first();

        $people = User::query()
            ->select(['id', 'name', 'last_name', 'email', 'profile_photo_path'])
            ->whereNotIn('id', array_filter([(int) auth()->id(), $this->automatedUserId]))
            ->when($searchTerm, function ($builder) use ($searchTerm): void {
                $like = '%'.$searchTerm.'%';
                $builder->where(function ($query) use ($like): void {
                    $query->where('name', 'like', $like)
                        ->orWhere('last_name', 'like', $like)
                        ->orWhere('email', 'like', $like);
                });
            })
            ->orderBy('name')
            ->orderBy('last_name')
            ->limit(25)
            ->get()
            ->map(function (User $user): array {
                $name = $this->userName($user);

                return [
                    'id' => (int) $user->id,
                    'type' => 'user',
                    'name' => $name,
                    'email' => $user->email,
                    'handle' => $this->mentionHandle($user),
                    'initials' => $this->initials($name),
                    'photo_url' => $this->profilePhotoUrl($user),
                ];
            })
            ->filter(function (array $user) use ($needle): bool {
                if ($needle === '') {
                    return true;
                }

                return Str::contains(Str::lower($user['handle'].' '.$user['name'].' '.$user['email']), $needle);
            })
            ->take(6)
            ->values()
            ->all();

        $everyone = ($needle === '' || Str::contains('todos', $needle)) ? [[
            'id' => 0,
            'type' => 'everyone',
            'name' => 'Todos',
            'email' => 'Notificar a todos los colaboradores',
            'handle' => 'todos',
            'initials' => '@',
            'photo_url' => null,
        ]] : [];

        return [...$everyone, ...$people];
    }

    private function notifyMentionedUsers(SupportChatMessage $message, User $sender): void
    {
        $mentionsEveryone = preg_match('/(?:^|\s)@todos\b/ui', $message->message) === 1;
        preg_match_all('/@([\pL\pN._-]+-(\d+))\b/u', $message->message, $matches, PREG_SET_ORDER);

        if (! $mentionsEveryone && $matches === []) {
            return;
        }

        $handlesById = collect($matches)
            ->mapWithKeys(fn (array $match): array => [(int) $match[2] => $match[1]])
            ->except([(int) $sender->id]);

        if (! $mentionsEveryone && $handlesById->isEmpty()) {
            return;
        }

        $recipients = User::query()
            ->select(['id', 'name', 'last_name', 'email'])
            ->whereNotIn('id', array_filter([(int) $sender->id, $this->automatedUserId]))
            ->when(! $mentionsEveryone, fn ($query) => $query->whereKey($handlesById->keys()))
            ->get()
            ->filter(fn (User $user): bool => $mentionsEveryone || $handlesById->get((int) $user->id) === $this->mentionHandle($user));

        $senderName = $this->userName($sender);
        $notificationService = app(SystemNotificationService::class);

        foreach ($recipients as $recipient) {
            $notificationService->sendToUser($recipient, [
                'category' => 'support',
                'severity' => 'info',
                'title' => $mentionsEveryone ? 'Mensaje para todos en Soporte' : 'Te mencionaron en Soporte',
                'message' => $senderName.($mentionsEveryone ? ' envió un mensaje para todos: ' : ' te mencionó: ').Str::limit($message->message, 180),
                'action_url' => route('soporte.ticket'),
                'context' => [
                    'support_message_id' => $message->id,
                    'sender_id' => $sender->id,
                ],
            ]);
        }
    }

    private function refreshOnlineUsers(): void
    {
        $currentUserId = (int) auth()->id();

        try {
            $onlineIds = DB::table((string) config('session.table', 'sessions'))
                ->whereNotNull('user_id')
                ->where('last_activity', '>=', now()->subMinutes(2)->timestamp)
                ->distinct()
                ->pluck('user_id')
                ->map(fn ($id): int => (int) $id)
                ->push($currentUserId)
                ->push($this->automatedUserId)
                ->merge($this->alwaysOnlineUserIds)
                ->filter()
                ->unique()
                ->values();

            $this->onlineUsers = User::query()
                ->select(['id', 'name', 'last_name', 'email', 'profile_photo_path'])
                ->whereKey($onlineIds)
                ->orderBy('name')
                ->orderBy('last_name')
                ->limit(20)
                ->get()
                ->map(function (User $user) use ($currentUserId): array {
                    $name = $this->userName($user);

                    return [
                        'id' => (int) $user->id,
                        'name' => $name,
                        'email' => $user->email,
                        'initials' => $this->initials($name),
                        'photo_url' => $this->profilePhotoUrl($user),
                        'is_current' => (int) $user->id === $currentUserId,
                    ];
                })
                ->all();
        } catch (Throwable) {
            $this->onlineUsers = User::query()
                ->select(['id', 'name', 'last_name', 'email', 'profile_photo_path'])
                ->whereKey(array_filter([$currentUserId, $this->automatedUserId, ...$this->alwaysOnlineUserIds]))
                ->get()
                ->map(function (User $user) use ($currentUserId): array {
                    $name = $this->userName($user);

                    return [
                        'id' => (int) $user->id,
                        'name' => $name,
                        'email' => $user->email,
                        'initials' => $this->initials($name),
                        'photo_url' => $this->profilePhotoUrl($user),
                        'is_current' => (int) $user->id === $currentUserId,
                    ];
                })
                ->all();
        }
    }

    private function mentionHandle(User $user): string
    {
        $slug = Str::slug($this->userName($user));

        return ($slug !== '' ? $slug : 'usuario').'-'.$user->id;
    }

    private function userName(User $user): string
    {
        return trim($user->name.' '.($user->last_name ?? '')) ?: 'Usuario';
    }

    private function initials(string $name): string
    {
        return collect(preg_split('/\s+/', trim($name)))
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('') ?: '?';
    }

    private function profilePhotoUrl(User $user): ?string
    {
        $automatedUser = (array) config('support.automated_user', []);

        if ($user->email === ($automatedUser['email'] ?? 'sofia.soporte@sistema.local')) {
            return url('/'.ltrim((string) ($automatedUser['avatar'] ?? 'img/support/sofia-avatar.svg'), '/'));
        }

        if (! $user->profile_photo_path) {
            return null;
        }

        if (config('filesystems.disks.public.driver') === 'local') {
            return url('/storage/'.ltrim($user->profile_photo_path, '/'));
        }

        return $user->profile_photo_url;
    }

    private function profilePhotoDataUri(User $user): ?string
    {
        $automatedUser = (array) config('support.automated_user', []);

        if ($user->email === ($automatedUser['email'] ?? 'sofia.soporte@sistema.local')) {
            $path = realpath(public_path(ltrim((string) ($automatedUser['avatar'] ?? 'img/support/sofia-avatar.svg'), '/')));
        } elseif ($user->profile_photo_path && config('filesystems.disks.public.driver') === 'local') {
            $storageRoot = realpath(storage_path('app/public'));
            $path = realpath(storage_path('app/public/'.ltrim($user->profile_photo_path, '/')));

            if (! $storageRoot || ! $path || ! str_starts_with($path, $storageRoot.DIRECTORY_SEPARATOR)) {
                return null;
            }
        } else {
            return null;
        }

        if (! $path || ! is_file($path) || filesize($path) > 5 * 1024 * 1024) {
            return null;
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            return null;
        }

        $mime = mime_content_type($path) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode($contents);
    }

    private function greetingForHour(int $hour): string
    {
        if ($hour < 12) {
            return 'Buenos días';
        }

        if ($hour < 19) {
            return 'Buenas tardes';
        }

        return 'Buenas noches';
    }

    private function ensureCurrentGreeting(): void
    {
        $now = Carbon::now($this->timezone());
        $period = match (true) {
            $now->hour < 12 => 'morning',
            $now->hour < 19 => 'afternoon',
            default => 'night',
        };
        $automaticKey = 'daily-greeting:'.$now->toDateString().':'.$period;

        $loadedGreeting = collect($this->messages)->firstWhere('automatic_key', $automaticKey);
        if ($loadedGreeting && $this->hourMatchesGreetingPeriod((int) substr($loadedGreeting['time'], 0, 2), $period)) {
            return;
        }

        $existingGreeting = SupportChatMessage::withTrashed()
            ->where('automatic_key', $automaticKey)
            ->first();

        if ($existingGreeting) {
            $this->repairAutomaticMessageTimezone($existingGreeting, $period);

            return;
        }

        SupportChatMessage::query()->insertOrIgnore([
            'user_id' => $this->automatedUserId ?: $this->automatedUser()->id,
            'message' => $this->greetingForHour($now->hour),
            'automatic_key' => $automaticKey,
            'created_at' => $now->copy()->timezone($this->applicationTimezone()),
            'updated_at' => $now->copy()->timezone($this->applicationTimezone()),
        ]);
    }

    private function repairAutomaticMessageTimezone(SupportChatMessage $message, string $period): void
    {
        if ($message->trashed() || $this->hourMatchesGreetingPeriod(
            $message->created_at->timezone($this->timezone())->hour,
            $period
        )) {
            return;
        }

        $corrected = Carbon::parse((string) $message->getRawOriginal('created_at'), 'UTC')
            ->timezone($this->applicationTimezone());

        if (! $this->hourMatchesGreetingPeriod($corrected->copy()->timezone($this->timezone())->hour, $period)) {
            return;
        }

        DB::table('support_chat_messages')
            ->where('id', $message->id)
            ->update([
                'created_at' => $corrected,
                'updated_at' => $corrected,
            ]);
    }

    private function hourMatchesGreetingPeriod(int $hour, string $period): bool
    {
        return match ($period) {
            'morning' => $hour < 12,
            'afternoon' => $hour >= 12 && $hour < 19,
            'night' => $hour >= 19,
            default => false,
        };
    }

    private function automatedUser(): User
    {
        $role = Role::query()->firstOrCreate(
            ['role' => 'Auxiliar'],
            [
                'description' => 'Rol operativo predeterminado',
                'permission_profile' => Role::PROFILE_AUXILIARY,
            ]
        );
        $settings = (array) config('support.automated_user', []);

        $user = User::query()->firstOrCreate(
            ['email' => $settings['email'] ?? 'sofia.soporte@sistema.local'],
            [
                'name' => $settings['name'] ?? 'Sofia',
                'last_name' => $settings['last_name'] ?? 'Soporte (bot)',
                'email_verified_at' => now(),
                'password' => Str::random(64),
                'role_id' => $role->id,
            ]
        );

        $expectedIdentity = [
            'name' => $settings['name'] ?? 'Sofia',
            'last_name' => $settings['last_name'] ?? 'Soporte (bot)',
        ];

        if ($user->only(array_keys($expectedIdentity)) !== $expectedIdentity) {
            $user->updateQuietly($expectedIdentity);
        }

        return $user;
    }

    public function render(): View
    {
        return view('livewire.support.ticket-chat')->layout('layouts.app');
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private function dayBounds(): array
    {
        $now = Carbon::now($this->timezone());

        return [
            $now->copy()->startOfDay()->timezone($this->applicationTimezone()),
            $now->copy()->endOfDay()->timezone($this->applicationTimezone()),
        ];
    }

    private function applicationTimezone(): string
    {
        return (string) config('app.timezone', 'UTC');
    }

    private function isSupportAdministrator(User $user): bool
    {
        return $user->role?->usesPermissionProfile(Role::PROFILE_ADMINISTRATOR) === true
            || $user->role?->role === 'Administrador';
    }

    private function timezone(): string
    {
        return (string) config('support.timezone', 'America/Mexico_City');
    }
}
