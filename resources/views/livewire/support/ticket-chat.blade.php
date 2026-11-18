@php
    $chatTour = [
        ['target' => 'chat-online', 'title' => '1. Quién está en línea', 'description' => 'Aquí ves a las personas conectadas al chat en este momento. Pulsa una foto para ampliarla o un nombre para abrir su perfil.'],
        ['target' => 'chat-search', 'title' => '2. Busca en la conversación', 'description' => 'Escribe un nombre, un correo o parte de un mensaje y la conversación se filtra mientras escribes. Esc limpia la búsqueda.'],
        ['target' => 'chat-messages', 'title' => '3. Lee la conversación', 'description' => 'Aquí están los mensajes de la conversación. Puedes reaccionar con Me gusta, Me encanta o No me gusta y eliminar los tuyos.'],
        ['target' => 'chat-composer', 'title' => '4. Escribe tu mensaje', 'description' => 'Escribe aquí. Con los tres puntos de la derecha adjuntas hasta 10 archivos o imágenes y agregas emojis o stickers. Con @ mencionas a una persona o a todos; Enter envía.'],
        ['target' => 'chat-send', 'title' => '5. Envía el mensaje', 'description' => 'Presiona Enviar o la tecla Enter. Si falta algo, un aviso arriba te dice qué es.'],
    ];
@endphp
{{-- Chat general de soporte: mismo diseño que Reloj Checador, Cronómetro, Manager Control y Gráficos generales (variables de
     panel, texto de 15px, separaciones de 20px, tarjetas con cabecera, sin sombras ni textos grises, botones y enlaces verdes y
     los errores como aviso emergente). Solo cambia la presentación: el chat funciona igual. --}}
<div
    data-panel
    data-productivity
    data-support-chat
    class="relative isolate flex h-[calc(var(--app-dvh)-123px)] min-h-0 w-full min-w-0 flex-col overflow-hidden text-[15px]"
    x-data="{
        profilePhotoOpen: false,
        profilePhotoUrl: '',
        profilePhotoName: '',
        tip: { show: false, x: 0, y: 0, below: false, title: '', names: [], extra: 0 },
        showTip(event, title, names) {
            const zoom = parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--app-zoom')) || 1;
            const rect = event.currentTarget.getBoundingClientRect();
            const half = 150 * zoom;
            const centre = Math.min(Math.max(rect.left + rect.width / 2, half + 20), window.innerWidth - half - 20);
            this.tip.title = title + ' · ' + names.length;
            this.tip.names = names.slice(0, 10);
            this.tip.extra = Math.max(0, names.length - 10);
            this.tip.below = rect.top < 260 * zoom;
            this.tip.x = centre / zoom;
            this.tip.y = (this.tip.below ? rect.bottom : rect.top) / zoom;
            this.tip.arrow = (rect.left + rect.width / 2 - centre) / zoom;
            this.tip.show = true;
        },
        hideTip() { this.tip.show = false; },
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

    <div class="relative z-10 flex min-h-0 w-full min-w-0 flex-1 flex-col">
    <main class="relative z-10 mx-auto flex min-h-0 w-full min-w-0 flex-1 flex-col gap-[20px] p-[20px] sm:gap-[30px] sm:p-[30px] xl:gap-[50px] xl:px-[50px] xl:py-[50px]">
        <section class="flex shrink-0 flex-wrap items-center justify-between gap-x-[20px] gap-y-[15px]">
            <div class="min-w-0">
                <h1 class="panel-accent text-xl font-normal leading-none tracking-tight">Chat general de soporte</h1>
                <p class="mt-[5px] leading-[30px]">Espacio compartido para conversar con todos los colaboradores.</p>
            </div>
            <a href="{{ route('section.guide', 'chat-soporte') }}" title="Descargar la guía del Chat general de soporte en PDF" class="clock-link whitespace-nowrap leading-none">Descargar guía PDF</a>
        </section>
        <div x-data="clockQuickTour(@js($chatTour))" class="contents">
        <div data-support-chat-panel class="min-h-0 min-w-0 flex-1" style="display:grid;grid-template-columns:360px minmax(0,1fr);grid-template-rows:minmax(0,1fr);column-gap:50px;align-items:stretch;">
            <aside class="panel-surface panel-border flex w-[360px] max-h-none min-h-0 shrink-0 flex-col overflow-hidden rounded-xl border" style="grid-column:1;grid-row:1;">
                <header class="panel-header panel-border flex min-h-[98px] shrink-0 flex-wrap items-center justify-between gap-x-[20px] gap-y-[15px] border-b p-[20px]">
                    <div class="min-w-0">
                        <h2 class="panel-accent leading-none">En línea ahora</h2>
                        <p class="chat-sub mt-[5px]">Ahora hay {{ count($onlineUsers) }} {{ count($onlineUsers) === 1 ? 'conectado' : 'conectados' }}</p>
                    </div>
                </header>
                <div id="chat-online" data-scroll-on-hover x-data="backupOverlayScroll()" x-init="$nextTick(() => update())" class="relative min-h-0 flex-1 overflow-hidden">
                    <div x-ref="scroller" @scroll="update()" @scroll.debounce.150ms="(() => { const rows = [...$el.querySelectorAll('[data-online-row]')]; if (!rows.length) return; const max = $el.scrollHeight - $el.clientHeight; if ($el.scrollTop <= 2 || max - $el.scrollTop <= 2) return; const tops = rows.map(row => row.offsetTop); const target = Math.min(max, tops.reduce((a, b) => Math.abs(b - $el.scrollTop) < Math.abs(a - $el.scrollTop) ? b : a)); if (Math.abs(target - $el.scrollTop) > 2) $el.scrollTo({ top: target, behavior: 'smooth' }); })()" @mouseenter="update()" data-support-online-users class="panel-scroll h-full snap-y snap-mandatory overflow-y-auto"
                        x-init="(() => { const box = $el; const mark = () => { box.querySelectorAll('[data-online-row]').forEach((row) => { row.toggleAttribute('data-edge', Math.abs(row.offsetTop + row.offsetHeight - box.scrollTop - box.clientHeight) <= 2); }); }; mark(); box.addEventListener('scroll', mark, { passive: true }); new ResizeObserver(mark).observe(box); window.addEventListener('support-messages-refreshed', () => $nextTick(mark)); })()">
                            @forelse ($onlineUsers as $onlineUser)
                                <div wire:key="online-user-{{ $onlineUser['id'] }}" data-online-row class="panel-border flex snap-start items-center gap-[15px] border-b p-[20px] transition last:border-b-0 hover:bg-white/60" style="height:20%;min-height:80px;">
                                    @if ($onlineUser['photo_url'])
                                        <button type="button" @click="showProfilePhoto(@js($onlineUser['photo_url']), @js($onlineUser['name']))" class="relative flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-[15px] font-normal text-black ring-1 ring-zinc-200 outline-none focus:outline-none focus:ring-0" aria-label="Ampliar foto de {{ $onlineUser['name'] }}">
                                            <img src="{{ $onlineUser['photo_url'] }}" alt="Foto de {{ $onlineUser['name'] }}" class="h-full w-full rounded-full object-cover">
                                            <span class="absolute bottom-0 right-0 h-2.5 w-2.5 rounded-full bg-emerald-500 ring-2 ring-gray-100" aria-label="En línea"></span>
                                        </button>
                                    @else
                                        <span class="relative flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-[15px] font-normal text-black ring-1 ring-zinc-200">
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
                    <span :class="visible ? '' : 'hidden'" class="panel-scroll-thumb" style="right: 5px;" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
                </div>

                <div class="panel-border shrink-0 border-t p-[20px]">
                    <div class="flex flex-col gap-[20px]">
                        <button type="button" @click="startQuickTour()" class="chat-primary inline-flex min-h-[48px] w-full cursor-pointer items-center justify-center rounded-xl px-[20px] py-[15px] focus:outline-none focus:ring-0">Tutorial rápido</button>
                    </div>
                </div>
            </aside>

            <div
                class="panel-surface panel-border flex min-h-0 min-w-0 flex-1 flex-col overflow-hidden rounded-xl border"
                style="grid-column:2;grid-row:1;min-width:0;"
                wire:poll.2s.visible="refreshMessages"
                x-data="{
                    stayAtBottom: true,
                    searchQuery: '',
                    emojiOpen: false,
                    stickerOpen: false,
                    currentDayLabel: @js(data_get(collect($messages)->last(), 'date_label', 'Hoy')),
                    messageCount: {{ count($messages) }},
                    readingPositionKey: 'support-ticket-last-message-{{ auth()->id() }}',
                    messageBox() { return document.getElementById('chat-messages') },
                    scrollToBottom() { const box = this.messageBox(); if (box) box.scrollTop = box.scrollHeight },
                    restoreReadingPosition() {
                        const box = this.messageBox();
                        if (!box) return;
                        const savedId = localStorage.getItem(this.readingPositionKey);
                        const savedMessage = savedId
                            ? box.querySelector(`[data-chat-message-id=&quot;${savedId}&quot;]`)
                            : null;

                        if (savedMessage) {
                            box.scrollTop = Math.max(0, savedMessage.offsetTop - (box.clientHeight / 2) + (savedMessage.offsetHeight / 2));
                            this.stayAtBottom = false;
                        } else {
                            this.scrollToBottom();
                        }

                        this.updateReadingPosition();
                    },
                    updateReadingPosition() {
                        const box = this.messageBox();
                        if (!box) return;
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
                        this.messageCount = box.querySelectorAll('[data-chat-message-id]').length;
                    },
                    conversationSummary() {
                        const label = this.currentDayLabel || 'Hoy';
                        return `${label} · ${this.messageCount} ${this.messageCount === 1 ? 'mensaje' : 'mensajes'}`;
                    },
                    normalizedSearch() { return this.searchQuery.trim().toLocaleLowerCase('es-MX') },
                    hasSearchResults() {
                        const query = this.normalizedSearch();
                        const box = this.messageBox();
                        return !query || !box || [...box.querySelectorAll('[data-chat-message]')]
                            .some((message) => (message.dataset.search || '').includes(query));
                    },
                    trackPosition() {
                        const box = this.messageBox();
                        if (!box) return;
                        this.stayAtBottom = box.scrollHeight - box.scrollTop - box.clientHeight < 140;
                        this.updateReadingPosition();
                    }
                }"
                x-init="$nextTick(() => restoreReadingPosition())"
                @support-message-sent.window="$nextTick(() => { stayAtBottom = true; scrollToBottom(); updateReadingPosition() })"
                @support-messages-refreshed.window="$nextTick(() => updateReadingPosition())"
            >
                <header data-support-chat-header class="panel-header panel-border flex min-h-[98px] flex-wrap items-center justify-between gap-[20px] border-b p-[20px]">
                    <div class="min-w-0">
                        <h2 class="panel-accent leading-none">Conversación general</h2>
                        <p class="chat-sub mt-[5px]" x-text="conversationSummary()">{{ data_get(collect($messages)->last(), 'date_label', 'Hoy') }} · {{ count($messages) }} {{ count($messages) === 1 ? 'mensaje' : 'mensajes' }}</p>
                    </div>
                    <div class="flex min-w-0 items-center gap-[20px]">
                        <div id="chat-search" class="relative w-[min(360px,calc(100vw-80px))]">
                            <svg class="pointer-events-none absolute left-[20px] top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m21 21-4.35-4.35m2.35-5.65a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z" /></svg>
                            <input x-ref="searchInput" x-model.debounce.150ms="searchQuery" @keydown.escape.stop="searchQuery = ''" type="text" placeholder="Buscar por nombre, correo o contenido…" class="panel-field w-full rounded-xl border border-zinc-200 bg-white/75 py-[15px] pl-[50px] pr-[20px] text-[15px] text-zinc-700 outline-none transition">
                        </div>
                    </div>
                </header>

                <div data-scroll-on-hover x-data="backupOverlayScroll()" x-init="$nextTick(() => update())" class="relative min-h-0 flex-1">
                <div id="chat-messages" x-ref="scroller" @scroll="update(); trackPosition()" @mouseenter="update()" class="panel-scroll absolute inset-0 space-y-[20px] overflow-y-auto bg-white/35 px-[20px] pb-[70px] pt-[20px]">
                    <div x-show="searchQuery && !hasSearchResults()" class="py-[40px] text-center" style="display: none;">
                        <svg class="mx-auto h-8 w-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m21 21-4.35-4.35m2.35-5.65a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z" /></svg>
                        <p class="mt-[10px] text-[15px] font-normal text-gray-500">No se encontraron mensajes.</p>
                    </div>
                    @if ($hasOlderMessages)
                        <div class="flex justify-center">
                            <button type="button" wire:click="loadOlderMessages" wire:loading.attr="disabled" wire:target="loadOlderMessages" class="clock-link leading-none">Cargar mensajes anteriores</button>
                        </div>
                    @endif
                    @forelse ($messages as $chatMessage)
                        @php
                            $nameParts = array_values(array_filter(preg_split('/\s+/', trim($chatMessage['name']))));
                            $initials = collect($nameParts)->take(1)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
                        @endphp
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
                                    @if ($chatMessage['message_html'] !== '')
                                        <p class="mt-[15px] whitespace-pre-wrap break-words leading-6 text-zinc-700">{!! $chatMessage['message_html'] !!}</p>
                                    @endif
                                    @if ($chatMessage['attachments'] !== [])
                                        <div class="mt-[15px] flex flex-wrap gap-[10px]">
                                            @foreach ($chatMessage['attachments'] as $file)
                                                @if ($file['is_image'])
                                                    <a href="{{ $file['url'] }}" target="_blank" rel="noopener noreferrer" class="block overflow-hidden rounded-xl border border-zinc-200" title="{{ $file['name'] }}">
                                                        <img src="{{ $file['url'] }}" alt="{{ $file['name'] }}" class="block h-[110px] w-[110px] object-cover">
                                                    </a>
                                                @else
                                                    <a href="{{ $file['url'] }}" target="_blank" rel="noopener noreferrer" download class="flex h-[50px] w-[240px] items-center gap-[10px] rounded-xl border border-zinc-200 px-[10px] text-black transition-colors hover:bg-zinc-100" title="{{ $file['name'] }}">
                                                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Zm0 0v6h6M8 13h8M8 17h5"/></svg>
                                                        <span class="min-w-0 flex-1 truncate leading-none">{{ $file['name'] }}</span>
                                                        @if ($file['size'] > 0)<span class="shrink-0 leading-none">{{ number_format($file['size'] / 1024, 0) }} KB</span>@endif
                                                    </a>
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif
                                    <div class="no-print mt-[15px] flex items-center gap-[15px] {{ $chatMessage['is_mine'] ? 'justify-end' : 'justify-start' }}">
                                        <button type="button" wire:click="toggleReaction({{ $chatMessage['id'] }}, 'like')" @mouseenter="showTip($event, 'Me gusta', @js($chatMessage['reaction_users']['like']))" @mouseleave="hideTip()" wire:loading.attr="disabled" wire:target="toggleReaction({{ $chatMessage['id'] }}, 'like')" class="inline-flex h-6 w-6 items-center justify-center bg-transparent p-0 outline-none transition-opacity hover:opacity-60 focus:outline-none focus:ring-0 disabled:opacity-50 {{ $chatMessage['my_reaction'] === 'like' ? 'text-black' : 'text-zinc-400' }}" aria-label="Me gusta" title="Me gusta · {{ $chatMessage['reactions']['like'] }}">
                                            <svg class="h-4 w-4" fill="{{ $chatMessage['my_reaction'] === 'like' ? 'currentColor' : 'none' }}" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 10v11H3V10h4Zm0 10h10.2a2 2 0 0 0 1.94-1.52l1.5-6A2 2 0 0 0 18.7 10H14l.7-4.2A2.4 2.4 0 0 0 12.33 3L7 10Z" /></svg>
                                        </button>
                                        <button type="button" wire:click="toggleReaction({{ $chatMessage['id'] }}, 'heart')" @mouseenter="showTip($event, 'Me encanta', @js($chatMessage['reaction_users']['heart']))" @mouseleave="hideTip()" wire:loading.attr="disabled" wire:target="toggleReaction({{ $chatMessage['id'] }}, 'heart')" class="inline-flex h-6 w-6 items-center justify-center bg-transparent p-0 text-red-700 outline-none transition-opacity hover:opacity-60 focus:outline-none focus:ring-0 disabled:opacity-50" aria-label="Me encanta" title="Me encanta · {{ $chatMessage['reactions']['heart'] }}">
                                            <svg class="h-4 w-4" fill="{{ $chatMessage['my_reaction'] === 'heart' ? 'currentColor' : 'none' }}" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78L12 21.23l8.84-8.84a5.5 5.5 0 0 0 0-7.78Z" /></svg>
                                        </button>
                                        <button type="button" wire:click="toggleReaction({{ $chatMessage['id'] }}, 'dislike')" @mouseenter="showTip($event, 'No me gusta', @js($chatMessage['reaction_users']['dislike']))" @mouseleave="hideTip()" wire:loading.attr="disabled" wire:target="toggleReaction({{ $chatMessage['id'] }}, 'dislike')" class="inline-flex h-6 w-6 items-center justify-center bg-transparent p-0 outline-none transition-opacity hover:opacity-60 focus:outline-none focus:ring-0 disabled:opacity-50 {{ $chatMessage['my_reaction'] === 'dislike' ? 'text-black' : 'text-zinc-400' }}" aria-label="No me gusta" title="No me gusta · {{ $chatMessage['reactions']['dislike'] }}">
                                            <svg class="h-4 w-4" fill="{{ $chatMessage['my_reaction'] === 'dislike' ? 'currentColor' : 'none' }}" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 14V3H3v11h4Zm0-10h10.2a2 2 0 0 1 1.94 1.52l1.5 6A2 2 0 0 1 18.7 14H14l.7 4.2A2.4 2.4 0 0 1 12.33 21L7 14Z" /></svg>
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
                <span :class="visible ? '' : 'hidden'" class="panel-scroll-thumb" style="right: 5px;" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>

                {{-- Quién está escribiendo: va encima del final de la lista (no empuja nada) y deja libre ese espacio con el relleno de abajo --}}
                @if ($typingUsers !== [])
                    @php
                        $typist = $typingUsers[0];
                        $typingNames = collect($typingUsers)->pluck('name')->map(fn ($name) => \Illuminate\Support\Str::before($name, ' '));
                        $typingText = count($typingUsers) === 1
                            ? $typingNames[0].' está escribiendo'
                            : (count($typingUsers) === 2 ? $typingNames[0].' y '.$typingNames[1].' están escribiendo' : 'Varias personas están escribiendo');
                    @endphp
                    <div class="panel-surface pointer-events-none absolute bottom-0 left-0 right-0 z-10 flex h-[50px] items-center gap-[20px] px-[20px]" role="status" aria-live="polite" data-typing-indicator>
                        @if ($typist['photo_url'])
                            <img src="{{ $typist['photo_url'] }}" alt="{{ $typist['name'] }}" class="h-9 w-9 shrink-0 rounded-full border border-zinc-200 object-cover">
                        @else
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-zinc-200 bg-zinc-100 text-black">{{ $typist['initials'] }}</span>
                        @endif
                        <span class="flex min-w-0 items-end gap-[10px]">
                            <span class="truncate leading-[30px]">{{ $typingText }}</span>
                            <span class="mb-[13px] flex items-center gap-[6px]" aria-hidden="true">
                                <span class="chat-typing-dot"></span>
                                <span class="chat-typing-dot"></span>
                                <span class="chat-typing-dot"></span>
                            </span>
                        </span>
                    </div>
                @endif
                </div>

                <form id="chat-composer" wire:submit="sendMessage" class="no-print border-t border-zinc-200 bg-white/70 p-[20px]" x-data="{ menu: null }" @click.outside="menu = null" @keydown.escape.window="menu = null">
                    @if ($files !== [])
                        <div class="mb-[15px] flex flex-wrap items-center gap-[10px]">
                            @foreach ($files as $index => $file)
                                @php($isImage = in_array(strtolower($file->getClientOriginalExtension()), ['jpg', 'jpeg', 'png', 'webp'], true))
                                <div wire:key="chat-file-{{ $index }}-{{ $file->getFilename() }}" class="flex h-[50px] w-[220px] items-center gap-[10px] rounded-xl border border-zinc-200 bg-zinc-50 px-[10px]">
                                    @if ($isImage)
                                        <img src="{{ $file->temporaryUrl() }}" alt="" class="h-[30px] w-[30px] shrink-0 rounded-lg object-cover">
                                    @else
                                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Zm0 0v6h6M8 13h8M8 17h5"/></svg>
                                    @endif
                                    <span class="min-w-0 flex-1 truncate leading-none" title="{{ $file->getClientOriginalName() }}">{{ $file->getClientOriginalName() }}</span>
                                    <button type="button" wire:click="removeFile({{ $index }})" class="inline-flex shrink-0 items-center justify-center rounded-lg p-[5px] text-red-700 transition-colors hover:bg-red-50" aria-label="Quitar archivo" title="Quitar archivo">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 6l12 12M18 6 6 18"/></svg>
                                    </button>
                                </div>
                            @endforeach
                            <span class="leading-none">{{ count($files) }}/10</span>
                            <span wire:loading wire:target="newFiles" class="leading-none">Preparando archivos...</span>
                        </div>
                    @endif
                    <div class="grid grid-cols-[minmax(0,1fr)_auto] items-start gap-x-[20px]">
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
                                class="support-message-input block max-h-32 w-full resize-none overflow-y-auto rounded-xl border border-zinc-200 bg-white/75 py-[15px] pl-[20px] pr-[60px] text-[15px] leading-6 text-zinc-700 outline-none transition focus:border-zinc-300 focus:outline-none focus:ring-0"
                            ></textarea>

                            <button type="button" @click="menu = menu ? null : 'main'" :aria-expanded="menu !== null" class="absolute right-[10px] top-[10px] inline-flex h-[30px] w-[30px] items-center justify-center rounded-lg transition-colors hover:bg-zinc-100 focus:outline-none focus:ring-0" aria-label="Más opciones" title="Adjuntar, emojis y stickers">
                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="5" r="1.8"/><circle cx="12" cy="12" r="1.8"/><circle cx="12" cy="19" r="1.8"/></svg>
                            </button>

                            <div x-show="menu" x-transition style="display: none;" class="absolute bottom-[calc(100%+10px)] right-0 z-40 w-[280px] rounded-xl border border-zinc-200 bg-white p-[10px] shadow-[0_18px_45px_rgba(0,0,0,0.12)]">
                                <div x-show="menu === 'main'" class="flex flex-col">
                                    <label for="support-chat-images" @click="menu = null" class="flex h-[50px] cursor-pointer items-center gap-[15px] rounded-xl px-[15px] transition-colors hover:bg-zinc-100">
                                        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.82-2.83l8.48-8.48"/></svg>
                                        Adjuntar imagen
                                    </label>
                                    <label for="support-chat-docs" @click="menu = null" class="flex h-[50px] cursor-pointer items-center gap-[15px] rounded-xl px-[15px] transition-colors hover:bg-zinc-100">
                                        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Zm0 0v6h6"/></svg>
                                        Adjuntar archivo
                                    </label>
                                    <button type="button" @click="menu = 'emoji'" class="flex h-[50px] items-center gap-[15px] rounded-xl px-[15px] text-left transition-colors hover:bg-zinc-100">
                                        <span class="w-4 shrink-0 text-center text-[15px] leading-none">😊</span>
                                        Emojis
                                    </button>
                                    <button type="button" @click="menu = 'sticker'" class="flex h-[50px] items-center gap-[15px] rounded-xl px-[15px] text-left transition-colors hover:bg-zinc-100">
                                        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 12a8 8 0 1 1-8-8h4a4 4 0 0 1 4 4v4Zm0 0a4 4 0 0 1-4 4h-4v4"/></svg>
                                        Stickers
                                    </button>
                                </div>
                                <div x-show="menu === 'emoji'" class="p-[5px]">
                                    <button type="button" @click="menu = 'main'" class="clock-link mb-[10px] leading-none">← Volver</button>
                                    <div class="grid grid-cols-4 gap-[5px]">
                                        @foreach (['😀', '😂', '😊', '😍', '👍', '👏', '🙏', '🎉', '❤️', '✅', '👀', '🤝'] as $emoji)
                                            <button type="button" wire:click="appendEmoji('{{ $emoji }}')" @click="menu = null" class="flex h-10 w-10 items-center justify-center rounded-lg text-[20px] transition-colors hover:bg-zinc-100" aria-label="Agregar emoji {{ $emoji }}">{{ $emoji }}</button>
                                        @endforeach
                                    </div>
                                </div>
                                <div x-show="menu === 'sticker'" class="p-[5px]">
                                    <button type="button" @click="menu = 'main'" class="clock-link mb-[10px] leading-none">← Volver</button>
                                    <div class="grid grid-cols-3 gap-[10px]">
                                        @foreach ([
                                            'fry-sospecha' => 'img/support/stickers/fry-sospecha.jpg',
                                            'avestruz-genial' => 'img/support/stickers/avestruz-genial.jpg',
                                            'ternura' => 'img/support/stickers/ternura.jpg',
                                        ] as $stickerKey => $stickerPath)
                                            <button type="button" wire:click="sendSticker('{{ $stickerKey }}')" @click="menu = null" class="overflow-hidden rounded-xl border border-zinc-200 bg-zinc-50 p-[5px] outline-none transition-colors hover:border-zinc-300 hover:bg-zinc-100 focus:outline-none focus:ring-0" aria-label="Enviar sticker">
                                                <img src="{{ asset($stickerPath) }}" alt="Sticker" class="aspect-square w-full rounded-lg object-cover">
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <input id="support-chat-images" type="file" multiple wire:model="newFiles" accept="image/jpeg,image/png,image/webp" class="sr-only">
                            <input id="support-chat-docs" type="file" multiple wire:model="newFiles" accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.txt,.zip" class="sr-only">

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

                            <x-error-toast :wire:key="'chat-error-'.$errorSeq.'-'.md5((string) $errors->first())" title="No se pudo enviar" :message="$errors->first()" />
                        </div>
                        <button id="chat-send" type="submit" wire:loading.attr="disabled" wire:target="sendMessage"
                            class="chat-primary inline-flex shrink-0 items-center justify-center gap-[10px] self-start rounded-xl px-[20px] py-[15px] font-normal disabled:cursor-wait disabled:opacity-60">
                            <svg wire:loading.remove wire:target="sendMessage" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12l14-7-4 14-3-6-7-1zm7 1l7-8" /></svg>
                            <svg wire:loading wire:target="sendMessage" class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                            <span>Enviar</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <template x-teleport="body">
            <div data-panel data-productivity data-support-chat x-cloak x-show="quickTourStep > 0" x-transition.opacity class="pointer-events-none fixed inset-0 z-[299999]" style="background:transparent" @click.stop @wheel.prevent @touchmove.prevent>
                <div :style="maskTopStyle" class="z-[300000]" aria-hidden="true"></div>
                <div :style="maskLeftStyle" class="z-[300000]" aria-hidden="true"></div>
                <div :style="maskRightStyle" class="z-[300000]" aria-hidden="true"></div>
                <div :style="maskBottomStyle" class="z-[300000]" aria-hidden="true"></div>
                <div :style="spotlightStyle" class="pointer-events-auto z-[300001]" aria-hidden="true"></div>
                <section :style="popoverStyle" class="app-zoom-overlay-off panel-surface panel-border pointer-events-auto fixed left-1/2 top-1/2 z-[300002] w-[min(440px,calc(100vw-40px))] -translate-x-1/2 -translate-y-1/2 rounded-xl border p-[20px] shadow-[0_18px_55px_rgba(0,0,0,0.30)]" role="dialog" aria-modal="true" aria-labelledby="clock-tour-title">
                    <p class="panel-accent leading-[30px]" x-text="quickTourStep ? 'Paso ' + quickTourStep + ' de ' + quickTourSteps.length : ''"></p>
                    <h2 id="clock-tour-title" class="mt-[15px] leading-[30px]" x-text="quickTourStep ? quickTourSteps[quickTourStep - 1].title : ''"></h2>
                    <p class="mt-[15px] text-justify leading-[30px]" x-text="quickTourStep ? quickTourSteps[quickTourStep - 1].description : ''"></p>
                    <div class="mt-[20px] flex flex-wrap items-center justify-between gap-x-[20px] gap-y-[20px]">
                        <button type="button" @click="closeQuickTour()" class="clock-link whitespace-nowrap leading-none">Saltar recorrido</button>
                        <div class="flex items-center gap-[20px] whitespace-nowrap">
                            <button x-show="quickTourStep > 1" type="button" @click="previousQuickTourStep()" class="panel-button-secondary rounded-xl px-[20px] py-[15px] leading-none">Anterior</button>
                            <button x-ref="quickTourNext" type="button" @click="nextQuickTourStep()" class="chat-primary rounded-xl px-[20px] py-[15px] leading-none" x-text="quickTourStep === quickTourSteps.length ? 'Terminar' : 'Siguiente'"></button>
                        </div>
                    </div>
                </section>
            </div>
        </template>
        </div>
    </main>

    </div>

    {{-- Globo de quiénes reaccionaron: mismo diseño que los demás globos (caja con flecha, 20px de relleno y títulos en azul) --}}
    <template x-teleport="body">
        <div data-panel data-productivity data-support-chat x-cloak x-show="tip.show" role="tooltip" style="min-height:0"
            class="app-zoom-overlay panel-surface panel-border panel-text pointer-events-none fixed z-[200001] w-max min-w-[180px] max-w-[380px] rounded-xl border p-[20px] shadow-[0_18px_55px_rgba(0,0,0,0.30)]"
            :style="`min-height:0;left:${tip.x}px;top:${tip.y}px;transform:translate(-50%, ${tip.below ? '20px' : 'calc(-100% - 20px)'})`">
            <span class="panel-accent block leading-[30px]" x-text="tip.title"></span>
            <template x-for="(name, i) in tip.names" :key="i"><span class="block truncate leading-[30px]" x-text="name"></span></template>
            <span x-show="tip.extra > 0" class="block leading-[30px]" x-text="'y ' + tip.extra + ' más'"></span>
            <span x-show="tip.names.length === 0" class="block leading-[30px]">Nadie ha reaccionado todavía</span>
            <span class="panel-surface panel-border absolute h-[11px] w-[11px] rotate-45" :class="tip.below ? '-top-[6px] border-l border-t' : '-bottom-[6px] border-b border-r'" :style="`left:calc(50% + ${tip.arrow || 0}px - 5.5px)`"></span>
        </div>
    </template>

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
        [data-support-chat-panel] {
            display: grid !important;
            grid-template-columns: 360px minmax(0, 1fr);
            align-items: stretch;
            gap: 30px;
        }
        [data-support-chat-panel] > aside {
            width: 360px;
            min-width: 360px;
            max-height: none;
        }
        [data-support-chat-panel] > :last-child { min-width: 0; }
        @media (min-width: 1280px) {
            [data-support-chat-panel] { gap: 50px; }
        }
        [data-support-chat] {
            --chat-green: #15803d;
            --panel-muted: var(--panel-text);
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
        /* La fila que toca el borde de abajo no dibuja su línea: esa línea ya la dibuja el pie (así nunca se ve doble) */
        [data-support-chat] [data-online-row][data-edge] { border-bottom-color: transparent !important; }
        /* Puntitos de «escribiendo» (mismos que en Preguntas) */
        .chat-typing-dot { width: 4px; height: 4px; border-radius: 9999px; background: currentColor; animation: chat-typing 1.2s infinite ease-in-out; }
        .chat-typing-dot:nth-child(2) { animation-delay: 0.18s; }
        .chat-typing-dot:nth-child(3) { animation-delay: 0.36s; }
        @keyframes chat-typing { 0%, 60%, 100% { transform: translateY(0); opacity: 0.35; } 30% { transform: translateY(-5px); opacity: 1; } }
        @media (prefers-reduced-motion: reduce) { .chat-typing-dot { animation-duration: 2.4s; } }
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
        /* Texto que puede partirse en varias líneas: 20px de espacio entre una línea y la siguiente (igual que el Cronómetro) */
        [data-support-chat] p.chat-sub { line-height: 35px; margin-bottom: -10px; }
        /* El scroll solo aparece con el puntero encima (igual que en los demás módulos) */
        [data-support-chat] .support-chat-scrollbar {
            scrollbar-width: thin;
            scrollbar-color: transparent transparent;
            scrollbar-gutter: auto;
            overscroll-behavior: contain;
        }
        [data-support-chat] .support-chat-scrollbar:hover {
            scrollbar-color: var(--panel-accent) transparent;
        }
        [data-support-chat] .support-chat-scrollbar::-webkit-scrollbar {
            width: 4px;
            height: 4px;
        }
        [data-support-chat] .support-chat-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        [data-support-chat] .support-chat-scrollbar::-webkit-scrollbar-button {
            display: none;
            width: 0;
            height: 0;
        }
        [data-support-chat] .support-chat-scrollbar::-webkit-scrollbar-thumb {
            background: transparent;
            border-radius: 9999px;
            border: 0;
            background-clip: border-box;
        }
        [data-support-chat] .support-chat-scrollbar:hover::-webkit-scrollbar-thumb {
            background: var(--panel-accent);
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

        @media (min-width: 1280px) {
            html:has([data-support-chat]) { overflow-y: hidden; }
        }
        @media (max-width: 1279px) {
            [data-support-chat] { height: auto; min-height: calc(var(--app-dvh) - 123px); overflow: visible; }
            [data-support-chat-panel] { min-height: 620px; height: auto; }
            [data-support-chat] main { min-height: 0; }
        }
        @media (max-width: 639px) {
            [data-support-chat] [data-support-online-users] { max-height: 210px; }
        }
    </style>
</div>
