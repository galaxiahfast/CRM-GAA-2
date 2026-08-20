<?php

namespace App\Livewire\Support;

use App\Models\Role;
use App\Models\SupportChatMessage;
use App\Models\SupportChatMessageReaction;
use App\Models\User;
use App\Services\Notifications\SystemNotificationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class TicketChat extends Component
{
    use WithFileUploads;

    public string $message = '';

    public $image = null;

    public $attachment = null;

    private const STICKERS = [
        'fry-sospecha' => 'img/support/stickers/fry-sospecha.jpg',
        'avestruz-genial' => 'img/support/stickers/avestruz-genial.jpg',
        'ternura' => 'img/support/stickers/ternura.jpg',
    ];

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

        [$start] = $this->retentionBounds();
        $expiredMessages = SupportChatMessage::withTrashed()->where('created_at', '<', $start);
        $expiredImagePaths = (clone $expiredMessages)->whereNotNull('image_path')->pluck('image_path')->all();

        if ($expiredImagePaths !== []) {
            Storage::disk('public')->delete($expiredImagePaths);
        }
        $expiredAttachmentPaths = (clone $expiredMessages)->whereNotNull('attachment_path')->pluck('attachment_path')->all();

        if ($expiredAttachmentPaths !== []) {
            Storage::disk('public')->delete($expiredAttachmentPaths);
        }

        $expiredMessages->forceDelete();

        $now = Carbon::now($this->timezone());
        $this->todayLabel = $now->locale('es')->translatedFormat('d M Y');
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

    public function appendEmoji(string $emoji): void
    {
        $allowed = ['😀', '😂', '😊', '😍', '👍', '👏', '🙏', '🎉', '❤️', '✅', '👀', '🤝'];

        if (! in_array($emoji, $allowed, true) || mb_strlen($this->message.$emoji) > 1000) {
            return;
        }

        $this->message .= $emoji;
        $this->updatedMessage($this->message);
    }

    public function removeImage(): void
    {
        $this->reset('image');
        $this->resetValidation('image');
    }

    public function removeAttachment(): void
    {
        $this->reset('attachment');
        $this->resetValidation('attachment');
    }

    public function sendSticker(string $stickerKey): void
    {
        $user = auth()->user();
        abort_unless($user, 403);

        if (! array_key_exists($stickerKey, self::STICKERS)) {
            return;
        }

        $rateKey = 'support-chat:'.$user->getAuthIdentifier();
        if (RateLimiter::tooManyAttempts($rateKey, 12)) {
            $this->addError('message', 'Espera unos segundos antes de enviar más mensajes.');

            return;
        }

        RateLimiter::hit($rateKey, 60);
        SupportChatMessage::create([
            'user_id' => $user->getAuthIdentifier(),
            'message' => '',
            'sticker_key' => $stickerKey,
        ]);

        $this->refreshMessages();
        $this->dispatch('support-message-sent');
    }

    public function sendMessage(): void
    {
        $user = auth()->user();
        abort_unless($user, 403);

        $validated = $this->validate([
            'message' => ['nullable', 'string', 'max:1000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,csv,txt,zip', 'max:10240'],
        ], [
            'message.max' => 'El mensaje no puede superar los 1000 caracteres.',
            'image.image' => 'El archivo adjunto debe ser una imagen válida.',
            'image.mimes' => 'La imagen debe ser JPG, PNG o WebP.',
            'image.max' => 'La imagen no puede superar los 5 MB.',
            'attachment.mimes' => 'El archivo debe ser PDF, Word, Excel, CSV, TXT o ZIP.',
            'attachment.max' => 'El archivo no puede superar los 10 MB.',
        ]);

        if (trim((string) ($validated['message'] ?? '')) === '' && ! $this->image && ! $this->attachment) {
            $this->addError('message', 'Escribe un mensaje o adjunta una imagen o archivo antes de enviar.');

            return;
        }

        $rateKey = 'support-chat:'.$user->getAuthIdentifier();
        if (RateLimiter::tooManyAttempts($rateKey, 12)) {
            $this->addError('message', 'Espera unos segundos antes de enviar más mensajes.');

            return;
        }

        RateLimiter::hit($rateKey, 60);

        $imagePath = $this->image?->storePublicly('support-chat-images', 'public');
        $attachmentPath = $this->attachment?->storePublicly('support-chat-files', 'public');

        try {
            $message = SupportChatMessage::create([
                'user_id' => $user->getAuthIdentifier(),
                'message' => trim((string) ($validated['message'] ?? '')),
                'image_path' => $imagePath,
                'image_original_name' => $this->image?->getClientOriginalName(),
                'attachment_path' => $attachmentPath,
                'attachment_original_name' => $this->attachment?->getClientOriginalName(),
                'attachment_mime' => $this->attachment?->getMimeType(),
                'attachment_size' => $this->attachment?->getSize(),
            ]);
        } catch (Throwable $exception) {
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }
            if ($attachmentPath) {
                Storage::disk('public')->delete($attachmentPath);
            }

            throw $exception;
        }

        $this->notifyMentionedUsers($message, $user);
        $this->respondAsAutomatedUser($message, $user);

        $this->reset(['message', 'image', 'attachment']);
        $this->mentionSuggestions = [];
        $this->refreshMessages();
        $this->dispatch('support-message-sent');
    }

    public function refreshMessages(): void
    {
        $this->ensureCurrentGreeting();
        $this->messages = $this->recentMessages();
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

    public function toggleReaction(int $messageId, string $reaction): void
    {
        abort_unless(in_array($reaction, ['like', 'heart', 'dislike'], true), 422);

        [$start, $end] = $this->retentionBounds();
        $message = SupportChatMessage::query()
            ->whereBetween('created_at', [$start, $end])
            ->findOrFail($messageId);
        $userId = (int) auth()->id();
        abort_unless($userId > 0, 403);

        $existing = SupportChatMessageReaction::query()
            ->where('support_chat_message_id', $message->id)
            ->where('user_id', $userId)
            ->first();

        if ($existing?->reaction === $reaction) {
            $existing->delete();
        } elseif ($existing) {
            $existing->update(['reaction' => $reaction]);
        } else {
            SupportChatMessageReaction::create([
                'support_chat_message_id' => $message->id,
                'user_id' => $userId,
                'reaction' => $reaction,
            ]);
        }

        $this->refreshMessages();
    }

    public function downloadPdf(): StreamedResponse
    {
        abort_unless(auth()->check(), 403);

        $messages = $this->recentMessages(includePdfPhotos: true);
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
    private function recentMessages(bool $includePdfPhotos = false): array
    {
        [$start, $end] = $this->retentionBounds();
        $currentUserId = (int) auth()->id();

        return SupportChatMessage::query()
            ->withTrashed()
            ->with([
                'user:id,name,last_name,email,profile_photo_path',
                'reactions:id,support_chat_message_id,user_id,reaction',
            ])
            ->whereBetween('created_at', [$start, $end])
            ->oldest('created_at')
            ->oldest('id')
            ->get()
            ->map(function (SupportChatMessage $message) use ($currentUserId, $includePdfPhotos): array {
                $createdAt = $message->created_at->timezone($this->timezone());
                $isToday = $createdAt->isSameDay(Carbon::now($this->timezone()));

                return [
                    'id' => $message->id,
                    'user_id' => (int) $message->user_id,
                    'name' => trim(($message->user?->name ?? 'Usuario').' '.($message->user?->last_name ?? '')),
                    'email' => $message->user?->email ?? 'Correo no disponible',
                    'message' => $message->trashed() ? '' : $message->message,
                    'message_html' => $message->trashed() ? '' : $this->highlightMentions($message->message),
                    'image_url' => ! $message->trashed() && $message->image_path ? $this->supportImageUrl($message->image_path) : null,
                    'image_name' => $message->image_original_name,
                    'attachment_url' => ! $message->trashed() && $message->attachment_path ? $this->supportImageUrl($message->attachment_path) : null,
                    'attachment_name' => $message->attachment_original_name,
                    'attachment_size' => $message->attachment_size,
                    'sticker_url' => ! $message->trashed() && isset(self::STICKERS[$message->sticker_key])
                        ? url('/'.self::STICKERS[$message->sticker_key])
                        : null,
                    'automatic_key' => $message->automatic_key,
                    'photo_url' => $message->user ? $this->profilePhotoUrl($message->user) : null,
                    'pdf_photo_data' => $includePdfPhotos && $message->user ? $this->profilePhotoDataUri($message->user) : null,
                    'time' => $createdAt->format('H:i'),
                    'date_key' => $createdAt->toDateString(),
                    'date_label' => ($isToday ? 'Hoy · ' : '').$createdAt->locale('es')->translatedFormat('D d M'),
                    'is_mine' => (int) $message->user_id === $currentUserId,
                    'is_deleted' => $message->trashed(),
                    'can_delete' => ! $message->trashed()
                        && ((int) $message->user_id === $currentUserId || $this->canDeleteAllMessages),
                    'reactions' => [
                        'like' => $message->reactions->where('reaction', 'like')->count(),
                        'heart' => $message->reactions->where('reaction', 'heart')->count(),
                        'dislike' => $message->reactions->where('reaction', 'dislike')->count(),
                    ],
                    'my_reaction' => $message->reactions->firstWhere('user_id', $currentUserId)?->reaction,
                ];
            })
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
            ->whereKeyNot((int) auth()->id())
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

    private function highlightMentions(string $message): string
    {
        $escapedMessage = e($message);

        return preg_replace(
            '/(@todos\b|@[\pL\pN._-]+-\d+\b)/ui',
            '<span class="font-semibold text-blue-600">$1</span>',
            $escapedMessage
        ) ?? $escapedMessage;
    }

    private function supportImageUrl(string $path): string
    {
        if (config('filesystems.disks.public.driver') === 'local') {
            return url('/storage/'.ltrim($path, '/'));
        }

        return Storage::disk('public')->url($path);
    }

    private function userName(User $user): string
    {
        return trim($user->name.' '.($user->last_name ?? '')) ?: 'Usuario';
    }

    private function initials(string $name): string
    {
        return collect(preg_split('/\s+/', trim($name)))
            ->filter()
            ->take(1)
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

    private function respondAsAutomatedUser(SupportChatMessage $message, User $sender): void
    {
        if ((int) $sender->id === (int) $this->automatedUserId || ! $this->messageAddressesAutomatedUser($message->message)) {
            return;
        }

        $automaticKey = 'bot-reply:'.$message->id;

        $reply = $this->automatedReplyFor($message->message, $sender);
        $helpRecipient = $this->messageNeedsHumanAttention($message->message)
            ? $this->helpRecipient()
            : null;

        if ($helpRecipient) {
            $reply .= "\n\n@".$this->mentionHandle($helpRecipient).' ¿puedes revisar esta solicitud de ayuda?';
        }

        SupportChatMessage::query()->insertOrIgnore([
            'user_id' => $this->automatedUserId ?: $this->automatedUser()->id,
            'message' => $reply,
            'automatic_key' => $automaticKey,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $botReply = SupportChatMessage::query()->where('automatic_key', $automaticKey)->first();
        $botUser = User::query()->find($this->automatedUserId);

        if ($botReply && $botUser && $helpRecipient) {
            $this->notifyMentionedUsers($botReply, $botUser);
        }
    }

    private function messageNeedsHumanAttention(string $message): bool
    {
        $text = Str::lower(Str::ascii($message));

        return Str::contains($text, [
            'ayuda', 'soporte', 'problema', 'error', 'falla', 'no funciona',
            'no puedo', 'urgente', 'incidente', 'reportar',
        ]);
    }

    private function helpRecipient(): ?User
    {
        $recipient = User::query()
            ->where('is_support_help_recipient', true)
            ->oldest('id')
            ->first();

        if ($recipient) {
            return $recipient;
        }

        $email = trim((string) config('support.help_recipient_email'));

        $recipient = $email !== ''
            ? User::query()->where('email', $email)->first()
            : null;

        if ($recipient) {
            $recipient->forceFill(['is_support_help_recipient' => true])->saveQuietly();
        }

        return $recipient;
    }

    private function messageAddressesAutomatedUser(string $message): bool
    {
        $normalized = Str::lower(Str::ascii(trim($message)));

        if ($normalized === '') {
            return false;
        }

        $mentionsBotById = preg_match(
            '/@[\pL\pN._-]+-'.preg_quote((string) $this->automatedUserId, '/').'\b/ui',
            $message
        ) === 1;

        return $mentionsBotById
            || preg_match('/(?:^|\s)@sofia\b/u', $normalized) === 1
            || preg_match('/^sofia(?:\s|[,.:;!?])/u', $normalized) === 1;
    }

    private function automatedReplyFor(string $message, User $sender): string
    {
        $text = Str::lower(Str::ascii($message));
        $firstName = trim((string) $sender->name) ?: 'colaborador';

        if (Str::contains($text, ['contrasena', 'password', 'clave', 'no puedo entrar', 'iniciar sesion'])) {
            return 'Para cambiar tu contraseña, abre Mi cuenta, entra a Mi perfil y busca la sección Cambiar contraseña. Si olvidaste la actual, utiliza “¿Olvidaste tu contraseña?” en el inicio de sesión.';
        }

        if (Str::contains($text, ['cronometro', 'temporizador', 'actividad', 'control de horas', 'registrar tiempo'])) {
            return 'Entra a Actividades > Control de horas > Cronómetro. Selecciona el cliente y la actividad, y después pulsa Iniciar actividad. Solo puede existir un cronómetro activo a la vez.';
        }

        if (Str::contains($text, ['reloj checador', 'entrada', 'salida', 'marcar hora', 'asistencia'])) {
            return 'Puedes consultar tus registros en Actividades > Control de horas > Reloj checador. Ahí encontrarás entradas, salidas y el resumen del periodo seleccionado.';
        }

        if (Str::contains($text, ['perfil', 'foto', 'correo', 'nombre', 'apellido', 'cuenta'])) {
            return 'Abre Mi cuenta y selecciona Mi perfil. Desde Editar perfil puedes actualizar nombres, apellidos, correo, descripción, fotografía y opciones de seguridad.';
        }

        if (Str::contains($text, ['ticket', 'soporte', 'problema', 'error', 'falla', 'ayuda'])) {
            return 'Cuéntame brevemente qué apartado presenta el problema y qué estabas intentando hacer. También puedes adjuntar una imagen o un archivo para que el equipo de soporte tenga más contexto.';
        }

        if (Str::contains($text, ['archivo', 'adjuntar', 'imagen', 'documento', 'sticker', 'emoji'])) {
            return 'Debajo del campo de mensaje encontrarás opciones para adjuntar imágenes, archivos, emojis y stickers. Selecciona el contenido y después pulsa Enviar.';
        }

        if (Str::contains($text, ['gracias', 'muchas gracias', 'te agradezco'])) {
            return 'Con gusto, '.$firstName.'. Si necesitas otra cosa, mencióname nuevamente.';
        }

        if (Str::contains($text, ['adios', 'hasta luego', 'nos vemos'])) {
            return 'Hasta luego, '.$firstName.'. Estaré disponible cuando vuelvas a necesitar ayuda.';
        }

        if (Str::contains($text, ['hola', 'buenos dias', 'buenas tardes', 'buenas noches', 'que tal'])) {
            return 'Hola, '.$firstName.'. Puedo ayudarte con tu perfil, contraseña, control de horas, cronómetro, archivos y soporte. ¿Qué necesitas consultar?';
        }

        return 'No identifiqué con claridad la consulta. Puedo ayudarte con perfil, contraseña, control de horas, cronómetro, archivos o soporte. Escríbeme uno de esos temas para orientarte.';
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
    private function retentionBounds(): array
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
