@props([
    'submit',
    'title',
    'subtitle' => null,
    'modalId',
    'closeAction',
])

<div
    class="fixed inset-0 z-[9999] flex items-center justify-center bg-gray-900/55 p-4 backdrop-blur-[2px]"
    role="dialog"
    aria-modal="true"
    aria-labelledby="{{ $modalId }}-title"
    data-administration-modal="{{ $modalId }}"
>
    <div
        x-data
        @click.outside="$wire.{{ $closeAction }}()"
        @keydown.escape.window="$wire.{{ $closeAction }}()"
        class="relative w-full max-w-xl overflow-hidden rounded-2xl border border-gray-300 bg-[#F3F3F3] shadow-2xl"
        style="font-size: 15px;"
    >
        <form wire:submit.prevent="{{ $submit }}">
            <header class="flex items-center justify-between gap-[15px] border-b border-gray-300 p-5">
                <div class="flex min-w-0 items-center gap-[15px]">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-[#1A3A6B] text-white shadow-sm">
                        {{ $icon }}
                    </div>

                    <div class="min-w-0">
                        <h2 id="{{ $modalId }}-title" class="truncate text-[15px] font-semibold text-gray-900">
                            {{ $title }}
                        </h2>
                        @if ($subtitle)
                            <p class="mt-[10px] truncate text-[15px] text-gray-500">{{ $subtitle }}</p>
                        @endif
                    </div>
                </div>

                <button
                    type="button"
                    wire:click="{{ $closeAction }}"
                    wire:loading.attr="disabled"
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-gray-300 bg-white text-xl leading-none text-gray-500 transition hover:border-[#1A3A6B] hover:text-[#1A3A6B] focus:outline-none focus:ring-0 disabled:cursor-wait disabled:opacity-50"
                    aria-label="Cerrar"
                >
                    &times;
                </button>
            </header>

            @if (isset($navigation))
                <nav class="flex border-b border-gray-300 bg-[#F3F3F3] px-5" aria-label="Gestión del catálogo">
                    {{ $navigation }}
                </nav>
            @endif

            <div class="p-5">
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    {{ $form }}
                </div>
            </div>

            <footer class="flex justify-end gap-[15px] border-t border-gray-300 p-5">
                {{ $actions }}
            </footer>
        </form>
    </div>
</div>
