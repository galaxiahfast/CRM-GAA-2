<article class="panel-surface panel-border overflow-hidden rounded-xl border">
    @php($totalSessions = count($this->sessions) + 2)
    <header class="panel-header panel-border flex min-h-[85px] flex-wrap items-center justify-between gap-x-[20px] gap-y-[10px] border-b p-[20px]">
        <div class="min-w-0"><h3 class="panel-accent leading-none">Sesiones y dispositivos</h3><p class="pf-sub mt-[5px]">Administra los accesos activos de tu cuenta</p></div>
        <p class="inline-flex items-center gap-[10px] leading-none"><span class="h-2 w-2 rounded-full bg-emerald-500"></span>{{ $totalSessions }} {{ $totalSessions === 1 ? 'sesión activa' : 'sesiones activas' }}</p>
    </header>

    <div class="flex flex-col gap-[20px] p-[20px]">
        {{-- Accesos fijos de la organización: el servidor y la PC del administrador (siempre activos y protegidos) --}}
        <section class="panel-border flex min-w-0 flex-wrap items-center justify-between gap-x-[20px] gap-y-[10px] rounded-xl border p-[20px]">
            <div class="min-w-0">
                <p class="truncate leading-[30px]" title="Servidor de la organización">Servidor de la organización</p>
                <p class="leading-[30px]">Servidor · 192.168.2.249 · Activa permanentemente</p>
            </div>
            <p class="shrink-0 whitespace-nowrap leading-none">Sesión protegida</p>
        </section>

        <section class="panel-border flex min-w-0 flex-wrap items-center justify-between gap-x-[20px] gap-y-[10px] rounded-xl border p-[20px]">
            <div class="min-w-0">
                <p class="truncate leading-[30px]" title="PC del administrador">PC del administrador</p>
                <p class="leading-[30px]">Computadora · Administración · Activa permanentemente</p>
            </div>
            <p class="shrink-0 whitespace-nowrap leading-none">Sesión protegida</p>
        </section>

        @foreach ($this->sessions as $session)
            @php($device = $session->agent->isDesktop() ? 'Computadora' : ($session->agent->isTablet() ? 'Tableta' : 'Dispositivo móvil'))
            <section wire:key="profile-session-{{ $session->id }}" class="panel-border flex min-w-0 flex-wrap items-center justify-between gap-x-[20px] gap-y-[10px] rounded-xl border p-[20px]">
                <div class="min-w-0">
                    <p class="truncate leading-[30px]" title="{{ $session->agent->browser() ?: 'Navegador desconocido' }} · {{ $device }}">{{ $session->agent->browser() ?: 'Navegador desconocido' }} · {{ $device }}</p>
                    <p class="leading-[30px]">{{ $session->agent->platform() ?: 'Desconocido' }} · {{ $session->ip_address ?: 'No disponible' }} · {{ $session->is_current_device ? 'Activa ahora' : $session->last_active }}</p>
                </div>
                @if ($session->is_current_device)
                    <button type="button" wire:click="logoutCurrentSession" wire:confirm="¿Quieres cerrar la sesión actual?" wire:loading.attr="disabled" class="pf-link shrink-0 whitespace-nowrap leading-none focus:outline-none focus:ring-0 disabled:opacity-40">Cerrar esta sesión</button>
                @else
                    <button type="button" wire:click="logoutSession('{{ $session->id }}')" wire:confirm="¿Quieres cerrar esta sesión?" wire:loading.attr="disabled" class="pf-link shrink-0 whitespace-nowrap leading-none focus:outline-none focus:ring-0 disabled:opacity-40">Cerrar sesión</button>
                @endif
            </section>
        @endforeach

        <x-action-message on="loggedOut" class="pf-green-text">Sesión cerrada correctamente.</x-action-message>
    </div>
</article>
