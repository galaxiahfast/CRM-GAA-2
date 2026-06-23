@props(['text' => null, 'icon' => null, 'color' => 'blue'])

<div
    {{ $attributes->merge(['class' => 'flex flex-col items-center gap-2 hover:scale-110 transition-transform duration-200 cursor-pointer']) }}>
    <div
        class="h-16 w-16 rounded-full flex items-center justify-center shadow-md bg-{{ $color }}-50 text-{{ $color }}-600">
        @svg($icon, 'h-8 w-8')
    </div>
    <p class="text-sm text-gray-600 text-center font-medium">{{ $text }}</p>
</div>
