<div class="min-h-[calc(100dvh-90px)] w-full bg-gray-100 text-[15px] text-gray-700">
    <div class="min-h-[calc(100dvh-90px)] min-w-[1300px]">
    <header class="flex items-center justify-between gap-20 whitespace-nowrap border-b border-gray-200 px-[40px] py-[25px]">
        <div class="flex items-center gap-3 text-gray-500">
            <span class="font-medium">Soporte</span>
            <span class="text-gray-300">&gt;</span>
            <span class="font-medium">Centro de ayuda</span>
            <span class="text-gray-300">&gt;</span>
            <span class="font-semibold text-[#1A3A6B]">Ticket</span>
        </div>

        <div class="no-print flex items-center gap-[30px]">
            <button type="button" wire:click="downloadPdf" wire:loading.attr="disabled" wire:target="downloadPdf" class="inline-flex items-center gap-[15px] border-0 bg-transparent p-0 font-medium text-gray-500 transition-colors hover:text-[#1A3A6B] disabled:cursor-wait disabled:opacity-60">
                <svg wire:loading.remove wire:target="downloadPdf" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                <svg wire:loading wire:target="downloadPdf" class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                <span>Descargar PDF</span>
            </button>
            <button type="button" onclick="window.print()" class="inline-flex items-center gap-[15px] border-0 bg-transparent p-0 font-medium text-gray-500 transition-colors hover:text-[#1A3A6B]">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10zM9 7V3h6v4" />
                </svg>
                <span>Imprimir</span>
            </button>
        </div>
    </header>

    <main class="mx-auto w-full max-w-[1500px] space-y-[25px] px-[40px] py-[25px]">
        <section class="flex items-end justify-between gap-[15px]">
            <div class="flex items-start gap-[15px]">
                <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl border border-[#cbd9ed] text-[#1A3A6B]">
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 10h8m-8 4h5m8-2a9 9 0 01-9 9 9.8 9.8 0 01-4-.84L3 21l.84-5A9 9 0 1121 12z" />
                    </svg>
                </span>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-gray-900">Chat general de soporte</h1>
                    <p class="mt-1 text-gray-500">Espacio compartido para conversar con todos los colaboradores.</p>
                </div>
            </div>

            <div class="flex items-center gap-2 rounded-xl border border-emerald-200 px-4 py-2.5 text-[13px] text-emerald-700">
                <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                Disponible para todos los usuarios
            </div>
        </section>

        <section data-support-chat-panel class="grid h-[640px] min-h-0 grid-cols-[290px_minmax(0,1fr)] overflow-hidden rounded-xl border border-gray-200 bg-gray-100 shadow-[0_8px_24px_rgba(15,35,66,0.06)]">
            <aside class="border-r border-gray-200">
                <div data-support-profile-header class="flex h-[74px] items-center gap-[15px] border-b border-gray-200 px-[25px] py-[15px]">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-[#1A3A6B] font-semibold text-white">
                        @if (auth()->user()->profile_photo_path)
                            <img src="{{ url('/storage/'.ltrim(auth()->user()->profile_photo_path, '/')) }}" alt="Foto de {{ auth()->user()->name }}" class="h-full w-full object-cover">
                        @else
                            {{ mb_strtoupper(mb_substr(auth()->user()->name ?? '?', 0, 1)) }}
                        @endif
                    </span>
                    <div class="min-w-0">
                        <p class="truncate font-semibold text-gray-800">{{ trim((auth()->user()->name ?? '').' '.(auth()->user()->last_name ?? '')) }}</p>
                        <p class="truncate text-[12px] text-gray-500">{{ auth()->user()->email }}</p>
                    </div>
                </div>

                <div class="space-y-[25px] p-[25px]">
                    <div>
                        <p class="text-[12px] font-semibold uppercase tracking-[0.08em] text-gray-400">Chat del día</p>
                            <p class="mt-[10px] text-[12px] font-medium capitalize text-gray-700">{{ $todayLabel }}</p>
                    </div>

                    <div>
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-[12px] font-semibold uppercase tracking-[0.08em] text-gray-400">Personas en línea</p>
                            <span class="text-[11px] font-medium text-gray-400">{{ count($onlineUsers) }} en línea</span>
                        </div>

                        <div data-support-online-users class="support-chat-scrollbar mt-[15px] max-h-[350px] space-y-[10px] overflow-y-auto pr-[5px]">
                            @forelse ($onlineUsers as $onlineUser)
                                <div wire:key="online-user-{{ $onlineUser['id'] }}" class="flex items-center gap-[10px] rounded-xl px-[10px] py-[10px] transition hover:bg-white/60">
                                    <span class="relative flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white text-[11px] font-semibold text-[#1A3A6B] shadow-sm ring-1 ring-gray-200">
                                        @if ($onlineUser['photo_url'])
                                            <img src="{{ $onlineUser['photo_url'] }}" alt="Foto de {{ $onlineUser['name'] }}" class="h-full w-full rounded-full object-cover">
                                        @else
                                            {{ $onlineUser['initials'] }}
                                        @endif
                                        <span class="absolute bottom-0 right-0 h-2.5 w-2.5 rounded-full bg-emerald-500 ring-2 ring-gray-100" aria-label="En línea"></span>
                                    </span>
                                    <div class="min-w-0">
                                        <p class="truncate text-[13px] font-semibold text-gray-700">
                                            {{ $onlineUser['name'] }}
                                            @if ($onlineUser['is_current'])
                                                <span class="font-normal text-gray-400">(Tú)</span>
                                            @endif
                                        </p>
                                        <p class="truncate text-[11px] text-gray-400">{{ $onlineUser['email'] }}</p>
                                    </div>
                                </div>
                            @empty
                                <p class="rounded-xl bg-white/50 px-3 py-4 text-center text-[12px] text-gray-400">No hay personas activas ahora.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </aside>

            <div
                class="flex min-h-0 flex-col"
                wire:poll.3s.visible="refreshMessages"
                x-data="{
                    stayAtBottom: true,
                    searchOpen: false,
                    searchQuery: '',
                    scrollToBottom() { this.$refs.messages.scrollTop = this.$refs.messages.scrollHeight },
                    normalizedSearch() { return this.searchQuery.trim().toLocaleLowerCase('es-MX') },
                    hasSearchResults() {
                        const query = this.normalizedSearch();
                        return !query || [...this.$refs.messages.querySelectorAll('[data-chat-message]')]
                            .some((message) => (message.dataset.search || '').includes(query));
                    },
                    trackPosition() {
                        const box = this.$refs.messages;
                        this.stayAtBottom = box.scrollHeight - box.scrollTop - box.clientHeight < 140;
                    }
                }"
                x-init="$nextTick(() => scrollToBottom())"
                @support-message-sent.window="$nextTick(() => { stayAtBottom = true; scrollToBottom() })"
                @support-messages-refreshed.window="$nextTick(() => { if (stayAtBottom) scrollToBottom() })"
            >
                <header data-support-chat-header class="flex h-[74px] items-center justify-between gap-[25px] border-b border-gray-200 px-[25px] py-[15px]">
                    <div>
                        <h2 class="font-semibold text-gray-800">Conversación general</h2>
                        <p class="mt-0.5 text-[12px] text-gray-500">{{ count($messages) }} {{ count($messages) === 1 ? 'mensaje hoy' : 'mensajes hoy' }}</p>
                    </div>
                    <div class="flex items-center gap-[25px]">
                        <button
                            type="button"
                            @click="searchOpen = !searchOpen; if (searchOpen) $nextTick(() => $refs.searchInput.focus()); if (!searchOpen) searchQuery = ''"
                            class="inline-flex items-center gap-[10px] bg-transparent p-0 text-[12px] font-medium transition-colors"
                            :class="searchOpen ? 'text-[#1A3A6B]' : 'text-gray-500 hover:text-[#1A3A6B]'"
                            aria-label="Buscar mensajes"
                            :aria-expanded="searchOpen"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m21 21-4.35-4.35m2.35-5.65a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z" /></svg>
                            Buscar mensajes
                        </button>
                        <span class="inline-flex items-center gap-[10px] whitespace-nowrap text-[12px] font-medium text-gray-500" title="Los mensajes se actualizan automáticamente">
                            <span class="relative flex h-3 w-3 items-center justify-center">
                                <span class="absolute h-3 w-3 animate-ping rounded-full bg-[#1A3A6B]/20"></span>
                                <span class="relative h-1.5 w-1.5 rounded-full bg-[#1A3A6B]"></span>
                            </span>
                            Actualización automática
                        </span>
                    </div>
                </header>

                <div data-support-daily-note class="flex items-center gap-[10px] border-b border-gray-200 bg-[#1A3A6B]/[0.035] px-[25px] py-[10px] text-[12px] leading-5 text-gray-500">
                    <svg class="h-4 w-4 shrink-0 text-[#1A3A6B]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8h.01M11 12h1v4h1m8-4a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p>La conversación se reinicia cada día y los mensajes anteriores se eliminan automáticamente.</p>
                </div>

                <div x-show="searchOpen" x-transition class="border-b border-gray-200 px-[25px] py-[10px]" style="display: none;">
                    <div class="relative">
                        <svg class="pointer-events-none absolute left-[15px] top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m21 21-4.35-4.35m2.35-5.65a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z" /></svg>
                        <input x-ref="searchInput" x-model.debounce.150ms="searchQuery" type="text" placeholder="Buscar por nombre, correo o contenido…" class="h-[40px] w-full rounded-xl border border-gray-300 bg-transparent pl-[45px] pr-[40px] text-[13px] text-gray-700 outline-none transition focus:border-[#1A3A6B] focus:ring-4 focus:ring-[#1A3A6B]/10">
                        <button x-show="searchQuery" @click="searchQuery = ''; $refs.searchInput.focus()" type="button" class="absolute right-[10px] top-1/2 flex h-[25px] w-[25px] -translate-y-1/2 items-center justify-center bg-transparent text-gray-400 hover:text-[#1A3A6B]" aria-label="Limpiar búsqueda">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                </div>

                <div x-ref="messages" @scroll="trackPosition()" class="support-chat-scrollbar min-h-0 flex-1 space-y-[15px] overflow-y-auto px-[25px] py-[25px]">
                    <div x-show="searchQuery && !hasSearchResults()" class="py-[40px] text-center" style="display: none;">
                        <svg class="mx-auto h-8 w-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m21 21-4.35-4.35m2.35-5.65a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z" /></svg>
                        <p class="mt-[10px] text-[13px] font-medium text-gray-500">No se encontraron mensajes.</p>
                    </div>
                    @forelse ($messages as $chatMessage)
                        @php
                            $nameParts = array_values(array_filter(preg_split('/\s+/', trim($chatMessage['name']))));
                            $initials = collect($nameParts)->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
                        @endphp
                        <article
                            wire:key="support-message-{{ $chatMessage['id'] }}"
                            data-chat-message
                            data-search="{{ mb_strtolower($chatMessage['name'].' '.$chatMessage['email'].' '.($chatMessage['is_deleted'] ? 'Mensaje eliminado' : $chatMessage['message'])) }}"
                            x-show="!normalizedSearch() || ($el.dataset.search || '').includes(normalizedSearch())"
                            class="flex items-end justify-start gap-[15px] {{ $chatMessage['is_mine'] ? 'flex-row-reverse' : '' }}"
                        >
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-[12px] font-semibold shadow-sm {{ $chatMessage['is_mine'] ? 'border border-[#b9c9e2] bg-[#1A3A6B] text-white' : 'border border-gray-200 bg-white text-[#1A3A6B]' }}" title="{{ $chatMessage['name'] }}">
                                @if ($chatMessage['photo_url'])
                                    <img src="{{ $chatMessage['photo_url'] }}" alt="Foto de {{ $chatMessage['name'] }}" class="h-full w-full rounded-full object-cover">
                                @else
                                    {{ $initials ?: '?' }}
                                @endif
                            </span>

                            <div class="max-w-[min(78%,760px)] rounded-xl border px-[15px] py-[10px] shadow-[0_3px_10px_rgba(15,35,66,0.04)] {{ $chatMessage['is_mine'] ? 'border-[#b9c9e2] bg-[#eef4fc]' : 'border-gray-200 bg-white' }}">
                                <div class="flex min-w-0 items-start gap-[15px] border-b border-current/10 pb-[10px]">
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate font-semibold text-gray-800">{{ $chatMessage['name'] }}</p>
                                        <p class="truncate text-[11px] text-gray-500">{{ $chatMessage['email'] }}</p>
                                    </div>
                                    <div class="flex shrink-0 items-center gap-[10px]">
                                        <span class="pt-0.5 text-[11px] text-gray-400">{{ $chatMessage['time'] }}</span>
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
                                    <p class="mt-[10px] italic leading-6 text-gray-400">Mensaje eliminado</p>
                                @else
                                    <p class="mt-[10px] whitespace-pre-wrap break-words leading-6 text-gray-700">{{ $chatMessage['message'] }}</p>
                                @endif
                            </div>
                        </article>
                    @empty
                        <div class="flex min-h-[390px] flex-col items-center justify-center px-6 text-center">
                            <span class="flex h-16 w-16 items-center justify-center rounded-full border border-gray-200 text-gray-300">
                                <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 10h8m-8 4h5m8-2a9 9 0 01-9 9 9.8 9.8 0 01-4-.84L3 21l.84-5A9 9 0 1121 12z" /></svg>
                            </span>
                            <p class="mt-4 font-semibold text-gray-600">Inicia la conversación del día</p>
                            <p class="mt-1 max-w-sm text-[13px] text-gray-400">Escribe el primer mensaje para solicitar ayuda o conversar con los demás colaboradores.</p>
                        </div>
                    @endforelse
                </div>

                <form wire:submit="sendMessage" class="no-print border-t border-gray-200 px-[25px] py-[15px]">
                    <div class="grid grid-cols-[minmax(0,1fr)_auto] items-start gap-x-[15px]">
                        <div class="relative min-w-0">
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
                                class="support-message-input block h-[54px] max-h-32 w-full resize-none overflow-y-auto rounded-xl border border-gray-300 bg-transparent px-4 py-[15px] text-[15px] leading-6 text-gray-700 outline-none transition focus:border-[#1A3A6B] focus:ring-4 focus:ring-[#1A3A6B]/10"
                            ></textarea>

                            @if ($mentionSuggestions !== [])
                                <div data-mention-suggestions class="absolute bottom-[calc(100%+10px)] left-0 z-30 w-full max-w-[440px] overflow-hidden rounded-2xl border border-[#1A3A6B]/15 bg-white shadow-[0_18px_45px_rgba(15,35,66,0.16)]">
                                    <div class="border-b border-gray-100 px-[15px] py-[12px]">
                                        <p class="text-[12px] font-semibold text-gray-700">Mencionar en el mensaje</p>
                                        <p class="mt-[5px] text-[11px] text-gray-400">Selecciona una persona o notifica a todos.</p>
                                    </div>
                                    <div class="support-chat-scrollbar max-h-[300px] overflow-y-auto p-2">
                                        @foreach ($mentionSuggestions as $suggestion)
                                            <button
                                                wire:key="mention-suggestion-{{ $suggestion['type'] }}-{{ $suggestion['id'] }}"
                                                wire:click="selectMention({{ $suggestion['id'] }})"
                                                type="button"
                                                class="group flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left transition hover:bg-[#1A3A6B]/[0.055] focus:bg-[#1A3A6B]/[0.055] focus:outline-none"
                                            >
                                                @if ($suggestion['type'] === 'everyone')
                                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#1A3A6B] text-white">
                                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m7-10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm13 10v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" /></svg>
                                                    </span>
                                                @else
                                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full border border-[#1A3A6B]/15 bg-[#1A3A6B]/[0.04] text-[11px] font-semibold text-[#1A3A6B] transition group-hover:bg-white">
                                                        @if ($suggestion['photo_url'])
                                                            <img src="{{ $suggestion['photo_url'] }}" alt="Foto de {{ $suggestion['name'] }}" class="h-full w-full object-cover">
                                                        @else
                                                            {{ $suggestion['initials'] }}
                                                        @endif
                                                    </span>
                                                @endif
                                                <span class="min-w-0 flex-1">
                                                    <span class="block truncate text-[13px] font-semibold text-gray-700">{{ $suggestion['name'] }}</span>
                                                    <span class="mt-0.5 block truncate text-[11px] text-gray-400">{{ $suggestion['email'] }}</span>
                                                </span>
                                                <svg class="h-4 w-4 shrink-0 text-gray-300 transition group-hover:translate-x-0.5 group-hover:text-[#1A3A6B]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @error('message') <p class="mt-[10px] text-[12px] text-red-600">{{ $message }}</p> @enderror
                            <p class="mt-[10px] text-[11px] text-gray-400">Escribe @ para mencionar · Enter para enviar</p>
                        </div>
                        <button type="submit" wire:loading.attr="disabled" wire:target="sendMessage"
                            class="inline-flex h-[54px] shrink-0 items-center justify-center gap-2 self-start rounded-xl bg-[#1A3A6B] px-5 font-semibold text-white shadow-sm transition hover:bg-[#15305a] disabled:cursor-wait disabled:opacity-60">
                            <svg wire:loading.remove wire:target="sendMessage" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12l14-7-4 14-3-6-7-1zm7 1l7-8" /></svg>
                            <svg wire:loading wire:target="sendMessage" class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                            <span>Enviar</span>
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </main>

    </div>

    <style>
        html, body, .support-page-scrollbar, .support-chat-scrollbar {
            scrollbar-width: thin;
            scrollbar-color: #1A3A6B #f8fafc;
        }
        html::-webkit-scrollbar, body::-webkit-scrollbar, .support-page-scrollbar::-webkit-scrollbar, .support-chat-scrollbar::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        html::-webkit-scrollbar-track, body::-webkit-scrollbar-track, .support-page-scrollbar::-webkit-scrollbar-track, .support-chat-scrollbar::-webkit-scrollbar-track {
            background: #f8fafc;
        }
        html::-webkit-scrollbar-thumb, body::-webkit-scrollbar-thumb, .support-page-scrollbar::-webkit-scrollbar-thumb, .support-chat-scrollbar::-webkit-scrollbar-thumb {
            background: #1A3A6B;
            border-radius: 9999px;
        }
        .support-message-input {
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        .support-message-input::-webkit-scrollbar {
            display: none;
            width: 0;
            height: 0;
        }
        @media print {
            #main-nav, #logo-sidebar, .no-print { display: none !important; }
            #main-content { margin-left: 0 !important; padding-top: 0 !important; }
            .support-page-scrollbar { overflow: visible !important; }
            .support-page-scrollbar > div { min-width: 0 !important; }
        }
    </style>
</div>
