{{-- Resumen anual del cliente con el mismo formato de los formularios del panel. --}}
<div>
    @teleport('body')
        <div x-data="{ open: true, pressedOnBackdrop: false }" x-show="open" x-on:keydown.escape.window="open = false; $wire.closeAnnualReport()"
            x-on:pointerdown.self="pressedOnBackdrop = true" x-on:pointerdown="if ($event.target !== $el) pressedOnBackdrop = false"
            class="fixed inset-0 z-[200001] flex items-center justify-center p-[20px] backdrop-blur-sm" style="background: rgba(0, 0, 0, 0.45);"
            role="dialog" aria-modal="true" aria-labelledby="annual-report-title"
            x-on:click.self="if (pressedOnBackdrop) { open = false; $wire.closeAnnualReport() } pressedOnBackdrop = false">
            <x-panel-styles />
            <style>
                .annual-report-modal .annual-close-button { --annual-green: #15803d; border: 1px solid var(--annual-green); background: transparent; color: var(--annual-green); }
                html.module-dark-theme .annual-report-modal .annual-close-button { --annual-green: #4ade80; }
                .annual-report-modal .annual-close-button:hover { background: color-mix(in srgb, var(--annual-green) 8%, transparent); }
            </style>
            <div data-panel class="annual-report-modal panel-surface panel-border flex h-[min(620px,calc(100dvh-40px))] w-full max-w-[620px] flex-col overflow-hidden rounded-xl border shadow-2xl">
                @if ($notFound)
                    <div class="p-[20px]"><x-not-found></x-not-found></div>
                @else
                    @php $uniqueServiceIds = $customer->services->pluck('service_id')->unique(); @endphp

                    <header class="panel-surface panel-border flex items-start justify-between gap-[20px] border-b p-[20px]">
                        <div class="min-w-0">
                            <h2 id="annual-report-title" class="panel-text flex items-center gap-[10px] leading-none">
                                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 3v3m8-3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z" /></svg>
                                <span>Desglose anual {{ $selectedYear }}</span>
                            </h2>
                            <p class="mt-[15px] truncate leading-none" title="{{ $customer->name }}">{{ mb_strtoupper((string) ($customer->name ?? 'Sin cliente')) }}</p>
                            <p class="mt-[15px] leading-none">RFC: {{ mb_strtoupper((string) $customer->rfc) }}</p>
                        </div>
                        <button type="button" x-on:click="open = false; $wire.closeAnnualReport()" aria-label="Cerrar"
                            class="panel-icon-button inline-flex h-[30px] w-[30px] shrink-0 items-center justify-center rounded-lg focus:outline-none focus:ring-0">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                        </button>
                    </header>

                    <div x-data="backupOverlayScroll()" x-init="$nextTick(() => { const content = $refs.scroller.firstElementChild; if (content) new ResizeObserver(() => update()).observe(content); })" class="panel-surface relative flex min-h-0 flex-1 flex-col">
                    <div x-ref="scroller" @scroll="update()" @mouseenter="update()" class="panel-scroll min-h-0 flex-1 overflow-y-auto p-[20px]">
                        @if ($customer->services->isNotEmpty())
                            <div class="flex flex-col gap-[20px]">
                                @foreach ($months as $month)
                                    <section x-data="{ open: false }" class="panel-border overflow-hidden rounded-xl border">
                                        <button type="button" x-on:click="open = !open" :aria-expanded="open"
                                            class="panel-surface panel-text flex w-full items-center justify-between gap-[10px] p-[20px] text-left leading-none focus:outline-none focus:ring-0">
                                            <span>{{ $month['name'] }}</span>
                                            <svg class="h-4 w-4 shrink-0 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m6 9 6 6 6-6" /></svg>
                                        </button>

                                        <div x-show="open" x-cloak class="panel-border grid grid-cols-1 gap-[20px] border-t p-[20px] sm:grid-cols-2">
                                            @foreach ($uniqueServiceIds as $serviceId)
                                                @php
                                                    $inService = $this->services[$serviceId] ?? null;
                                                    $percentageKey = "{$customer->id}-{$serviceId}-{$month['number']}";
                                                @endphp
                                                <div class="panel-surface panel-border flex flex-col items-center gap-[15px] rounded-xl border p-[20px] text-center">
                                                    <span class="leading-none">{{ $inService?->service ?? 'Sin servicio' }}</span>
                                                    <x-radial-progress :month="$month['number']" :customerId="$customer->id" :percentageKey="$percentageKey" :serviceId="$serviceId" />
                                                </div>
                                            @endforeach
                                        </div>
                                    </section>
                                @endforeach
                            </div>
                        @else
                            <p class="leading-none">No se encontraron servicios contratados.</p>
                        @endif
                    </div>
                    <span x-show="visible" x-cloak class="panel-scroll-thumb" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
                    </div>

                    <footer class="panel-surface panel-border flex justify-end border-t p-[20px]">
                        <button type="button" x-on:click="open = false; $wire.closeAnnualReport()"
                            class="annual-close-button inline-flex items-center justify-center rounded-xl px-[20px] py-[15px] leading-none focus:outline-none focus:ring-0">Cerrar</button>
                    </footer>
                @endif
            </div>
        </div>
    @endteleport
</div>
