@props([
    'title',
    'subtitle' => null,
])

{{-- Marco común de las listas de Administración: mismo lenguaje visual que el panel y los modales
     (fondo blanco, bordes zinc-200, botón negro, 20px de separación). --}}
<div data-admin-list class="min-h-[calc(var(--app-dvh)-123px)] w-full min-w-0 bg-[#ffffff] text-[15px] text-black">
    <style>
        /* Sin borde ni halo morado al enfocar: el campo conserva su borde gris (igual que en los modales). */
        [data-admin-list] input.admin-list-search[id]:focus,
        [data-admin-list] input.admin-list-search[id]:focus-visible {
            border-color: #d4d4d8 !important;
            box-shadow: none !important;
            outline: none !important;
        }
    </style>

    <div class="w-full space-y-[20px] p-[20px] sm:p-[30px] xl:p-[50px]">
        <div class="flex flex-col justify-between gap-[20px] lg:flex-row lg:items-center">
            <div class="flex min-w-0 items-center gap-[15px]">
                <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl border border-zinc-200 bg-[#ffffff] text-black">
                    {{ $icon }}
                </span>
                <div class="min-w-0">
                    <h1 class="text-xl font-normal leading-none tracking-tight text-[#1A3A6B]">{{ $title }}</h1>
                    @if ($subtitle)
                        <p class="mt-[10px] leading-none text-black">{{ $subtitle }}</p>
                    @endif
                </div>
            </div>

            @isset($actions)
                <div class="flex flex-col gap-[10px] sm:flex-row sm:items-center">
                    {{ $actions }}
                </div>
            @endisset
        </div>

        {{ $slot }}
    </div>
</div>
