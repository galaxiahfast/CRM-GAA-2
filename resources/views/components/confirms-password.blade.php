@props(['title' => 'Confirmar contraseña', 'content' => 'Por seguridad, confirma tu contraseña para continuar.', 'button' => 'Confirmar'])

@php
    $confirmableId = md5($attributes->wire('then'));
@endphp

<span
    {{ $attributes->wire('then') }}
    x-data
    x-ref="span"
    x-on:click="$wire.startConfirmingPassword('{{ $confirmableId }}')"
    x-on:password-confirmed.window="setTimeout(() => $event.detail.id === '{{ $confirmableId }}' && $refs.span.dispatchEvent(new CustomEvent('then', { bubbles: false })), 250);"
>
    {{ $slot }}
</span>

@once
{{-- La ventana sale al <body> para no quedar dentro de la vista con zoom; usa los estilos de [data-profile-overlay] de la página de Ajustes. --}}
<div x-data="{ show: @entangle('confirmingPassword').live }" x-on:keydown.escape.window="show = false" style="display: contents;">
    <template x-teleport="body">
        <div data-profile-overlay x-cloak x-show="show" class="fixed inset-0 z-[120] flex items-center justify-center overflow-y-auto p-[20px]" role="dialog" aria-modal="true" aria-label="{{ $title }}">
            <div x-show="show" x-on:click="show = false"
                x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-black/25 backdrop-blur-md"></div>

            <div x-show="show" x-trap.inert.noscroll="show"
                :style="{ transform: `scale(${(typeof viewScale === 'undefined' ? 100 : Number(viewScale)) / 100})` }"
                x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-4"
                class="relative w-full max-w-xl overflow-hidden rounded-xl border border-[#d4d4d8] bg-white shadow-2xl">
                <header class="flex h-[85px] items-center gap-[10px] border-b border-[#d4d4d8] bg-zinc-100 p-[20px]">
                    <span class="profile-icon-box flex shrink-0 items-center justify-center rounded-lg border border-[#d4d4d8] bg-white text-[#1A3A6B]">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                    </span>
                    <div class="min-w-0">
                        <h2 class="leading-none text-[#1A3A6B]">{{ $title }}</h2>
                        <div class="mt-[10px] flex flex-col"><p class="profile-lines text-black">{{ $content }}</p></div>
                    </div>
                </header>

                <div class="p-[20px]" x-data="{}" x-on:confirming-password.window="setTimeout(() => $refs.confirmable_password.focus(), 250)">
                    <input type="password" class="block h-11 w-full rounded-xl border border-[#d4d4d8] bg-white px-[20px] text-black" placeholder="Contraseña" autocomplete="current-password"
                        x-ref="confirmable_password"
                        wire:model="confirmablePassword"
                        wire:keydown.enter="confirmPassword" />

                    <x-input-error for="confirmable_password" class="mt-[10px]" />
                </div>

                <footer class="flex items-center justify-end gap-[20px] border-t border-[#d4d4d8] px-[20px]">
                    <button type="button" wire:click="stopConfirmingPassword" wire:loading.attr="disabled" class="inline-flex items-center gap-[10px] whitespace-nowrap bg-transparent py-[15px] leading-none text-[#1A3A6B] transition-colors hover:text-black focus:outline-none focus:ring-0 disabled:opacity-40">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 6l12 12M18 6L6 18" /></svg>
                        <span>Cancelar</span>
                    </button>

                    <button type="button" dusk="confirm-password-button" wire:click="confirmPassword" wire:loading.attr="disabled" class="inline-flex items-center gap-[10px] whitespace-nowrap bg-transparent py-[15px] leading-none text-[#1A3A6B] transition-colors hover:text-black focus:outline-none focus:ring-0 disabled:opacity-40">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 13l4 4L19 7" /></svg>
                        <span>{{ $button }}</span>
                    </button>
                </footer>
            </div>
        </div>
    </template>
</div>
@endonce
