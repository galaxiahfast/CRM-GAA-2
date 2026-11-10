<div
    data-clock-particle-network
    data-panel
    data-support-chat
    class="relative isolate flex min-h-[calc(var(--app-dvh)-123px)] w-full min-w-0 flex-col text-[15px]"
    x-data="{
        profilePhotoOpen: false,
        profilePhotoUrl: '',
        profilePhotoName: '',
        showProfilePhoto(url, name) {
            if (!url) return;
            this.profilePhotoUrl = url;
            this.profilePhotoName = name;
            this.profilePhotoOpen = true;
        }
    }"
    @keydown.escape.window="profilePhotoOpen = false"
>
    <x-panel-styles />

    <canvas wire:ignore data-clock-network-canvas class="pointer-events-none absolute inset-0 z-0 h-full w-full opacity-[0.18]" aria-hidden="true"></canvas>

    <div class="relative z-10 flex min-h-0 w-full min-w-0 flex-1 flex-col">
    <main class="relative z-10 mx-auto flex w-full min-w-0 flex-1 flex-col gap-[20px] p-[20px] sm:gap-[30px] sm:p-[30px] xl:gap-[50px] xl:p-[50px]">
        <section class="flex min-h-[75px] shrink-0 flex-wrap items-center justify-between gap-x-[20px] gap-y-[15px]">
            <div class="flex min-w-0 items-center gap-[15px]">
                <span class="panel-surface panel-border panel-text grid h-[50px] w-[50px] shrink-0 place-items-center rounded-xl border">
                    <svg class="h-[30px] w-[30px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M8 10h8m-8 4h5m8-2a9 9 0 01-9 9 9.8 9.8 0 01-4-.84L3 21l.84-5A9 9 0 1121 12z" />
                    </svg>
                </span>
                <div class="min-w-0">
                    <h1 class="panel-accent break-words text-xl font-normal leading-none tracking-tight">Chat general de soporte</h1>
                    <p class="chat-sub mt-[15px]">Espacio compartido para conversar con todos los colaboradores.</p>
                </div>
            </div>

            <span class="panel-muted inline-flex items-center gap-[10px] text-[15px] leading-[30px]">
                <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                Disponible para todos los usuarios
            </span>
        </section>

        <div data-support-chat-panel class="flex min-h-[680px] min-w-0 flex-1 flex-col gap-[30px]">
            <aside class="panel-surface panel-border flex max-h-[520px] min-h-0 flex-col overflow-hidden rounded-xl border shadow-[0_8px_24px_rgba(0,0,0,0.05)]">
                <header class="panel-header panel-border shrink-0 border-b p-[20px]">
                    <div class="flex items-center justify-between gap-[15px]">
                        <h2 class="panel-accent flex items-center gap-[10px] leading-none"><span class="h-2 w-2 shrink-0 rounded-full bg-emerald-500" aria-hidden="true"></span>En línea ahora</h2>
                        <span class="shrink-0 leading-none">{{ count($onlineUsers) }} en línea</span>
                    </div>
                    <p class="chat-sub mt-[15px] whitespace-nowrap">Quién está conectado al chat.</p>
                </header>
                <div data-support-profile-header class="panel-surface panel-border flex items-center gap-[15px] border-b p-[20px]">
                    @if (auth()->user()->profile_photo_path)
                        <button type="button" @click="showProfilePhoto(@js(url('/storage/'.ltrim(auth()->user()->profile_photo_path, '/'))), @js(auth()->user()->name))" class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-black font-normal text-white outline-none focus:outline-none focus:ring-0" aria-label="Ampliar foto de {{ auth()->user()->name }}">
                            <img src="{{ url('/storage/'.ltrim(auth()->user()->profile_photo_path, '/')) }}" alt="Foto de {{ auth()->user()->name }}" class="h-full w-full object-cover">
                        </button>
                    @else
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-black font-normal text-white">
                            {{ mb_strtoupper(mb_substr(auth()->user()->name ?? '?', 0, 1)) }}
                        </span>
                    @endif
                    <div class="min-w-0">
                        <a href="{{ route('profile.show') }}" class="block truncate font-normal text-black hover:text-zinc-600">{{ trim((auth()->user()->name ?? '').' '.(auth()->user()->last_name ?? '')) }}</a>
                        <p class="mt-[5px] truncate text-[15px] text-zinc-500">{{ auth()->user()->email }}</p>
                    </div>
                </div>

                <div class="support-chat-scrollbar min-h-0 flex-1 overflow-y-auto p-[20px]">
                        <div data-support-online-users class="support-chat-scrollbar max-h-[350px] space-y-[10px] overflow-y-auto pr-[5px]">
                            @forelse ($onlineUsers as $onlineUser)
                                <div wire:key="online-user-{{ $onlineUser['id'] }}" class="flex items-center gap-[10px] rounded-xl px-[10px] py-[10px] transition hover:bg-white/60">
                                    @if ($onlineUser['photo_url'])
                                        <button type="button" @click="showProfilePhoto(@js($onlineUser['photo_url']), @js($onlineUser['name']))" class="relative flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-[15px] font-normal text-black ring-1 ring-zinc-200 outline-none focus:outline-none focus:ring-0" aria-label="Ampliar foto de {{ $onlineUser['name'] }}">
                                            <img src="{{ $onlineUser['photo_url'] }}" alt="Foto de {{ $onlineUser['name'] }}" class="h-full w-full rounded-full object-cover">
                                            <span class="absolute bottom-0 right-0 h-2.5 w-2.5 rounded-full bg-emerald-500 ring-2 ring-gray-100" aria-label="En línea"></span>
                                        </button>
                                    @else
                                        <span class="relative flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-[15px] font-normal text-black ring-1 ring-zinc-200">
                                            {{ $onlineUser['initials'] }}
                                            <span class="absolute bottom-0 right-0 h-2.5 w-2.5 rounded-full bg-emerald-500 ring-2 ring-gray-100" aria-label="En línea"></span>
                                        </span>
                                    @endif
                                    <div class="min-w-0">
                                        <a href="{{ $onlineUser['is_current'] ? route('profile.show') : route('profiles.show', $onlineUser['id']) }}" class="block truncate text-[15px] font-normal text-black hover:text-zinc-600">
                                            {{ $onlineUser['name'] }}
                                            @if ($onlineUser['is_current'])
                                                <span class="font-normal text-gray-400">(Tú)</span>
                                            @endif
                                        </a>
                                        <p class="mt-[5px] truncate text-[15px] text-zinc-500">{{ $onlineUser['email'] }}</p>
                                    </div>
                                </div>
                            @empty
                                <p class="rounded-xl border border-zinc-200 bg-white/70 p-[15px] text-center text-[15px] text-zinc-500">No hay personas activas ahora.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </aside>

            <div
                class="panel-surface panel-border flex min-h-0 min-w-0 flex-1 flex-col overflow-hidden rounded-xl border shadow-[0_8px_24px_rgba(0,0,0,0.05)]"
                wire:poll.3s.visible="refreshMessages"
                x-data="{
                    stayAtBottom: true,
                    searchQuery: '',
                    emojiOpen: false,
                    stickerOpen: false,
                    currentDayLabel: '',
                    readingPositionKey: 'support-ticket-last-message-{{ auth()->id() }}',
                    scrollToBottom() { this.$refs.messages.scrollTop = this.$refs.messages.scrollHeight },
                    restoreReadingPosition() {
                        const savedId = localStorage.getItem(this.readingPositionKey);
                        const savedMessage = savedId
                            ? this.$refs.messages.querySelector(`[data-chat-message-id=&quot;${savedId}&quot;]`)
                            : null;

                        if (savedMessage) {
                            const box = this.$refs.messages;
                            box.scrollTop = Math.max(0, savedMessage.offsetTop - (box.clientHeight / 2) + (savedMessage.offsetHeight / 2));
                            this.stayAtBottom = false;
                        } else {
                            this.scrollToBottom();
                        }

                        this.updateReadingPosition();
                    },
                    updateReadingPosition() {
                        const box = this.$refs.messages;
                        const boxRect = box.getBoundingClientRect();
                        const visibleMessages = [...box.querySelectorAll('[data-chat-message-id]')]
                            .filter(message => {
                                const rect = message.getBoundingClientRect();
                                return rect.bottom > boxRect.top + 20 && rect.top < boxRect.bottom - 20;
                            });
                        const reference = visibleMessages[visibleMessages.length - 1];
                        const dayReference = visibleMessages[0];

                        if (reference) {
                            localStorage.setItem(this.readingPositionKey, reference.dataset.chatMessageId);
                        }
                        if (dayReference) {
                            this.currentDayLabel = dayReference.dataset.dayLabel || '';
                        }
                    },
                    normalizedSearch() { return this.searchQuery.trim().toLocaleLowerCase('es-MX') },
                    hasSearchResults() {
                        const query = this.normalizedSearch();
                        return !query || [...this.$refs.messages.querySelectorAll('[data-chat-message]')]
                            .some((message) => (message.dataset.search || '').includes(query));
                    },
                    trackPosition() {
                        const box = this.$refs.messages;
                        this.stayAtBottom = box.scrollHeight - box.scrollTop - box.clientHeight < 140;
                        this.updateReadingPosition();
                    }
                }"
                x-init="$nextTick(() => restoreReadingPosition())"
                @support-message-sent.window="$nextTick(() => { stayAtBottom = true; scrollToBottom(); updateReadingPosition() })"
                @support-messages-refreshed.window="$nextTick(() => updateReadingPosition())"
            >
                <header data-support-chat-header class="panel-header panel-border flex flex-wrap items-center justify-between gap-[20px] border-b p-[20px]">
                    <div>
                        <h2 class="panel-accent flex items-center gap-[10px] leading-none"><svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h8m-8 4h5m8-2a9 9 0 01-9 9 9.8 9.8 0 01-4-.84L3 21l.84-5A9 9 0 1121 12z" /></svg>Conversación general</h2>
                        <p class="mt-[5px] text-[15px] text-zinc-500">
                            <span x-text="currentDayLabel || 'Semana actual'">Semana actual</span>
                            <span> · {{ count($messages) }} {{ count($messages) === 1 ? 'mensaje' : 'mensajes' }}</span>
                        </p>
                    </div>
                    <div class="flex min-w-0 items-center gap-[20px]">
                        <div class="relative w-[min(360px,calc(100vw-80px))]">
                            <svg class="pointer-events-none absolute left-[20px] top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m21 21-4.35-4.35m2.35-5.65a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z" /></svg>
                            <input x-ref="searchInput" x-model.debounce.150ms="searchQuery" @keydown.escape.stop="searchQuery = ''" type="text" placeholder="Buscar por nombre, correo o contenido…" class="panel-field w-full rounded-xl border border-zinc-200 bg-white/75 py-[15px] pl-[50px] pr-[20px] text-[15px] text-zinc-700 outline-none transition">
                        </div>
                    </div>
                </header>

                <div data-support-daily-note class="flex items-center gap-[15px] border-b border-zinc-200 bg-white/70 p-[20px] text-[15px] text-zinc-500">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-zinc-200 bg-white/75 text-zinc-500">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8h.01M11 12h1v4h1m8-4a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </span>
                    <p class="text-justify leading-6">La conversación conserva únicamente los mensajes del día. Al comenzar un nuevo día, los mensajes anteriores se eliminan automáticamente.</p>
                </div>


                <div x-ref="messages" @scroll="trackPosition()" class="support-chat-scrollbar min-h-0 flex-1 space-y-[20px] overflow-y-auto bg-white/35 p-[20px]">
                    <div x-show="searchQuery && !hasSearchResults()" class="py-[40px] text-center" style="display: none;">
                        <svg class="mx-auto h-8 w-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m21 21-4.35-4.35m2.35-5.65a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z" /></svg>
                        <p class="mt-[10px] text-[15px] font-normal text-gray-500">No se encontraron mensajes.</p>
                    </div>
                    @php
                        $visibleDateKey = null;
                    @endphp
                    @forelse ($messages as $chatMessage)
                        @php
                            $nameParts = array_values(array_filter(preg_split('/\s+/', trim($chatMessage['name']))));
                            $initials = collect($nameParts)->take(1)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
                        @endphp
                        @if ($visibleDateKey !== $chatMessage['date_key'])
                            @php($visibleDateKey = $chatMessage['date_key'])
                            <div class="sticky top-0 z-10 !mt-0 flex items-center gap-[15px] bg-transparent" data-day-separator="{{ $chatMessage['date_key'] }}">
                                <span class="w-full text-center text-[15px] font-normal capitalize text-black">{{ $chatMessage['date_label'] }}</span>
                            </div>
                        @endif
                        <article
                            wire:key="support-message-{{ $chatMessage['id'] }}"
                            data-chat-message
                            data-chat-message-id="{{ $chatMessage['id'] }}"
                            data-day-label="{{ $chatMessage['date_label'] }}"
                            data-search="{{ mb_strtolower($chatMessage['name'].' '.$chatMessage['email'].' '.($chatMessage['is_deleted'] ? 'Mensaje eliminado' : $chatMessage['message'])) }}"
                            x-show="!normalizedSearch() || ($el.dataset.search || '').includes(normalizedSearch())"
                            class="flex items-end gap-[15px] {{ $chatMessage['is_mine'] ? 'flex-row-reverse justify-start' : 'justify-start' }} {{ $chatMessage['sticker_url'] && $chatMessage['is_mine'] ? 'pl-[20px]' : '' }}"
                        >
                            @if ($chatMessage['photo_url'])
                                <button type="button" @click="showProfilePhoto(@js($chatMessage['photo_url']), @js($chatMessage['name']))" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-[15px] font-normal outline-none focus:outline-none focus:ring-0 {{ $chatMessage['is_mine'] ? 'border border-black bg-black text-white' : 'border border-zinc-200 bg-zinc-100 text-black' }}" title="Ampliar foto de {{ $chatMessage['name'] }}" aria-label="Ampliar foto de {{ $chatMessage['name'] }}">
                                    <img src="{{ $chatMessage['photo_url'] }}" alt="Foto de {{ $chatMessage['name'] }}" class="h-full w-full rounded-full object-cover">
                                </button>
                            @else
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-[15px] font-normal {{ $chatMessage['is_mine'] ? 'border border-black bg-black text-white' : 'border border-zinc-200 bg-zinc-100 text-black' }}" title="{{ $chatMessage['name'] }}">
                                    {{ $initials ?: '?' }}
                                </span>
                            @endif

                            <div class="max-w-[min(78%,760px)] rounded-xl border shadow-[0_3px_10px_rgba(0,0,0,0.04)] {{ $chatMessage['sticker_url'] ? 'border-transparent bg-transparent p-0 shadow-none' : 'p-[20px] '.($chatMessage['is_mine'] ? 'border-zinc-300 bg-zinc-50' : 'border-zinc-200 bg-white/90') }}">
                                <div class="{{ $chatMessage['sticker_url'] ? 'hidden' : 'flex' }} min-w-0 items-start gap-[15px] border-b border-zinc-200 pb-[15px]">
                                    <div class="min-w-0 flex-1">
                                        <a href="{{ $chatMessage['is_mine'] ? route('profile.show') : route('profiles.show', $chatMessage['user_id']) }}" class="block truncate font-normal text-black hover:text-zinc-600">{{ $chatMessage['name'] }}</a>
                                        <p class="mt-[5px] truncate text-[15px] text-zinc-500">{{ $chatMessage['email'] }}</p>
                                    </div>
                                    <div class="flex shrink-0 items-center gap-[10px]">
                                        <span class="text-[15px] text-zinc-500">{{ $chatMessage['time'] }}</span>
                                        @if ($chatMessage['can_delete'])
                                            <button
                                                type="button"
                                                wire:click="deleteMessage({{ $chatMessage['id'] }})"
                                                wire:confirm="¿Deseas eliminar este mensaje?"
                                                wire:loading.attr="disabled"
                                                wire:target="deleteMessage({{ $chatMessage['id'] }})"
                                                class="no-print inline-flex h-6 w-6 items-center justify-center rounded-md bg-transparent text-gray-300 transition hover:bg-red-50 hover:text-red-600 disabled:cursor-wait disabled:opacity-50"
                                                aria-label="Eliminar mensaje"
                                                title="Eliminar mensaje"
                                            >
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 7l-.9 12.1A2 2 0 0116.1 21H7.9a2 2 0 01-2-1.9L5 7m3 0V4.8A1.8 1.8 0 019.8 3h4.4A1.8 1.8 0 0116 4.8V7M3 7h18" /></svg>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                                @if ($chatMessage['is_deleted'])
                                    <p class="mt-[15px] italic leading-6 text-zinc-400">Mensaje eliminado</p>
                                @else
                                    @if ($chatMessage['sticker_url'])
                                        <div class="group">
                                            <div class="mb-[10px] flex items-center gap-[10px] text-[15px] {{ $chatMessage['is_mine'] ? 'justify-end text-right' : 'justify-start text-left' }}">
                                                <a href="{{ $chatMessage['is_mine'] ? route('profile.show') : route('profiles.show', $chatMessage['user_id']) }}" class="max-w-[135px] truncate font-normal text-black hover:text-zinc-600" title="Ver perfil de {{ $chatMessage['name'] }}">{{ $chatMessage['name'] }}</a>
                                                <span class="shrink-0 text-zinc-500">{{ $chatMessage['time'] }}</span>
                                            </div>
                                            <div class="relative">
                                                <img src="{{ $chatMessage['sticker_url'] }}" alt="Sticker enviado por {{ $chatMessage['name'] }}" class="block h-[180px] w-[180px] rounded-2xl object-cover" title="{{ $chatMessage['name'] }} · {{ $chatMessage['time'] }}">
                                                @if ($chatMessage['can_delete'])
                                                    <button type="button" wire:click="deleteMessage({{ $chatMessage['id'] }})" wire:confirm="¿Deseas eliminar este sticker?" wire:loading.attr="disabled" wire:target="deleteMessage({{ $chatMessage['id'] }})" class="no-print absolute right-[10px] top-[10px] inline-flex h-8 w-8 items-center justify-center rounded-full border border-red-200 bg-white/95 text-red-600 opacity-0 shadow-sm outline-none transition group-hover:opacity-100 hover:bg-red-50 focus:opacity-100 focus:outline-none focus:ring-0 disabled:cursor-wait disabled:opacity-50" aria-label="Eliminar sticker" title="Eliminar sticker">
                                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 6l12 12M18 6 6 18" /></svg>
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                    @if ($chatMessage['image_url'])
                                        <a href="{{ $chatMessage['image_url'] }}" target="_blank" rel="noopener noreferrer" class="mt-[15px] block overflow-hidden rounded-xl border border-zinc-200 bg-zinc-50" title="Abrir imagen adjunta">
                                            <img src="{{ $chatMessage['image_url'] }}" alt="{{ $chatMessage['image_name'] ?: 'Imagen adjunta' }}" class="max-h-[360px] w-full object-contain">
                                        </a>
                                    @endif
                                    @if ($chatMessage['message_html'] !== '')
                                        <p class="mt-[15px] whitespace-pre-wrap break-words leading-6 text-zinc-700">{!! $chatMessage['message_html'] !!}</p>
                                    @endif
                                    @if ($chatMessage['attachment_url'])
                                        <a href="{{ $chatMessage['attachment_url'] }}" target="_blank" rel="noopener noreferrer" download class="mt-[15px] flex items-center gap-[15px] rounded-xl border border-zinc-200 bg-white p-[15px] text-black transition-colors hover:bg-zinc-100">
                                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-black text-white">
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Zm0 0v6h6M8 13h8M8 17h5"/></svg>
                                            </span>
                                            <span class="min-w-0 flex-1">
                                                <span class="block truncate text-[15px] font-normal">{{ $chatMessage['attachment_name'] }}</span>
                                                <span class="mt-[5px] block text-[15px] text-zinc-500">{{ number_format(((int) $chatMessage['attachment_size']) / 1024, 1) }} KB</span>
                                            </span>
                                            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3v12m0 0 4-4m-4 4-4-4M5 21h14"/></svg>
                                        </a>
                                    @endif
                                    <div class="no-print mt-[15px] flex items-center gap-[10px] {{ $chatMessage['is_mine'] ? 'justify-end' : 'justify-start' }}">
                                        <button type="button" wire:click="toggleReaction({{ $chatMessage['id'] }}, 'like')" wire:loading.attr="disabled" wire:target="toggleReaction({{ $chatMessage['id'] }}, 'like')" class="inline-flex items-center gap-[10px] rounded-full border px-[15px] py-[10px] outline-none transition-colors focus:outline-none focus:ring-0 disabled:opacity-50 {{ $chatMessage['my_reaction'] === 'like' ? 'border-black bg-black text-white' : 'border-zinc-200 bg-white text-black hover:bg-zinc-100' }}" aria-label="Me gusta" title="Me gusta">
                                            <svg class="h-4 w-4" fill="{{ $chatMessage['my_reaction'] === 'like' ? 'currentColor' : 'none' }}" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 10v11H3V10h4Zm0 10h10.2a2 2 0 0 0 1.94-1.52l1.5-6A2 2 0 0 0 18.7 10H14l.7-4.2A2.4 2.4 0 0 0 12.33 3L7 10Z" /></svg>
                                            <span>{{ $chatMessage['reactions']['like'] }}</span>
                                        </button>
                                        <button type="button" wire:click="toggleReaction({{ $chatMessage['id'] }}, 'heart')" wire:loading.attr="disabled" wire:target="toggleReaction({{ $chatMessage['id'] }}, 'heart')" class="inline-flex items-center gap-[10px] rounded-full border px-[15px] py-[10px] outline-none transition-colors focus:outline-none focus:ring-0 disabled:opacity-50 {{ $chatMessage['my_reaction'] === 'heart' ? 'border-red-700 bg-red-700 text-white' : 'border-zinc-200 bg-white text-red-700 hover:bg-red-50' }}" aria-label="Me encanta" title="Me encanta">
                                            <svg class="h-4 w-4" fill="{{ $chatMessage['my_reaction'] === 'heart' ? 'currentColor' : 'none' }}" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78L12 21.23l8.84-8.84a5.5 5.5 0 0 0 0-7.78Z" /></svg>
                                            <span>{{ $chatMessage['reactions']['heart'] }}</span>
                                        </button>
                                        <button type="button" wire:click="toggleReaction({{ $chatMessage['id'] }}, 'dislike')" wire:loading.attr="disabled" wire:target="toggleReaction({{ $chatMessage['id'] }}, 'dislike')" class="inline-flex items-center gap-[10px] rounded-full border px-[15px] py-[10px] outline-none transition-colors focus:outline-none focus:ring-0 disabled:opacity-50 {{ $chatMessage['my_reaction'] === 'dislike' ? 'border-zinc-700 bg-zinc-700 text-white' : 'border-zinc-200 bg-white text-black hover:bg-zinc-100' }}" aria-label="No me gusta" title="No me gusta">
                                            <svg class="h-4 w-4" fill="{{ $chatMessage['my_reaction'] === 'dislike' ? 'currentColor' : 'none' }}" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 14V3H3v11h4Zm0-10h10.2a2 2 0 0 1 1.94 1.52l1.5 6A2 2 0 0 1 18.7 14H14l.7 4.2A2.4 2.4 0 0 1 12.33 21L7 14Z" /></svg>
                                            <span>{{ $chatMessage['reactions']['dislike'] }}</span>
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </article>
                    @empty
                        <div class="flex min-h-[390px] flex-col items-center justify-center px-6 text-center">
                            <span class="flex h-16 w-16 items-center justify-center rounded-full border border-gray-200 text-gray-300">
                                <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 10h8m-8 4h5m8-2a9 9 0 01-9 9 9.8 9.8 0 01-4-.84L3 21l.84-5A9 9 0 1121 12z" /></svg>
                            </span>
                            <p class="mt-4 font-normal text-gray-600">Inicia la conversación del día</p>
                            <p class="mt-1 max-w-sm text-[15px] text-gray-400">Escribe el primer mensaje para solicitar ayuda o conversar con los demás colaboradores.</p>
                        </div>
                    @endforelse
                </div>

                <form wire:submit="sendMessage" class="no-print border-t border-zinc-200 bg-white/70 p-[20px]">
                    <div class="grid grid-cols-[minmax(0,1fr)_auto] items-start gap-x-[20px]">
                        <div class="relative min-w-0">
                            @if ($image)
                                <div class="mb-[15px] flex items-center gap-[15px] rounded-xl border border-zinc-200 bg-zinc-50 p-[15px]">
                                    <img src="{{ $image->temporaryUrl() }}" alt="Vista previa de la imagen" class="h-16 w-16 shrink-0 rounded-lg object-cover">
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-[15px] font-normal text-black">{{ $image->getClientOriginalName() }}</p>
                                        <p class="mt-[5px] text-[15px] text-zinc-500">Imagen lista para enviar</p>
                                    </div>
                                    <button type="button" wire:click="removeImage" class="inline-flex shrink-0 items-center justify-center rounded-lg p-[10px] text-red-700 transition-colors hover:bg-red-50" aria-label="Quitar imagen" title="Quitar imagen">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 6l12 12M18 6 6 18"/></svg>
                                    </button>
                                </div>
                            @endif
                            @if ($attachment)
                                <div class="mb-[15px] flex items-center gap-[15px] rounded-xl border border-zinc-200 bg-zinc-50 p-[15px]">
                                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-black text-white">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Zm0 0v6h6M8 13h8M8 17h5"/></svg>
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-[15px] font-normal text-black">{{ $attachment->getClientOriginalName() }}</p>
                                        <p class="mt-[5px] text-[15px] text-zinc-500">Archivo listo para enviar</p>
                                    </div>
                                    <button type="button" wire:click="removeAttachment" class="inline-flex shrink-0 items-center justify-center rounded-lg p-[10px] text-red-700 transition-colors hover:bg-red-50" aria-label="Quitar archivo" title="Quitar archivo">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 6l12 12M18 6 6 18"/></svg>
                                    </button>
                                </div>
                            @endif
                            <textarea
                                wire:model.live.debounce.180ms="message"
                                @keydown.enter.prevent.stop="
                                    const value = $event.target.value;
                                    if (value.trim()) {
                                        $wire.set('message', value).then(() => $wire.sendMessage());
                                    }
                                "
                                rows="1"
                                maxlength="1000"
                                placeholder="Escribe un mensaje…"
                                class="support-message-input block max-h-32 w-full resize-none overflow-y-auto rounded-xl border border-zinc-200 bg-white/75 px-[20px] py-[15px] text-[15px] leading-6 text-zinc-700 outline-none transition focus:border-zinc-300 focus:outline-none focus:ring-0"
                            ></textarea>

                            <div class="mt-[10px] flex items-center gap-[10px]">
                                <label for="support-chat-image" class="inline-flex cursor-pointer items-center gap-[10px] rounded-xl border border-zinc-200 bg-white px-[20px] py-[15px] text-[15px] font-normal text-black transition-colors hover:bg-zinc-100">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.82-2.83l8.48-8.48"/></svg>
                                    Adjuntar imagen
                                </label>
                                <input id="support-chat-image" type="file" wire:model="image" accept="image/jpeg,image/png,image/webp" class="sr-only">

                                <label for="support-chat-file" class="inline-flex cursor-pointer items-center gap-[10px] rounded-xl border border-zinc-200 bg-white px-[20px] py-[15px] text-[15px] font-normal text-black transition-colors hover:bg-zinc-100">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Zm0 0v6h6"/></svg>
                                    Adjuntar archivo
                                </label>
                                <input id="support-chat-file" type="file" wire:model="attachment" accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.txt,.zip" class="sr-only">

                                <div class="relative" @click.outside="emojiOpen = false">
                                    <button type="button" @click="emojiOpen = !emojiOpen" class="inline-flex items-center gap-[10px] rounded-xl border border-zinc-200 bg-white px-[20px] py-[15px] text-[15px] font-normal text-black transition-colors hover:bg-zinc-100" :aria-expanded="emojiOpen">
                                        <span class="text-[20px] leading-none">😊</span>
                                        Emojis
                                    </button>
                                    <div x-show="emojiOpen" x-transition class="absolute bottom-[calc(100%+10px)] left-0 z-40 grid w-[220px] grid-cols-4 gap-[5px] rounded-xl border border-zinc-200 bg-white p-[15px] shadow-[0_18px_45px_rgba(0,0,0,0.12)]" style="display: none;">
                                        @foreach (['😀', '😂', '😊', '😍', '👍', '👏', '🙏', '🎉', '❤️', '✅', '👀', '🤝'] as $emoji)
                                            <button type="button" wire:click="appendEmoji('{{ $emoji }}')" @click="emojiOpen = false" class="flex h-10 w-10 items-center justify-center rounded-lg text-[20px] transition-colors hover:bg-zinc-100" aria-label="Agregar emoji {{ $emoji }}">{{ $emoji }}</button>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="relative" @click.outside="stickerOpen = false">
                                    <button type="button" @click="stickerOpen = !stickerOpen" class="inline-flex items-center gap-[10px] rounded-xl border border-zinc-200 bg-white px-[20px] py-[15px] text-[15px] font-normal text-black transition-colors hover:bg-zinc-100" :aria-expanded="stickerOpen">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 12a8 8 0 1 1-8-8h4a4 4 0 0 1 4 4v4Zm0 0a4 4 0 0 1-4 4h-4v4"/></svg>
                                        Stickers
                                    </button>
                                    <div x-show="stickerOpen" x-transition class="absolute bottom-[calc(100%+10px)] right-0 z-40 grid w-[330px] grid-cols-3 gap-[10px] rounded-xl border border-zinc-200 bg-white p-[15px] shadow-[0_18px_45px_rgba(0,0,0,0.12)]" style="display: none;">
                                        @foreach ([
                                            'fry-sospecha' => 'img/support/stickers/fry-sospecha.jpg',
                                            'avestruz-genial' => 'img/support/stickers/avestruz-genial.jpg',
                                            'ternura' => 'img/support/stickers/ternura.jpg',
                                        ] as $stickerKey => $stickerPath)
                                            <button type="button" wire:click="sendSticker('{{ $stickerKey }}')" @click="stickerOpen = false" class="overflow-hidden rounded-xl border border-zinc-200 bg-zinc-50 p-[5px] outline-none transition-colors hover:border-zinc-300 hover:bg-zinc-100 focus:border-zinc-300 focus:outline-none focus:ring-0" aria-label="Enviar sticker">
                                                <img src="{{ asset($stickerPath) }}" alt="Sticker" class="aspect-square w-full rounded-lg object-cover">
                                            </button>
                                        @endforeach
                                    </div>
                                </div>

                                <span wire:loading wire:target="image,attachment" class="text-[15px] text-zinc-500">Preparando archivo...</span>
                            </div>

                            @if ($mentionSuggestions !== [])
                                <div data-mention-suggestions class="absolute bottom-[calc(100%+10px)] left-0 z-30 w-full max-w-[440px] overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-[0_18px_45px_rgba(0,0,0,0.12)]">
                                    <div class="border-b border-zinc-200 p-[20px]">
                                        <p class="text-[15px] font-normal text-black">Mencionar en el mensaje</p>
                                        <p class="mt-[5px] text-[15px] text-zinc-500">Selecciona una persona o notifica a todos.</p>
                                    </div>
                                    <div class="support-chat-scrollbar max-h-[300px] overflow-y-auto p-2">
                                        @foreach ($mentionSuggestions as $suggestion)
                                            <button
                                                wire:key="mention-suggestion-{{ $suggestion['type'] }}-{{ $suggestion['id'] }}"
                                                wire:click="selectMention({{ $suggestion['id'] }})"
                                                type="button"
                                                class="group flex w-full items-center gap-[15px] rounded-xl p-[15px] text-left transition hover:bg-zinc-100 focus:bg-zinc-100 focus:outline-none"
                                            >
                                                @if ($suggestion['type'] === 'everyone')
                                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-black text-white">
                                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m7-10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm13 10v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" /></svg>
                                                    </span>
                                                @else
                                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full border border-zinc-200 bg-zinc-100 text-[15px] font-normal text-black transition group-hover:bg-white">
                                                        @if ($suggestion['photo_url'])
                                                            <img src="{{ $suggestion['photo_url'] }}" alt="Foto de {{ $suggestion['name'] }}" class="h-full w-full object-cover">
                                                        @else
                                                            {{ $suggestion['initials'] }}
                                                        @endif
                                                    </span>
                                                @endif
                                                <span class="min-w-0 flex-1">
                                                    <span class="block truncate text-[15px] font-normal text-gray-700">{{ $suggestion['name'] }}</span>
                                                    <span class="mt-[5px] block truncate text-[15px] text-zinc-500">{{ $suggestion['email'] }}</span>
                                                </span>
                                                <svg class="h-4 w-4 shrink-0 text-zinc-300 transition group-hover:translate-x-0.5 group-hover:text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @error('message') <p class="mt-[10px] text-[12px] text-red-600">{{ $message }}</p> @enderror
                            @error('image') <p class="mt-[10px] text-[15px] text-red-600">{{ $message }}</p> @enderror
                            @error('attachment') <p class="mt-[10px] text-[15px] text-red-600">{{ $message }}</p> @enderror
                            <p class="mt-[10px] text-[12px] text-gray-400">Escribe @ para mencionar · Enter para enviar</p>
                        </div>
                        <button type="submit" wire:loading.attr="disabled" wire:target="sendMessage"
                            class="chat-primary inline-flex shrink-0 items-center justify-center gap-[10px] self-start rounded-xl px-[20px] py-[15px] font-normal disabled:cursor-wait disabled:opacity-60">
                            <svg wire:loading.remove wire:target="sendMessage" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12l14-7-4 14-3-6-7-1zm7 1l7-8" /></svg>
                            <svg wire:loading wire:target="sendMessage" class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                            <span>Enviar</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    </div>

    <template x-teleport="body">
        <div
            x-show="profilePhotoOpen"
            x-cloak
            x-transition.opacity
            class="no-print fixed inset-0 z-[9999] flex items-center justify-center bg-black/60 p-[20px] backdrop-blur-sm"
            role="dialog"
            aria-modal="true"
            :aria-label="'Foto de perfil de ' + profilePhotoName"
            @click.self="profilePhotoOpen = false"
        >
            <button type="button" @click="profilePhotoOpen = false" class="absolute right-[20px] top-[20px] inline-flex h-11 w-11 items-center justify-center rounded-full bg-white text-black shadow-lg outline-none transition-colors hover:bg-zinc-100 focus:outline-none focus:ring-0" aria-label="Cerrar foto ampliada" title="Cerrar">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 6l12 12M18 6 6 18" /></svg>
            </button>
            <div class="flex max-h-[90vh] max-w-[90vw] flex-col items-center gap-[20px]" @click.stop>
                <img :src="profilePhotoUrl" :alt="'Foto ampliada de ' + profilePhotoName" class="max-h-[78vh] max-w-[82vw] rounded-full border-[6px] border-white object-contain shadow-2xl">
                <p class="rounded-full bg-white px-[20px] py-[10px] text-[15px] font-normal text-black" x-text="profilePhotoName"></p>
            </div>
        </div>
    </template>

    <style>
        [data-support-chat-panel] { container: support-chat / inline-size; }
        @container support-chat (min-width: 900px) {
            [data-support-chat-panel] {
                height: calc(var(--app-dvh) - 330px);
                min-height: 620px;
                flex-direction: row;
                gap: 50px;
            }
            [data-support-chat-panel] > aside {
                width: 360px;
                max-height: none;
                flex-shrink: 0;
            }
        }
        [data-support-chat] {
            --chat-green: #15803d;
        }
        html.module-dark-theme [data-support-chat] { --chat-green: #4ade80; }
        [data-support-chat] * { font-weight: 400; }
        [data-support-chat] .chat-primary {
            border: 1px solid var(--chat-green);
            background-color: color-mix(in srgb, var(--chat-green) 12%, var(--panel-surface));
            color: var(--chat-green);
            transition: background-color 150ms ease;
        }
        [data-support-chat] .chat-primary:hover { background-color: color-mix(in srgb, var(--chat-green) 20%, var(--panel-surface)); }
        [data-support-chat] .clock-link { color: var(--chat-green); transition: opacity 150ms ease; }
        [data-support-chat] .clock-link:hover { opacity: .75; }
        [data-support-chat] [class~="text-black"],
        [data-support-chat] [class~="text-zinc-700"],
        [data-support-chat] [class~="text-gray-700"] { color: var(--panel-text) !important; }
        [data-support-chat] [class~="text-zinc-500"],
        [data-support-chat] [class~="text-zinc-600"],
        [data-support-chat] [class~="text-zinc-400"],
        [data-support-chat] [class~="text-gray-500"],
        [data-support-chat] [class~="text-gray-400"],
        [data-support-chat] [class~="text-gray-300"] { color: var(--panel-muted) !important; }
        [data-support-chat] [class~="bg-white"],
        [data-support-chat] [class~="bg-white/95"],
        [data-support-chat] [class~="bg-white/90"],
        [data-support-chat] [class~="bg-white/75"],
        [data-support-chat] [class~="bg-white/70"],
        [data-support-chat] [class~="bg-white/65"],
        [data-support-chat] [class~="bg-white/60"],
        [data-support-chat] [class~="bg-white/55"],
        [data-support-chat] [class~="bg-white/35"] { background-color: var(--panel-surface) !important; }
        [data-support-chat] [class~="bg-zinc-50"],
        [data-support-chat] [class~="bg-zinc-100"] { background-color: var(--panel-subtle) !important; }
        [data-support-chat] [class~="bg-black"] { background-color: var(--panel-button) !important; }
        [data-support-chat] [class~="text-white"] { color: var(--panel-button-text) !important; }
        [data-support-chat] [class~="border-zinc-200"],
        [data-support-chat] [class~="border-zinc-300"] { border-color: var(--panel-border) !important; }
        [data-support-chat] [class~="ring-zinc-200"] { --tw-ring-color: var(--panel-border) !important; }
        [data-support-chat] [class~="hover:bg-zinc-100"]:hover,
        [data-support-chat] [class~="focus:bg-zinc-100"]:focus { background-color: var(--panel-hover) !important; }
        [data-support-chat] :is(input, textarea, select):focus,
        [data-support-chat] :is(input, textarea, select):focus-visible {
            border-color: var(--panel-border) !important;
            box-shadow: none !important;
            outline: none !important;
        }
        [data-support-chat] :is(input, textarea, select) { color-scheme: inherit; }
        [data-support-chat] p { line-height: 30px; }
        [data-support-chat] p.chat-sub { line-height: 1; }
        [data-support-chat] .support-chat-scrollbar {
            scrollbar-width: thin;
            scrollbar-color: var(--panel-accent) var(--panel-surface);
        }
        [data-support-chat] .support-chat-scrollbar::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        [data-support-chat] .support-chat-scrollbar::-webkit-scrollbar-track {
            background: var(--panel-surface);
        }
        [data-support-chat] .support-chat-scrollbar::-webkit-scrollbar-thumb {
            background: var(--panel-accent);
            border-radius: 9999px;
        }
        .support-message-input {
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        [data-support-chat] .support-message-input:focus,
        [data-support-chat] .support-message-input:focus-visible {
            border-color: var(--panel-border) !important;
            box-shadow: none !important;
            outline: none !important;
            --tw-ring-color: transparent !important;
        }
        .support-message-input::-webkit-scrollbar {
            display: none;
            width: 0;
            height: 0;
        }
        @media print {
            #main-nav, #logo-sidebar, .no-print { display: none !important; }
            #main-content { margin-left: 0 !important; padding-top: 0 !important; }
            [data-support-chat] { overflow: visible !important; }
            [data-support-chat] > div { min-width: 0 !important; zoom: 1 !important; }
        }

        @media (max-width: 1279px) {
            [data-support-chat-panel] { height: auto; }
            [data-support-chat] main { min-height: 0; }
        }
        @media (max-width: 639px) {
            [data-support-chat-panel] { grid-template-rows: minmax(220px, 32dvh) minmax(460px, 1fr); }
            [data-support-chat] [data-support-online-users] { max-height: 100px; }
        }
    </style>
</div>
