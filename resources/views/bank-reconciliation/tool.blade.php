<x-app-layout>
    {{-- Conciliación bancaria y Conciliación OCR: mismo diseño que Panel general, Administración, Reloj Checador y
         Reportes de soporte (texto de 15px, separaciones de 20px, escala de vista igual al zoom automático
         y tema oscuro con fondo negro). Solo cambia la presentación: la herramienta (resources/tools/*.html) se sirve
         igual que antes dentro del iframe y su lógica no se toca. --}}
    <x-panel-styles />

    <div
        data-panel
        class="relative isolate w-full min-w-0 overflow-hidden text-[15px]"
        x-data="{
            // La herramienta se escala con el mismo factor que el resto de las vistas (zoom automático según la ventana)
            viewScale: Math.round(window.appAutoScale() * 100),
            isCompact: window.innerWidth / window.appAutoScale() < 1024,
        }"
        @resize.window.debounce.150ms="isCompact = window.innerWidth / window.appAutoScale() < 1024; viewScale = Math.round(window.appAutoScale() * 100)"
    >
        <section
            class="w-full overflow-hidden h-[calc(var(--app-dvh)-123px)] min-h-[640px]"
            aria-label="{{ $title }}"
        >
            <div class="origin-top-left" :style="isCompact ? 'width:100%;height:100%;transform:none' : 'width:' + (10000 / viewScale) + '%;height:' + (10000 / viewScale) + '%;transform:scale(' + (viewScale / 100) + ')'">
                <iframe src="{{ $contentUrl }}" title="{{ $title }}" class="h-full w-full border-0 bg-transparent" loading="eager" referrerpolicy="same-origin"></iframe>
            </div>
        </section>
    </div>
</x-app-layout>
