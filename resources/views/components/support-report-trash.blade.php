@props(['reports'])

@php
    $timezone = config('support.timezone', 'America/Mexico_City');
    $iconView = 'M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Zm10 3a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z';
    $iconTrash = 'M5 7h14M9 7V4h6v3m-8 0 1 13h8l1-13M10 11v5m4-5v5';
    $iconRestore = 'M1 4v6h6M3.51 15a9 9 0 1 0 2.13-9.36L1 10';

@endphp

{{-- Papelera de reciclaje: reportes eliminados, con opción de restaurarlos o borrarlos para siempre. --}}
<div
    x-data="{
        open: false,
        purge: null,
        folioText: '',
        wordText: '',
        canPurge() {
            return this.purge !== null
                && this.folioText.trim().toUpperCase() === String(this.purge.folio).toUpperCase()
                && this.wordText.trim().toUpperCase() === 'ELIMINAR';
        }
    }"
    @keydown.escape.window="if (purge) { purge = null } else { open = false }"
    {{ $attributes->class(['shrink-0']) }}
>
    <button type="button" @click="open = true" class="flex items-center gap-[10px] bg-transparent py-[20px] leading-none text-black transition-colors hover:text-[#1A3A6B] focus:outline-none focus:ring-0" title="Papelera de reciclaje">
        <svg class="h-[18px] w-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconTrash }}"/></svg>
        <span>Papelera de reciclaje ({{ $reports->count() }})</span>
    </button>

    <template x-teleport="body">
        <div data-support-modal x-cloak x-show="open" class="fixed inset-0 z-[200001] flex items-center justify-center bg-black/45 p-[20px] backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="support-trash-title">
            <div :style="{ transform: `scale(${(typeof viewScale === 'undefined' ? 100 : Number(viewScale)) / 100})` }" @click.outside="if (! purge) open = false" class="flex w-full max-w-[620px] flex-col overflow-hidden rounded-xl border border-[#d4d4d8] bg-white text-[15px] text-black shadow-2xl" style="height: min(620px, calc(100dvh - 40px));">
                <header class="flex shrink-0 items-center justify-between gap-[20px] border-b border-[#d4d4d8] bg-white p-[20px]">
                    <div class="flex min-w-0 items-center gap-[20px]">
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-[#d4d4d8] bg-white text-black">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconTrash }}"/></svg>
                        </span>
                        <div class="min-w-0">
                            <h1 id="support-trash-title" class="font-semibold leading-none text-black">Papelera de reciclaje</h1>
                            <p class="mt-[10px] truncate leading-none text-black">Restaura un reporte o elimínalo de forma permanente.</p>
                        </div>
                    </div>
                    <button type="button" @click="open = false" aria-label="Cerrar" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-red-600 leading-none text-white focus:outline-none focus:ring-0">×</button>
                </header>

                <div x-data="backupOverlayScroll()" x-init="$nextTick(() => update())" x-effect="open; $nextTick(() => update())" class="relative min-h-0 flex-1 bg-white">
                    <div x-ref="scroller" @scroll="update()" @mouseenter="update()" class="support-service-scroll absolute inset-0 overflow-y-auto">
                        @forelse ($reports as $report)
                            <div class="flex items-center gap-[20px] border-b border-[#d4d4d8] bg-white p-[20px]" style="min-height: 77px;">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate leading-none text-[#1A3A6B]" title="{{ $report->folio }}">{{ $report->folio }}</p>
                                    <p class="mt-[10px] truncate leading-none text-black" title="{{ $report->service_recipient }}">{{ $report->service_recipient }}</p>
                                    <p class="mt-[10px] truncate leading-none text-black">Eliminado: {{ $report->deleted_at?->timezone($timezone)->translatedFormat('d M Y · H:i') }}</p>
                                </div>

                                <div class="flex shrink-0 items-center gap-[10px]">
                                    <a href="{{ route('soporte.reportes-tecnicos.pdf', $report) }}" target="_blank" rel="noopener" class="flex h-[44px] w-[44px] shrink-0 items-center justify-center rounded-xl text-black transition-colors hover:bg-zinc-100 hover:text-[#1A3A6B] focus:outline-none focus:ring-0" aria-label="Ver {{ $report->folio }}" title="Ver">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconView }}"/></svg>
                                    </a>
                                    <form method="POST" action="{{ route('soporte.reportes-tecnicos.restore', $report) }}">
                                        @csrf
                                        <button type="submit" class="flex h-[44px] w-[44px] shrink-0 items-center justify-center rounded-xl text-black transition-colors hover:bg-zinc-100 hover:text-[#1A3A6B] focus:outline-none focus:ring-0" aria-label="Restaurar {{ $report->folio }}" title="Restaurar">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconRestore }}"/></svg>
                                        </button>
                                    </form>
                                    <button type="button" @click="folioText = ''; wordText = ''; purge = { folio: @js($report->folio), url: @js(route('soporte.reportes-tecnicos.purge', $report)) }" class="flex h-[44px] w-[44px] shrink-0 items-center justify-center rounded-xl text-black transition-colors hover:bg-zinc-100 hover:text-red-600 focus:outline-none focus:ring-0" aria-label="Eliminar de forma permanente {{ $report->folio }}" title="Eliminar de forma permanente">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconTrash }}"/></svg>
                                    </button>
                                </div>
                            </div>
                        @empty
                            <div class="grid h-full min-h-[260px] place-items-center p-[20px] text-center leading-[20px] text-black">
                                La papelera está vacía.
                            </div>
                        @endforelse
                    </div>
                    <span x-show="visible" x-cloak class="support-service-scroll-thumb" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
                </div>

                <footer class="flex shrink-0 justify-end gap-[10px] border-t border-[#d4d4d8] bg-white p-[20px]">
                    <button type="button" @click="open = false" class="h-[44px] rounded-xl border border-[#d4d4d8] bg-white px-[20px] leading-none text-black focus:outline-none focus:ring-0">Cerrar</button>
                </footer>
            </div>
        </div>
    </template>

    {{-- Segunda confirmación para borrar de forma permanente. --}}
    <template x-teleport="body">
        <div data-support-modal x-cloak x-show="purge" class="fixed inset-0 z-[200010] flex items-center justify-center bg-black/45 p-[20px] backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="support-purge-title">
            <div :style="{ transform: `scale(${(typeof viewScale === 'undefined' ? 100 : Number(viewScale)) / 100})` }" @click.outside="purge = null" class="flex w-full max-w-[620px] flex-col overflow-hidden rounded-xl border border-[#d4d4d8] bg-white text-[15px] text-black shadow-2xl">
                <header class="flex items-center justify-between gap-[20px] border-b border-[#d4d4d8] bg-white p-[20px]">
                    <div class="flex min-w-0 items-center gap-[20px]">
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-[#d4d4d8] bg-white text-black">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconTrash }}"/></svg>
                        </span>
                        <div class="min-w-0">
                            <h1 id="support-purge-title" class="font-semibold leading-none text-black">Eliminar de forma permanente</h1>
                            <p class="mt-[10px] truncate leading-none text-black">Ya no podrás restaurar este reporte.</p>
                        </div>
                    </div>
                    <button type="button" @click="purge = null" aria-label="Cerrar" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-red-600 leading-none text-white focus:outline-none focus:ring-0">×</button>
                </header>

                <form method="POST" :action="purge?.url" @submit="if (! canPurge()) $event.preventDefault()">
                    @csrf
                    @method('DELETE')

                    <div class="p-[20px]">
                        <section class="border-b border-[#d4d4d8] pb-[20px]">
                            <h2 class="leading-none text-black">Reporte seleccionado</h2>
                            <p class="mt-[20px] leading-none text-[#1A3A6B]" x-text="purge?.folio"></p>
                        </section>

                        <section class="pt-[20px]">
                            <h2 class="leading-none text-black">Verificación</h2>

                            <label class="mt-[20px] block">
                                <span class="block leading-none text-black">Escribe el folio exacto del reporte</span>
                                <input type="text" x-model="folioText" autocomplete="new-password" onpaste="return false" class="mt-[20px] h-[44px] w-full rounded-xl border border-[#d4d4d8] bg-white px-[20px] font-normal uppercase text-black placeholder:text-zinc-500 focus:outline-none focus:ring-0" placeholder="Folio del reporte...">
                            </label>

                            <label class="mt-[20px] block">
                                <span class="block leading-none text-black">Escribe ELIMINAR</span>
                                <input type="text" x-model="wordText" autocomplete="new-password" onpaste="return false" class="mt-[20px] h-[44px] w-full rounded-xl border border-[#d4d4d8] bg-white px-[20px] font-normal uppercase text-black placeholder:text-zinc-500 focus:outline-none focus:ring-0" placeholder="ELIMINAR">
                            </label>
                        </section>
                    </div>

                    <div class="flex justify-end gap-[10px] border-t border-[#d4d4d8] bg-white p-[20px]">
                        <button type="button" @click="purge = null" class="h-[44px] rounded-xl border border-[#d4d4d8] bg-white px-[20px] leading-none text-black focus:outline-none focus:ring-0">Cancelar</button>
                        <button type="submit" :disabled="! canPurge()" class="h-[44px] rounded-xl bg-black px-[20px] leading-none text-white focus:outline-none focus:ring-0 disabled:cursor-not-allowed disabled:opacity-40">Eliminar</button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>
