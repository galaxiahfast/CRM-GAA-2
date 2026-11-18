<article class="panel-surface panel-border overflow-hidden rounded-xl border">
    @if ($errors->any())
        <x-error-toast :wire:key="'two-factor-error-'.md5($errors->first())" title="No se pudo confirmar" :message="collect($errors->all())->unique()->implode(' ')" />
    @endif

    <header class="panel-header panel-border flex min-h-[85px] flex-wrap items-center justify-between gap-x-[20px] gap-y-[10px] border-b p-[20px]">
        <div class="min-w-0"><h3 class="panel-accent leading-none">Autenticación de dos factores</h3><p class="pf-sub mt-[5px]">Agrega seguridad adicional a tu cuenta.</p></div>
        <p class="inline-flex items-center gap-[10px] leading-none"><span class="h-2 w-2 rounded-full {{ $this->enabled && ! $showingConfirmation ? 'bg-emerald-500' : 'bg-gray-400' }}"></span>{{ $this->enabled ? ($showingConfirmation ? 'Pendiente de confirmar' : 'Activada') : 'Desactivada' }}</p>
    </header>

    <div class="flex flex-col gap-[20px] p-[20px]">
        <div>
            <p class="panel-accent leading-[30px]">
                @if ($this->enabled)
                    {{ $showingConfirmation ? 'Termina de habilitar la autenticación de dos factores.' : 'Has habilitado la autenticación de dos factores.' }}
                @else
                    No has habilitado la autenticación de dos factores.
                @endif
            </p>
            <p class="max-w-3xl text-justify leading-[30px]">
                Cuando la autenticación de dos factores está habilitada, se te pedirá un código seguro y aleatorio al iniciar sesión. Puedes obtener este código desde la aplicación Google Authenticator de tu teléfono.
            </p>
        </div>

        @if ($this->enabled && $showingRecoveryCodes)
            <div>
                <p class="max-w-3xl text-justify leading-[30px]">
                    Guarda estos códigos de recuperación en un administrador de contraseñas seguro. Sirven para recuperar el acceso a tu cuenta si pierdes el dispositivo de autenticación de dos factores.
                </p>

                <div class="panel-header panel-border mt-[20px] flex max-w-xl flex-col rounded-xl border p-[20px]">
                    @foreach (json_decode(decrypt($this->user->two_factor_recovery_codes), true) as $code)
                        <p class="font-mono leading-[30px]">{{ $code }}</p>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="flex flex-wrap items-center gap-x-[20px] gap-y-[15px]">
            @if (! $this->enabled)
                <x-confirms-password wire:then="enableTwoFactorAuthentication">
                    <button type="button" wire:loading.attr="disabled" class="pf-primary rounded-xl px-[20px] py-[15px] leading-none">Habilitar</button>
                </x-confirms-password>
            @else
                @if ($showingQrCode)
                    <button type="button" x-on:click="$dispatch('open-two-factor-popup')" class="pf-link leading-none focus:outline-none focus:ring-0">{{ $showingConfirmation ? 'Ingresar código' : 'Ver código QR' }}</button>
                @endif

                @if ($showingRecoveryCodes)
                    <x-confirms-password wire:then="regenerateRecoveryCodes">
                        <button type="button" class="pf-link leading-none focus:outline-none focus:ring-0">Regenerar códigos de recuperación</button>
                    </x-confirms-password>
                @elseif (! $showingConfirmation)
                    <x-confirms-password wire:then="showRecoveryCodes">
                        <button type="button" class="pf-link leading-none focus:outline-none focus:ring-0">Mostrar códigos de recuperación</button>
                    </x-confirms-password>
                @endif

                <x-confirms-password wire:then="disableTwoFactorAuthentication">
                    @if ($showingConfirmation)
                        <button type="button" wire:loading.attr="disabled" class="pf-link leading-none focus:outline-none focus:ring-0">Cancelar</button>
                    @else
                        <button type="button" wire:loading.attr="disabled" class="whitespace-nowrap leading-none focus:outline-none focus:ring-0" style="color: var(--panel-danger)">Deshabilitar</button>
                    @endif
                </x-confirms-password>
            @endif
        </div>
    </div>

    @if ($this->enabled && $showingQrCode)
        {{-- Ventana emergente con el código QR, la clave y el campo del código. Sale al <body> para no quedar dentro de la vista con zoom. --}}
        <template x-teleport="body">
            <div data-panel data-productivity data-profile x-data="{ open: true }" x-on:open-two-factor-popup.window="open = true" x-on:keydown.escape.window="open = false"
                x-effect="if (open) $nextTick(() => $refs.twoFactorCode?.focus())"
                x-cloak x-show="open" class="fixed inset-0 z-[100] flex items-center justify-center overflow-y-auto p-[20px]" style="background: transparent; min-height: 0" role="dialog" aria-modal="true" aria-label="Autenticación de dos factores">
                <div x-show="open" x-on:click="open = false"
                    x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                    class="fixed inset-0 bg-black/25 backdrop-blur-md"></div>

                <div x-show="open"
                    x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                    class="app-zoom-overlay panel-surface panel-border relative w-full max-w-xl overflow-hidden rounded-xl border shadow-[0_18px_55px_rgba(0,0,0,0.30)]">
                    <header class="panel-header panel-border flex min-h-[85px] items-center border-b p-[20px]">
                        <div class="min-w-0">
                            <h2 class="panel-accent leading-none">Autenticación de dos factores</h2>
                            <p class="pf-sub mt-[5px]">{{ $showingConfirmation ? 'Escanea el código QR y escribe el código que genera tu app' : 'Escanea el código QR con tu app de autenticación' }}</p>
                        </div>
                    </header>

                    <div class="flex flex-col gap-[20px] p-[20px]">
                        <p class="text-justify leading-[30px]">
                            @if ($showingConfirmation)
                                Para terminar de habilitar la autenticación de dos factores, escanea el siguiente código QR con la aplicación de autenticación de tu teléfono, o ingresa la clave de configuración y escribe el código que se genera.
                            @else
                                La autenticación de dos factores ya está habilitada. Escanea el siguiente código QR con la aplicación de autenticación de tu teléfono o ingresa la clave de configuración.
                            @endif
                        </p>

                        <div class="flex flex-wrap items-start gap-[20px]">
                            <div class="profile-qr panel-border inline-block shrink-0 rounded-xl border p-[10px]">
                                {!! $this->user->twoFactorQrCodeSvg() !!}
                            </div>

                            <div class="min-w-[180px] flex-1">
                                <p class="panel-accent leading-[30px]">Clave de configuración</p>
                                <p class="break-all leading-[30px]">{{ decrypt($this->user->two_factor_secret) }}</p>

                                @if ($showingConfirmation)
                                    <label for="code" class="pf-label mt-[20px]">Código</label>
                                    <input id="code" type="text" name="code" x-ref="twoFactorCode" class="pf-field" inputmode="numeric" autocomplete="one-time-code"
                                        placeholder="Código de 6 dígitos"
                                        wire:model="code"
                                        wire:keydown.enter="confirmTwoFactorAuthentication" />
                                @endif
                            </div>
                        </div>
                    </div>

                    <footer class="panel-border flex items-center justify-end gap-[20px] border-t p-[20px]">
                        <button type="button" x-on:click="open = false" class="pf-link leading-none focus:outline-none focus:ring-0">Cerrar</button>

                        @if ($showingConfirmation)
                            <x-confirms-password wire:then="confirmTwoFactorAuthentication">
                                <button type="button" wire:loading.attr="disabled" class="pf-primary rounded-xl px-[20px] py-[15px] leading-none">Confirmar</button>
                            </x-confirms-password>
                        @endif
                    </footer>
                </div>
            </div>
        </template>
    @endif
</article>
