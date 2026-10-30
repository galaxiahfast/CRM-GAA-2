<article class="overflow-hidden rounded-xl border border-[#d4d4d8] bg-white">
    @php
        $twoFactorButton = 'inline-flex items-center gap-[10px] whitespace-nowrap bg-transparent py-[15px] leading-none text-[#1A3A6B] transition-colors hover:text-black focus:outline-none focus:ring-0 disabled:cursor-wait disabled:opacity-40';
    @endphp

    <header class="profile-strip flex items-center justify-between gap-[20px] border-b border-[#d4d4d8] bg-zinc-100 p-[20px]">
        <div class="flex min-w-0 items-center gap-[10px]">
            <span class="profile-icon-box flex shrink-0 items-center justify-center rounded-lg border border-[#d4d4d8] bg-white text-[#1A3A6B]">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
            </span>
            <div class="min-w-0">
                <h3 class="leading-none text-[#1A3A6B]">Autenticación de dos factores</h3>
                <p class="mt-[10px] leading-none text-black">Agrega seguridad adicional a tu cuenta.</p>
            </div>
        </div>
        <span class="inline-flex shrink-0 items-center gap-[10px] leading-none text-black {{ $this->enabled ? 'rounded-full border border-[#d4d4d8] bg-white px-[20px] py-[10px]' : '' }}">
            <span class="h-2 w-2 rounded-full {{ $this->enabled && ! $showingConfirmation ? 'bg-emerald-500' : 'bg-gray-400' }}"></span>{{ $this->enabled ? ($showingConfirmation ? 'Pendiente de confirmar' : 'Activada') : 'Desactivada' }}
        </span>
    </header>

    <div class="flex flex-col gap-[10px] p-[20px]">
        <p class="leading-none text-[#1A3A6B]">
            @if ($this->enabled)
                @if ($showingConfirmation)
                    Termina de habilitar la autenticación de dos factores.
                @else
                    Has habilitado la autenticación de dos factores.
                @endif
            @else
                No has habilitado la autenticación de dos factores.
            @endif
        </p>

        <p class="profile-lines max-w-3xl text-justify text-black">
            Cuando la autenticación de dos factores está habilitada, se te pedirá un código seguro y aleatorio al iniciar sesión. Puedes obtener este código desde la aplicación Google Authenticator de tu teléfono.
        </p>

        @if ($this->enabled)
            @if ($showingRecoveryCodes)
                <p class="profile-lines max-w-3xl text-justify text-black">
                    Guarda estos códigos de recuperación en un administrador de contraseñas seguro. Sirven para recuperar el acceso a tu cuenta si pierdes el dispositivo de autenticación de dos factores.
                </p>

                <div class="flex max-w-xl flex-col gap-[10px] rounded-xl border border-[#d4d4d8] bg-zinc-100 p-[20px]">
                    @foreach (json_decode(decrypt($this->user->two_factor_recovery_codes), true) as $code)
                        <p class="font-mono leading-none text-black">{{ $code }}</p>
                    @endforeach
                </div>
            @endif
        @endif

        <div class="profile-actions flex flex-wrap items-center gap-x-[20px]">
            @if (! $this->enabled)
                <x-confirms-password wire:then="enableTwoFactorAuthentication">
                    <button type="button" wire:loading.attr="disabled" class="{{ $twoFactorButton }}">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m5.6-3.4A11.9 11.9 0 0112 3a11.9 11.9 0 01-8.6 3.6A12 12 0 003 10c0 5.2 3.8 9.5 9 11 5.2-1.5 9-5.8 9-11 0-1.2-.1-2.3-.4-3.4z" /></svg>
                        <span>Habilitar</span>
                    </button>
                </x-confirms-password>
            @else
                @if ($showingQrCode)
                    <button type="button" x-on:click="$dispatch('open-two-factor-popup')" class="{{ $twoFactorButton }}">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 4h6v6H4V4zm10 0h6v6h-6V4zM4 14h6v6H4v-6zm10 0h2m4 0v6h-6m0-6v2m0 4h.01" /></svg>
                        <span>{{ $showingConfirmation ? 'Ingresar código' : 'Ver código QR' }}</span>
                    </button>
                @endif

                @if ($showingRecoveryCodes)
                    <x-confirms-password wire:then="regenerateRecoveryCodes">
                        <button type="button" class="{{ $twoFactorButton }}">
                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 11a8.1 8.1 0 0 0-15.5-2M4 4v5h5m-5 4a8.1 8.1 0 0 0 15.5 2M20 20v-5h-5" /></svg>
                            <span>Regenerar códigos de recuperación</span>
                        </button>
                    </x-confirms-password>
                @elseif (! $showingConfirmation)
                    <x-confirms-password wire:then="showRecoveryCodes">
                        <button type="button" class="{{ $twoFactorButton }}">
                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 7a4 4 0 11-2.9 6.76L9 17H7v2H5v2H3v-3l7.24-7.24A4 4 0 0115 7z" /></svg>
                            <span>Mostrar códigos de recuperación</span>
                        </button>
                    </x-confirms-password>
                @endif

                @if ($showingConfirmation)
                    <x-confirms-password wire:then="disableTwoFactorAuthentication">
                        <button type="button" wire:loading.attr="disabled" class="{{ $twoFactorButton }}">
                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 6l12 12M18 6L6 18" /></svg>
                            <span>Cancelar</span>
                        </button>
                    </x-confirms-password>
                @else
                    <x-confirms-password wire:then="disableTwoFactorAuthentication">
                        <button type="button" wire:loading.attr="disabled" class="profile-danger-text inline-flex items-center gap-[10px] whitespace-nowrap bg-transparent py-[15px] leading-none transition-colors focus:outline-none focus:ring-0 disabled:cursor-wait disabled:opacity-40">
                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M18.36 6.64A9 9 0 115.64 18.36 9 9 0 0118.36 6.64zM6 6l12 12" /></svg>
                            <span>Deshabilitar</span>
                        </button>
                    </x-confirms-password>
                @endif
            @endif
        </div>
    </div>

    @if ($this->enabled && $showingQrCode)
        {{-- Ventana emergente con el código QR, la clave y el campo del código. Sale al <body> para no quedar dentro de la vista con zoom. --}}
        <template x-teleport="body">
            <div data-profile-overlay x-data="{ open: true }" x-on:open-two-factor-popup.window="open = true" x-on:keydown.escape.window="open = false"
                x-effect="if (open) $nextTick(() => $refs.twoFactorCode?.focus())"
                x-cloak x-show="open" class="fixed inset-0 z-[100] flex items-center justify-center overflow-y-auto p-[20px]" role="dialog" aria-modal="true" aria-label="Autenticación de dos factores">
                <div x-show="open" x-on:click="open = false"
                    x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                    class="fixed inset-0 bg-black/25 backdrop-blur-md"></div>

                <div x-show="open"
                    :style="{ transform: `scale(${(typeof viewScale === 'undefined' ? 100 : Number(viewScale)) / 100})` }"
                    x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                    class="relative w-full max-w-xl overflow-hidden rounded-xl border border-[#d4d4d8] bg-white shadow-2xl">
                    <header class="profile-strip flex items-center gap-[10px] border-b border-[#d4d4d8] bg-zinc-100 p-[20px]">
                        <span class="profile-icon-box flex shrink-0 items-center justify-center rounded-lg border border-[#d4d4d8] bg-white text-[#1A3A6B]">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 4h6v6H4V4zm10 0h6v6h-6V4zM4 14h6v6H4v-6zm10 0h2m4 0v6h-6m0-6v2m0 4h.01" /></svg>
                        </span>
                        <div class="min-w-0">
                            <h2 class="leading-none text-[#1A3A6B]">Autenticación de dos factores</h2>
                            <div class="mt-[10px] flex flex-col"><p class="profile-lines text-black">{{ $showingConfirmation ? 'Escanea el código QR y escribe el código que genera tu app' : 'Escanea el código QR con tu app de autenticación' }}</p></div>
                        </div>
                    </header>

                    <div class="flex flex-col gap-[20px] p-[20px]">
                        <p class="profile-lines text-black">
                            @if ($showingConfirmation)
                                Para terminar de habilitar la autenticación de dos factores, escanea el siguiente código QR con la aplicación de autenticación de tu teléfono, o ingresa la clave de configuración y escribe el código generado.
                            @else
                                La autenticación de dos factores ya está habilitada. Escanea el siguiente código QR con la aplicación de autenticación de tu teléfono o ingresa la clave de configuración.
                            @endif
                        </p>

                        <div class="flex flex-wrap items-start gap-[20px]">
                            <div class="profile-qr inline-block shrink-0 rounded-xl border border-[#d4d4d8] p-[10px]">
                                {!! $this->user->twoFactorQrCodeSvg() !!}
                            </div>

                            <div class="min-w-[180px] flex-1">
                                <p class="leading-none text-[#1A3A6B]">Clave de configuración</p>
                                <div class="mt-[10px]"><p class="profile-lines break-all text-black">{{ decrypt($this->user->two_factor_secret) }}</p></div>

                                @if ($showingConfirmation)
                                    <label for="code" class="mb-[10px] mt-[20px] block leading-none text-[#1A3A6B]">Código</label>

                                    <input id="code" type="text" name="code" x-ref="twoFactorCode" class="block h-11 w-full rounded-xl border border-[#d4d4d8] bg-white px-[20px] text-black" inputmode="numeric" autocomplete="one-time-code"
                                        placeholder="Código de 6 dígitos"
                                        wire:model="code"
                                        wire:keydown.enter="confirmTwoFactorAuthentication" />

                                    <x-input-error for="code" class="mt-[10px]" />
                                @endif
                            </div>
                        </div>
                    </div>

                    <footer class="flex items-center justify-end gap-[20px] border-t border-[#d4d4d8] px-[20px]">
                        <button type="button" x-on:click="open = false" class="{{ $twoFactorButton }}">
                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 6l12 12M18 6L6 18" /></svg>
                            <span>Cerrar</span>
                        </button>

                        @if ($showingConfirmation)
                            <x-confirms-password wire:then="confirmTwoFactorAuthentication">
                                <button type="button" wire:loading.attr="disabled" class="{{ $twoFactorButton }}">
                                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 13l4 4L19 7" /></svg>
                                    <span>Confirmar</span>
                                </button>
                            </x-confirms-password>
                        @endif
                    </footer>
                </div>
            </div>
        </template>
    @endif
</article>
