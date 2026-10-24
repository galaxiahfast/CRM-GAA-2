@props([
    'submit',
    'title',
    'subtitle' => null,
    'modalId',
    'closeAction',
])

<div
    {{ $attributes }}
    x-data="{ visible: true }"
    x-show="visible"
    wire:click.self="{{ $closeAction }}"
    @click.self="visible = false"
    @click.capture="const button = $event.target.closest('button'); if (button?.getAttribute('wire:click') === @js($closeAction)) visible = false"
    @keydown.escape.window="$wire.{{ $closeAction }}()"
    @keyup.escape.window="visible = false"
    class="fixed inset-0 z-[200001] flex items-center justify-center bg-black/45 p-[20px] backdrop-blur-sm"
    role="dialog"
    aria-modal="true"
    aria-labelledby="{{ $modalId }}-title"
    data-administration-modal="{{ $modalId }}"
>
    <div
        class="relative w-full max-w-xl overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-2xl"
        style="font-size: 15px;"
    >
        <form wire:submit.prevent="{{ $submit }}">
            <header class="flex items-center justify-between gap-[15px] border-b border-zinc-200 bg-white p-[20px]">
                <div class="flex min-w-0 items-center gap-[15px]">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-zinc-200 bg-white text-black">
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
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-zinc-200 bg-white text-xl leading-none text-zinc-500 transition hover:bg-zinc-100 hover:text-black focus:outline-none focus:ring-0"
                    aria-label="Cerrar"
                >
                    &times;
                </button>
            </header>

            @if (isset($navigation))
                <nav class="flex border-b border-zinc-200 bg-white px-[20px]" aria-label="Gestión del catálogo">
                    {{ $navigation }}
                </nav>
            @endif

            <div class="bg-white p-[20px]">
                <div class="rounded-xl border border-zinc-200 bg-white p-[20px] shadow-none">
                    {{ $form }}
                </div>
            </div>

            <footer class="flex justify-end gap-[10px] border-t border-zinc-200 bg-white p-[20px]">
                {{ $actions }}
            </footer>
        </form>
    </div>
</div>
