@props(['click' => null, 'title' => '', 'subtitle' => '', 'class' => ''])
<div @if ($click) wire:click="{{ $click }}" @endif
    {{ $attributes->merge(['class' => "p-6 bg-white shadow-md hover:shadow-xl transition-all duration-300 rounded-2xl cursor-pointer $class"]) }}>
    <h1 class="text-3xl font-extrabold text-gray-800">{{ $title }}</h1>
    <p class="text-gray-500 mt-2">{{ $subtitle }}</p>
</div>
