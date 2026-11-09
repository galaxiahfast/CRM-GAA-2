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

{{-- Tarjeta de archivo PDF compacta con el diseño de los paneles: icono a la izquierda y dos líneas de texto
     (15px entre líneas). Toda la tarjeta es el botón para subir el PDF. Los nombres de los campos, wire:model,
     wire:change y deletePdf son los mismos de siempre. --}}
@if ($comprobanteNormalIsVisible)
    <label for="{{ $inputId }}" title="{{ $hasFile ? 'Archivo cargado' : 'Haz clic para subir el PDF' }}"
        class="panel-upload panel-surface panel-border group relative flex min-h-[85px] cursor-pointer items-center gap-[15px] rounded-xl border p-[20px] {{ $hasFile ? 'pr-[60px]' : 'border-dashed' }}">

        <span class="{{ $hasFile ? 'panel-tone-green' : 'panel-muted group-hover:text-[color:var(--panel-accent)]' }} shrink-0 transition-colors">
            @if ($hasFile)
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8zM14 2v6h6M9 15l2 2 4-4" /></svg>
            @else
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M17 8l-5-5-5 5M12 3v12" /></svg>
            @endif
        </span>

        <span class="min-w-0 flex-1">
            <span class="block truncate leading-none">@if ($state)<span class="panel-muted">{{ $state->key }} · </span>@endif{{ $fileType }} <span class="panel-muted">{{ $declarationType }}</span></span>

            @if ($hasFile)
                @foreach ($pdf as $p)
                    <span class="mt-[15px] block truncate leading-none tabular-nums" title="{{ $p->pivot->original_name ?? '' }}">
                        {{ Str::limit($p->pivot->original_name ?? '', 18) }} · {{ optional($p->pivot->updated_at)->format('d/m/Y') ?? '' }}
                    </span>
                @endforeach
            @else
                <span class="panel-tone-amber mt-[15px] flex items-center gap-[10px] leading-none group-hover:text-[color:var(--panel-accent)]">
                    <span class="h-2 w-2 shrink-0 rounded-full bg-current"></span>
                    <span class="group-hover:hidden">Pendiente</span>
                    <span class="hidden group-hover:inline">Haz clic para subir el PDF</span>
                </span>
            @endif
        </span>

        @if ($hasFile)
            @foreach ($pdf as $p)
                <div class="absolute right-[10px] top-1/2 z-10 -translate-y-1/2">
                    <x-dropdown align="right" width="48" content-classes="panel-surface panel-border border p-[5px] !rounded-xl" dropdown-classes="!top-full !mt-[8px] !rounded-xl">
                        <x-slot name="trigger">
                            <button type="button" aria-label="Opciones del archivo"
                                class="panel-icon-button flex h-9 w-9 cursor-pointer items-center justify-center rounded-xl focus:outline-none focus:ring-0">
                                @svg('feathericon-more-vertical', 'h-5 w-5')
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            @if ($p->pivot)
                                <a href="{{ asset('storage/' . $p->pivot->file_path) ?? '' }}"
                                    target="_blank"
                                    class="panel-tab block rounded-lg bg-transparent px-[20px] py-[15px] leading-none focus:outline-none">
                                    Ver
                                </a>
                                <a wire:click.prevent="deletePdf({{ $p->pivot->id }})"
                                    class="panel-menu-item panel-menu-danger block cursor-pointer rounded-lg px-[20px] py-[15px] leading-none focus:outline-none">
                                    Eliminar
                                </a>
                            @endif
                        </x-slot>
                    </x-dropdown>
                </div>
            @endforeach
        @endif

        <input accept=".pdf" wire:loading.attr="disabled" wire:model="{{ $model }}"
            wire:change="handleUpload({{ $state->id ?? 'null' }}, {{ $statement->id ?? 'null' }})"
            id="{{ $inputId }}" type="file" title="Seleccionar archivo pdf"
            class="absolute inset-0 h-full w-full cursor-pointer opacity-0" />
    </label>
@else
    {{-- Comprobante normal bloqueado mientras exista una declaración complementaria --}}
    <label for="{{ $inputId }}"
        class="panel-header panel-border panel-muted relative flex min-h-[85px] cursor-default items-center gap-[15px] rounded-xl border p-[20px]">

        <span class="shrink-0">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8zM14 2v6h6" /></svg>
        </span>

        <span class="min-w-0 flex-1">
            <span class="block truncate leading-none">{{ $fileType }} {{ $declarationType }}</span>
            <span class="mt-[15px] block truncate leading-none">
                @if ($hasFile)
                    @foreach ($pdf as $p)
                        {{ Str::limit($p->pivot->original_name ?? '', 18) }} · {{ optional($p->pivot->updated_at)->format('d/m/Y') ?? '' }}
                    @endforeach
                @else
                    Bloqueado por la complementaria
                @endif
            </span>
        </span>
    </label>
@endif
