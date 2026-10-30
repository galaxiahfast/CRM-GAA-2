<article class="overflow-hidden rounded-xl border border-[#d4d4d8] bg-white">
    @php($totalSessions = count($this->sessions) + 2)
    <header class="profile-strip flex items-center justify-between gap-[20px] border-b border-[#d4d4d8] bg-zinc-100 p-[20px]">
        <div class="flex min-w-0 items-center gap-[10px]">
            <span class="profile-icon-box flex shrink-0 items-center justify-center rounded-lg border border-[#d4d4d8] bg-white text-[#1A3A6B]">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m5.6-3.4A11.9 11.9 0 0112 3a11.9 11.9 0 01-8.6 3.6A12 12 0 003 10c0 5.2 3.8 9.5 9 11 5.2-1.5 9-5.8 9-11 0-1.2-.1-2.3-.4-3.4z" /></svg>
            </span>
            <div class="min-w-0"><h3 class="leading-none text-[#1A3A6B]">Sesiones y dispositivos</h3><p class="mt-[10px] leading-none text-black">Administra los accesos activos de tu cuenta</p></div>
        </div>
        <span class="inline-flex shrink-0 items-center gap-[10px] leading-none text-black">
            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>{{ $totalSessions }} {{ $totalSessions === 1 ? 'sesión activa' : 'sesiones activas' }}
        </span>
    </header>

    <div class="p-[20px]">
        <div class="flex flex-col gap-[20px]">
            <section class="profile-session flex min-w-0 items-center justify-between gap-[20px] rounded-xl border border-[#d4d4d8] bg-white p-[20px]">
                <div class="flex min-w-0 items-center gap-[15px]">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl border border-[#d4d4d8] bg-white text-[#1A3A6B]"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 21h16M6 18V5a2 2 0 012-2h8a2 2 0 012 2v13M9 7h.01M12 7h.01M15 7h.01M9 11h.01M12 11h.01M15 11h.01M9 15h.01M12 15h.01M15 15h.01" /></svg></span>
                    <div class="min-w-0"><p class="truncate leading-none text-black" title="Servidor de la organización">Servidor de la organización</p><div class="mt-[10px] flex flex-col"><p class="profile-lines text-black">Servidor · 192.168.2.249 · Activa permanentemente</p></div></div>
                </div>
                <span class="inline-flex shrink-0 items-center gap-[10px] whitespace-nowrap py-[15px] leading-none text-black"><svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 17v.01M8 10V8a4 4 0 118 0v2m-9 0h10a2 2 0 012 2v7H5v-7a2 2 0 012-2z" /></svg><span>Sesión protegida</span></span>
            </section>

            <section class="profile-session flex min-w-0 items-center justify-between gap-[20px] rounded-xl border border-[#d4d4d8] bg-white p-[20px]">
                <div class="flex min-w-0 items-center gap-[15px]">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl border border-[#d4d4d8] bg-white text-[#1A3A6B]"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg></span>
                    <div class="min-w-0"><p class="truncate leading-none text-black" title="PC del administrador">PC del administrador</p><div class="mt-[10px] flex flex-col"><p class="profile-lines text-black">Computadora · Administración · Activa permanentemente</p></div></div>
                </div>
                <span class="inline-flex shrink-0 items-center gap-[10px] whitespace-nowrap py-[15px] leading-none text-black"><svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 17v.01M8 10V8a4 4 0 118 0v2m-9 0h10a2 2 0 012 2v7H5v-7a2 2 0 012-2z" /></svg><span>Sesión protegida</span></span>
            </section>

            @foreach ($this->sessions as $session)
                <section wire:key="profile-session-{{ $session->id }}" class="profile-session flex min-w-0 items-center justify-between gap-[20px] rounded-xl border border-[#d4d4d8] bg-white p-[20px]">
                    <div class="flex min-w-0 items-center gap-[15px]">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl border border-[#d4d4d8] bg-white text-[#1A3A6B]">
                            @if ($session->agent->isDesktop())
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 4h16a1 1 0 011 1v10a1 1 0 01-1 1H4a1 1 0 01-1-1V5a1 1 0 011-1zm5 16h6m-3-4v4" /></svg>
                            @else
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 2h8a2 2 0 012 2v16a2 2 0 01-2 2H8a2 2 0 01-2-2V4a2 2 0 012-2zm3 17h2" /></svg>
                            @endif
                        </span>
                        <div class="min-w-0">
                            <p class="truncate leading-none text-black" title="{{ $session->agent->browser() ?: 'Navegador desconocido' }} · {{ $session->agent->isDesktop() ? 'Computadora' : ($session->agent->isTablet() ? 'Tableta' : 'Dispositivo móvil') }}">{{ $session->agent->browser() ?: 'Navegador desconocido' }} · {{ $session->agent->isDesktop() ? 'Computadora' : ($session->agent->isTablet() ? 'Tableta' : 'Dispositivo móvil') }}</p>
                            <div class="mt-[10px] flex flex-col"><p class="profile-lines text-black">{{ $session->agent->platform() ?: 'Desconocido' }} · {{ $session->ip_address ?: 'No disponible' }} · {{ $session->is_current_device ? 'Activa ahora' : $session->last_active }}</p></div>
                        </div>
                    </div>
                    @if ($session->is_current_device)
                        <button type="button" wire:click="logoutCurrentSession" wire:confirm="¿Quieres cerrar la sesión actual?" wire:loading.attr="disabled" class="inline-flex shrink-0 items-center gap-[10px] whitespace-nowrap bg-transparent py-[15px] leading-none text-[#1A3A6B] transition-colors hover:text-black focus:outline-none focus:ring-0 disabled:opacity-40"><svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14 8l4 4m0 0-4 4m4-4H8m3 9H5a2 2 0 01-2-2V5a2 2 0 012-2h6" /></svg><span>Cerrar sesión</span></button>
                    @else
                        <button type="button" wire:click="logoutSession('{{ $session->id }}')" wire:confirm="¿Quieres cerrar esta sesión?" wire:loading.attr="disabled" class="inline-flex shrink-0 items-center gap-[10px] whitespace-nowrap bg-transparent py-[15px] leading-none text-[#1A3A6B] transition-colors hover:text-black focus:outline-none focus:ring-0 disabled:opacity-40"><svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14 8l4 4m0 0-4 4m4-4H8m3 9H5a2 2 0 01-2-2V5a2 2 0 012-2h6" /></svg><span>Cerrar sesión</span></button>
                    @endif
                </section>
            @endforeach
        </div>

        <x-action-message on="loggedOut" class="profile-message mt-[20px] text-emerald-600">Sesión cerrada correctamente.</x-action-message>
    </div>
</article>
