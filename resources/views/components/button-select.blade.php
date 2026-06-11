<button
    {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center px-4 py-2 hover:border-matisse-900 border border rounded-md font-semibold text-xs text-black uppercase tracking-widest disabled:opacity-50 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
