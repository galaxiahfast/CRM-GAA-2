@props([
    'title',
    'subtitle' => null,
    'modalId' => 'administration-panel',
    'cancelAction' => 'cancel',
    'carouselStyle' => false,
])

<div
    x-data="{ visible: true, pressedOnBackdrop: false }"
    x-show="visible"
    @pointerdown="pressedOnBackdrop = ($event.target === $el)"
    @click.self="if (pressedOnBackdrop) { visible = false; $wire.{{ $cancelAction }}() } pressedOnBackdrop = false"
    @click.capture="const button = $event.target.closest('button'); if (button?.getAttribute('wire:click') === @js($cancelAction)) visible = false"
    @keydown.escape.window="visible = false; $wire.{{ $cancelAction }}()"
    @wheel.self.prevent
    @touchmove.self.prevent
    class="fixed inset-0 z-[200001] flex items-center justify-center bg-black/45 p-[20px] backdrop-blur-sm"
    role="dialog"
    aria-modal="true"
    aria-labelledby="{{ $modalId }}-title"
    data-administration-modal="{{ $modalId }}"
>
    <x-administration-modal-styles />

    <div
        class="administration-form-panel relative flex w-full max-w-3xl flex-col overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-2xl"
        style="max-width: 620px; height: min(620px, calc(100dvh - 40px)); max-height: calc(100dvh - 40px); font-size: 15px; overscroll-behavior: contain;"
    >
        <header class="flex flex-shrink-0 items-center justify-between gap-[15px] border-b border-zinc-200 bg-white p-[20px]">
            <div class="flex min-w-0 items-center gap-[15px]">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-zinc-200 bg-white text-black">
                    {{ $icon }}
                </div>

                <div class="flex min-w-0 flex-col gap-[10px]">
                    <h1 id="{{ $modalId }}-title" class="truncate text-[15px] font-semibold leading-none text-black" style="margin: 0;">
                        {{ $title }}
                    </h1>
                    @if ($subtitle)
                        <p class="truncate text-[15px] leading-none text-zinc-500" style="margin: 0;">{{ $subtitle }}</p>
                    @endif
                </div>
            </div>

            <button
                type="button"
                wire:click="{{ $cancelAction }}"
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-zinc-200 bg-white text-xl leading-none text-zinc-500 transition hover:bg-zinc-100 hover:text-black focus:outline-none focus:ring-0"
                aria-label="Cerrar"
            >
                &times;
            </button>
        </header>

        @if (isset($navigation))
            <nav @class(['flex flex-shrink-0 border-b border-zinc-200 bg-white px-[20px]', 'hidden' => $modalId === 'roles-management']) aria-label="Secciones del formulario">
                {{ $navigation }}
            </nav>
        @endif

        <div x-data="backupOverlayScroll()" x-init="$nextTick(() => { const c = $refs.scroller.firstElementChild; if (c) new ResizeObserver(() => update()).observe(c); })" x-effect="$nextTick(() => update())" class="relative min-h-0 flex-1 bg-white">
            <div x-ref="scroller" @scroll="update()" @mouseenter="update()" class="administration-form-scrollbar absolute inset-0 overflow-y-auto bg-white" style="overscroll-behavior: contain;">
            <div class="m-0 bg-white p-[20px] text-[15px]">
                {{ $content }}
            </div>
        </div>
        <span x-show="visible" x-cloak class="administration-form-thumb" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
        </div>

        @if (isset($actions))
            <footer class="flex flex-shrink-0 justify-end gap-[10px] border-t border-zinc-200 bg-white p-[20px]">
                {{ $actions }}
            </footer>
        @endif
    </div>
</div>
