<a href="{{ route('soporte.ticket') }}" wire:poll.15s.visible
    class="relative flex h-[42px] w-[42px] items-center justify-center rounded-lg border-0 bg-transparent text-white transition-colors duration-200 hover:bg-white/10 focus:outline-none focus:ring-0"
    aria-label="Abrir chat de soporte{{ $unread > 0 ? ' ('.$unread.' mensajes sin leer)' : '' }}"
    title="Chat de soporte">
    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M21 11.5a8.4 8.4 0 0 1-8.9 8.4 9 9 0 0 1-3.4-.7L3 21l1.9-5.1A8.4 8.4 0 0 1 3 11.5 8.5 8.5 0 0 1 12 3a8.5 8.5 0 0 1 9 8.5Z" />
    </svg>
    @if ($unread > 0)
        <span class="absolute right-0 top-0 flex h-5 min-w-5 items-center justify-center rounded-full bg-red-600 text-center text-[9px] leading-none text-white ring-2 ring-[#1a3a6b] tabular-nums {{ $unread < 10 ? 'w-5 p-0' : 'px-1' }}">{{ $unread > 99 ? '99+' : $unread }}</span>
    @endif
</a>
