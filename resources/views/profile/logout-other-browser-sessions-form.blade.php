<section class="w-full min-w-0 bg-gray-100">
    <header class="flex items-center justify-between gap-6 border-b border-gray-300 pb-[25px]" style="padding-top: 75px;">
        <div>
            <h2 class="text-[16px] font-semibold text-gray-800">Sesiones y dispositivos</h2>
            <p class="mt-[12px] text-[15px] text-gray-500">Si detectas una actividad o un dispositivo que no reconoces, puedes cerrar esa sesión para proteger tu cuenta.</p>
        </div>
        <span class="inline-flex h-11 w-fit shrink-0 items-center gap-[10px] rounded-xl border border-emerald-200 bg-emerald-50 px-4 text-[13px] font-semibold text-emerald-700 shadow-sm">
            <span class="grid h-6 w-6 place-items-center rounded-lg bg-emerald-100 text-emerald-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m5.6-3.4A11.9 11.9 0 0112 3a11.9 11.9 0 01-8.6 3.6A12 12 0 003 10c0 5.2 3.8 9.5 9 11 5.2-1.5 9-5.8 9-11 0-1.2-.1-2.3-.4-3.4z" /></svg>
            </span>
            <span>{{ count($this->sessions) + 1 }} {{ count($this->sessions) + 1 === 1 ? 'sesión activa' : 'sesiones activas' }}</span>
        </span>
    </header>

    <div class="pt-[5px]" style="padding-bottom: 55px;">
            <div class="divide-y divide-gray-300">
                <article class="grid min-w-0 grid-cols-[0.8fr_1.15fr_1.15fr_1fr] items-center py-[15px]">
                    <div class="flex min-w-0 self-center items-center justify-start gap-[12px] border-r border-gray-300 pl-[20px] pr-[20px]" style="height: 92px;">
                        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-[#1A3A6B] text-white">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 21h16M6 18V5a2 2 0 012-2h8a2 2 0 012 2v13M9 7h.01M12 7h.01M15 7h.01M9 11h.01M12 11h.01M15 11h.01M9 15h.01M12 15h.01M15 15h.01" /></svg>
                        </span>
                        <h3 class="truncate text-[15px] font-semibold text-gray-800">Servidor</h3>
                    </div>
                    <div class="flex min-w-0 items-center justify-start border-r border-gray-300" style="height: 92px; padding-left: 25px; padding-right: 20px;">
                        <div class="text-gray-500">
                            <div><p class="text-[12px] font-semibold text-gray-600">Sistema</p><p class="mt-1 text-[15px]">Servidor de la organización</p></div>
                            <div style="margin-top: 4px;"><p class="text-[12px] font-semibold text-gray-600">Dirección IP</p><p class="mt-1 text-[15px]">192.168.2.249</p></div>
                        </div>
                    </div>
                    <div class="flex min-w-0 items-center justify-start border-r border-gray-300" style="height: 92px; padding-left: 25px; padding-right: 20px;">
                        <div class="text-gray-500">
                            <div><p class="text-[12px] font-semibold text-gray-600">Tipo</p><p class="mt-1 text-[15px]">Servidor</p></div>
                            <div style="margin-top: 4px;"><p class="text-[12px] font-semibold text-gray-600">Actividad</p><p class="mt-1 text-[15px]">Activa permanentemente</p></div>
                        </div>
                    </div>
                    <div class="grid place-items-center px-[15px] py-[15px]">
                        <button type="button" disabled class="inline-flex h-10 shrink-0 cursor-not-allowed items-center justify-center gap-2 whitespace-nowrap rounded-xl border border-gray-300 px-4 text-[15px] font-semibold text-gray-400 opacity-70" style="width: 185px; min-width: 185px; max-width: 185px;">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 17v.01M8 10V8a4 4 0 118 0v2m-9 0h10a2 2 0 012 2v7H5v-7a2 2 0 012-2z" /></svg>
                            Sesión protegida
                        </button>
                    </div>
                </article>
                @foreach ($this->sessions as $session)
                    <article class="grid min-w-0 grid-cols-[0.8fr_1.15fr_1.15fr_1fr] items-center py-[15px]">
                        <div class="flex min-w-0 self-center items-center justify-start gap-[12px] border-r border-gray-300 pl-[20px] pr-[20px]" style="height: 92px;">
                            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl {{ $session->is_current_device ? 'bg-[#1A3A6B] text-white' : 'bg-gray-200 text-gray-500' }}">
                            @if ($session->agent->isDesktop())
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 4h16a1 1 0 011 1v10a1 1 0 01-1 1H4a1 1 0 01-1-1V5a1 1 0 011-1zm5 16h6m-3-4v4" /></svg>
                            @else
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 2h8a2 2 0 012 2v16a2 2 0 01-2 2H8a2 2 0 01-2-2V4a2 2 0 012-2zm3 17h2" /></svg>
                            @endif
                            </span>
                            <h3 class="truncate text-[15px] font-semibold text-gray-800">{{ $session->agent->browser() ?: 'Navegador desconocido' }}</h3>
                        </div>
                        <div class="flex min-w-0 items-center justify-start border-r border-gray-300" style="height: 92px; padding-left: 25px; padding-right: 20px;">
                            <div class="text-gray-500">
                                <div><p class="text-[12px] font-semibold text-gray-600">Sistema</p><p class="mt-1 text-[15px]">{{ $session->agent->platform() ?: 'Desconocido' }}</p></div>
                                <div style="margin-top: 4px;"><p class="text-[12px] font-semibold text-gray-600">Dirección IP</p><p class="mt-1 text-[15px]">{{ $session->ip_address ?: 'No disponible' }}</p></div>
                            </div>
                        </div>
                        <div class="flex min-w-0 items-center justify-start border-r border-gray-300" style="height: 92px; padding-left: 25px; padding-right: 20px;">
                            <div class="text-gray-500">
                                <div><p class="text-[12px] font-semibold text-gray-600">Tipo</p><p class="mt-1 text-[15px]">{{ $session->agent->isDesktop() ? 'Computadora' : ($session->agent->isTablet() ? 'Tableta' : 'Dispositivo móvil') }}</p></div>
                                <div style="margin-top: 4px;"><p class="text-[12px] font-semibold text-gray-600">Actividad</p><p class="mt-1 text-[15px]">{{ $session->is_current_device ? 'Activa ahora' : $session->last_active }}</p></div>
                            </div>
                        </div>
                        <div class="grid place-items-center px-[15px] py-[15px]">
                            @if ($session->is_current_device)
                                <button type="button" wire:click="logoutCurrentSession" wire:confirm="¿Quieres cerrar la sesión actual?" wire:loading.attr="disabled" class="inline-flex h-10 shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-xl border border-red-300 px-4 text-[15px] font-semibold text-red-600 transition hover:bg-red-50 disabled:opacity-60" style="width: 185px; min-width: 185px; max-width: 185px;">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14 8l4 4m0 0-4 4m4-4H8m3 9H5a2 2 0 01-2-2V5a2 2 0 012-2h6" /></svg>
                                    Cerrar sesión
                                </button>
                            @else
                                <button type="button" wire:click="logoutSession('{{ $session->id }}')" wire:confirm="¿Quieres cerrar esta sesión?" wire:loading.attr="disabled" class="inline-flex h-10 shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-xl border border-red-300 px-4 text-[15px] font-semibold text-red-600 transition hover:bg-red-50" style="width: 185px; min-width: 185px; max-width: 185px;">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14 8l4 4m0 0-4 4m4-4H8m3 9H5a2 2 0 01-2-2V5a2 2 0 012-2h6" /></svg>
                                    Cerrar sesión
                                </button>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>

        <x-action-message on="loggedOut" class="mt-[15px] text-[12px] font-medium text-emerald-600">Sesión cerrada correctamente.</x-action-message>
    </div>
</section>
