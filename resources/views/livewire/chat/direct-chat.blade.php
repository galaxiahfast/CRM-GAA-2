@php
    $tz = config('support.timezone', 'America/Mexico_City');
    $fullName = fn ($person) => trim($person->name.' '.$person->last_name);
    $shortName = fn ($person) => trim(\Illuminate\Support\Str::before((string) $person->name, ' ').' '.\Illuminate\Support\Str::before((string) $person->last_name, ' '));
    $initials = fn ($person) => mb_strtoupper(mb_substr($person->name, 0, 1).mb_substr($person->last_name ?? '', 0, 1));
    $dayLabel = function ($date) use ($tz) {
        $date = $date->copy()->timezone($tz);
        $today = now($tz);

        return $date->isSameDay($today) ? 'Hoy' : ($date->isSameDay($today->copy()->subDay()) ? 'Ayer' : ucfirst($date->locale('es')->isoFormat($date->year === $today->year ? 'dddd D [de] MMMM' : 'dddd D [de] MMMM [de] YYYY')));
    };
    $shortTime = function ($date) use ($tz) {
        if (! $date) {
            return '';
        }
        $date = $date->copy()->timezone($tz);

        return $date->isSameDay(now($tz)) ? $date->format('H:i') : $date->format('d/m');
    };
@endphp

{{-- Chat «Mensajes»: abajo a la derecha en todas las pantallas (a 50px del borde), se minimiza y se expande. Mismo diseño que
     Inicio: tarjeta con bordes de 18px, títulos de 19px, texto de 15px en gris para lo secundario y 20px entre todo. Los mensajes
     se guardan de forma indefinida; al enviar, el mensaje aparece al instante con un reloj mientras el servidor lo guarda. --}}
<div
    data-panel
    data-productivity
    data-direct-chat
    class="no-print fixed z-[160] flex justify-end"
    style="right: calc(50px * var(--app-zoom, 1)); bottom: 0; min-height: 0; background: transparent"
    @if ($expanded) wire:poll.2s.visible @else wire:poll.5s.visible @endif
    x-data="{
        open: @js($expanded),
        pending: [],
        focusInput() { this.$nextTick(() => this.$root.querySelector('form input')?.focus()); },
        submitMessage() {
            const box = this.$root.querySelector('form input');
            const text = (box?.value || '').trim();
            if (! text) { box?.focus(); return; }
            const item = { id: Date.now() + Math.random(), text };
            this.pending.push(item);
            box.value = '';
            this.$dispatch('direct-chat-sent');
            this.$wire.send(text).finally(() => {
                this.pending = this.pending.filter((p) => p.id !== item.id);
                // Si no se pudo enviar, el texto vuelve a la caja para no perderlo
                if (this.$wire.message && ! box.value) { box.value = this.$wire.message; }
            });
        },
    }"
    x-init="
        // El estado abierto/minimizado lo lleva el navegador (al instante) y se avisa al servidor sin esperar para recordarlo al recargar
        $watch('open', (value) => $wire.setExpanded(value));
        // Al llegar desde una notificación (?chat=ID) se quita ese parámetro de la dirección: así, minimizar y recargar no lo vuelve a abrir
        const url = new URL(window.location.href);
        if (url.searchParams.has('chat')) { url.searchParams.delete('chat'); window.history.replaceState(null, '', url); }
    "
    x-effect="if (open) { $dispatch('direct-chat-sent'); focusInput(); }"
    @direct-chat-opened.window="open = true"
    @click.outside="if (open) open = false"
    @keydown.escape.window="if (open) open = false"
    @visibilitychange.document="if (document.hidden && open) open = false"
>
    <div class="app-zoom-overlay grid items-end justify-items-end">

    <x-error-toast :wire:key="'direct-chat-error-'.$errorSeq" title="No se pudo enviar" :message="$errors->first('message')" />

        {{-- Minimizado: avisos de mensajes nuevos y de quién está escribiendo, y la barra «Mensajes» --}}
        <div x-show="! open" style="grid-area: 1 / 1;@if ($expanded) display: none;@endif" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="flex flex-col items-end">
        @if ($previews->isNotEmpty())
            <div class="mb-[20px] flex flex-col items-end gap-[20px]">
                @foreach ($previews as $preview)
                    @php($previewUser = $preview['user'])
                    <button type="button" wire:key="direct-preview-{{ $previewUser->id }}" wire:click="openConversation({{ $previewUser->id }})" class="dc-card dc-preview flex w-[400px] max-w-[calc(100vw-20px)] items-center gap-[20px] p-[20px] text-left">
                        <img src="{{ $previewUser->avatarUrl() }}" alt="Foto de {{ $previewUser->name }}" class="dc-avatar">
                        <span class="min-w-0 flex-1">
                            <span class="dc-name block truncate">{{ $fullName($previewUser) }}</span>
                            @if ($preview['typing'])
                                <span class="dc-green-text dc-line dc-second block">escribiendo<span class="dc-inline-dots ml-[10px]" aria-hidden="true"><span class="dc-dot"></span><span class="dc-dot"></span><span class="dc-dot"></span></span></span>
                            @else
                                <span class="dc-muted dc-line dc-second block truncate">{{ $preview['last'] }}</span>
                            @endif
                        </span>
                        @if ($preview['unread'] > 0)
                            <span class="dc-badge">{{ $preview['unread'] }}</span>
                        @endif
                    </button>
                @endforeach
            </div>
        @endif

        <button type="button" @click="open = true" class="dc-card dc-bar flex w-[400px] max-w-[calc(100vw-20px)] items-center justify-between gap-[20px] p-[20px] text-left" aria-label="Abrir mensajes" title="Abrir mensajes">
            <span class="dc-title">Mensajes</span>
            @if ($unreadTotal > 0)
                <span class="dc-badge">{{ $unreadTotal }}</span>
            @endif
        </button>
        </div>

        {{-- Expandido: sube desde abajo al abrir y baja al minimizar --}}
        <section x-show="open" style="grid-area: 1 / 1;@if (! $expanded) display: none;@endif" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-full" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-full" class="dc-card dc-bar flex {{ $activeUser ? 'h-[572px]' : '' }} max-h-[calc((100dvh-123px)/var(--app-zoom,1))] w-[400px] max-w-[calc(100vw-20px)] flex-col overflow-hidden" aria-label="Mensajes">
            <header @if (! $activeUser) @click="open = false" title="Minimizar" @endif class="dc-divider flex shrink-0 {{ $activeUser ? '' : 'cursor-pointer' }} items-center justify-between gap-[20px] border-b p-[20px]" x-data="{ zoom: false }">
                @if ($activeUser)
                    <div class="flex min-w-0 items-center gap-[20px]">
                        @if ($activeUser->hasProfilePhotoFile())
                            <button type="button" @click="zoom = true" class="shrink-0 cursor-zoom-in rounded-full" aria-label="Ver la foto más grande" title="Ver la foto más grande">
                                <img src="{{ $activeUser->profile_photo_url }}" alt="Foto de {{ $activeUser->name }}" class="dc-avatar">
                            </button>
                            <div x-show="zoom" x-cloak x-transition.opacity @click.stop="zoom = false" @keydown.escape.window="zoom = false" class="fixed inset-0 z-[300] grid place-items-center bg-black/70 p-[20px]" style="display: none;">
                                <img src="{{ $activeUser->profile_photo_url }}" alt="Foto ampliada de {{ $activeUser->name }}" class="max-h-[82vh] max-w-[82vw] rounded-full border-[6px] border-[#e7e7e7] object-contain shadow-2xl">
                            </div>
                        @else
                            <img src="{{ $activeUser->avatarUrl() }}" alt="Foto de {{ $activeUser->name }}" class="dc-avatar">
                        @endif
                        <a href="{{ route('profiles.show', $activeUser) }}" class="min-w-0" title="Ver perfil de {{ $fullName($activeUser) }}">
                            <p class="dc-name truncate" title="{{ $fullName($activeUser) }}">{{ $shortName($activeUser) }}</p>
                            <p class="dc-muted dc-second truncate"><span class="dc-inline-dot mr-[10px] rounded-full {{ $activeOnline ? 'bg-emerald-500' : 'bg-gray-400' }}"></span>{{ $activeOnline ? 'En línea' : 'Desconectado' }}</p>
                        </a>
                    </div>
                @else
                    <p class="dc-title leading-none">Mensajes</p>
                @endif
                <div class="flex shrink-0 items-center gap-[10px]">
                    @if ($activeUser)
                        <button type="button" wire:click="closeConversation" @click.stop class="dc-icon-btn" aria-label="Todas las conversaciones" title="Todas las conversaciones">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                        </button>
                    @endif
                    <button type="button" @click.stop="open = false" class="dc-icon-btn" aria-label="Minimizar" title="Minimizar">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6" /></svg>
                    </button>
                </div>
            </header>

            @if ($activeUser)
                {{-- Conversación --}}
                <div data-scroll-on-hover
                    x-data="{ ...backupOverlayScroll(), stick: true, end() { const box = this.$refs.scroller; if (box) { box.scrollTop = box.scrollHeight; this.update(); } },
                        older() { const box = this.$refs.scroller; const h = box.scrollHeight, t = box.scrollTop; this.stick = false;
                            this.$wire.loadOlder().then(() => this.$nextTick(() => { box.scrollTop = box.scrollHeight - h + t; this.update(); })); } }"
                    x-init="$nextTick(() => { end(); new MutationObserver(() => { if (stick) { end(); } }).observe($refs.scroller, { childList: true, subtree: true }); })"
                    x-on:direct-chat-sent.window="stick = true; $nextTick(() => end())"
                    class="relative min-h-0 flex-1 overflow-hidden">
                    <div x-ref="scroller" @scroll="update(); stick = $el.scrollHeight - $el.scrollTop - $el.clientHeight < 140" @mouseenter="update()" class="panel-scroll flex h-full flex-col gap-[20px] overflow-y-auto px-[20px] pb-[70px] pt-[20px]">
                        @if ($hasOlder)
                            <button type="button" @click="older()" wire:loading.attr="disabled" wire:target="loadOlder" class="dc-chip mx-auto shrink-0 disabled:cursor-wait disabled:opacity-60">
                                <span wire:loading.remove wire:target="loadOlder">Ver mensajes anteriores</span>
                                <span wire:loading wire:target="loadOlder">Cargando…</span>
                            </button>
                        @endif
                        @php($lastDay = null)
                        @forelse ($messages as $item)
                            @php($mine = $item->sender_id === auth()->id())
                            @php($day = $dayLabel($item->created_at))
                            @if ($day !== $lastDay)
                                @php($lastDay = $day)
                                <p class="dc-muted shrink-0 text-center leading-none">{{ $day }}</p>
                            @endif
                            <div wire:key="direct-message-{{ $item->id }}" class="flex shrink-0 {{ $mine ? 'justify-end' : 'justify-start' }}">
                                <div class="dc-bubble {{ $mine ? 'dc-mine' : '' }}" title="{{ $item->created_at->timezone($tz)->format('d/m/Y H:i') }}">
                                    <p class="whitespace-pre-wrap break-words">{{ $item->body }}</p>
                                    <p class="dc-muted dc-time">{{ $item->created_at->timezone($tz)->format('H:i') }}
                                        @if ($mine)
                                            @if ($item->read_at)
                                                <svg class="h-[18px] w-[18px] text-sky-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-label="Leído" role="img"><title>Leído</title><path d="M2 12.5l4.5 4.5L15 7.5M10 15.5l1.5 1.5L20 7.5" /></svg>
                                            @elseif ($activeOnline)
                                                <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-label="Recibido" role="img"><title>Recibido</title><path d="M2 12.5l4.5 4.5L15 7.5M10 15.5l1.5 1.5L20 7.5" /></svg>
                                            @else
                                                <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-label="Enviado" role="img"><title>Enviado</title><path d="M5 12.5l4.5 4.5L19 7.5" /></svg>
                                            @endif
                                        @endif
                                    </p>
                                </div>
                            </div>
                        @empty
                            <p class="dc-muted px-[20px] py-[40px] text-center" x-show="! pending.length">Todavía no hay mensajes. Escribe el primero a {{ $shortName($activeUser) }}.</p>
                        @endforelse
                        {{-- Enviándose: aparece al instante con un reloj hasta que el servidor lo guarda --}}
                        <template x-for="item in pending" :key="item.id">
                            <div class="flex shrink-0 justify-end">
                                <div class="dc-bubble dc-mine">
                                    <p class="whitespace-pre-wrap break-words" x-text="item.text"></p>
                                    <p class="dc-muted dc-time"><svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-label="Enviando" role="img"><title>Enviando</title><circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 2" /></svg></p>
                                </div>
                            </div>
                        </template>
                    </div>
                    <span :class="visible ? '' : 'hidden'" class="panel-scroll-thumb" style="right: 5px;" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>

                    {{-- Quién está escribiendo: encima del final de la lista (no empuja nada; el relleno de abajo deja libre ese espacio) --}}
                    @if ($activeTyping)
                        <div class="dc-typing pointer-events-none absolute bottom-0 left-0 right-0 z-10 flex h-[50px] items-end gap-[10px] px-[20px] pb-[10px]" role="status" aria-live="polite">
                            <span class="dc-muted truncate">{{ $shortName($activeUser) }} está escribiendo</span>
                            <span class="dc-muted mb-[13px] flex items-center gap-[6px]" aria-hidden="true"><span class="dc-dot"></span><span class="dc-dot"></span><span class="dc-dot"></span></span>
                        </div>
                    @endif
                </div>

                <form @submit.prevent="submitMessage()" class="dc-divider flex shrink-0 items-center gap-[20px] border-t p-[20px]">
                    <input type="text" @input.debounce.500ms="$wire.typing($el.value)" maxlength="1000" autocomplete="off" placeholder="Escribe un mensaje…" aria-label="Mensaje" class="dc-field min-w-0 flex-1">
                    <button type="submit" class="dc-primary shrink-0">Enviar</button>
                </form>
            @else
                {{-- Lista: búsqueda y personas (conversaciones recientes primero). Filas de 91px (foto de 50px con 20px arriba y abajo) y alto de exactamente 4 filas: nunca se corta una --}}
                <div class="dc-divider shrink-0 border-b p-[20px]">
                    <input type="search" wire:model.live.debounce.250ms="search" placeholder="Buscar por nombre o correo…" aria-label="Buscar colaborador" class="dc-field w-full">
                </div>

                <div data-scroll-on-hover x-data="backupOverlayScroll()" x-init="$nextTick(() => update())" x-effect="$wire.$get('search'); $nextTick(() => update())" class="relative h-[364px] shrink-0 overflow-hidden">
                    <div x-ref="scroller" @scroll="update()" @mouseenter="update()" class="panel-scroll h-full snap-y snap-mandatory overflow-y-auto" wire:loading.class="opacity-60" wire:target="search">
                        @forelse ($people as $row)
                            @php($person = $row['user'])
                            <button type="button" wire:key="direct-person-{{ $person->id }}" wire:click="openConversation({{ $person->id }})" class="dc-row dc-divider flex h-[91px] w-full snap-start items-center gap-[20px] border-b p-[20px] text-left">
                                <span class="relative shrink-0">
                                    <img src="{{ $person->avatarUrl() }}" alt="Foto de {{ $person->name }}" class="dc-avatar">
                                    @if ($row['online'])
                                        <span class="dc-status absolute bottom-[2px] right-[2px] h-[10px] w-[10px] rounded-full bg-emerald-500"></span>
                                    @endif
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="dc-name block truncate">{{ $fullName($person) }}</span>
                                    @if ($row['typing'])
                                        <span class="dc-green-text dc-line dc-second block">escribiendo<span class="dc-inline-dots ml-[10px]" aria-hidden="true"><span class="dc-dot"></span><span class="dc-dot"></span><span class="dc-dot"></span></span></span>
                                    @else
                                        <span class="dc-muted dc-line dc-second block truncate">{{ $row['last'] }}</span>
                                    @endif
                                </span>
                                @if ($row['unread'] > 0)
                                    <span class="dc-badge">{{ $row['unread'] }}</span>
                                @elseif ($row['at'])
                                    <span class="dc-muted shrink-0 leading-none">{{ $shortTime($row['at']) }}</span>
                                @endif
                            </button>
                        @empty
                            <p class="dc-muted px-[20px] py-[40px] text-center">{{ trim($search) !== '' ? 'No se encontraron colaboradores con «'.trim($search).'».' : 'Busca a un colaborador para empezar una conversación.' }}</p>
                        @endforelse
                    </div>
                    <span :class="visible ? '' : 'hidden'" class="panel-scroll-thumb" style="right: 5px;" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
                </div>
            @endif
        </section>
    </div>

</div>
