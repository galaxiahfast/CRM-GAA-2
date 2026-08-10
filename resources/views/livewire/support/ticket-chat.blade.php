<div class="min-h-[calc(100dvh-90px)] text-[15px] text-gray-700">
    <header class="border-b border-gray-200 px-6 py-6 lg:px-10">
        <div class="flex items-center gap-3 text-gray-500">
            <span class="font-medium">Soporte</span>
            <span class="text-gray-300">&gt;</span>
            <span class="font-semibold text-[#1A3A6B]">Ticket</span>
        </div>
    </header>

    <main class="mx-auto w-full max-w-[1500px] space-y-6 px-6 py-8 lg:px-10">
        <section class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div class="flex items-start gap-4">
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

        <section class="grid min-h-[640px] grid-cols-1 overflow-hidden rounded-xl border border-gray-200 shadow-[0_8px_24px_rgba(15,35,66,0.06)] lg:h-[640px] lg:min-h-0 lg:grid-cols-[290px_minmax(0,1fr)]">
            <aside class="border-b border-gray-200 p-5 lg:border-b-0 lg:border-r">
                <div class="flex items-center gap-3 border-b border-gray-200 pb-5">
                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-[#1A3A6B] font-semibold text-white">
                        {{ mb_strtoupper(mb_substr(auth()->user()->name ?? '?', 0, 1)) }}
                    </span>
                    <div class="min-w-0">
                        <p class="truncate font-semibold text-gray-800">{{ trim((auth()->user()->name ?? '').' '.(auth()->user()->last_name ?? '')) }}</p>
                        <p class="truncate text-[12px] text-gray-500">{{ auth()->user()->email }}</p>
                    </div>
                </div>

                <div class="space-y-5 pt-5">
                    <div>
                        <p class="text-[12px] font-semibold uppercase tracking-[0.08em] text-gray-400">Chat del día</p>
                        <p class="mt-2 font-medium capitalize text-gray-700">{{ $todayLabel }}</p>
                    </div>

                    <div class="rounded-xl border border-amber-200 p-4 text-[13px] leading-5 text-amber-800">
                        <div class="flex items-start gap-2">
                            <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 4h.01M10.3 3.6L2.6 17A2 2 0 004.3 20h15.4a2 2 0 001.7-3L13.7 3.6a2 2 0 00-3.4 0z" /></svg>
                            <p>La conversación se reinicia cada día. Los mensajes anteriores se eliminan automáticamente.</p>
                        </div>
                    </div>

                    <div class="space-y-3 text-[13px] text-gray-500">
                        <div class="flex items-center gap-2">
                            <svg class="h-4 w-4 text-[#1A3A6B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.1 17A9 9 0 1119 6.1M9 17H5v4" /></svg>
                            Actualización automática
                        </div>
                        <div class="flex items-center gap-2">
                            <svg class="h-4 w-4 text-[#1A3A6B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2m7-10a4 4 0 100-8 4 4 0 000 8zm13 10v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75" /></svg>
                            Nombre y correo visibles
                        </div>
                    </div>
                </div>
            </aside>

            <div
                class="flex min-h-0 flex-col"
                wire:poll.3s.visible="refreshMessages"
                x-data="{
                    stayAtBottom: true,
                    scrollToBottom() { this.$refs.messages.scrollTop = this.$refs.messages.scrollHeight },
                    trackPosition() {
                        const box = this.$refs.messages;
                        this.stayAtBottom = box.scrollHeight - box.scrollTop - box.clientHeight < 140;
                    }
                }"
                x-init="$nextTick(() => scrollToBottom())"
                @support-message-sent.window="$nextTick(() => { stayAtBottom = true; scrollToBottom() })"
                @support-messages-refreshed.window="$nextTick(() => { if (stayAtBottom) scrollToBottom() })"
            >
                <header class="flex min-h-[74px] items-center justify-between gap-4 border-b border-gray-200 px-5 py-4">
                    <div>
                        <h2 class="font-semibold text-gray-800">Conversación general</h2>
                        <p class="mt-0.5 text-[12px] text-gray-500">{{ count($messages) }} {{ count($messages) === 1 ? 'mensaje hoy' : 'mensajes hoy' }}</p>
                    </div>
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl border border-gray-200 text-gray-500" title="Los mensajes se actualizan automáticamente">
                        <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 4v6h6M20 20v-6h-6M5.5 15a7 7 0 0011.5 2M18.5 9A7 7 0 007 7" /></svg>
                    </span>
                </header>

                <div x-ref="messages" @scroll="trackPosition()" class="support-chat-scrollbar min-h-[430px] flex-1 space-y-4 overflow-y-auto px-5 py-5">
                    @forelse ($messages as $chatMessage)
                        @php
                            $nameParts = array_values(array_filter(preg_split('/\s+/', trim($chatMessage['name']))));
                            $initials = collect($nameParts)->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
                        @endphp
                        <article wire:key="support-message-{{ $chatMessage['id'] }}" class="flex items-end justify-start gap-3 {{ $chatMessage['is_mine'] ? 'flex-row-reverse' : '' }}">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-[12px] font-semibold shadow-sm {{ $chatMessage['is_mine'] ? 'border border-[#b9c9e2] bg-[#1A3A6B] text-white' : 'border border-gray-200 bg-white text-[#1A3A6B]' }}" title="{{ $chatMessage['name'] }}">
                                {{ $initials ?: '?' }}
                            </span>

                            <div class="max-w-[min(78%,760px)] rounded-xl border px-4 py-3 shadow-[0_3px_10px_rgba(15,35,66,0.04)] {{ $chatMessage['is_mine'] ? 'border-[#b9c9e2] bg-[#eef4fc]' : 'border-gray-200 bg-white' }}">
                                <div class="flex min-w-0 items-start gap-3 border-b border-current/10 pb-2">
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate font-semibold text-gray-800">{{ $chatMessage['name'] }}</p>
                                        <p class="truncate text-[11px] text-gray-500">{{ $chatMessage['email'] }}</p>
                                    </div>
                                    <span class="shrink-0 pt-0.5 text-[11px] text-gray-400">{{ $chatMessage['time'] }}</span>
                                </div>
                                <p class="mt-2.5 whitespace-pre-wrap break-words leading-6 text-gray-700">{{ $chatMessage['message'] }}</p>
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

                <form wire:submit="sendMessage" class="border-t border-gray-200 p-4">
                    <div class="grid grid-cols-[minmax(0,1fr)_auto] items-start gap-x-3">
                        <div class="min-w-0">
                            <textarea
                                wire:model="message"
                                @keydown.enter.exact.prevent="
                                    const value = $event.target.value;
                                    if (value.trim()) {
                                        $wire.set('message', value).then(() => $wire.sendMessage());
                                    }
                                "
                                rows="1"
                                maxlength="1000"
                                placeholder="Escribe un mensaje…"
                                class="support-chat-scrollbar block h-[54px] max-h-32 w-full resize-none rounded-xl border border-gray-300 bg-transparent px-4 py-[15px] text-[15px] leading-6 text-gray-700 outline-none transition focus:border-[#1A3A6B] focus:ring-4 focus:ring-[#1A3A6B]/10"
                            ></textarea>
                            @error('message') <p class="mt-1.5 text-[12px] text-red-600">{{ $message }}</p> @enderror
                            <p class="mt-1 text-[11px] text-gray-400">Enter para enviar · Shift + Enter para una nueva línea</p>
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

    <style>
        .support-chat-scrollbar { scrollbar-width: thin; scrollbar-color: #1A3A6B transparent; }
        .support-chat-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
        .support-chat-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .support-chat-scrollbar::-webkit-scrollbar-thumb { background: #1A3A6B; border-radius: 9999px; }
    </style>
</div>
