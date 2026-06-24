@props([
    'message' => null,
    'typeMessage' => null,
    'position' => 'bottom-right',
    'icon' => '',
])

@php
    $type = [
        'SUCCESS' => 'success',
        'ERROR' => 'error',
        'ALERT' => 'alert',
    ];
@endphp

<div id="toast-default"
    class="@if ($position === 'bottom-right') bottom-4 right-4 @endif @if ($position === 'top-right') top-4 right-4 @endif @if ($position === 'bottom-left') bottom-4 left-4 @endif @if ($position === 'top-left') top-4 left-4 @endif @if ($typeMessage === $type['SUCCESS']) bg-green-100 border border-green-500 text-green-600 @endif @if ($typeMessage === $type['ERROR']) bg-red-100 border border-red-500 text-red-600 @endif @if ($typeMessage === $type['ALERT']) bg-orange-100 border border-orange-500 text-orange-600 @endif fixed z-50 flex w-full max-w-xs transform items-center rounded-lg p-4 shadow-sm transition-all duration-300 ease-in-out"
    role="alert" x-data="{ show: true }" x-show="show"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 translate-y-2"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 translate-y-2"
    @toast-hidden.window="if ($event.detail.message === '{{ $message }}') show = false"
    x-init="setTimeout(() => {
        show = false;
        $wire.set('successMessage', null);
    }, 3000)">

    <div
        class="@if ($typeMessage === $type['SUCCESS']) bg-green-200 text-green-600 @endif @if ($typeMessage === $type['ERROR']) bg-red-200 text-red-600 @endif @if ($typeMessage === $type['ALERT']) bg-orange-200 text-orange-600 @endif inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg">
        @svg($icon)
    </div>

    <div class="ms-3 text-sm font-normal">{{ $message }}</div>

    <button type="button"
        class="@if ($typeMessage === $type['SUCCESS']) bg-green-100 text-green-600 hover:bg-green-200 hover:text-green-500 focus:ring-green-300 @endif @if ($typeMessage === $type['ERROR']) bg-red-100 text-red-600 hover:bg-red-200 hover:text-red-500 focus:ring-red-300 @endif @if ($typeMessage === $type['ALERT']) bg-orange-100 text-orange-600 hover:bg-orange-200 hover:text-orange-500 focus:ring-orange-300 @endif -mx-1.5 -my-1.5 ms-auto inline-flex h-8 w-8 items-center justify-center rounded-lg p-1.5 focus:ring-2"
        @click="show = false; $wire.set('successMessage', null);" aria-label="Close">
        <span class="sr-only">Close</span>
        <svg class="h-3 w-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none"
            viewBox="0 0 14 14">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
        </svg>
    </button>
</div>
