<article class="mt-[25px] rounded-2xl border border-zinc-200 bg-white p-[25px] shadow-[0_4px_14px_rgba(0,0,0,0.025)]">
    <header class="flex items-center justify-between gap-6 border-b border-gray-200 pb-[20px]">
        <div class="flex items-center gap-3">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-zinc-200 text-black">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m5.6-3.4A11.9 11.9 0 0112 3a11.9 11.9 0 01-8.6 3.6A12 12 0 003 10c0 5.2 3.8 9.5 9 11 5.2-1.5 9-5.8 9-11 0-1.2-.1-2.3-.4-3.4z" /></svg>
            </span>
            <div><p class="font-semibold text-gray-800">Sesiones y dispositivos</p><p class="text-[12px] text-gray-400">Administra los accesos activos de tu cuenta</p></div>
        </div>
        <span class="inline-flex shrink-0 items-center gap-2 rounded-full border border-emerald-200 px-3 py-1.5 text-[12px] font-medium text-emerald-700">
            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>{{ count($this->sessions) + 1 }} {{ count($this->sessions) + 1 === 1 ? 'sesión activa' : 'sesiones activas' }}
        </span>
    </header>

    <p class="mt-[20px] text-[13px] leading-6 text-gray-500">Si detectas una actividad o un dispositivo que no reconoces, puedes cerrar esa sesión para proteger tu cuenta.</p>

    <div class="mt-[15px] space-y-[12px]">
        <section class="grid min-w-0 grid-cols-[minmax(0,1fr)_170px] items-center gap-[15px] rounded-xl border border-zinc-200 bg-white p-[15px]">
            <div class="flex min-w-0 items-center gap-[12px] pr-[15px]">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-black text-white"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 21h16M6 18V5a2 2 0 012-2h8a2 2 0 012 2v13M9 7h.01M12 7h.01M15 7h.01M9 11h.01M12 11h.01M15 11h.01M9 15h.01M12 15h.01M15 15h.01" /></svg></span>
                <div class="min-w-0"><p class="truncate font-semibold text-gray-800" title="Servidor de la organización">Servidor de la organización</p><p class="mt-1 truncate text-[12px] text-gray-400" title="Servidor · 192.168.2.249 · Activa permanentemente">Servidor · 192.168.2.249 · Activa permanentemente</p></div>
            </div>
            <button type="button" disabled class="inline-flex h-10 w-[170px] cursor-not-allowed items-center justify-center gap-1.5 whitespace-nowrap rounded-xl border border-zinc-200 px-2 font-semibold text-zinc-400"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 17v.01M8 10V8a4 4 0 118 0v2m-9 0h10a2 2 0 012 2v7H5v-7a2 2 0 012-2z" /></svg>Sesión protegida</button>
        </section>

        @foreach ($this->sessions as $session)
            <section wire:key="profile-session-{{ $session->id }}" class="grid min-w-0 grid-cols-[minmax(0,1fr)_170px] items-center gap-[15px] rounded-xl border border-zinc-200 bg-white p-[15px]">
                <div class="flex min-w-0 items-center gap-[12px] pr-[15px]">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-black text-white">
                        @if ($session->agent->isDesktop())
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 4h16a1 1 0 011 1v10a1 1 0 01-1 1H4a1 1 0 01-1-1V5a1 1 0 011-1zm5 16h6m-3-4v4" /></svg>
                        @else
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 2h8a2 2 0 012 2v16a2 2 0 01-2 2H8a2 2 0 01-2-2V4a2 2 0 012-2zm3 17h2" /></svg>
                        @endif
                    </span>
                    <div class="min-w-0">
                        <p class="truncate font-semibold text-gray-800" title="{{ $session->agent->browser() ?: 'Navegador desconocido' }} · {{ $session->agent->isDesktop() ? 'Computadora' : ($session->agent->isTablet() ? 'Tableta' : 'Dispositivo móvil') }}">{{ $session->agent->browser() ?: 'Navegador desconocido' }} · {{ $session->agent->isDesktop() ? 'Computadora' : ($session->agent->isTablet() ? 'Tableta' : 'Dispositivo móvil') }}</p>
                        <p class="mt-1 truncate text-[12px] text-gray-400" title="{{ $session->agent->platform() ?: 'Desconocido' }} · {{ $session->ip_address ?: 'No disponible' }} · {{ $session->is_current_device ? 'Activa ahora' : $session->last_active }}">{{ $session->agent->platform() ?: 'Desconocido' }} · {{ $session->ip_address ?: 'No disponible' }} · {{ $session->is_current_device ? 'Activa ahora' : $session->last_active }}</p>
                    </div>
                </div>
                @if ($session->is_current_device)
                    <button type="button" wire:click="logoutCurrentSession" wire:confirm="¿Quieres cerrar la sesión actual?" wire:loading.attr="disabled" class="inline-flex h-10 w-[170px] items-center justify-center gap-1.5 whitespace-nowrap rounded-xl border border-zinc-300 px-2 font-semibold text-black transition hover:bg-zinc-100 disabled:opacity-60"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14 8l4 4m0 0-4 4m4-4H8m3 9H5a2 2 0 01-2-2V5a2 2 0 012-2h6" /></svg>Cerrar sesión</button>
                @else
                    <button type="button" wire:click="logoutSession('{{ $session->id }}')" wire:confirm="¿Quieres cerrar esta sesión?" wire:loading.attr="disabled" class="inline-flex h-10 w-[170px] items-center justify-center gap-1.5 whitespace-nowrap rounded-xl border border-zinc-300 px-2 font-semibold text-black transition hover:bg-zinc-100 disabled:opacity-60"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14 8l4 4m0 0-4 4m4-4H8m3 9H5a2 2 0 01-2-2V5a2 2 0 012-2h6" /></svg>Cerrar sesión</button>
                @endif
            </section>
        @endforeach
    </div>

    <x-action-message on="loggedOut" class="mt-[15px] text-[12px] font-medium text-emerald-600">Sesión cerrada correctamente.</x-action-message>
</article>
