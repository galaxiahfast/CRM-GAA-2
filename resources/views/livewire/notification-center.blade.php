<div
    class="relative"
    x-data="{ open: false, filterOpen: false }"
    wire:poll.30s.visible
    @keydown.escape.window="filterOpen ? filterOpen = false : open = false"
>
    <button
        @click="open = !open; filterOpen = false"
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
        @click.away="open = false; filterOpen = false"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute right-0 z-[100] mt-[23px] w-[min(400px,calc(100vw-30px))] origin-top-right overflow-visible rounded-xl border border-gray-200 bg-white text-[15px] shadow-xl"
        style="display: none;"
        aria-label="Panel de notificaciones"
    >
        <header class="flex items-center justify-between rounded-t-xl border-b border-gray-100 bg-white px-4 py-3.5">
            <div>
                <h2 class="font-semibold text-gray-800">Notificaciones</h2>
                <p class="mt-0.5 text-[12px] text-gray-500">Eventos recientes del sistema</p>
            </div>

            @if ($unreadCount > 0 && ! $selectionMode)
                <button wire:click="markAllAsRead" type="button" class="max-w-[145px] rounded-lg px-2 py-1 text-right text-[12px] font-medium leading-4 text-[#1a3a6b] transition hover:bg-blue-50">
                    Marcar todas como leídas
                </button>
            @endif
        </header>

        <div class="relative flex min-h-[58px] flex-wrap items-center justify-between gap-2 border-b border-gray-100 bg-white px-3 py-2.5">
            <div class="relative" @click.away="filterOpen = false">
                <button
                    @click="filterOpen = !filterOpen"
                    type="button"
                    class="flex items-center gap-2 rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-[13px] font-medium text-gray-700 transition hover:border-gray-300 hover:bg-gray-100"
                    :aria-expanded="filterOpen"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 01.8 1.6L15 12.333V19a1 1 0 01-.553.894l-4 2A1 1 0 019 21v-8.667L3.2 4.6A1 1 0 013 4z" /></svg>
                    Filtrar
                    @if ($filter !== 'all')
                        <span class="h-2 w-2 rounded-full bg-[#1a3a6b]"></span>
                    @endif
                </button>

                <div
                    x-show="filterOpen"
                    x-transition
                    class="absolute left-0 top-[46px] z-[120] w-56 rounded-xl border border-gray-200 bg-white p-2 shadow-xl"
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
                            class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-[13px] transition {{ $filter === $filterValue ? 'bg-blue-50 font-semibold text-[#1a3a6b]' : 'text-gray-600 hover:bg-gray-50' }}"
                        >
                            {{ $filterLabel }}
                            @if ($filter === $filterValue)
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>

            @if ($selectionMode)
                <div class="ml-auto flex items-center gap-1.5">
                    <button wire:click="toggleSelectAll" type="button" class="flex items-center gap-2 whitespace-nowrap rounded-xl px-2 py-2 text-[12px] font-medium text-gray-600 transition hover:bg-gray-50">
                        <span class="flex h-5 w-5 items-center justify-center rounded-md border {{ $allVisibleSelected ? 'border-[#1a3a6b] bg-[#1a3a6b] text-white' : 'border-gray-300 bg-white text-transparent' }}">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                        </span>
                        Seleccionar todo
                    </button>
                    <button
                        wire:click="deleteSelected"
                        type="button"
                        @disabled($selected === [])
                        class="flex h-9 w-9 items-center justify-center rounded-xl text-red-600 transition hover:bg-red-50 disabled:cursor-not-allowed disabled:text-gray-300 disabled:hover:bg-transparent"
                        aria-label="Eliminar seleccionadas"
                        title="Eliminar seleccionadas"
                    >
                        <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                    </button>
                    <button wire:click="toggleSelectionMode" type="button" class="rounded-lg px-2 py-2 text-[12px] font-medium text-gray-500 hover:bg-gray-50">Cancelar</button>
                </div>
            @else
                <button wire:click="toggleSelectionMode" type="button" @disabled($notifications->isEmpty()) class="rounded-xl px-3 py-2 text-[13px] font-medium text-[#1a3a6b] transition hover:bg-blue-50 disabled:cursor-not-allowed disabled:text-gray-300 disabled:hover:bg-transparent">
                    Seleccionar
                </button>
            @endif
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
            <div class="notification-scrollbar max-h-[440px] space-y-2 overflow-y-auto rounded-b-xl bg-gray-50/70 p-3 overscroll-contain">
                @foreach ($notifications as $notification)
                    @php
                        $data = $notification->data;
                        $severity = $data['severity'] ?? 'info';
                        $tone = match ($severity) {
                            'success' => 'bg-emerald-50 text-emerald-600',
                            'warning' => 'bg-amber-50 text-amber-600',
                            'error' => 'bg-red-50 text-red-600',
                            default => 'bg-blue-50 text-blue-600',
                        };
                        $iconTone = $notification->read_at ? 'bg-white/80 text-gray-400' : $tone;
                        $isSelected = in_array((string) $notification->id, $selected, true);
                    @endphp
                    <article
                        wire:key="notification-{{ $notification->id }}"
                        class="relative flex w-full gap-2.5 rounded-xl border p-3 text-left {{ $isSelected ? 'border-[#1a3a6b] bg-blue-50/70 shadow-sm' : ($notification->read_at ? 'border-gray-200 bg-gray-100/90 shadow-none' : 'border-blue-200 bg-white shadow-sm') }}"
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
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $iconTone }}">
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

                        <button wire:click="markAsRead('{{ $notification->id }}')" type="button" class="min-w-0 flex-1 text-left">
                            <span class="flex items-start gap-2 pr-7">
                                <span class="font-semibold {{ $notification->read_at ? 'text-gray-600' : 'text-gray-900' }}">{{ $data['title'] ?? 'Aviso del sistema' }}</span>
                                @if (! $notification->read_at)
                                    <span class="sr-only">Sin leer</span>
                                @endif
                            </span>
                            <span class="mt-1 block break-words text-[13px] leading-5 {{ $notification->read_at ? 'text-gray-500' : 'text-gray-600' }}">{{ $data['message'] ?? '' }}</span>
                            <span class="mt-2 block text-[11px] {{ $notification->read_at ? 'text-gray-400' : 'text-gray-500' }}" title="{{ $notification->created_at?->format('d/m/Y H:i:s') }}">{{ $notification->created_at?->diffForHumans() }}</span>
                        </button>

                        @if (! $selectionMode)
                            <button
                                wire:click="deleteNotification('{{ $notification->id }}')"
                                type="button"
                                class="absolute right-2.5 top-2.5 flex h-7 w-7 items-center justify-center rounded-lg text-gray-400 transition hover:bg-red-50 hover:text-red-600"
                                aria-label="Eliminar notificación"
                                title="Eliminar"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            </button>
                        @endif
                    </article>
                @endforeach
            </div>
        @endif
    </section>
</div>
