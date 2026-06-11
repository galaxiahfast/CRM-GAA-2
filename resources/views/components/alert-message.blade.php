@props(['type' => 'info'])

@php
    switch ($type) {
        case 'success':
            $bg = 'bg-green-50 dark:bg-gray-800';
            $border = 'border-green-300 dark:border-green-800';
            $text = 'text-green-800 dark:text-green-400';
            $title = '¡Éxito!';
            $icon = '<x-hugeicons-checkmark-circle-01 />';
            break;
        case 'error':
            $bg = 'bg-red-50 dark:bg-gray-800';
            $border = 'border-red-300 dark:border-red-800';
            $text = 'text-red-800 dark:text-red-400';
            $title = '¡Error!';
            $icon = '<svg class="shrink-0 inline w-4 h-4 me-3" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zm-1-4h2v2h-2v-2zm0-8h2v6h-2V6z"/></svg>';
            break;
        case 'warning':
            $bg = 'bg-yellow-50 dark:bg-gray-800';
            $border = 'border-yellow-300 dark:border-yellow-800';
            $text = 'text-yellow-800 dark:text-yellow-400';
            $title = '¡Advertencia!';
            $icon = '<svg class="shrink-0 inline w-4 h-4 me-3" fill="currentColor" viewBox="0 0 20 20"><path d="M8.257 3.099c.765-1.36 2.722-1.36 3.487 0l6.518 11.6c.75 1.334-.213 3-1.743 3H3.482c-1.53 0-2.493-1.666-1.743-3l6.518-11.6zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-2a1 1 0 01-1-1V7a1 1 0 112 0v3a1 1 0 01-1 1z"/></svg>';
            break;
        default: // info
            $bg = 'bg-blue-50 dark:bg-gray-800';
            $border = 'border-blue-300 dark:border-blue-800';
            $text = 'text-blue-800 dark:text-blue-400';
            $title = '¡Info!';
            $icon = '<svg class="shrink-0 inline w-4 h-4 me-3" fill="currentColor" viewBox="0 0 20 20"><path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/></svg>';
            break;
    }
@endphp

<div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
    class="fixed top-4 left-1/2 transform -translate-x-1/2 z-50 flex items-center p-4 mb-4 text-sm border rounded-lg {{ $bg }} {{ $text }} {{ $border }}"
    role="alert">
    {!! $icon !!}
    <span class="sr-only">{{ $title }}</span>
    <div>
        <span class="font-medium">{{ $title }}</span> {{ $slot }}
    </div>
</div>