@props(['title' => 'No se pudo completar la acción', 'message' => '', 'seconds' => 5])

{{-- Aviso de error emergente: arriba al centro, sobre la página (nunca ocupa lugar en ella), mismo diseño que el de
     ManagerControl (marco y fondo de peligro, icono y texto) y se va solo. Cada error nuevo lleva su propio wire:key. --}}
@if (filled($message))
    @teleport('body')
        <div
            data-panel
            data-productivity
            data-error-toast
            x-data="{ visible: true }"
            x-init="setTimeout(() => visible = false, {{ (int) $seconds * 1000 }})"
            x-show="visible"
            x-transition.opacity.duration.200ms
            style="background: transparent"
            class="pointer-events-none fixed inset-x-0 top-[40px] z-[300100] flex justify-center px-[20px]"
            role="alert"
            aria-live="assertive"
            {{ $attributes }}
        >
            <section class="app-zoom-overlay panel-surface relative w-full overflow-hidden rounded-xl border shadow-[0_18px_55px_rgba(0,0,0,0.30)]" style="max-width: 620px; border-color: var(--panel-danger); background-color: var(--panel-danger-soft)">
                <div class="p-[20px]">
                    <div class="flex items-center gap-[20px]">
                        <svg class="h-5 w-5 shrink-0" style="color: var(--panel-danger)" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v5m0 3.5h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                        <p class="leading-none" style="color: var(--panel-danger)">{{ $title }}</p>
                    </div>
                    <p class="mt-[20px] leading-[25px]" data-testid="error-toast-message">{{ $message }}</p>
                </div>
            </section>
        </div>
    @endteleport
@endif
