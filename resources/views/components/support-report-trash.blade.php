@props(['reports'])

@php
    $timezone = config('support.timezone', 'America/Mexico_City');
    $iconView = 'M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Zm10 3a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z';
    $iconTrash = 'M5 7h14M9 7V4h6v3m-8 0 1 13h8l1-13M10 11v5m4-5v5';
    $iconRestore = 'M1 4v6h6M3.51 15a9 9 0 1 0 2.13-9.36L1 10';

@endphp

{{-- Papelera de reciclaje: reportes eliminados, con opción de restaurarlos o borrarlos para siempre.
     Diseño del panel; las ventanas se montan fuera de la página y usan `effScale` (escala efectiva de la vista). --}}
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
    <button type="button" @click="open = true" class="panel-tab flex items-center gap-[10px] bg-transparent leading-none focus:outline-none focus:ring-0" title="Papelera de reciclaje">
        <svg class="h-[18px] w-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconTrash }}"/></svg>
        <span>Papelera de reciclaje ({{ $reports->count() }})</span>
    </button>

    <template x-teleport="body">
        <div data-support-modal x-cloak x-show="open" class="fixed inset-0 z-[200001] flex items-center justify-center bg-black/50 p-[20px]" role="dialog" aria-modal="true" aria-labelledby="support-trash-title">
            <div data-panel :style="{ transform: `scale(${(typeof effScale === 'undefined' ? 100 : Number(effScale)) / 100})` }" @click.outside="if (! purge) open = false" class="panel-surface panel-border flex w-full max-w-[620px] flex-col overflow-hidden rounded-xl border shadow-2xl" style="height: min(620px, calc(100dvh - 40px));">
                <header class="panel-header panel-border flex shrink-0 items-center justify-between gap-[15px] border-b p-[20px]">
                    <div class="flex min-w-0 items-center gap-[15px]">
                        <span class="panel-surface panel-border panel-text grid h-10 w-10 shrink-0 place-items-center rounded-xl border">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconTrash }}"/></svg>
                        </span>
                        <div class="min-w-0">
                            <h3 id="support-trash-title" class="panel-accent truncate leading-none">Papelera de reciclaje</h3>
                            <p class="mt-[10px] leading-tight sm:truncate">Restaura un reporte o elimínalo de forma permanente.</p>
                        </div>
                    </div>
                    <button type="button" @click="open = false" aria-label="Cerrar" class="panel-icon-button inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg focus:outline-none focus:ring-0">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg>
                    </button>
                </header>

                <div x-data="backupOverlayScroll()" x-init="$nextTick(() => update())" x-effect="open; $nextTick(() => update())" class="relative min-h-0 flex-1">
                    <div x-ref="scroller" @scroll="update()" @mouseenter="update()" class="panel-scroll absolute inset-0 overflow-y-auto">
                        @forelse ($reports as $report)
                            <div class="panel-border flex items-center gap-[15px] border-b p-[20px]" style="min-height: 77px;">
                                <div class="min-w-0 flex-1">
                                    <p class="panel-accent truncate leading-none" title="{{ $report->folio }}">{{ $report->folio }}</p>
                                    <p class="mt-[10px] truncate leading-none" title="{{ $report->service_recipient }}">{{ $report->service_recipient }}</p>
                                    <p class="panel-muted mt-[10px] truncate leading-none">Eliminado: {{ $report->deleted_at?->timezone($timezone)->translatedFormat('d M Y · H:i') }}</p>
                                </div>

                                <div class="flex shrink-0 items-center gap-[5px] sm:gap-[10px]">
                                    <a href="{{ route('soporte.reportes-tecnicos.pdf', $report) }}" target="_blank" rel="noopener" class="panel-icon-button flex h-11 w-11 shrink-0 items-center justify-center rounded-xl focus:outline-none focus:ring-0" aria-label="Ver {{ $report->folio }}" title="Ver">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconView }}"/></svg>
                                    </a>
                                    <form method="POST" action="{{ route('soporte.reportes-tecnicos.restore', $report) }}">
                                        @csrf
                                        <button type="submit" class="panel-icon-button flex h-11 w-11 shrink-0 items-center justify-center rounded-xl focus:outline-none focus:ring-0" aria-label="Restaurar {{ $report->folio }}" title="Restaurar">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconRestore }}"/></svg>
                                        </button>
                                    </form>
                                    <button type="button" @click="folioText = ''; wordText = ''; purge = { folio: @js($report->folio), url: @js(route('soporte.reportes-tecnicos.purge', $report)) }" class="panel-icon-button support-icon-danger flex h-11 w-11 shrink-0 items-center justify-center rounded-xl focus:outline-none focus:ring-0" aria-label="Eliminar de forma permanente {{ $report->folio }}" title="Eliminar de forma permanente">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconTrash }}"/></svg>
                                    </button>
                                </div>
                            </div>
                        @empty
                            <div class="panel-muted grid h-full min-h-[260px] place-items-center p-[20px] text-center leading-tight">
                                La papelera está vacía.
                            </div>
                        @endforelse
                    </div>
                    <span x-show="visible" x-cloak class="panel-scroll-thumb" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
                </div>

                <footer class="panel-border flex shrink-0 justify-end gap-[10px] border-t p-[20px]">
                    <button type="button" @click="open = false" class="panel-button-secondary inline-flex h-11 items-center justify-center rounded-xl px-[20px] leading-none focus:outline-none focus:ring-0">Cerrar</button>
                </footer>
            </div>
        </div>
    </template>

    {{-- Segunda confirmación para borrar de forma permanente. --}}
    <template x-teleport="body">
        <div data-support-modal x-cloak x-show="purge" class="fixed inset-0 z-[200010] flex items-center justify-center bg-black/50 p-[20px]" role="dialog" aria-modal="true" aria-labelledby="support-purge-title">
            <div data-panel :style="{ transform: `scale(${(typeof effScale === 'undefined' ? 100 : Number(effScale)) / 100})` }" @click.outside="purge = null" class="panel-surface panel-border flex w-full max-w-[620px] flex-col overflow-hidden rounded-xl border shadow-2xl" style="max-height: calc(100dvh - 40px);">
                <header class="panel-header panel-border flex shrink-0 items-center justify-between gap-[15px] border-b p-[20px]">
                    <div class="flex min-w-0 items-center gap-[15px]">
                        <span class="panel-surface panel-border panel-text grid h-10 w-10 shrink-0 place-items-center rounded-xl border">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconTrash }}"/></svg>
                        </span>
                        <div class="min-w-0">
                            <h3 id="support-purge-title" class="panel-accent truncate leading-none">Eliminar de forma permanente</h3>
                            <p class="mt-[10px] leading-tight sm:truncate">Ya no podrás restaurar este reporte.</p>
                        </div>
                    </div>
                    <button type="button" @click="purge = null" aria-label="Cerrar" class="panel-icon-button inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg focus:outline-none focus:ring-0">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg>
                    </button>
                </header>

                <form method="POST" :action="purge?.url" @submit="if (! canPurge()) $event.preventDefault()" class="flex min-h-0 flex-1 flex-col overflow-y-auto">
                    @csrf
                    @method('DELETE')

                    <div class="p-[20px]">
                        <section class="panel-border border-b pb-[20px]">
                            <h2 class="leading-none">Reporte seleccionado</h2>
                            <p class="panel-accent mt-[15px] leading-none" x-text="purge?.folio"></p>
                        </section>

                        <section class="pt-[20px]">
                            <h2 class="leading-none">Verificación</h2>

                            <label class="mt-[15px] block">
                                <span class="block leading-none">Escribe el folio exacto del reporte</span>
                                <input type="text" x-model="folioText" autocomplete="new-password" onpaste="return false" class="panel-field mt-[10px] block h-11 w-full rounded-xl border px-[20px] uppercase focus:ring-0" placeholder="Folio del reporte...">
                            </label>

                            <label class="mt-[20px] block">
                                <span class="block leading-none">Escribe ELIMINAR</span>
                                <input type="text" x-model="wordText" autocomplete="new-password" onpaste="return false" class="panel-field mt-[10px] block h-11 w-full rounded-xl border px-[20px] uppercase focus:ring-0" placeholder="ELIMINAR">
                            </label>
                        </section>
                    </div>

                    <div class="panel-border mt-auto flex justify-end gap-[10px] border-t p-[20px]">
                        <button type="button" @click="purge = null" class="panel-button-secondary inline-flex h-11 items-center justify-center rounded-xl px-[20px] leading-none focus:outline-none focus:ring-0">Cancelar</button>
                        <button type="submit" :disabled="! canPurge()" class="panel-button inline-flex h-11 items-center justify-center rounded-xl px-[20px] leading-none focus:outline-none focus:ring-0">Eliminar</button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>
