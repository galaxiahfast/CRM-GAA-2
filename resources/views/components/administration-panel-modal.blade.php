@props([
    'title',
    'subtitle' => null,
    'modalId' => 'administration-panel',
    'cancelAction' => 'cancel',
])

<div
    x-data
    wire:click.self="{{ $cancelAction }}"
    @keydown.escape.window="$wire.{{ $cancelAction }}()"
    class="fixed inset-0 z-[9999] flex items-center justify-center bg-gray-900/55 p-4 backdrop-blur-[2px]"
    role="dialog"
    aria-modal="true"
    aria-labelledby="{{ $modalId }}-title"
    data-administration-modal="{{ $modalId }}"
>
    <style>
        .administration-panel-scrollbar {
            scrollbar-width: thin;
            scrollbar-color: #1A3A6B #F3F3F3;
        }

        .administration-panel-scrollbar::-webkit-scrollbar {
            width: 6px;
        }

        .administration-panel-scrollbar::-webkit-scrollbar-track {
            background: #F3F3F3;
            border-radius: 9999px;
        }

        .administration-panel-scrollbar::-webkit-scrollbar-thumb {
            background: #1A3A6B;
            border-radius: 9999px;
        }

        .administration-panel-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #15305a;
        }
    </style>

    <div
        class="relative flex w-full max-w-3xl flex-col overflow-hidden rounded-2xl border border-gray-300 bg-[#F3F3F3] shadow-2xl"
        style="height: min(86vh, 820px); max-height: 86vh; font-size: 15px; overscroll-behavior: contain;"
    >
        <header class="flex flex-shrink-0 items-center justify-between gap-[15px] border-b border-gray-300 bg-[#F3F3F3] p-5">
            <div class="flex min-w-0 items-center gap-[15px]">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-[#1A3A6B] text-white shadow-sm">
                    {{ $icon }}
                </div>

                <div class="flex min-w-0 flex-col gap-[10px]">
                    <h1 id="{{ $modalId }}-title" class="truncate text-[15px] font-semibold leading-none text-gray-900" style="margin: 0;">
                        {{ $title }}
                    </h1>
                    @if ($subtitle)
                        <p class="truncate text-[15px] leading-none text-gray-500" style="margin: 0;">{{ $subtitle }}</p>
                    @endif
                </div>
            </div>

            <button
                type="button"
                wire:click="{{ $cancelAction }}"
                wire:loading.attr="disabled"
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-gray-300 bg-white text-xl leading-none text-gray-500 transition hover:border-[#1A3A6B] hover:text-[#1A3A6B] focus:outline-none focus:ring-0 disabled:cursor-wait disabled:opacity-50"
                aria-label="Cerrar"
            >
                &times;
            </button>
        </header>

        @if (isset($navigation))
            <nav class="flex flex-shrink-0 border-b border-gray-300 bg-[#F3F3F3] px-5" aria-label="Secciones del formulario">
                {{ $navigation }}
            </nav>
        @endif

        <div class="administration-panel-scrollbar min-h-0 flex-1 overflow-y-auto" style="overscroll-behavior: contain;">
            <div class="m-5 rounded-xl border border-dashed border-gray-300 bg-white p-5 text-[15px] shadow-sm">
                {{ $content }}
            </div>
        </div>

        @if (isset($actions))
            <footer class="flex flex-shrink-0 justify-end gap-[15px] border-t border-gray-300 bg-[#F3F3F3] p-5">
                {{ $actions }}
            </footer>
        @endif
    </div>
</div>
