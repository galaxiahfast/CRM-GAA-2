<div
    data-clock-particle-network
    class="relative isolate min-h-[calc(100dvh-90px)] w-full overflow-hidden bg-[#F3F3F3] text-[15px] text-zinc-700"
    x-data="{ viewScale: 100, isFullscreen: false, initialized: {{ $initialScanStarted ? 'true' : 'false' }}, processed: $wire.entangle('processedProfiles'), total: $wire.entangle('totalProfiles'), scanning: $wire.entangle('reading'), statusMessage: $wire.entangle('statusMessage'), recipientsOpen: false, selectedRecipients: $wire.entangle('emailRecipients'), mailSending: false, mailStatus: @js($emailStatus), mailSent: {{ $emailSent ? 'true' : 'false' }}, init() { const saved = Number(localStorage.getItem('backup-reports-view-scale')); if (saved >= 70 && saved <= 100) this.viewScale = saved; if (!this.initialized) { this.initialized = true; setTimeout(() => $wire.startReading(), 300); } }, async sendReport() { if (this.mailSending) return; const scrollPosition = window.scrollY; this.mailSending = true; this.mailStatus = ''; try { const result = await this.$wire.sendEmail(); this.mailSent = result.sent; this.mailStatus = result.status; if (result.sent) this.recipientsOpen = false; } finally { this.mailSending = false; this.$nextTick(() => window.scrollTo(0, scrollPosition)); } }, saveScale() { localStorage.setItem('backup-reports-view-scale', String(this.viewScale)); }, async toggleFullscreen(container) { if (document.fullscreenElement === container) return document.exitFullscreen(); if (document.fullscreenElement) await document.exitFullscreen(); await container.requestFullscreen(); } }"
    x-on:backup-read-next.window="setTimeout(() => $wire.readNext(), 120)"
    x-on:backup-scan-progress.window="processed = $event.detail.processed; total = $event.detail.total; scanning = $event.detail.reading; statusMessage = $event.detail.message; if ($event.detail.reading) { mailStatus = ''; mailSent = false; }"
    @fullscreenchange.window="isFullscreen = document.fullscreenElement === $root"
>
    <span
        class="hidden"
        wire:key="backup-scan-state-{{ $processedProfiles }}-{{ $totalProfiles }}-{{ $reading ? 'reading' : 'idle' }}"
        x-init="$nextTick(() => { processed = {{ $processedProfiles }}; total = {{ $totalProfiles }}; scanning = {{ $reading ? 'true' : 'false' }}; statusMessage = @js($statusMessage); })"
        aria-hidden="true"
    ></span>
    <canvas wire:ignore data-clock-network-canvas class="pointer-events-none absolute inset-0 z-0 h-full w-full opacity-[0.55]" aria-hidden="true"></canvas>

    <div class="absolute left-1/2 top-[20px] z-30 flex -translate-x-1/2 items-center gap-[10px] rounded-xl border border-zinc-200 bg-white/95 px-[15px] py-[10px] shadow-[0_8px_24px_rgba(0,0,0,0.10)] backdrop-blur-sm">
        <input type="range" min="70" max="100" step="5" x-model.number="viewScale" @input="saveScale()" class="h-1.5 w-[130px] cursor-pointer accent-black" aria-label="Ajustar tamaño de la vista">
        <span class="w-[42px] text-right font-semibold tabular-nums text-black" x-text="viewScale + '%'">100%</span>
        <span class="h-5 w-px bg-zinc-200"></span>
        <button type="button" @click="toggleFullscreen($root)" class="rounded-lg p-[5px] text-black transition hover:bg-zinc-100" :title="isFullscreen ? 'Salir de pantalla completa' : 'Pantalla completa'">
            <svg x-show="!isFullscreen" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 3H3v5M16 3h5v5M21 16v5h-5M3 16v5h5"/></svg>
            <svg x-show="isFullscreen" x-cloak class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 3v5H3M16 3v5h5M21 16h-5v5M3 16h5v5"/></svg>
        </button>
    </div>

    <div class="relative z-10 min-h-[calc(100dvh-90px)] min-w-[1180px] origin-top" :style="`width: ${10000 / viewScale}%; margin-left: ${(100 - (10000 / viewScale)) / 2}%; transform: scale(${viewScale / 100});`">
        <header class="flex items-center justify-between gap-[20px] whitespace-nowrap border-b border-zinc-200 bg-white/75 p-[50px]">
            <div class="flex items-center gap-[15px] text-zinc-500">
                <span class="font-medium">Soporte</span>
                <span class="text-gray-300">&gt;</span>
                <span class="font-semibold text-black">Reportes de respaldos</span>
            </div>
            <div class="flex items-center gap-[10px]">
                <button type="button" wire:click="startReading" wire:loading.attr="disabled" :disabled="scanning" class="rounded-xl border border-zinc-200 bg-white px-[18px] py-[12px] font-medium text-black transition hover:bg-zinc-100 disabled:cursor-not-allowed disabled:opacity-50">
                    Actualizar registros
                </button>
                <button type="button" wire:click="openServer" class="rounded-xl border border-zinc-200 bg-white px-[18px] py-[12px] font-medium text-black transition hover:bg-zinc-100">Agregar servidor</button>
                <div class="relative select-none">
                    <button type="button" @click="recipientsOpen = !recipientsOpen" class="select-none rounded-xl border border-zinc-200 bg-white px-[18px] py-[12px] font-medium text-black outline-none transition hover:bg-zinc-100 focus:outline-none focus:ring-0">
                        Destinatarios (<span x-text="selectedRecipients.length">{{ count($emailRecipients) }}</span>)
                    </button>
                        <div x-show="recipientsOpen" x-cloak x-transition.opacity.duration.150ms x-on:click.outside="recipientsOpen = false" class="absolute right-0 top-[52px] z-50 w-[370px] whitespace-normal rounded-xl border border-zinc-200 bg-white p-[18px] shadow-[0_18px_50px_rgba(0,0,0,0.18)]">
                            <div class="mb-[14px]">
                                <strong class="block text-black">Enviar reporte a</strong>
                                <span class="mt-[3px] block text-sm text-zinc-500">Selecciona uno o varios destinatarios.</span>
                            </div>
                            <div class="space-y-[8px]">
                                @foreach ($recipientOptions as $email => $label)
                                    <label wire:key="backup-recipient-{{ $email }}" class="flex cursor-pointer select-none items-center gap-[12px] rounded-xl border border-zinc-200 px-[13px] py-[11px] outline-none transition hover:bg-zinc-50 focus-within:border-zinc-300 focus-within:outline-none focus-within:ring-0">
                                        <input x-model="selectedRecipients" type="checkbox" value="{{ $email }}" class="h-4 w-4 rounded border-zinc-300 text-blue-950 outline-none focus:outline-none focus:ring-0 focus:ring-offset-0">
                                        <span class="min-w-0"><strong class="block text-sm text-black">{{ $label }}</strong><span class="block truncate text-xs text-zinc-500">{{ $email }}</span></span>
                                    </label>
                                @endforeach
                            </div>
                            <p x-show="selectedRecipients.length === 0" x-cloak class="mt-[12px] text-sm font-medium text-amber-700">Selecciona al menos un correo.</p>
                            <button type="button" @click.prevent.stop="sendReport()" :disabled="mailSending || scanning || total === 0 || processed < total || selectedRecipients.length === 0" class="mt-[14px] w-full select-none rounded-xl bg-black px-[16px] py-[11px] text-sm font-medium text-white outline-none transition hover:bg-zinc-800 focus:outline-none focus:ring-0 disabled:cursor-not-allowed disabled:bg-zinc-200 disabled:text-zinc-500">
                                <span x-show="!mailSending">Enviar reporte</span>
                                <span x-show="mailSending" x-cloak>Enviando...</span>
                            </button>
                        </div>
                </div>
                <button type="button" wire:click="downloadPdf" wire:loading.attr="disabled" class="rounded-xl bg-black px-[18px] py-[12px] font-medium text-white transition hover:bg-zinc-800 disabled:opacity-50">Guardar PDF</button>
            </div>
        </header>

        <main class="mx-auto w-full space-y-[20px] p-[50px]">
            <section class="flex items-center justify-between gap-[20px]">
                <div class="flex items-center gap-[20px]">
                    <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl border border-zinc-200 bg-white/80 text-black">
                        <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 18h16M6 15V9m4 6V5m4 10v-3m4 3V7"/></svg>
                    </span>
                    <div>
                        <h1 class="text-xl font-semibold text-black">Informe técnico de respaldos</h1>
                        <p class="mt-[5px] text-zinc-500">Consulta los logs de Create Synchronicity, revisa incidencias y genera el informe de servidores.</p>
                    </div>
                </div>
                <div class="flex items-center gap-[10px] text-zinc-500"><span class="h-2 w-2 rounded-full bg-emerald-500"></span>Acceso exclusivo para administradores</div>
            </section>

            <section class="rounded-xl border border-zinc-200 bg-white/85 p-[20px] shadow-[0_8px_24px_rgba(0,0,0,0.04)]" aria-live="polite">
                <div class="flex items-center justify-between gap-[20px]">
                    <div>
                        <strong class="block" :class="mailStatus ? (mailSent ? 'text-emerald-700' : 'text-amber-800') : 'text-black'" x-text="mailSending ? 'Generando PDF y enviando el correo...' : (mailStatus || statusMessage)">{{ $statusMessage }}</strong>
                        <span class="mt-[5px] block text-zinc-500" x-text="mailSending || mailStatus ? 'El reporte se envía únicamente a los destinatarios seleccionados.' : 'La consulta es de solo lectura y procesa cada perfil de manera independiente.'">La consulta es de solo lectura y procesa cada perfil de manera independiente.</span>
                    </div>
                    <div class="flex items-center gap-[15px]">
                        <span x-show="!mailSending && !mailStatus" class="font-semibold tabular-nums text-black"><span x-text="processed">{{ $processedProfiles }}</span> / <span x-text="total">{{ $totalProfiles }}</span></span>
                        <span x-show="mailSending" x-cloak class="font-medium text-blue-950">Procesando</span>
                        <span x-show="!mailSending && mailStatus" x-cloak class="font-medium" :class="mailSent ? 'text-emerald-700' : 'text-amber-800'" x-text="mailSent ? '100%' : 'Aviso'"></span>
                        <button x-show="scanning" x-cloak type="button" wire:click="stopReading" class="rounded-lg border border-red-200 bg-red-50 px-[12px] py-[8px] font-medium text-red-600 hover:bg-red-100">Detener</button>
                    </div>
                </div>
                <div class="mt-[15px] h-2 overflow-hidden rounded-full bg-zinc-100">
                    <div x-show="!mailSending && !mailStatus" class="h-full rounded-full bg-blue-950 transition-all duration-300" :style="`width: ${total > 0 ? Math.min(100, (processed / total) * 100) : 0}%`" style="width: {{ $totalProfiles > 0 ? min(100, ($processedProfiles / $totalProfiles) * 100) : 0 }}%"></div>
                    <div x-show="mailSending" x-cloak class="h-full w-1/2 animate-pulse rounded-full bg-blue-950"></div>
                    <div x-show="!mailSending && mailStatus" x-cloak class="h-full rounded-full transition-all duration-300" :class="mailSent ? 'w-full bg-emerald-600' : 'w-1/3 bg-amber-500'"></div>
                </div>
            </section>

            <section class="grid grid-cols-5 gap-[15px]">
                @foreach ([['Servidores con datos', $summary['servers']], ['Perfiles revisados', $summary['profiles']], ['Perfiles con error', $summary['errors']], ['Empresas afectadas', $summary['companies']], ['Sin datos / pendientes', $summary['pending']]] as [$label, $value])
                    <div class="rounded-xl border border-zinc-200 bg-white/80 p-[20px] shadow-[0_8px_24px_rgba(0,0,0,0.03)]">
                        <strong class="block text-2xl font-semibold text-black">{{ $value }}</strong>
                        <span class="mt-[6px] block text-zinc-500">{{ $label }}</span>
                    </div>
                @endforeach
            </section>

            <section class="space-y-[20px]">
                @forelse ($servers as $serverIndex => $server)
                    @php
                        $availableCount = collect($server['profiles'])->where('available', true)->count();
                        $errorCount = collect($server['profiles'])->filter(fn ($profile) => $profile['available'] && ($profile['failed'] || $profile['errors'] !== [] || $profile['issues'] !== []))->count();
                        $pendingCount = collect($server['profiles'])->where('done', false)->count();
                    @endphp
                    <article wire:key="backup-server-{{ $server['key'] }}" class="overflow-hidden rounded-xl border border-zinc-200 bg-white/85 shadow-[0_8px_24px_rgba(0,0,0,0.04)]">
                        <header class="flex items-center justify-between gap-[20px] border-b border-zinc-200 p-[20px]">
                            <div>
                                <div class="flex items-center gap-[10px]"><h2 class="text-lg font-semibold text-black">{{ $server['name'] }}</h2><span class="text-zinc-400">{{ $server['ip'] }}</span></div>
                                <span class="mt-[5px] block font-mono text-xs text-zinc-400">{{ $server['share'] }}</span>
                            </div>
                            <div class="flex items-center gap-[10px]">
                                <span class="rounded-full px-[10px] py-[5px] font-medium {{ $pendingCount ? 'bg-blue-50 text-blue-900' : ($availableCount === 0 ? 'bg-amber-50 text-amber-700' : ($errorCount ? 'bg-red-50 text-red-600' : 'bg-emerald-50 text-emerald-700')) }}">
                                    {{ $pendingCount ? 'Leyendo' : ($availableCount === 0 ? 'Sin datos' : ($errorCount ? 'Con incidencias' : 'Todo realizado')) }}
                                </span>
                                <button type="button" wire:click="openServer({{ $serverIndex }})" class="rounded-lg border border-zinc-200 bg-white px-[12px] py-[8px] font-medium text-black hover:bg-zinc-100">Editar servidor</button>
                            </div>
                        </header>

                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[980px] text-left">
                                <thead class="bg-zinc-50 text-zinc-500"><tr><th class="px-[20px] py-[12px] font-medium">Perfil</th><th class="px-[20px] py-[12px] font-medium">Última ejecución</th><th class="px-[20px] py-[12px] font-medium">Hecho</th><th class="px-[20px] py-[12px] font-medium">Resultado</th><th class="px-[20px] py-[12px] text-right font-medium">Acción</th></tr></thead>
                                @foreach ($server['profiles'] as $profileIndex => $profile)
                                    @php $isBad = $profile['failed'] || $profile['errors'] !== [] || $profile['issues'] !== []; @endphp
                                    <tbody wire:key="backup-profile-{{ $server['key'] }}-{{ $profile['log'] }}-{{ $profileIndex }}" class="border-t border-zinc-200">
                                        <tr class="transition hover:bg-zinc-50/70">
                                            <td class="px-[20px] py-[15px] font-semibold text-black">{{ $profile['name'] }}</td>
                                            <td class="px-[20px] py-[15px]">{{ $profile['available'] ? ($profile['date'] ?: 'Sin fecha') : ($profile['done'] ? 'Sin datos' : 'Leyendo…') }}</td>
                                            <td class="px-[20px] py-[15px] font-mono">{{ $profile['available'] ? ($profile['processed'] ?: '—') : '—' }}</td>
                                            <td class="px-[20px] py-[15px] font-medium {{ $profile['available'] ? ($isBad ? 'text-red-600' : 'text-emerald-700') : 'text-amber-700' }}">
                                                {{ $profile['available'] ? ($isBad ? 'Error' : ($profile['processed'] === '0/0' ? 'Sin cambios' : 'Todo realizado')) : ($profile['done'] ? 'No disponible' : 'Pendiente') }}
                                            </td>
                                            <td class="px-[20px] py-[15px] text-right"><button type="button" wire:click="openProfile({{ $serverIndex }}, {{ $profileIndex }})" class="rounded-lg border border-zinc-200 bg-white px-[12px] py-[8px] font-medium text-black hover:bg-zinc-100">Editar</button></td>
                                        </tr>
                                        @if ($profile['errors'] !== [] || $profile['issues'] !== [] || ($profile['message'] && $profile['done'] && !$profile['available']))
                                            <tr><td colspan="5" class="px-[20px] pb-[18px]">
                                                <div class="ml-[20px] border-l-2 {{ $isBad ? 'border-red-400' : 'border-amber-400' }} pl-[15px]">
                                                    @foreach ($profile['errors'] as $error)<div class="text-red-600">{{ $error }}</div>@endforeach
                                                    @foreach ($profile['issues'] as $issue)<div class="text-red-600">{{ $issue }}</div>@endforeach
                                                    @if ($profile['message'] && !$profile['available'])<div class="text-amber-700">{{ $profile['message'] }}</div>@endif
                                                </div>
                                            </td></tr>
                                        @endif
                                    </tbody>
                                @endforeach
                            </table>
                        </div>
                        <div class="border-t border-zinc-200 p-[15px]"><button type="button" wire:click="addProfile({{ $serverIndex }})" class="rounded-lg border border-zinc-200 bg-white px-[12px] py-[8px] font-medium text-black hover:bg-zinc-100">Agregar perfil</button></div>
                    </article>
                @empty
                    <div class="rounded-xl border border-dashed border-zinc-300 bg-white/60 p-[40px] text-center text-zinc-500">No hay servidores en este informe.</div>
                @endforelse
            </section>
        </main>
    </div>

    @if ($showProfileEditor)
        <div class="fixed inset-0 z-[80] flex items-center justify-center bg-black/45 p-[30px]" wire:key="profile-editor">
            <form wire:submit="saveProfile" class="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-2xl bg-white p-[30px] shadow-2xl">
                <div class="flex items-start justify-between"><div><h2 class="text-xl font-semibold text-black">{{ $editingProfile === null ? 'Agregar perfil' : 'Editar perfil' }}</h2><p class="mt-[5px] text-zinc-500">Los ajustes solo afectan el informe actual.</p></div><button type="button" wire:click="$set('showProfileEditor', false)" class="rounded-lg p-[8px] text-zinc-500 hover:bg-zinc-100">✕</button></div>
                <div class="mt-[25px] grid grid-cols-2 gap-[15px]">
                    <label class="block"><span class="mb-[7px] block font-medium text-black">Perfil *</span><input wire:model="profileForm.name" class="w-full rounded-xl border-zinc-200 bg-zinc-50 px-[15px] py-[12px] focus:border-blue-900 focus:ring-blue-900"></label>
                    <label class="block"><span class="mb-[7px] block font-medium text-black">Nombre del log</span><input wire:model="profileForm.log" class="w-full rounded-xl border-zinc-200 bg-zinc-50 px-[15px] py-[12px] focus:border-blue-900 focus:ring-blue-900"></label>
                    <label class="block"><span class="mb-[7px] block font-medium text-black">Fecha y hora</span><input wire:model="profileForm.date" class="w-full rounded-xl border-zinc-200 bg-zinc-50 px-[15px] py-[12px] focus:border-blue-900 focus:ring-blue-900"></label>
                    <label class="block"><span class="mb-[7px] block font-medium text-black">Hecho</span><input wire:model="profileForm.processed" placeholder="0/0" class="w-full rounded-xl border-zinc-200 bg-zinc-50 px-[15px] py-[12px] focus:border-blue-900 focus:ring-blue-900"></label>
                    <label class="col-span-2 block"><span class="mb-[7px] block font-medium text-black">Empresas con error</span><textarea wire:model="profileForm.errors" rows="4" placeholder="Una empresa por línea" class="w-full rounded-xl border-zinc-200 bg-zinc-50 px-[15px] py-[12px] focus:border-blue-900 focus:ring-blue-900"></textarea></label>
                    <label class="col-span-2 block"><span class="mb-[7px] block font-medium text-black">Incidencias generales</span><textarea wire:model="profileForm.issues" rows="3" placeholder="Ej. Directorio destino inválido" class="w-full rounded-xl border-zinc-200 bg-zinc-50 px-[15px] py-[12px] focus:border-blue-900 focus:ring-blue-900"></textarea></label>
                    <label class="col-span-2 flex items-center gap-[10px] rounded-xl border border-zinc-200 p-[15px]"><input wire:model="profileForm.available" type="checkbox" class="rounded border-zinc-300 text-blue-950 focus:ring-blue-900"><span class="font-medium text-black">Incluir este perfil en el PDF</span></label>
                    <details class="col-span-2 rounded-xl border border-zinc-200 p-[15px]"><summary class="cursor-pointer font-medium text-black">Datos técnicos</summary><div class="mt-[15px] grid grid-cols-3 gap-[12px]"><input wire:model="profileForm.source" placeholder="Origen" class="rounded-xl border-zinc-200 bg-zinc-50"><input wire:model="profileForm.destination" placeholder="Destino" class="rounded-xl border-zinc-200 bg-zinc-50"><input wire:model="profileForm.duration" placeholder="Duración" class="rounded-xl border-zinc-200 bg-zinc-50"></div></details>
                </div>
                @error('profileForm.name')<span class="mt-[10px] block text-red-600">{{ $message }}</span>@enderror
                <div class="mt-[25px] flex justify-between">@if ($editingProfile !== null)<button type="button" wire:click="deleteProfile" wire:confirm="¿Eliminar este perfil del informe?" class="rounded-xl border border-red-200 bg-red-50 px-[18px] py-[12px] font-medium text-red-600">Eliminar</button>@else<span></span>@endif<div class="flex gap-[10px]"><button type="button" wire:click="$set('showProfileEditor', false)" class="rounded-xl border border-zinc-200 px-[18px] py-[12px] font-medium text-black">Cancelar</button><button type="submit" class="rounded-xl bg-black px-[18px] py-[12px] font-medium text-white">Guardar</button></div></div>
            </form>
        </div>
    @endif

    @if ($showServerEditor)
        <div class="fixed inset-0 z-[80] flex items-center justify-center bg-black/45 p-[30px]" wire:key="server-editor">
            <form wire:submit="saveServer" class="w-full max-w-2xl rounded-2xl bg-white p-[30px] shadow-2xl">
                <div class="flex items-start justify-between"><div><h2 class="text-xl font-semibold text-black">{{ $editingServer === null ? 'Agregar servidor' : 'Editar servidor' }}</h2><p class="mt-[5px] text-zinc-500">Define el nombre y el recurso compartido donde están los logs.</p></div><button type="button" wire:click="$set('showServerEditor', false)" class="rounded-lg p-[8px] text-zinc-500 hover:bg-zinc-100">✕</button></div>
                <div class="mt-[25px] space-y-[15px]"><label class="block"><span class="mb-[7px] block font-medium text-black">Servidor *</span><input wire:model="serverForm.name" class="w-full rounded-xl border-zinc-200 bg-zinc-50 px-[15px] py-[12px] focus:border-blue-900 focus:ring-blue-900"></label><label class="block"><span class="mb-[7px] block font-medium text-black">Dirección IP</span><input wire:model="serverForm.ip" class="w-full rounded-xl border-zinc-200 bg-zinc-50 px-[15px] py-[12px] focus:border-blue-900 focus:ring-blue-900"></label><label class="block"><span class="mb-[7px] block font-medium text-black">Carpeta compartida</span><input wire:model="serverForm.share" placeholder="\\SERVIDOR\log" class="w-full rounded-xl border-zinc-200 bg-zinc-50 px-[15px] py-[12px] font-mono focus:border-blue-900 focus:ring-blue-900"></label></div>
                @error('serverForm.name')<span class="mt-[10px] block text-red-600">{{ $message }}</span>@enderror
                <div class="mt-[25px] flex justify-between">@if ($editingServer !== null)<button type="button" wire:click="deleteServer" wire:confirm="¿Eliminar este servidor y sus perfiles del informe?" class="rounded-xl border border-red-200 bg-red-50 px-[18px] py-[12px] font-medium text-red-600">Eliminar</button>@else<span></span>@endif<div class="flex gap-[10px]"><button type="button" wire:click="$set('showServerEditor', false)" class="rounded-xl border border-zinc-200 px-[18px] py-[12px] font-medium text-black">Cancelar</button><button type="submit" class="rounded-xl bg-black px-[18px] py-[12px] font-medium text-white">Guardar</button></div></div>
            </form>
        </div>
    @endif
</div>
