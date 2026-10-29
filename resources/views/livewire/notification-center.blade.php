<div
    class="relative"
    x-data="{
        open: false,
        filterOpen: false,
        actionMenu: null,
        menuStyle: {},
        panelTop: '29px',
        placePanel() {
            const nav = document.getElementById('main-nav');
            if (! nav) { return; }
            this.panelTop = `${nav.getBoundingClientRect().bottom - this.$root.getBoundingClientRect().bottom}px`;
        },
        rowClick(id, event) {
            if (event.target.closest('[data-no-toggle]')) { return; }
            if (this.actionMenu !== null || this.filterOpen) { this.actionMenu = null; this.filterOpen = false; return; }
            this.$wire.toggleNotification(id);
        },
        toggleMenu(id, event) {
            if (this.actionMenu === id) { this.actionMenu = null; return; }
            const rect = event.currentTarget.getBoundingClientRect();
            const width = 300;
            const height = 170;
            const left = Math.max(8, Math.min(rect.right - width, window.innerWidth - width - 8));
            const top = rect.bottom + 10 + height > window.innerHeight - 8 ? Math.max(8, rect.top - 10 - height) : rect.bottom + 10;
            this.menuStyle = { top: `${top}px`, left: `${left}px` };
            this.actionMenu = id;
        }
    }"
    wire:poll.30s.visible
    @resize.window="placePanel()"
    @keydown.escape.window="filterOpen ? filterOpen = false : open = false"
>
    <button
        @click="placePanel(); open = !open; filterOpen = false"
        wire:click="loadNotifications"
        type="button"
        class="relative flex h-[42px] w-[42px] items-center justify-center rounded-lg border-0 bg-transparent p-0 text-white transition-colors duration-200 hover:bg-white/10 focus:outline-none focus:ring-0"
        aria-label="Abrir notificaciones"
        :aria-expanded="open"
    >
        <svg class="h-[20px] w-[20px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>

        @if ($unreadCount > 0)
            <span
                wire:key="notification-badge-{{ $unreadCount }}"
                @class([
                    'absolute -right-1.5 -top-1.5 flex h-5 min-w-5 items-center justify-center rounded-full bg-red-600 text-center text-[9px] font-bold leading-none text-white ring-2 ring-[#1a3a6b] tabular-nums',
                    'w-5 p-0' => $unreadCount < 10,
                    'px-1' => $unreadCount >= 10,
                ])
                aria-label="{{ $unreadCount }} notificaciones sin leer"
            ><span class="inline-block translate-y-px text-center leading-[9px]">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span></span>
        @endif
    </button>

    {{-- El panel se pega al borde inferior de la barra superior (panelTop se calcula al abrir). --}}
    <section
        x-show="open"
        @click.away="open = false; filterOpen = false; actionMenu = null"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        :style="{ marginTop: panelTop }"
        class="notification-panel absolute right-0 z-[100] origin-top-right overflow-visible rounded-b-xl border border-[#d4d4d8] bg-white text-[15px] text-black shadow-[0_18px_45px_rgba(0,0,0,0.14)]"
        style="display: none; width: min(480px, calc(100vw - 30px)); margin-top: 29px; border-top-width: 0; border-top-left-radius: 0; border-top-right-radius: 0;"
        aria-label="Panel de notificaciones"
    >
        @php
            $filterLabels = [
                'all' => 'Todas',
                'unread' => 'No leídas',
                'read' => 'Leídas',
                'system' => 'Errores del sistema',
                'auth' => 'Inicio de sesión y seguridad',
            ];
            $iconPaths = [
                'success' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
                'warning' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
                'error' => 'M12 8v4m0 4h.01M4.93 19h14.14a2 2 0 001.74-2.99L13.74 3.6a2 2 0 00-3.48 0L3.19 16.01A2 2 0 004.93 19z',
                'info' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
            ];
            $bellPath = 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9';
            $filterPath = 'M21 4h-7M10 4H3M21 12h-9M8 12H3M21 20h-5M12 20H3M14 2v4M8 10v4M16 18v4';
        @endphp

        <header class="flex items-center justify-between gap-[20px] border-b border-[#d4d4d8] bg-zinc-100 p-[20px]">
            <div class="flex min-w-0 items-center gap-[15px]">
                <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl border border-[#d4d4d8] bg-white text-black">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $bellPath }}" /></svg>
                </span>
                <div class="min-w-0">
                    <h2 class="notification-title leading-none text-[#1A3A6B]">Notificaciones</h2>
                    <p class="mt-[10px] leading-none text-black">Eventos recientes del sistema</p>
                </div>
            </div>
            @if ($notificationsLoaded)
                <span class="shrink-0 leading-none {{ $unreadCount > 0 ? 'text-[#1A3A6B]' : 'text-black' }}">{{ $unreadCount > 0 ? $unreadCount.' sin leer' : 'Todo al día' }}</span>
            @endif
        </header>

        @if (! $notificationsLoaded)
            <div class="rounded-b-xl p-[20px] py-[40px] text-center" aria-live="polite">
                <div class="mx-auto h-8 w-8 animate-spin rounded-full border-2 border-[#d4d4d8] border-t-[#1A3A6B]"></div>
                <p class="mt-[20px] leading-none text-black">Cargando notificaciones...</p>
            </div>
        @else
            {{-- Misma franja para dos usos: el filtro y, en cuanto hay alguna casilla marcada, las acciones de selección. --}}
            <div class="relative border-b border-[#d4d4d8] bg-zinc-100 p-[20px]" @click.away="filterOpen = false">
                @if ($selected === [])
                    <button
                        @click="filterOpen = !filterOpen"
                        type="button"
                        class="flex h-[44px] w-full items-center justify-between gap-[10px] rounded-xl border border-[#d4d4d8] bg-white px-[20px] text-left leading-none text-black transition-colors hover:text-[#1A3A6B]"
                        aria-haspopup="listbox"
                        :aria-expanded="filterOpen"
                    >
                        <span class="flex min-w-0 items-center gap-[10px]">
                            <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $filterPath }}" /></svg>
                            <span class="min-w-0">{{ $filterLabels[$filter] ?? 'Todas' }}</span>
                        </span>
                        <svg class="h-4 w-4 shrink-0 transition-transform" :class="filterOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" /></svg>
                    </button>

                    <div
                        x-show="filterOpen"
                        x-transition
                        class="absolute z-[120] overflow-hidden rounded-xl border border-[#d4d4d8] bg-white shadow-xl"
                        style="display: none; left: 20px; right: 20px; top: calc(100% - 10px);"
                        role="listbox"
                    >
                        @foreach ($filterLabels as $filterValue => $filterLabel)
                            <button
                                wire:click="setFilter('{{ $filterValue }}')"
                                @click="filterOpen = false"
                                type="button"
                                role="option"
                                aria-selected="{{ $filter === $filterValue ? 'true' : 'false' }}"
                                class="flex w-full items-center justify-between gap-[10px] border-b border-[#d4d4d8] bg-white p-[20px] text-left leading-none transition-colors last:border-b-0 hover:bg-zinc-50 {{ $filter === $filterValue ? 'text-[#1A3A6B]' : 'text-black' }}"
                            >
                                <span class="min-w-0">{{ $filterLabel }}</span>
                                @if ($filter === $filterValue)
                                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                @endif
                            </button>
                        @endforeach
                    </div>
                @else
                    <div class="flex h-[44px] items-center justify-between gap-[20px]">
                        <label class="flex cursor-pointer items-center gap-[10px] whitespace-nowrap leading-none text-black">
                            <input
                                wire:click="toggleSelectAll"
                                type="checkbox"
                                @checked($allVisibleSelected)
                                class="h-4 w-4 shrink-0 cursor-pointer rounded border-[#d4d4d8] text-black focus:outline-none focus:ring-0 focus:ring-offset-0"
                            >
                            <span>Seleccionar todas</span>
                        </label>
                        <button
                            wire:click="deleteSelected"
                            wire:confirm="¿Quieres eliminar las notificaciones seleccionadas? Esta acción no se puede deshacer."
                            type="button"
                            class="inline-flex items-center gap-[10px] whitespace-nowrap bg-transparent leading-none text-[#1A3A6B] transition-colors hover:text-red-600"
                        >
                            <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 7h14M9 7V4h6v3m-8 0 1 13h8l1-13M10 11v5m4-5v5" /></svg>
                            <span>Eliminar ({{ count($selected) }})</span>
                        </button>
                        <button
                            wire:click="$set('selected', [])"
                            type="button"
                            class="inline-flex items-center gap-[10px] whitespace-nowrap bg-transparent leading-none text-[#1A3A6B] transition-colors hover:text-black"
                        >
                            <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6 6 18" /></svg>
                            <span>Cancelar</span>
                        </button>
                    </div>
                @endif
            </div>

            @if ($notifications->isEmpty())
                <div class="rounded-b-xl p-[20px] py-[40px] text-center">
                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl border border-[#d4d4d8] bg-white text-black">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $bellPath }}" /></svg>
                    </span>
                    <p class="mt-[20px] leading-none text-[#1A3A6B]">{{ $totalCount === 0 ? 'No hay notificaciones' : 'No hay resultados para este filtro' }}</p>
                    <p class="mt-[10px] leading-[20px] text-black">{{ $totalCount === 0 ? 'Los avisos importantes aparecerán aquí.' : 'Prueba seleccionando otro tipo de notificación.' }}</p>
                </div>
            @else
                <div x-data="backupOverlayScroll()" x-init="$nextTick(() => update())" class="relative">
                    <div x-ref="scroller" @scroll="update(); actionMenu = null" @mouseenter="update()" class="notification-scroll overflow-y-auto overscroll-contain" style="max-height: min(408px, max(136px, calc(100dvh - 356px)));">
                        @foreach ($notifications as $notification)
                            @php
                                $data = $notification->data;
                                $severity = $data['severity'] ?? 'info';
                                $isUnread = ! $notification->read_at;
                                $isSelected = in_array((string) $notification->id, $selected, true);
                                $iconColor = $severity === 'error' ? 'text-red-600' : ($isUnread ? 'text-[#1A3A6B]' : 'text-black');
                            @endphp
                            {{-- Clic en la fila = se selecciona (aparece su check) y, si estaba sin leer, se marca como leída. Lo que lleva data-no-toggle (botones y menú) no la selecciona. --}}
                            <article
                                wire:key="notification-{{ $notification->id }}"
                                @click="rowClick('{{ $notification->id }}', $event)"
                                class="flex cursor-pointer select-none items-center gap-[20px] border-b border-[#d4d4d8] p-[20px] transition-colors {{ $isSelected ? 'bg-zinc-50 notification-row-selected' : 'bg-white hover:bg-zinc-50' }}"
                                style="height: 136px;"
                            >
                                <span class="flex h-[20px] w-[20px] shrink-0 items-center justify-center">
                                    @if ($isSelected)
                                        <input type="checkbox" checked tabindex="-1" aria-hidden="true" class="pointer-events-none h-4 w-4 shrink-0 rounded border-[#d4d4d8] text-black focus:outline-none focus:ring-0 focus:ring-offset-0">
                                        <span class="sr-only">Seleccionada</span>
                                    @else
                                        <svg class="h-4 w-4 shrink-0 {{ $iconColor }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconPaths[$severity] ?? $iconPaths['info'] }}" /></svg>
                                    @endif
                                </span>

                                <div class="min-w-0 flex-1 text-left">
                                    <div class="flex items-center gap-[10px]">
                                        <span class="block min-w-0 truncate leading-[20px] {{ $isUnread ? 'text-[#1A3A6B]' : 'text-black' }}" title="{{ $data['title'] ?? 'Aviso del sistema' }}">{{ $data['title'] ?? 'Aviso del sistema' }}</span>
                                        @if ($isUnread)
                                            <span class="h-[10px] w-[10px] shrink-0 rounded-full bg-[#1A3A6B] notification-dot" aria-hidden="true"></span>
                                            <span class="sr-only">Sin leer</span>
                                        @endif
                                    </div>
                                    <p class="notification-message mt-[10px] text-black" title="{{ $data['message'] ?? '' }}">{{ $data['message'] ?? '' }}</p>
                                    <div class="mt-[10px] flex items-center justify-between gap-[20px]">
                                        <p class="whitespace-nowrap leading-none text-black" title="{{ $notification->created_at?->format('d/m/Y H:i:s') }}">{{ $notification->created_at?->diffForHumans() }}</p>
                                        @if ($pendingFriendRequests->has((string) $notification->id))
                                            <div data-no-toggle class="flex shrink-0 items-center gap-[20px]">
                                                <button
                                                    type="button"
                                                    wire:click="acceptFriendRequest('{{ $notification->id }}')"
                                                    wire:loading.attr="disabled"
                                                    wire:target="acceptFriendRequest('{{ $notification->id }}'),cancelFriendRequest('{{ $notification->id }}')"
                                                    class="inline-flex items-center gap-[10px] whitespace-nowrap bg-transparent leading-none text-[#1A3A6B] transition-colors hover:text-black disabled:cursor-wait disabled:opacity-40"
                                                    style="margin: -10px 0; padding: 10px 0;"
                                                >
                                                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                                    <span>Aceptar</span>
                                                </button>
                                                <button
                                                    type="button"
                                                    wire:click="cancelFriendRequest('{{ $notification->id }}')"
                                                    wire:loading.attr="disabled"
                                                    wire:target="acceptFriendRequest('{{ $notification->id }}'),cancelFriendRequest('{{ $notification->id }}')"
                                                    class="inline-flex items-center gap-[10px] whitespace-nowrap bg-transparent leading-none text-black transition-colors hover:text-red-600 disabled:cursor-wait disabled:opacity-40"
                                                    style="margin: -10px 0; padding: 10px 0;"
                                                >
                                                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6 6 18" /></svg>
                                                    <span>Rechazar</span>
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <div data-no-toggle class="shrink-0" style="margin-right: -14px;">
                                    <button
                                        @click.stop="toggleMenu('{{ $notification->id }}', $event)"
                                        type="button"
                                        class="flex h-[44px] w-[44px] items-center justify-center rounded-xl text-black transition-colors hover:bg-zinc-100 hover:text-[#1A3A6B]"
                                        aria-label="Abrir acciones de la notificación"
                                        title="Más opciones"
                                    >
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="5" cy="12" r="1.7"/><circle cx="12" cy="12" r="1.7"/><circle cx="19" cy="12" r="1.7"/></svg>
                                    </button>
                                    <div x-show="actionMenu === '{{ $notification->id }}'" x-transition.opacity @click.outside="actionMenu = null" :style="menuStyle" class="fixed z-[130] overflow-hidden rounded-xl border border-[#d4d4d8] bg-white shadow-xl" style="display: none; width: 300px;">
                                        @if ($isUnread)
                                            <button type="button" wire:click="markAsRead('{{ $notification->id }}')" @click="actionMenu = null" class="flex w-full items-center gap-[10px] border-b border-[#d4d4d8] bg-white p-[20px] text-left leading-none text-black transition-colors hover:bg-zinc-50 hover:text-[#1A3A6B]">
                                                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                                <span>Marcar como leída</span>
                                            </button>
                                        @else
                                            <button type="button" wire:click="markAsUnread('{{ $notification->id }}')" @click="actionMenu = null" class="flex w-full items-center gap-[10px] border-b border-[#d4d4d8] bg-white p-[20px] text-left leading-none text-black transition-colors hover:bg-zinc-50 hover:text-[#1A3A6B]">
                                                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l9 6 9-6M5 5h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z" /></svg>
                                                <span>Marcar como no leída</span>
                                            </button>
                                        @endif
                                        <button type="button" wire:click="reportProblem('{{ $notification->id }}')" @click="actionMenu = null" title="Reportar problema al equipo de notificaciones" class="flex w-full items-center gap-[10px] border-b border-[#d4d4d8] bg-white p-[20px] text-left leading-none text-black transition-colors hover:bg-zinc-50 hover:text-[#1A3A6B]">
                                            <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M5 21h14a2 2 0 001.74-3L13.74 5a2 2 0 00-3.48 0L3.26 18A2 2 0 005 21z" /></svg>
                                            <span>Reportar problema</span>
                                        </button>
                                        <button type="button" wire:click="deleteNotification('{{ $notification->id }}')" wire:confirm="¿Quieres eliminar esta notificación?" @click="actionMenu = null" class="flex w-full items-center gap-[10px] bg-white p-[20px] text-left leading-none text-black transition-colors hover:bg-zinc-50 hover:text-red-600">
                                            <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 7h14M9 7V4h6v3m-8 0 1 13h8l1-13M10 11v5m4-5v5" /></svg>
                                            <span>Eliminar notificación</span>
                                        </button>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                    <span x-show="visible" x-cloak class="notification-scroll-thumb" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
                </div>

                <footer class="rounded-b-xl bg-zinc-100 p-[20px] leading-none text-black">
                    {{ $notifications->count() }} de {{ $totalCount }} {{ $totalCount === 1 ? 'notificación' : 'notificaciones' }}
                </footer>
            @endif
        @endif
    </section>
    <style>
        /* Una sola letra para todo el panel: 15px y peso normal, sin negritas. */
        .notification-panel :is(h2, p, span, label, button, a, footer, input) {
            font-size: 15px;
            font-weight: 400;
        }

        /* Mensaje de cada notificación: siempre el mismo alto (2 líneas), "..." al final si no cabe y texto justificado. */
        /* Título del panel: igual que el de la página (20px), único texto más grande. */
        .notification-panel h2.notification-title {
            font-size: 20px;
            font-weight: 400;
            letter-spacing: -0.025em;
            line-height: 1;
        }

        .notification-panel .notification-message {
            display: -webkit-box;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            height: 40px;
            overflow: hidden;
            overflow-wrap: break-word;
            line-height: 20px;
            text-align: justify;
            /* Reparte el espacio sobrante entre las letras y no en un solo hueco grande entre palabras. */
            text-justify: inter-character;
        }

        .notification-panel button:focus,
        .notification-panel button:focus-visible {
            outline: none !important;
            box-shadow: none !important;
        }

        .notification-panel input[type="checkbox"]:focus,
        .notification-panel input[type="checkbox"]:focus-visible {
            outline: none !important;
            box-shadow: none !important;
        }

        /* Scroll de la lista: una línea azul delgada, sin flechas. */
        .notification-scroll {
            scrollbar-width: none;
            scroll-snap-type: y mandatory;
        }

        .notification-scroll > article {
            scroll-snap-align: start;
        }

        .notification-scroll::-webkit-scrollbar {
            width: 0;
            height: 0;
        }

        .notification-scroll-thumb {
            position: absolute;
            right: 0;
            top: 0;
            z-index: 20;
            width: 3px;
            min-height: 32px;
            border-radius: 9999px;
            background: #1A3A6B;
            pointer-events: none;
        }
    </style>
</div>
