@props(['disabled' => false])

<input 
    {{ $disabled ? 'disabled' : '' }} 
    {!! $attributes->merge([
        'class' => $disabled 
            ? 'border-gray-300 bg-gray-200 cursor-not-allowed rounded-md  focus:ring-0' 
            : 'border-gray-300 focus:border-matisse-500 focus:ring-matisse-500 rounded-md shadow-sm' 
    ]) !!}
>
