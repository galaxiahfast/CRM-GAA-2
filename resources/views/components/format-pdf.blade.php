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

{{-- Tarjeta de archivo PDF con el diseño de los paneles (variables y clases panel-* de x-panel-styles).
     Solo icono y texto, sin recuadros dentro de la tarjeta. Los nombres de los campos, wire:model, wire:change
     y deletePdf son los mismos de siempre. --}}
@if ($comprobanteNormalIsVisible)
    <label for="{{ $inputId }}"
        class="panel-upload panel-surface panel-border relative flex min-h-[200px] cursor-pointer flex-col items-center gap-[10px] rounded-xl border p-[20px] text-center {{ $hasFile ? '' : 'border-dashed' }}">

        @if ($state)
            <span class="panel-muted absolute left-[20px] top-[20px] z-10 leading-none">
                {{ $state->key }}
            </span>
        @endif

        <span class="{{ $hasFile ? 'panel-tone-green' : 'panel-muted' }} mt-[5px]">
            @if ($hasFile)
                <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8zM14 2v6h6M9 15l2 2 4-4" /></svg>
            @else
                <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M17 8l-5-5-5 5M12 3v12" /></svg>
            @endif
        </span>

        <span class="leading-none">{{ $fileType }}</span>
        <span class="panel-muted leading-none">{{ $declarationType }}</span>

        @if ($hasFile)
            @foreach ($pdf as $p)
                <div class="absolute right-[10px] top-[10px] z-10">
                    <x-dropdown align="right" width="48" content-classes="panel-surface panel-border border p-[5px] !rounded-xl" dropdown-classes="!top-full !mt-[8px] !rounded-xl">
                        <x-slot name="trigger">
                            <button type="button" aria-label="Opciones del archivo"
                                class="panel-icon-button flex h-9 w-9 cursor-default items-center justify-center rounded-xl focus:outline-none focus:ring-0">
                                @svg('feathericon-more-vertical', 'h-5 w-5')
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            @if ($p->pivot)
                                <a href="{{ asset('storage/' . $p->pivot->file_path) ?? '' }}"
                                    target="_blank"
                                    class="panel-menu-item block rounded-lg px-[15px] py-[10px] focus:outline-none">
                                    Ver
                                </a>
                                <a wire:click.prevent="deletePdf({{ $p->pivot->id }})"
                                    class="panel-menu-item panel-menu-danger block cursor-pointer rounded-lg px-[15px] py-[10px] focus:outline-none">
                                    Eliminar
                                </a>
                            @endif
                        </x-slot>
                    </x-dropdown>
                </div>

                <span class="z-10 mt-[5px] block max-w-full truncate leading-none" title="{{ $p->pivot->original_name ?? '' }}">
                    {{ Str::limit($p->pivot->original_name ?? '', 20) }}
                </span>
                <span class="panel-muted leading-none tabular-nums">
                    {{ optional($p->pivot->updated_at)->format('d/m/Y') ?? '' }}
                </span>
            @endforeach
        @else
            <span class="panel-muted mt-[5px] leading-snug">Haz clic para subir el PDF</span>
        @endif

        <input accept=".pdf" wire:loading.attr="disabled" wire:model="{{ $model }}"
            wire:change="handleUpload({{ $state->id ?? 'null' }}, {{ $statement->id ?? 'null' }})"
            id="{{ $inputId }}" type="file" title="Seleccionar archivo pdf"
            class="absolute inset-0 h-full w-full cursor-pointer opacity-0" />
    </label>
@else
    {{-- Comprobante normal bloqueado mientras exista una declaración complementaria --}}
    <label for="{{ $inputId }}"
        class="panel-header panel-border panel-muted relative flex min-h-[200px] cursor-default flex-col items-center gap-[10px] rounded-xl border p-[20px] text-center">

        <span class="mt-[5px]">
            <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8zM14 2v6h6" /></svg>
        </span>

        <span class="leading-none">{{ $fileType }}</span>
        <span class="leading-none">{{ $declarationType }}</span>

        @if ($hasFile)
            @foreach ($pdf as $p)
                <span class="mt-[5px] block max-w-full truncate leading-none" title="{{ $p->pivot->original_name ?? '' }}">
                    {{ Str::limit($p->pivot->original_name ?? '', 20) }}
                </span>
                <span class="leading-none tabular-nums">
                    {{ optional($p->pivot->updated_at)->format('d/m/Y') ?? '' }}
                </span>
            @endforeach
        @endif
    </label>
@endif
