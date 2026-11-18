@php
    $tz = config('support.timezone', 'America/Mexico_City');
    $fullName = fn ($person) => trim($person->name.' '.$person->last_name);
    $shortName = fn ($person) => trim(\Illuminate\Support\Str::before((string) $person->name, ' ').' '.\Illuminate\Support\Str::before((string) $person->last_name, ' '));
    $initials = fn ($person) => mb_strtoupper(mb_substr($person->name, 0, 1).mb_substr($person->last_name ?? '', 0, 1));
    $dayLabel = function ($date) use ($tz) {
        $date = $date->copy()->timezone($tz);
        $today = now($tz);

        return $date->isSameDay($today) ? 'Hoy' : ($date->isSameDay($today->copy()->subDay()) ? 'Ayer' : ucfirst($date->locale('es')->isoFormat('dddd D [de] MMMM')));
    };
    $shortTime = function ($date) use ($tz) {
        if (! $date) {
            return '';
        }
        $date = $date->copy()->timezone($tz);

        return $date->isSameDay(now($tz)) ? $date->format('H:i') : $date->format('d/m');
    };
@endphp

{{-- Chat flotante: abajo a la derecha en todas las pantallas (a 50px del borde), se minimiza y se expande. Mismo diseño que el resto de los
     módulos. Minimizado muestra los mensajes nuevos y quién está escribiendo; expandido, filas exactas y la conversación. --}}
<div
    data-panel
    data-productivity
    data-direct-chat
    class="no-print fixed z-[160] flex justify-end"
    style="right: calc(50px * var(--app-zoom, 1)); bottom: 0; min-height: 0; background: transparent"
    @if ($expanded) wire:poll.2s.visible @else wire:poll.5s.visible @endif
    x-data="{
        open: $wire.entangle('expanded').live,
        focusInput() { this.$nextTick(() => this.$root.querySelector('form input')?.focus()); },
    }"
    x-effect="if (open) { $dispatch('direct-chat-sent'); focusInput(); }"
    @click.outside="if (open) open = false"
    @keydown.escape.window="if (open) open = false"
    @visibilitychange.document="if (document.hidden && open) open = false"
>
    <x-panel-styles />

    <div class="app-zoom-overlay flex flex-col items-end">

    <x-error-toast :wire:key="'direct-chat-error-'.$errorSeq" title="No se pudo enviar" :message="$errors->first('message')" />

        {{-- Minimizado: avisos de mensajes nuevos y de quién está escribiendo, y la barra «Mensajes» --}}
        <div x-show="! open" @if ($expanded) style="display: none;" @endif x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="flex flex-col items-end">
        @if ($previews->isNotEmpty())
            <div class="mb-[10px] flex flex-col items-end gap-[10px]">
                @foreach ($previews as $preview)
                    @php($previewUser = $preview['user'])
                    <button type="button" wire:key="direct-preview-{{ $previewUser->id }}" wire:click="openConversation({{ $previewUser->id }})" class="dc-preview panel-surface panel-border flex w-[320px] max-w-[calc(100vw-20px)] items-center gap-[15px] rounded-xl border p-[15px] text-left shadow-[0_8px_24px_rgba(0,0,0,0.18)]">
                        @if ($previewUser->hasProfilePhotoFile())
                            <img src="{{ $previewUser->profile_photo_url }}" alt="Foto de {{ $previewUser->name }}" class="panel-border h-10 w-10 shrink-0 rounded-full border object-cover">
                        @else
                            <span class="panel-subtle panel-border grid h-10 w-10 shrink-0 place-items-center rounded-full border">{{ $initials($previewUser) }}</span>
                        @endif
                        <span class="min-w-0 flex-1">
                            <span class="panel-accent block truncate leading-[30px]">{{ $fullName($previewUser) }}</span>
                            @if ($preview['typing'])
                                <span class="flex items-end gap-[10px] leading-[30px]"><span>escribiendo</span><span class="mb-[13px] flex items-center gap-[6px]" aria-hidden="true"><span class="dc-dot"></span><span class="dc-dot"></span><span class="dc-dot"></span></span></span>
                            @else
                                <span class="block truncate leading-[30px]">{{ $preview['last'] }}</span>
                            @endif
                        </span>
                        @if ($preview['unread'] > 0)
                            <span class="dc-badge">{{ $preview['unread'] }}</span>
                        @endif
                    </button>
                @endforeach
            </div>
        @endif

        <button type="button" @click="open = true" class="panel-surface panel-border flex w-[300px] max-w-[calc(100vw-20px)] items-center justify-between gap-[20px] rounded-t-xl border border-b-0 px-[20px] py-[15px] text-left leading-none shadow-[0_8px_24px_rgba(0,0,0,0.18)]" aria-label="Abrir mensajes" title="Abrir mensajes">
            <span class="panel-accent leading-none">Mensajes</span>
            @if ($unreadTotal > 0)
                <span class="dc-badge">{{ $unreadTotal }}</span>
            @endif
        </button>
        </div>

        {{-- Expandido: sube desde abajo al abrir y baja al minimizar --}}
        <section x-show="open" @if (! $expanded) style="display: none;" @endif x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-full" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-full" class="panel-surface panel-border flex h-[512px] max-h-[calc((100dvh-123px)/var(--app-zoom,1))] w-[360px] max-w-[calc(100vw-20px)] flex-col overflow-hidden rounded-t-xl border border-b-0 shadow-[0_18px_55px_rgba(0,0,0,0.30)]" aria-label="Mensajes">
            <header class="panel-header panel-border flex h-[70px] shrink-0 items-center justify-between gap-[20px] border-b px-[20px]">
                @if ($activeUser)
                    <div class="flex min-w-0 items-center gap-[15px]">
                        <span class="relative shrink-0">
                            @if ($activeUser->hasProfilePhotoFile())
                                <img src="{{ $activeUser->profile_photo_url }}" alt="Foto de {{ $activeUser->name }}" class="panel-border h-10 w-10 rounded-full border object-cover">
                            @else
                                <span class="panel-subtle panel-border grid h-10 w-10 place-items-center rounded-full border">{{ $initials($activeUser) }}</span>
                            @endif
                            <span class="dc-status absolute bottom-0 right-0 h-[10px] w-[10px] rounded-full {{ $activeOnline ? 'bg-emerald-500' : 'bg-gray-400' }}"></span>
                        </span>
                        <div class="min-w-0">
                            <p class="panel-accent truncate leading-none">{{ $fullName($activeUser) }}</p>
                            <p class="dc-sub mt-[5px] truncate">{{ $activeOnline ? 'En línea' : 'Desconectado' }}</p>
                        </div>
                    </div>
                @else
                    <p class="panel-accent leading-none">Mensajes</p>
                @endif
                <div class="flex shrink-0 items-center gap-[20px]">
                    @if ($activeUser)
                        <button type="button" wire:click="closeConversation" class="dc-link leading-none">Todas</button>
                    @endif
                    <button type="button" @click="open = false" class="dc-link leading-none">Minimizar</button>
                </div>
            </header>

            @if ($activeUser)
                {{-- Conversación --}}
                <div data-scroll-on-hover
                    x-data="{ ...backupOverlayScroll(), stick: true, end() { const box = this.$refs.scroller; if (box) { box.scrollTop = box.scrollHeight; this.update(); } } }"
                    x-init="$nextTick(() => { end(); new MutationObserver(() => { if (stick) { end(); } }).observe($refs.scroller, { childList: true, subtree: true }); })"
                    x-on:direct-chat-sent.window="stick = true; $nextTick(() => end())"
                    class="relative min-h-0 flex-1 overflow-hidden">
                    <div x-ref="scroller" @scroll="update(); stick = $el.scrollHeight - $el.scrollTop - $el.clientHeight < 140" @mouseenter="update()" class="panel-scroll flex h-full flex-col gap-[10px] overflow-y-auto px-[20px] pb-[60px] pt-[20px]">
                        @php($lastDay = null)
                        @forelse ($messages as $item)
                            @php($mine = $item->sender_id === auth()->id())
                            @php($day = $dayLabel($item->created_at))
                            @if ($day !== $lastDay)
                                @php($lastDay = $day)
                                <p class="py-[10px] text-center leading-none">{{ $day }}</p>
                            @endif
                            <div wire:key="direct-message-{{ $item->id }}" class="flex {{ $mine ? 'justify-end' : 'justify-start' }}">
                                <div class="dc-bubble {{ $mine ? 'dc-mine' : 'panel-subtle' }} panel-border max-w-[80%] rounded-xl border px-[15px] py-[10px]" title="{{ $item->created_at->timezone($tz)->format('d/m/Y H:i') }}">
                                    <p class="whitespace-pre-wrap break-words leading-[30px]">{{ $item->body }}</p>
                                    <p class="text-right leading-[20px]">{{ $item->created_at->timezone($tz)->format('H:i') }}{{ $mine && $item->read_at ? ' · Visto' : '' }}</p>
                                </div>
                            </div>
                        @empty
                            <p class="py-[40px] text-center leading-[30px]">Todavía no hay mensajes. Escribe el primero a {{ $shortName($activeUser) }}.</p>
                        @endforelse
                    </div>
                    <span :class="visible ? '' : 'hidden'" class="panel-scroll-thumb" style="right: 5px;" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>

                    {{-- Quién está escribiendo: encima del final de la lista (no empuja nada; el relleno de abajo deja libre ese espacio) --}}
                    @if ($activeTyping)
                        <div class="panel-surface pointer-events-none absolute bottom-0 left-0 right-0 z-10 flex h-[50px] items-end gap-[10px] px-[20px] pb-[10px]" role="status" aria-live="polite">
                            <span class="truncate leading-[30px]">{{ $shortName($activeUser) }} está escribiendo</span>
                            <span class="mb-[13px] flex items-center gap-[6px]" aria-hidden="true"><span class="dc-dot"></span><span class="dc-dot"></span><span class="dc-dot"></span></span>
                        </div>
                    @endif
                </div>

                <form wire:submit="send" class="panel-border flex shrink-0 items-center gap-[15px] border-t p-[20px]">
                    <input type="text" wire:model.live.debounce.500ms="message" maxlength="1000" autocomplete="off" placeholder="Escribe un mensaje…" class="dc-field min-w-0 flex-1">
                    <button type="submit" wire:loading.attr="disabled" wire:target="send" class="dc-primary shrink-0 rounded-xl px-[20px] py-[15px] leading-none disabled:cursor-wait disabled:opacity-60">Enviar</button>
                </form>
            @else
                {{-- Lista: búsqueda y personas (conversaciones recientes primero). Filas de 70px y alto de exactamente 5 filas: nunca se corta una --}}
                <div class="panel-border shrink-0 border-b p-[20px]">
                    <input type="search" wire:model.live.debounce.300ms="search" placeholder="Buscar colaborador…" class="dc-field w-full">
                </div>

                <div data-scroll-on-hover x-data="backupOverlayScroll()" x-init="$nextTick(() => update())" x-effect="$wire.$get('search'); $nextTick(() => update())" class="relative h-[350px] shrink-0 overflow-hidden">
                    <div x-ref="scroller" @scroll="update()" @mouseenter="update()" class="panel-scroll h-full snap-y snap-mandatory overflow-y-auto">
                        @forelse ($people as $row)
                            @php($person = $row['user'])
                            <button type="button" wire:key="direct-person-{{ $person->id }}" wire:click="openConversation({{ $person->id }})" class="dc-row panel-border flex h-[70px] w-full snap-start items-center gap-[15px] border-b px-[20px] text-left">
                                <span class="relative shrink-0">
                                    @if ($person->hasProfilePhotoFile())
                                        <img src="{{ $person->profile_photo_url }}" alt="Foto de {{ $person->name }}" class="panel-border h-10 w-10 rounded-full border object-cover">
                                    @else
                                        <span class="panel-subtle panel-border grid h-10 w-10 place-items-center rounded-full border">{{ $initials($person) }}</span>
                                    @endif
                                    @if ($row['online'])
                                        <span class="dc-status absolute bottom-0 right-0 h-[10px] w-[10px] rounded-full bg-emerald-500"></span>
                                    @endif
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate leading-[30px]">{{ $fullName($person) }}</span>
                                    @if ($row['typing'])
                                        <span class="dc-green-text flex items-end gap-[10px] leading-[30px]"><span>escribiendo</span><span class="mb-[13px] flex items-center gap-[6px]" aria-hidden="true"><span class="dc-dot"></span><span class="dc-dot"></span><span class="dc-dot"></span></span></span>
                                    @else
                                        <span class="block truncate leading-[30px]">{{ $row['last'] }}</span>
                                    @endif
                                </span>
                                @if ($row['unread'] > 0)
                                    <span class="dc-badge">{{ $row['unread'] }}</span>
                                @elseif ($row['at'])
                                    <span class="shrink-0 leading-none">{{ $shortTime($row['at']) }}</span>
                                @endif
                            </button>
                        @empty
                            <p class="px-[20px] py-[40px] text-center leading-[30px]">{{ trim($search) !== '' ? 'No se encontraron colaboradores.' : 'Busca a un colaborador para empezar una conversación.' }}</p>
                        @endforelse
                    </div>
                    <span :class="visible ? '' : 'hidden'" class="panel-scroll-thumb" style="right: 5px;" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
                </div>
            @endif
        </section>
    </div>

    <style>
        [data-direct-chat] { --dc-green: #15803d; --panel-muted: var(--panel-text); }
        html.module-dark-theme [data-direct-chat] { --dc-green: #4ade80; }
        [data-direct-chat] * { font-weight: 400; }
        [data-direct-chat] p { line-height: 30px; }
        [data-direct-chat] p.dc-sub { line-height: 35px; margin-bottom: -10px; }
        [data-direct-chat] .dc-link { color: var(--dc-green); transition: opacity 150ms ease; }
        [data-direct-chat] .dc-link:hover { opacity: .75; }
        [data-direct-chat] .dc-green-text { color: var(--dc-green); }
        [data-direct-chat] .dc-badge {
            display: inline-flex;
            min-width: 24px;
            height: 24px;
            flex-shrink: 0;
            align-items: center;
            justify-content: center;
            border-radius: 9999px;
            padding: 0 8px;
            background-color: var(--dc-green);
            color: #ffffff;
            line-height: 1;
        }
        [data-direct-chat] .dc-primary {
            border: 1px solid var(--dc-green);
            background-color: color-mix(in srgb, var(--dc-green) 12%, var(--panel-surface));
            color: var(--dc-green);
            transition: background-color 150ms ease;
        }
        [data-direct-chat] .dc-primary:hover { background-color: color-mix(in srgb, var(--dc-green) 20%, var(--panel-surface)); }
        [data-direct-chat] .dc-field {
            display: block;
            height: 50px;
            border-radius: 12px;
            border: 1px solid var(--panel-border);
            background-color: var(--panel-surface);
            color: var(--panel-text);
            padding: 0 20px;
        }
        [data-direct-chat] .dc-field::placeholder { color: var(--panel-muted); opacity: .6; }
        [data-direct-chat] .dc-row:hover, [data-direct-chat] .dc-preview:hover { background-color: var(--panel-hover); }
        [data-direct-chat] .dc-mine { background-color: color-mix(in srgb, var(--dc-green) 12%, var(--panel-surface)); border-color: var(--dc-green); }
        [data-direct-chat] .dc-status { box-shadow: 0 0 0 2px var(--panel-surface); }
        [data-direct-chat] :is(button, a, input):focus,
        [data-direct-chat] :is(button, a, input):focus-visible { outline: none !important; box-shadow: none !important; }
        [data-direct-chat] .dc-field:focus, [data-direct-chat] .dc-field:focus-visible { border-color: var(--panel-border) !important; }
        /* Puntitos de «escribiendo» (los mismos del Chat de soporte) */
        [data-direct-chat] .dc-dot { width: 4px; height: 4px; border-radius: 9999px; background: currentColor; animation: dc-typing 1.2s infinite ease-in-out; }
        [data-direct-chat] .dc-dot:nth-child(2) { animation-delay: 0.18s; }
        [data-direct-chat] .dc-dot:nth-child(3) { animation-delay: 0.36s; }
        @keyframes dc-typing { 0%, 60%, 100% { transform: translateY(0); opacity: 0.35; } 30% { transform: translateY(-5px); opacity: 1; } }
        @media (prefers-reduced-motion: reduce) { [data-direct-chat] .dc-dot { animation-duration: 2.4s; } }
        @media (max-width: 639px) { [data-direct-chat] { right: 10px !important; } }
        @media print { [data-direct-chat] { display: none !important; } }
    </style>
</div>
