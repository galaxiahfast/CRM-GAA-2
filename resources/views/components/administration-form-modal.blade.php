@props([
    'submit',
    'title',
    'subtitle' => null,
    'modalId' => 'administration-form',
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
    <style>
        [data-administration-modal] {
            font-size: 15px;
        }
        [data-administration-modal] :where(p, span, button, input, select, option, label, h1, h2, h3, small) {
            font-size: 15px !important;
        }
        [data-administration-modal] .administration-form-panel,
        [data-administration-modal] .administration-form-panel > form,
        [data-administration-modal] .administration-form-panel .bg-white {
            background-color: #ffffff !important;
        }

        /* Encabezado, cuerpo y pie en blanco; las líneas los separan. */
        [data-administration-modal] .administration-form-panel header,
        [data-administration-modal] .administration-form-panel footer {
            background-color: #ffffff !important;
            border-color: #d4d4d8 !important;
        }
        /* Scroll igual al del menú principal: delgado (3px), negro, sin flechas y siempre visible. */
        .administration-form-scrollbar {
            scrollbar-width: thin;
            scrollbar-color: #000000 transparent;
        }

        @supports selector(::-webkit-scrollbar) {
            .administration-form-scrollbar {
                scrollbar-width: auto;
                scrollbar-color: auto;
            }

            .administration-form-scrollbar::-webkit-scrollbar {
                width: 3px;
                height: 3px;
            }

            .administration-form-scrollbar::-webkit-scrollbar-track {
                background: transparent;
            }

            .administration-form-scrollbar::-webkit-scrollbar-thumb {
                min-height: 32px;
                border: 0;
                background: #000000;
                border-radius: 9999px;
            }

            .administration-form-scrollbar::-webkit-scrollbar-button,
            .administration-form-scrollbar::-webkit-scrollbar-corner {
                display: none;
                width: 0;
                height: 0;
            }
        }

        /* El scroll (3px) siempre reserva su carril, y se descuenta del relleno derecho (20px - 3px):
           así el contenido queda con 20px a cada lado, centrado y alineado con el encabezado y el pie,
           haya o no scroll. */
        [data-administration-modal] .administration-form-scrollbar {
            scrollbar-gutter: stable;
        }

        [data-administration-modal] .administration-form-scrollbar > div {
            padding-right: 17px !important;
        }

        /* Refuerzo: el scroll del cuerpo del formulario siempre en negro, sin borde. */
        [data-administration-modal] .administration-form-scrollbar::-webkit-scrollbar-thumb {
            border: 0 !important;
            background: #000000 !important;
        }

        /* Secciones sin cajas ni líneas divisorias; mismo espacio título → etiqueta → campo (10px). */
        [data-administration-modal] section:not([class*="bg-blue-50"]) {
            border: 0 !important;
            border-radius: 0 !important;
            background: transparent !important;
            padding: 0 !important;
        }

        /* Línea divisoria entre secciones: 20px de aire arriba y abajo (la última sección no lleva). */
        [data-administration-modal] section:not([class*="bg-blue-50"]):not(:last-child) {
            border-bottom: 1px solid #d4d4d8 !important;
            padding-bottom: 20px !important;
        }

        /* Botón cerrar (×): siempre relleno de rojo, con la × en blanco. */
        [data-administration-modal] .administration-form-panel header button[aria-label="Cerrar"] {
            border-color: #dc2626 !important;
            background-color: #dc2626 !important;
            color: #ffffff !important;
        }

        [data-administration-modal] .administration-form-panel header button[aria-label="Cerrar"]:hover {
            border-color: #b91c1c !important;
            background-color: #b91c1c !important;
            color: #ffffff !important;
        }

        /* Sin negritas dentro del formulario: solo el título principal. */
        [data-administration-modal] *:not(h1) {
            font-weight: 400 !important;
        }

        [data-administration-modal] header h1 {
            font-weight: 600 !important;
        }

        /* Cajas de texto sin aire interno (line-height: 1): la separación entre elementos es exacta
           y no depende del tamaño de letra ni de si es título, etiqueta o párrafo. */
        [data-administration-modal] section h2 {
            margin: 0 0 var(--admin-form-gap, 10px) !important;
            font-size: 15px !important;
            letter-spacing: 0 !important;
            line-height: 1 !important;
            text-transform: none !important;
            color: #000 !important;
        }

        [data-administration-modal] header h1,
        [data-administration-modal] header p {
            line-height: 1 !important;
            color: #000000 !important;
        }

        [data-administration-modal] label {
            line-height: 1 !important;
        }

        [data-administration-modal] textarea {
            resize: none;
        }

        /* Sin borde ni halo morado al enfocar: el campo conserva su borde gris. */
        [data-administration-modal] :is(input:not([type="checkbox"]):not([type="radio"]), select, textarea, [role="combobox"]):focus,
        [data-administration-modal] :is(input:not([type="checkbox"]):not([type="radio"]), select, textarea, [role="combobox"]):focus-visible {
            border-color: #d4d4d8 !important;
            outline: none !important;
            box-shadow: none !important;
            --tw-ring-shadow: 0 0 #0000 !important;
            --tw-ring-offset-shadow: 0 0 #0000 !important;
        }
        /* Mismo gris intermedio (#d4d4d8) en bordes de campos, botones e icono del encabezado. */
        [data-administration-modal] :is(input:not([type="checkbox"]):not([type="radio"]), select, textarea, [role="combobox"]) {
            border-color: #d4d4d8 !important;
        }

        [data-administration-modal] header div.border {
            border-color: #d4d4d8 !important;
        }

        [data-administration-modal] footer button {
            min-height: 44px;
            border: 1px solid #d4d4d8 !important;
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
        style="max-width: 620px; height: min(620px, calc(100dvh - 40px)); max-height: calc(100dvh - 40px); font-size: 15px; overscroll-behavior: contain;"
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
