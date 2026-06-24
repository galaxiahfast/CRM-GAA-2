@props(['formats' => ['csv', 'pdf', 'txt'], 'target' => 'export'])

@php
    $styles = [
        'csv' => 'bg-emerald-600 hover:bg-emerald-700',
        'pdf' => 'bg-red-600 hover:bg-red-700',
        'txt' => 'bg-gray-600 hover:bg-gray-700',
    ];
@endphp

<div class="flex flex-wrap items-center gap-2" wire:loading.class="opacity-60">
    <span class="text-sm text-gray-500 mr-1">Exportar:</span>
    @foreach ($formats as $format)
        <button type="button"
            wire:click="{{ $target }}('{{ $format }}')"
            wire:loading.attr="disabled"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-white text-sm rounded disabled:opacity-60 disabled:cursor-not-allowed {{ $styles[$format] ?? 'bg-blue-600 hover:bg-blue-700' }}">
            <x-feathericon-download class="w-4 h-4" />
            {{ strtoupper($format) }}
        </button>
    @endforeach
</div>
