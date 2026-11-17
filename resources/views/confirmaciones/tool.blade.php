<x-app-layout>
    {{-- Confirmaciones de Saldos: mismo diseño que Conciliación Bancaria, Panel general, Administración, Reloj
         Checador y Reportes de soporte (texto de 15px, separaciones de 20px, escala de vista al 90% ajustable,
         pantalla completa y tema oscuro con fondo negro). Solo cambia la presentación: la herramienta
         (resources/tools/*.html) se sirve igual que antes dentro del iframe y su lógica no se toca. --}}
    <x-panel-styles />

    <div
        data-panel
        class="relative isolate w-full min-w-0 overflow-hidden text-[15px]"
        x-data="{
            viewScale: 90,
            isFullscreen: false,
            isCompact: window.innerWidth / window.appAutoScale() < 1024,
            init() {
                try {
                    const saved = Number(null);
                    if (saved >= 70 && saved <= 100) this.viewScale = saved;
                } catch (error) {}
            },
            setScale(value) {
                this.viewScale = Math.min(100, Math.max(70, Number(value) || 90));
                try { localStorage.setItem('confirmaciones-view-scale', String(this.viewScale)); } catch (error) {}
            },
            async toggleFullscreen(container) {
                if (document.fullscreenElement === container) return document.exitFullscreen();
                if (document.fullscreenElement) await document.exitFullscreen();
                await container.requestFullscreen();
            }
        }"
        @fullscreenchange.window="isFullscreen = document.fullscreenElement === $root"
        @resize.window.debounce.150ms="isCompact = window.innerWidth / window.appAutoScale() < 1024"
    >
        <section
            class="w-full overflow-hidden"
            :class="isFullscreen ? 'h-dvh' : 'h-[calc(var(--app-dvh)-123px)] min-h-[640px]'"
            aria-label="{{ $title }}"
        >
            <div class="origin-top-left" :style="isCompact ? 'width:100%;height:100%;transform:none' : 'width:' + (10000 / viewScale) + '%;height:' + (10000 / viewScale) + '%;transform:scale(' + (viewScale / 100) + ')'">
                <iframe src="{{ $contentUrl }}" title="{{ $title }}" class="h-full w-full border-0 bg-transparent" loading="eager" referrerpolicy="same-origin"></iframe>
            </div>
        </section>

        <!-- Control de pantalla completa (igual que en Panel general, Administración, Reloj Checador y Reportes de soporte) -->
        <div class="no-print panel-surface panel-border fixed bottom-[30px] right-[30px] z-30 hidden items-center gap-[10px] rounded-xl border px-[15px] py-[10px] shadow-[0_8px_24px_rgba(0,0,0,0.10)] lg:flex">
            <button type="button" @click="toggleFullscreen($root)" class="panel-icon-button inline-flex h-8 w-8 items-center justify-center rounded-lg outline-none focus:ring-0" :title="isFullscreen ? 'Salir de pantalla completa' : 'Pantalla completa'" :aria-label="isFullscreen ? 'Salir de pantalla completa' : 'Ver {{ $title }} en pantalla completa'">
                <svg x-show="!isFullscreen" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 3H3v5M16 3h5v5M21 16v5h-5M3 16v5h5"/></svg>
                <svg x-show="isFullscreen" x-cloak class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 3v5H3M16 3v5h5M21 16h-5v5M3 16h5v5"/></svg>
            </button>
        </div>
    </div>
</x-app-layout>
