@props([
    'pdf' => null,
    'state' => null,
    'statement' => null,
    'model',
    'fileType',
    'comprobanteNormalIsVisible' => true,
    'declarationType',
    'uploadInProcess' => null,
    'successMessage' => null,
])

@php
    // Determinar el ID único para el input
    $inputId = $state
        ? "{$state->id}_{$fileType}_{$declarationType}"
        : ($statement
            ? "{$statement->id}_{$fileType}_{$declarationType}"
            : "{$fileType}_{$declarationType}");

    $hasFile = $pdf && $pdf->isNotEmpty();
@endphp

@if ($comprobanteNormalIsVisible)
    <label for="{{ $inputId }}"
        class="transition-scale align-center relative flex aspect-[1/1] cursor-pointer flex-col items-center justify-center gap-4 rounded-lg bg-white shadow-2xl duration-200 hover:scale-[1.02] sm:aspect-[4/3]">

        @if ($state)
            <span class="absolute left-2 top-2 z-10 text-xs text-gray-500">
                {{ $state->key }}
            </span>
        @endif

        <img src="/img/static/{{ $hasFile ? 'pdf.svg' : 'pdf_02.svg' }}" alt="pdf"
            class="h-12 w-12 object-contain sm:h-16 sm:w-16">

        @if ($hasFile)
            @foreach ($pdf as $p)
                <div class="absolute right-0 top-0">
                    <x-dropdown align="right" width="30">
                        <x-slot name="trigger">
                            <button
                                class="absolute right-2 top-2 z-10 cursor-default text-gray-500">
                                @svg('feathericon-more-vertical')
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            @if ($p->pivot)
                                <a href="{{ asset('storage/' . $p->pivot->file_path) ?? '' }}"
                                    target="_blank"
                                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    Ver
                                </a>
                                <a wire:click.prevent="deletePdf({{ $p->pivot->id }})"
                                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    Eliminar
                                </a>
                            @endif
                        </x-slot>
                    </x-dropdown>
                </div>
                @if ($p->pivot)
                    <div class="absolute bottom-0 right-2">
                        <span class="text-xs text-gray-500">
                            {{ optional($p->pivot->updated_at)->format('d/m/Y') ?? ''}}
                        </span>
                    </div>
                @endif
                <span class="z-10 max-w-[90%] truncate text-center text-xs text-gray-500">
                    {{ Str::limit($p->pivot->original_name ?? '', 20) }}
                </span>
            @endforeach
        @endif

        <span class="text-sm font-bold">{{ $fileType }}</span>
        <span
            class="{{ $hasFile ? 'text-red-500 bg-red-100 px-2 py-1 rounded-full' : 'text-gray-500 bg-gray-100 px-2 py-1 rounded-full' }} absolute bottom-1 left-1 text-[10px] sm:bottom-2 sm:left-2 sm:text-xs">
            {{ $declarationType }}
        </span>

        <input accept=".pdf" wire:loading.attr="disabled" wire:model="{{ $model }}"
            wire:change="handleUpload({{ $state->id ?? 'null' }}, {{ $statement->id ?? 'null' }})"
            id="{{ $inputId }}" type="file" title="Seleccionar archivo pdf"
            class="absolute inset-0 h-full w-full cursor-pointer opacity-0" />
    </label>
@else
    <label for="{{ $inputId }}"
        class="align-center relative flex aspect-[4/3] cursor-default flex-col items-center justify-center gap-4 rounded-lg bg-gray-100 shadow-2xl">

        <img src="/img/static/{{ $hasFile ? 'pdf_02.svg' : 'pdf_02.svg' }}" alt="pdf"
            class="h-16 w-16 object-contain">

        @if ($hasFile)
            @foreach ($pdf as $p)
                <span class="z-10 text-xs text-gray-500">
                    {{ Str::limit($p->pivot->original_name ?? '', 20) }}

                </span>
                @if ($p->pivot)    
                    <div class="absolute bottom-0 right-2">
                        <span class="text-xs text-gray-500">
                            {{ optional($p->pivot->updated_at)->format('d/m/Y') ?? '' }}
                        </span>
                    </div>
                @endif
            @endforeach
        @endif

        <span class="text-sm font-bold text-gray-400">{{ $fileType }}</span>
        <span class="absolute bottom-2 left-2 text-xs text-gray-400">{{ $declarationType }}</span>
    </label>
@endif
