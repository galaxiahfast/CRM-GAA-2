<div
    class="relative"
    x-data="{ open: false, filterOpen: false, actionMenu: null }"
    wire:poll.5s.visible
    @keydown.escape.window="filterOpen ? filterOpen = false : open = false"
>
    <button
        @click="open = !open; filterOpen = false"
        wire:click="loadNotifications"
        type="button"
        class="relative flex items-center justify-center rounded-lg border border-white/60 p-[10px] text-white transition-colors duration-200 hover:border-white focus:outline-none focus:ring-2 focus:ring-white/40"
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

    <section
        x-show="open"
        @click.away="open = false; filterOpen = false; actionMenu = null"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="notification-panel absolute right-0 z-[100] mt-[23px] w-max min-w-[325px] max-w-[calc(100vw-30px)] origin-top-right overflow-visible rounded-2xl border border-zinc-200 bg-white text-[15px] shadow-[0_18px_45px_rgba(0,0,0,0.14)]"
        style="display: none;"
        aria-label="Panel de notificaciones"
    >
        <header class="rounded-t-2xl border-b border-zinc-200 bg-white p-[20px]">
            <h2 class="text-[20px] font-semibold text-black">Notificaciones</h2>
            <p class="mt-1 text-zinc-500">Eventos recientes del sistema</p>
        </header>

        @if (! $notificationsLoaded)
            <div class="rounded-b-xl px-4 py-10 text-center" aria-live="polite">
                <div class="mx-auto h-8 w-8 animate-spin rounded-full border-2 border-zinc-200 border-t-black"></div>
                <p class="mt-3 text-[12px] text-gray-500">Cargando notificaciones...</p>
            </div>
        @else
        <div class="relative space-y-[15px] border-b border-zinc-200 bg-white p-[20px]">
            <div class="flex items-center gap-[15px]">
                <button wire:click="toggleSelectAll" type="button" @disabled($notifications->isEmpty()) class="inline-flex h-10 shrink-0 items-center justify-center whitespace-nowrap rounded-xl border border-zinc-200 bg-white px-[15px] font-semibold text-black transition hover:bg-zinc-100 disabled:cursor-not-allowed disabled:text-zinc-300">
                    {{ $allVisibleSelected ? 'Quitar selección' : 'Seleccionar todo' }}
                </button>
                <button wire:click="deleteSelected" wire:confirm="¿Quieres eliminar las notificaciones seleccionadas? Esta acción no se puede deshacer." type="button" @disabled($selected === []) class="inline-flex h-10 shrink-0 items-center justify-center whitespace-nowrap rounded-xl bg-black px-[15px] font-semibold text-white transition hover:bg-zinc-800 disabled:cursor-not-allowed disabled:bg-zinc-200 disabled:text-zinc-400">
                    Eliminar
                </button>
            </div>

            <div class="flex items-center gap-[15px]">
              <div class="shrink-0" @click.away="filterOpen = false">
                <button
                    @click="filterOpen = !filterOpen"
                    type="button"
                    class="flex h-10 items-center justify-center gap-2 rounded-xl border border-zinc-200 bg-white px-[15px] font-medium text-black transition hover:bg-zinc-100"
                    :aria-expanded="filterOpen"
                >
                    Filtrar
                    @if ($filter !== 'all')
                        <span class="h-2 w-2 rounded-full bg-black"></span>
                    @endif
                </button>

                <div
                    x-show="filterOpen"
                    x-transition
                    class="absolute inset-x-0 top-full z-[120] w-full rounded-b-2xl border border-zinc-200 bg-white p-[15px] shadow-xl"
                    style="display: none;"
                >
                    @foreach ([
                        'all' => 'Todas',
                        'unread' => 'No leídas',
                        'read' => 'Leídas',
                        'system' => 'Errores del sistema',
                        'auth' => 'Inicio de sesión y seguridad',
                    ] as $filterValue => $filterLabel)
                        <button
                            wire:click="setFilter('{{ $filterValue }}')"
                            @click="filterOpen = false"
                            type="button"
                            class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-left transition {{ $filter === $filterValue ? 'bg-zinc-100 font-semibold text-black' : 'text-zinc-600 hover:bg-zinc-50' }}"
                        >
                            {{ $filterLabel }}
                            @if ($filter === $filterValue)
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                            @endif
                        </button>
                    @endforeach
                </div>
              </div>
              <button wire:click="markAllAsRead" type="button" @disabled($unreadCount === 0) class="flex h-10 shrink-0 items-center justify-center whitespace-nowrap rounded-xl border border-zinc-200 bg-white px-[15px] font-semibold text-black transition hover:bg-zinc-100 disabled:cursor-not-allowed disabled:bg-zinc-100 disabled:text-zinc-400">
                  Marcar todas como leídas
              </button>
            </div>
        </div>

        @if ($notifications->isEmpty())
            <div class="rounded-b-xl px-4 py-10 text-center">
                <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-gray-50">
                    <svg class="h-9 w-9 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                </div>
                <p class="font-medium text-gray-500">{{ $totalCount === 0 ? 'No hay notificaciones' : 'No hay resultados para este filtro' }}</p>
                <p class="mt-1 text-[12px] text-gray-400">{{ $totalCount === 0 ? 'Los avisos importantes aparecerán aquí.' : 'Prueba seleccionando otro tipo de notificación.' }}</p>
            </div>
        @else
            <div class="notification-scrollbar w-full max-h-[440px] space-y-[15px] overflow-y-auto rounded-b-2xl bg-white p-[20px] overscroll-contain" style="contain: inline-size;">
                @foreach ($notifications as $notification)
                    @php
                        $data = $notification->data;
                        $severity = $data['severity'] ?? 'info';
                        $iconTone = $notification->read_at ? 'bg-white text-zinc-400' : 'bg-zinc-100 text-black';
                        $isSelected = in_array((string) $notification->id, $selected, true);
                    @endphp
                    <article
                        wire:key="notification-{{ $notification->id }}"
                        class="relative flex w-full gap-3 rounded-xl border p-[15px] text-left {{ $isSelected ? 'border-black bg-zinc-100' : ($notification->read_at ? 'border-zinc-200 bg-zinc-50' : 'border-zinc-300 bg-white shadow-[0_3px_10px_rgba(0,0,0,0.04)]') }}"
                    >
                        @if ($selectionMode)
                            <label class="mt-1 flex h-6 w-6 shrink-0 cursor-pointer items-center justify-center">
                                <input wire:model.live="selected" value="{{ $notification->id }}" type="checkbox" class="peer sr-only">
                                <span class="flex h-5 w-5 items-center justify-center rounded-md border border-gray-300 bg-white text-transparent transition peer-checked:border-[#1a3a6b] peer-checked:bg-[#1a3a6b] peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-blue-300">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                </span>
                                <span class="sr-only">Seleccionar {{ $data['title'] ?? 'notificación' }}</span>
                            </label>
                        @else
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-zinc-200 {{ $iconTone }}">
                                @if ($severity === 'success')
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                @elseif ($severity === 'warning')
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                @elseif ($severity === 'error')
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M4.93 19h14.14a2 2 0 001.74-2.99L13.74 3.6a2 2 0 00-3.48 0L3.19 16.01A2 2 0 004.93 19z" /></svg>
                                @else
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                @endif
                            </span>
                        @endif

                        <div wire:click="markAsRead('{{ $notification->id }}')" class="min-w-0 flex-1 cursor-pointer text-left">
                            <span class="flex items-start gap-2 pr-9">
                                <span class="font-semibold {{ $notification->read_at ? 'text-gray-600' : 'text-gray-900' }}">{{ $data['title'] ?? 'Aviso del sistema' }}</span>
                                @if (! $notification->read_at)
                                    <span class="sr-only">Sin leer</span>
                                @endif
                            </span>
                            <span class="mt-1 block break-words leading-6 {{ $notification->read_at ? 'text-zinc-500' : 'text-zinc-700' }}">{{ $data['message'] ?? '' }}</span>
                            @if ($pendingFriendRequests->has((string) $notification->id))
                                <span class="mt-[15px] flex items-center gap-[10px]">
                                    <button
                                        type="button"
                                        wire:click.stop="acceptFriendRequest('{{ $notification->id }}')"
                                        wire:loading.attr="disabled"
                                        wire:target="acceptFriendRequest('{{ $notification->id }}'),cancelFriendRequest('{{ $notification->id }}')"
                                        class="inline-flex items-center justify-center rounded-xl bg-black px-[15px] py-[10px] font-medium text-white transition-colors hover:bg-zinc-800 disabled:cursor-wait disabled:opacity-50"
                                    >
                                        Aceptar
                                    </button>
                                    <button
                                        type="button"
                                        wire:click.stop="cancelFriendRequest('{{ $notification->id }}')"
                                        wire:loading.attr="disabled"
                                        wire:target="acceptFriendRequest('{{ $notification->id }}'),cancelFriendRequest('{{ $notification->id }}')"
                                        class="inline-flex items-center justify-center rounded-xl border border-zinc-200 bg-white px-[15px] py-[10px] font-medium text-black transition-colors hover:bg-zinc-100 disabled:cursor-wait disabled:opacity-50"
                                    >
                                        Cancelar
                                    </button>
                                </span>
                            @endif
                            <span class="mt-2 block text-zinc-500" title="{{ $notification->created_at?->format('d/m/Y H:i:s') }}">{{ $notification->created_at?->diffForHumans() }}</span>
                        </div>

                        @if (! $selectionMode)
                            <button
                                @click.stop="actionMenu = actionMenu === '{{ $notification->id }}' ? null : '{{ $notification->id }}'"
                                type="button"
                                class="absolute right-2.5 top-2.5 flex h-8 w-8 items-center justify-center rounded-lg text-zinc-500 transition hover:bg-zinc-100 hover:text-black"
                                aria-label="Abrir acciones de la notificación"
                                title="Más opciones"
                            >
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><circle cx="5" cy="12" r="1.7"/><circle cx="12" cy="12" r="1.7"/><circle cx="19" cy="12" r="1.7"/></svg>
                            </button>
                            <div x-show="actionMenu === '{{ $notification->id }}'" x-transition @click.outside="actionMenu = null" class="absolute right-2.5 top-11 z-[130] w-[245px] rounded-xl border border-zinc-200 bg-white p-2 shadow-xl" style="display: none;">
                                <button type="button" wire:click="markAsRead('{{ $notification->id }}')" @click="actionMenu = null" class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left font-medium text-black transition hover:bg-zinc-100">
                                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 13 4 4L19 7" /></svg>
                                    Marcar como leída
                                </button>
                                <button type="button" wire:click="deleteNotification('{{ $notification->id }}')" wire:confirm="¿Quieres eliminar esta notificación?" @click="actionMenu = null" class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left font-medium text-black transition hover:bg-zinc-100">
                                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M4 7h16" /></svg>
                                    Eliminar notificación
                                </button>
                                <button type="button" wire:click="reportProblem('{{ $notification->id }}')" @click="actionMenu = null" class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left font-medium text-black transition hover:bg-zinc-100">
                                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v4m0 4h.01M5 21h14a2 2 0 001.74-3L13.74 5a2 2 0 00-3.48 0L3.26 18A2 2 0 005 21z" /></svg>
                                    Reportar problema al equipo de notificaciones
                                </button>
                            </div>
                        @endif
                    </article>
                @endforeach
            </div>
        @endif
        @endif
    </section>
    <style>
        .notification-panel button:focus,
        .notification-panel button:focus-visible {
            outline: none !important;
            box-shadow: none !important;
        }
        .notification-panel :is([class*="text-[11px]"], [class*="text-[12px]"], [class*="text-[13px]"]) {
            font-size: 15px !important;
        }
    </style>
</div>
