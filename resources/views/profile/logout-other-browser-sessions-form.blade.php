<section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-[0_8px_24px_rgba(15,35,66,0.06)]">
    <header class="flex flex-col gap-4 border-b border-gray-200 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-[#1A3A6B] text-white">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 17v2m6-2v2M5 21h14M4 4h16a1 1 0 011 1v10a1 1 0 01-1 1H4a1 1 0 01-1-1V5a1 1 0 011-1z" /></svg>
            </span>
            <div>
                <h2 class="font-semibold text-gray-800">Sesiones y dispositivos</h2>
                <p class="mt-0.5 text-[12px] text-gray-500">Revisa desde dónde se ha iniciado sesión en tu cuenta.</p>
            </div>
        </div>
        <span class="inline-flex w-fit items-center gap-2 rounded-full bg-emerald-50 px-3 py-1.5 text-[12px] font-semibold text-emerald-700">
            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
            {{ count($this->sessions) }} {{ count($this->sessions) === 1 ? 'sesión activa' : 'sesiones activas' }}
        </span>
    </header>

    <div class="p-6">
        <div class="flex gap-3 rounded-xl border border-[#d9e3f1] bg-[#f5f8fc] p-4 text-[13px] leading-6 text-gray-600">
            <svg class="mt-0.5 h-5 w-5 shrink-0 text-[#1A3A6B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v4m0 4h.01M10.3 3.8 2.8 17a2 2 0 001.7 3h15a2 2 0 001.7-3L13.7 3.8a2 2 0 00-3.4 0z" /></svg>
            <p>Si no reconoces alguna sesión, cierra las demás y cambia tu contraseña inmediatamente.</p>
        </div>

        @if (count($this->sessions) > 0)
            <div class="mt-5 grid gap-3 md:grid-cols-2">
                @foreach ($this->sessions as $session)
                    <article class="flex min-w-0 items-center gap-4 rounded-xl border p-4 {{ $session->is_current_device ? 'border-[#b9c9e2] bg-[#f7faff]' : 'border-gray-200 bg-white' }}">
                        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl {{ $session->is_current_device ? 'bg-[#1A3A6B] text-white' : 'bg-gray-100 text-gray-500' }}">
                            @if ($session->agent->isDesktop())
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 4h16a1 1 0 011 1v10a1 1 0 01-1 1H4a1 1 0 01-1-1V5a1 1 0 011-1zm5 16h6m-3-4v4" /></svg>
                            @else
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 2h8a2 2 0 012 2v16a2 2 0 01-2 2H8a2 2 0 01-2-2V4a2 2 0 012-2zm3 17h2" /></svg>
                            @endif
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-2">
                                <h3 class="truncate font-semibold text-gray-800">{{ $session->agent->browser() ?: 'Navegador desconocido' }}</h3>
                                @if ($session->is_current_device)
                                    <span class="shrink-0 rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-emerald-700">Este dispositivo</span>
                                @endif
                            </div>
                            <p class="mt-1 truncate text-[12px] text-gray-500">{{ $session->agent->platform() ?: 'Sistema desconocido' }} · {{ $session->ip_address }}</p>
                            @unless ($session->is_current_device)
                                <p class="mt-1 text-[11px] text-gray-400">Última actividad: {{ $session->last_active }}</p>
                            @endunless
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <div class="mt-5 rounded-xl border border-dashed border-gray-200 py-8 text-center text-gray-400">
                <svg class="mx-auto h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-8a2 2 0 00-2-2h-1V7a5 5 0 00-10 0v2H6a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                <p class="mt-2 text-[13px]">No hay sesiones disponibles para mostrar.</p>
            </div>
        @endif

        <footer class="mt-6 flex flex-col gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:items-center sm:justify-between">
            <p class="max-w-xl text-[12px] leading-5 text-gray-400">Tu sesión actual permanecerá abierta. Las demás deberán iniciar sesión nuevamente.</p>
            <div class="flex shrink-0 items-center gap-3">
                <x-action-message on="loggedOut" class="text-[12px] font-medium text-emerald-600">Sesiones cerradas.</x-action-message>
                <button type="button" wire:click="confirmLogout" wire:loading.attr="disabled" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-red-200 bg-white px-4 font-semibold text-red-600 transition hover:bg-red-50">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14 8l4 4m0 0-4 4m4-4H8m3 9H5a2 2 0 01-2-2V5a2 2 0 012-2h6" /></svg>
                    Cerrar las demás sesiones
                </button>
            </div>
        </footer>
    </div>

    <x-dialog-modal wire:model.live="confirmingLogout">
        <x-slot name="title">Confirmar cierre de sesiones</x-slot>
        <x-slot name="content">
            <p class="text-gray-600">Ingresa tu contraseña para cerrar las sesiones abiertas en otros dispositivos.</p>
            <div class="mt-5" x-data="{}" x-on:confirming-logout-other-browser-sessions.window="setTimeout(() => $refs.password.focus(), 250)">
                <label for="logout_sessions_password" class="mb-2 block text-[12px] font-semibold uppercase tracking-[0.06em] text-gray-500">Contraseña actual</label>
                <input id="logout_sessions_password" type="password" autocomplete="current-password" x-ref="password" wire:model="password" wire:keydown.enter="logoutOtherBrowserSessions" class="h-11 w-full rounded-xl border-gray-300 focus:border-[#1A3A6B] focus:ring-[#1A3A6B]/20">
                <x-input-error for="password" class="mt-2" />
            </div>
        </x-slot>
        <x-slot name="footer">
            <button type="button" wire:click="$toggle('confirmingLogout')" wire:loading.attr="disabled" class="h-10 rounded-xl border border-gray-300 px-4 font-medium text-gray-600 hover:bg-gray-50">Cancelar</button>
            <button type="button" wire:click="logoutOtherBrowserSessions" wire:loading.attr="disabled" class="ms-3 h-10 rounded-xl bg-red-600 px-4 font-semibold text-white hover:bg-red-700">Cerrar sesiones</button>
        </x-slot>
    </x-dialog-modal>
</section>
