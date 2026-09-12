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
    class="fixed inset-0 z-[9999] flex items-center justify-center bg-gray-900/55 p-4 backdrop-blur-[2px]"
    role="dialog"
    aria-modal="true"
    aria-labelledby="{{ $modalId }}-title"
    data-administration-modal="{{ $modalId }}"
>
    <style>
        .administration-form-scrollbar {
            scrollbar-width: thin;
            scrollbar-color: #1A3A6B #F3F3F3;
        }

        .administration-form-scrollbar::-webkit-scrollbar {
            width: 6px;
        }

        .administration-form-scrollbar::-webkit-scrollbar-track {
            background: #F3F3F3;
            border-radius: 9999px;
        }

        .administration-form-scrollbar::-webkit-scrollbar-thumb {
            background: #1A3A6B;
            border-radius: 9999px;
        }

        .administration-form-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #15305a;
        }

        [data-administration-modal] textarea {
            resize: none;
        }

        .organization-card-surface {
            background-color: rgba(255, 255, 255, 0.48);
            background-image:
                linear-gradient(135deg, rgba(255, 255, 255, 0.68), rgba(255, 255, 255, 0.24) 48%, rgba(255, 255, 255, 0.5)),
                radial-gradient(circle, rgba(26, 58, 107, 0.13) 0.7px, transparent 0.8px);
            background-position: 0 0;
            background-size: 100% 100%, 11px 11px;
        }
    </style>

    <div
        @class([
            'relative flex w-full max-w-3xl flex-col overflow-hidden border shadow-2xl',
            'rounded-[22px] border-[#1A3A6B] bg-[#F7FAFE]' => $carouselStyle,
            'rounded-2xl border-gray-300 bg-[#F3F3F3]' => ! $carouselStyle,
        ])
        style="height: min(86vh, 820px); max-height: 86vh; font-size: 15px; overscroll-behavior: contain;"
    >
        <form wire:submit.prevent="{{ $submit }}" class="flex min-h-0 flex-1 flex-col">
            <header @class([
                'flex flex-shrink-0 items-center justify-between gap-[15px] border-b p-5',
                'border-white/10 bg-[#1A3A6B]' => $carouselStyle,
                'border-gray-300 bg-[#F3F3F3]' => ! $carouselStyle,
            ])>
                <div class="flex min-w-0 items-center gap-[15px]">
                    <div @class([
                        'flex h-12 w-12 shrink-0 items-center justify-center text-white',
                        'rounded-2xl border border-white/15 bg-white/10' => $carouselStyle,
                        'rounded-full bg-[#1A3A6B] shadow-sm' => ! $carouselStyle,
                    ])>
                        {{ $icon }}
                    </div>

                    <div class="flex min-w-0 flex-col gap-[10px]">
                        <h1 id="{{ $modalId }}-title" @class(['truncate text-[15px] font-semibold leading-none', 'text-white' => $carouselStyle, 'text-gray-900' => ! $carouselStyle]) style="margin: 0;">
                            {{ $title }}
                        </h1>

                        @if ($subtitle)
                            <p @class(['truncate text-[15px] leading-none', 'text-[#C9E1FF]' => $carouselStyle, 'text-gray-500' => ! $carouselStyle]) style="margin: 0;">
                                {{ $subtitle }}
                            </p>
                        @endif
                    </div>
                </div>

                <button
                    type="button"
                    wire:click="{{ $cancelAction }}"
                    @class([
                        'flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border text-xl leading-none transition focus:outline-none focus:ring-0',
                        'border-white/20 bg-white/10 text-white hover:bg-white/20' => $carouselStyle,
                        'border-gray-300 bg-white text-gray-500 hover:border-[#1A3A6B] hover:text-[#1A3A6B]' => ! $carouselStyle,
                    ])
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

            <div @class(['administration-form-scrollbar min-h-0 flex-1 overflow-y-auto', 'organization-card-surface' => $carouselStyle]) style="overscroll-behavior: contain;">
                <div @class([
                    'text-[15px]',
                    'm-0 p-6' => $carouselStyle,
                    'm-5 rounded-xl border border-dashed border-gray-300 bg-white p-5 shadow-sm' => ! $carouselStyle,
                ])>
                    {{ $form }}
                </div>
            </div>

            <footer @class([
                'flex flex-shrink-0 justify-end gap-[15px] border-t p-5',
                'border-white/10 bg-[#1A3A6B]' => $carouselStyle,
                'border-gray-300 bg-[#F3F3F3]' => ! $carouselStyle,
            ])>
                {{ $actions }}
            </footer>
        </form>
    </div>
</div>
