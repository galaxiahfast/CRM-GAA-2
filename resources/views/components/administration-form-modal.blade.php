@props([
    'submit',
    'title',
    'subtitle' => null,
    'modalId' => 'administration-form',
    'cancelAction' => 'cancel',
    'carouselStyle' => false,
])

<div
    x-data="{ visible: true }"
    x-show="visible"
    wire:click.self="{{ $cancelAction }}"
    @click.self="visible = false"
    @click.capture="const button = $event.target.closest('button'); if (button?.getAttribute('wire:click') === @js($cancelAction)) visible = false"
    @keydown.escape.window="visible = false; $wire.{{ $cancelAction }}()"
    class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/45 p-[20px] backdrop-blur-[2px]"
    role="dialog"
    aria-modal="true"
    aria-labelledby="{{ $modalId }}-title"
    data-administration-modal="{{ $modalId }}"
>
    <style>
        [data-administration-modal] {
            font-size: 15px;
        }
        [data-administration-modal] :where(p, span, button, input, select, option, label, h1, h2, h3, small) {
            font-size: 15px !important;
        }
        [data-administration-modal] .administration-form-panel,
        [data-administration-modal] .administration-form-panel > form,
        [data-administration-modal] .administration-form-panel header,
        [data-administration-modal] .administration-form-panel footer,
        [data-administration-modal] .administration-form-panel .bg-white {
            background-color: #ffffff !important;
        }
        .administration-form-scrollbar {
            scrollbar-width: thin;
            scrollbar-color: #1A3A6B transparent;
        }

        .administration-form-scrollbar::-webkit-scrollbar {
            width: 3px;
        }

        .administration-form-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }

        .administration-form-scrollbar::-webkit-scrollbar-thumb {
            background: #1A3A6B;
            border-radius: 9999px;
        }

        .administration-form-scrollbar::-webkit-scrollbar-button {
            display: none;
            width: 0;
            height: 0;
        }

        [data-administration-modal] textarea {
            resize: none;
        }

        [data-administration-modal] input:focus,
        [data-administration-modal] input:focus-visible,
        [data-administration-modal] select:focus,
        [data-administration-modal] select:focus-visible,
        [data-administration-modal] textarea:focus,
        [data-administration-modal] textarea:focus-visible {
            border-color: #e4e4e7 !important;
            outline: none !important;
            box-shadow: none !important;
            --tw-ring-shadow: 0 0 #0000 !important;
            --tw-ring-offset-shadow: 0 0 #0000 !important;
        }
        [data-administration-modal] footer button {
            min-height: 44px;
            border: 1px solid #e4e4e7 !important;
            border-radius: 0.75rem !important;
            background: #ffffff !important;
            color: #18181b !important;
            box-shadow: none !important;
        }
        [data-administration-modal] footer button:last-child {
            border-color: #18181b !important;
            background: #18181b !important;
            color: #ffffff !important;
        }
    </style>

    <div
        @class([
            'administration-form-panel relative flex w-full max-w-3xl flex-col overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-2xl',
        ])
        style="height: min(760px, calc(100dvh - 40px)); max-height: calc(100dvh - 40px); font-size: 15px; overscroll-behavior: contain;"
    >
        <form wire:submit.prevent="{{ $submit }}" class="flex min-h-0 flex-1 flex-col">
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
                            <p class="truncate text-[15px] leading-none text-zinc-500" style="margin: 0;">
                                {{ $subtitle }}
                            </p>
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
                <nav @class(['flex flex-shrink-0 border-b border-gray-300 bg-[#F3F3F3] px-5', 'hidden' => $modalId === 'role-form']) aria-label="Secciones del formulario">
                    {{ $navigation }}
                </nav>
            @endif

            <div class="administration-form-scrollbar min-h-0 flex-1 overflow-y-auto bg-white" style="overscroll-behavior: contain;">
                <div class="p-[20px] text-[15px]">
                    {{ $form }}
                </div>
            </div>

            <footer class="flex flex-shrink-0 justify-end gap-[10px] border-t border-zinc-200 bg-white p-[20px]">
                {{ $actions }}
            </footer>
        </form>
    </div>
</div>
