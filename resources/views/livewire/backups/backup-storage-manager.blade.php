<div
    data-clock-particle-network
    data-backup-site="{{ $site }}"
    class="relative isolate min-h-[calc(100dvh-90px)] w-full overflow-hidden bg-white text-[15px] text-zinc-700"
    x-data="backupUploadManager({
        endpoint: @js($uploadEndpoint),
        site: @js($site),
        csrf: @js(csrf_token())
    })"
    x-init="init()"
    x-on:backup-upload-finished.window="$wire.$refresh()"
>
    <canvas wire:ignore data-clock-network-canvas class="pointer-events-none absolute inset-0 z-0 h-full w-full opacity-[0.45]" aria-hidden="true"></canvas>

    <div class="no-print fixed bottom-[30px] right-[30px] z-30 flex items-center gap-[10px] rounded-xl border border-zinc-200 bg-white/95 px-[15px] py-[10px] shadow-[0_8px_24px_rgba(0,0,0,0.10)] backdrop-blur-sm">
        <button type="button" @click="zoom = Math.max(70, zoom - 5); saveZoom()" class="inline-flex h-7 w-7 items-center justify-center text-black focus:outline-none">−</button>
        <input type="range" min="70" max="100" step="5" x-model.number="zoom" @input="saveZoom()" class="h-1.5 w-[130px] cursor-pointer accent-black" aria-label="Ajustar tamaño de Gestión de Respaldos">
        <button type="button" @click="zoom = Math.min(100, zoom + 5); saveZoom()" class="inline-flex h-7 w-7 items-center justify-center text-black focus:outline-none">+</button>
        <span class="w-[42px] text-right font-semibold tabular-nums text-black" x-text="zoom + '%'">100%</span>
        <span class="h-5 w-px bg-zinc-200"></span>
        <button type="button" @click="toggleFullscreen($root)" class="p-[5px] text-black focus:outline-none" title="Pantalla completa">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 3H3v5M16 3h5v5M21 16v5h-5M3 16v5h5"/></svg>
        </button>
    </div>

    <div class="relative z-10 min-h-[calc(100dvh-90px)] min-w-[1050px] origin-top" :style="`width:${10000 / zoom}%;margin-left:${(100 - (10000 / zoom)) / 2}%;transform:scale(${zoom / 100});`">
        <header class="flex min-h-[122px] items-center justify-between gap-[40px] border-b border-zinc-200 bg-white/80 px-[50px]">
            <div class="flex items-center gap-[15px] text-zinc-500">
                <span>Actividades</span><span class="text-zinc-300">&gt;</span><span class="font-semibold text-black">Gestión de Respaldos</span>
            </div>
            <div class="flex items-center gap-[30px]">
                <button type="button" @click="focusUploader()" class="inline-flex items-center gap-[10px] border-0 bg-transparent p-0 font-semibold text-black focus:outline-none">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 16V4m0 0L7 9m5-5 5 5M4 15v5h16v-5"/></svg>
                    Subir respaldo
                </button>
                <button type="button" @click="$wire.$refresh()" class="inline-flex items-center gap-[10px] border-0 bg-transparent p-0 text-zinc-500 focus:outline-none">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 11a8 8 0 0 0-15.5-2M4 4v5h5m-5 4a8 8 0 0 0 15.5 2M20 20v-5h-5"/></svg>
                    Actualizar
                </button>
            </div>
        </header>

        <main class="space-y-[20px] p-[50px]">
            <section class="flex items-center justify-between gap-[30px]">
                <div class="flex items-center gap-[20px]">
                    <span class="flex h-14 w-14 items-center justify-center rounded-xl border border-zinc-200 bg-white text-black">
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7h7l2 2h9v11H3V7Zm0 0V4h7l2 3"/></svg>
                    </span>
                    <div><h1 class="text-xl font-semibold text-black">Gestión de Respaldos</h1><p class="mt-[5px] text-zinc-500">Sube un ZIP por sede y consulta automáticamente su contenido.</p></div>
                </div>
                <nav class="flex items-center gap-[20px]" aria-label="Secciones de respaldos">
                    <button type="button" wire:click="selectTab('backups')" class="inline-flex items-center gap-[10px] border-0 bg-transparent p-0 {{ $activeTab === 'backups' ? 'font-semibold text-black' : 'text-zinc-500' }} focus:outline-none">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7h7l2 2h9v11H3V7Z"/></svg>
                        Respaldos
                    </button>
                    <button type="button" wire:click="selectTab('history')" class="inline-flex items-center gap-[10px] border-0 bg-transparent p-0 {{ $activeTab === 'history' ? 'font-semibold text-black' : 'text-zinc-500' }} focus:outline-none">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 12a9 9 0 1 0 3-6.7M3 4v5h5M12 7v5l3 2"/></svg>
                        Historial de registros
                    </button>
                </nav>
            </section>

            <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white">
                <div class="flex min-h-[80px] items-center justify-between gap-[30px] border-b border-zinc-200 px-[20px] py-[15px]">
                    <div><h2 class="font-semibold text-black">{{ $activeTab === 'history' ? 'Historial de registros' : 'Explorador de respaldos · '.($sites[$site] ?? ucfirst($site)) }}</h2><p class="mt-[5px] text-zinc-500">{{ $activeTab === 'history' ? 'Bitácora de archivos ZIP cargados y procesados.' : 'El servidor lee del ZIP las carpetas de cada cliente, Index y Bak.' }}</p></div>
                    <div class="flex items-center gap-[20px] font-medium text-black">
                        <span>{{ $statusCounts['completed'] ?? 0 }} completados</span>
                        <span>{{ ($statusCounts['uploading'] ?? 0) + ($statusCounts['waiting'] ?? 0) }} en carga o espera</span>
                    </div>
                </div>
                <div class="flex min-h-[76px] items-center gap-[20px] border-b border-zinc-200 px-[20px] py-[15px]">
                    <label class="relative block flex-1">
                        <span class="sr-only">Buscar cliente o archivo</span>
                        <svg class="pointer-events-none absolute left-[15px] top-1/2 h-5 w-5 -translate-y-1/2 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m21 21-4.35-4.35m2.35-5.65a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z"/></svg>
                        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Buscar cliente o archivo..." class="h-[50px] w-full rounded-xl border border-zinc-200 bg-white pl-[45px] pr-[20px] text-[15px] text-black shadow-none outline-none focus:border-zinc-200 focus:ring-0">
                    </label>
                </div>

                @if ($activeTab !== 'history')
                    <div class="grid min-h-[590px] grid-cols-[340px_minmax(0,1fr)]">
                        <aside x-ref="uploader" wire:key="backup-uploader-{{ $site }}" class="border-r border-zinc-200 p-[20px]">
                            <h3 class="font-semibold text-black">Subir respaldo</h3>
                            <p class="mt-[5px] text-zinc-500">Selecciona la sede y el archivo ZIP. Las carpetas de clientes se detectan automáticamente.</p>

                            <div class="mt-[20px] space-y-[20px]">
                                <label class="block">
                                    <span class="mb-[10px] block font-semibold text-black">Sede del respaldo</span>
                                    <select wire:change="selectSite($event.target.value)" class="h-[50px] w-full rounded-xl border border-zinc-200 bg-white px-[20px] text-[15px] text-black shadow-none outline-none focus:border-zinc-200 focus:ring-0">
                                        @foreach ($sites as $siteKey => $siteName)
                                            <option value="{{ $siteKey }}" @selected($site === $siteKey)>{{ $siteName }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <input x-ref="fallbackFile" type="file" class="hidden" accept=".zip,application/zip" @change="receiveFallback($event)">
                                <button type="button" @click="chooseFile()" :disabled="busy" class="inline-flex min-h-[50px] w-full items-center justify-center gap-[10px] rounded-xl border-0 bg-zinc-900 px-[20px] py-[15px] font-semibold text-white disabled:cursor-not-allowed disabled:bg-zinc-200 disabled:text-zinc-500">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 16V4m0 0L7 9m5-5 5 5M4 15v5h16v-5"/></svg>
                                    Seleccionar ZIP
                                </button>
                            </div>

                            <div x-show="fileName || message" x-cloak class="mt-[20px] rounded-xl border border-zinc-200 p-[20px]">
                                <div class="flex items-start justify-between gap-[15px]"><div class="min-w-0"><strong class="block truncate text-black" x-text="fileName || 'Carga pendiente'"></strong><span class="mt-[4px] block text-zinc-500" x-text="message"></span></div><span class="font-semibold tabular-nums text-black" x-text="progress + '%'">0%</span></div>
                                <div class="mt-[15px] h-2 overflow-hidden rounded-full bg-zinc-100"><div class="h-full rounded-full bg-black transition-all" :style="`width:${progress}%`"></div></div>
                                <div class="mt-[12px] flex items-center justify-between gap-[10px] text-zinc-500"><span x-text="speed"></span><span x-text="eta"></span></div>
                            </div>
                            <p class="mt-[20px] leading-6 text-zinc-500">Estructura esperada: <strong class="text-black">Cliente/Index/*.index</strong> y <strong class="text-black">Cliente/Bak/*.bak</strong>. La carga se reanuda si se interrumpe.</p>
                        </aside>

                        <div class="min-w-0">
                            <div class="grid min-h-[58px] grid-cols-[minmax(260px,1fr)_150px_180px] items-center border-b border-zinc-200 bg-zinc-100 px-[20px] font-semibold text-zinc-700"><span>Cliente / archivo</span><span>Tamaño</span><span>Fecha de respaldo</span></div>
                            <div class="max-h-[590px] overflow-y-auto overscroll-contain">
                                @forelse ($backupTree as $customerName => $customerFolders)
                                    @php $customerFileCount = $customerFolders->flatten(1)->count(); @endphp
                                    <details class="group border-b border-zinc-200" open>
                                        <summary class="flex min-h-[64px] cursor-pointer list-none items-center gap-[10px] px-[20px] font-semibold text-black hover:bg-zinc-50">
                                            <svg class="h-5 w-5 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7h7l2 2h9v11H3V7Z"/></svg>
                                            <span class="min-w-0 flex-1 truncate">{{ $customerName }}</span><span class="font-normal text-zinc-500">{{ $customerFileCount }} archivos</span>
                                        </summary>
                                        @foreach (['index' => 'Index', 'bak' => 'Bak'] as $categoryKey => $categoryLabel)
                                            @php $categoryFiles = $customerFolders->get($categoryKey, collect()); @endphp
                                            <details class="border-t border-zinc-100" open>
                                                <summary class="flex min-h-[54px] cursor-pointer list-none items-center gap-[10px] bg-zinc-50/60 px-[40px] text-black">
                                                    <svg class="h-4 w-4 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7h7l2 2h9v11H3V7Z"/></svg><span class="font-medium">{{ $categoryLabel }}</span><span class="text-zinc-400">{{ $categoryFiles->count() }}</span>
                                                </summary>
                                                @forelse ($categoryFiles as $file)
                                                    <a href="{{ $file['download_url'] }}" class="grid min-h-[58px] grid-cols-[minmax(240px,1fr)_150px_180px] items-center border-t border-zinc-100 px-[60px] text-zinc-600 hover:bg-zinc-50 hover:text-black">
                                                        <span class="flex min-w-0 items-center gap-[10px]"><svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 3h8l4 4v14H6V3Zm8 0v5h5"/></svg><span class="truncate">{{ $file['name'] }}</span></span>
                                                        <span class="tabular-nums">{{ number_format($file['size'] / 1048576, 2) }} MB</span><span>{{ $file['completed_at']?->format('d/m/Y H:i') }}</span>
                                                    </a>
                                                @empty
                                                    <div class="border-t border-zinc-100 px-[60px] py-[15px] text-zinc-400">Sin archivos en esta carpeta.</div>
                                                @endforelse
                                            </details>
                                        @endforeach
                                    </details>
                                @empty
                                    <div class="flex min-h-[220px] items-center justify-center text-zinc-500">No hay respaldos que coincidan con la búsqueda.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @else
                    <div class="max-h-[650px] overflow-auto">
                        <table class="w-full min-w-[1120px] border-collapse text-left">
                            <thead class="sticky top-0 z-10 bg-zinc-100"><tr>@foreach (['Fecha', 'Hora', 'Sede', 'Usuario', 'Contenido detectado', 'ZIP subido', 'Estado'] as $heading)<th class="border-b border-r border-zinc-200 px-[20px] py-[15px] font-semibold last:border-r-0">{{ $heading }}</th>@endforeach</tr></thead>
                            <tbody>
                                @forelse ($history as $record)
                                    <tr class="border-b border-zinc-200 bg-white">
                                        @php $detectedCustomers = collect($record->manifest ?? [])->pluck('customer')->unique()->count(); @endphp
                                        <td class="px-[20px] py-[15px]">{{ $record->created_at->format('d/m/Y') }}</td><td class="px-[20px] py-[15px] tabular-nums">{{ $record->created_at->format('H:i:s') }}</td><td class="px-[20px] py-[15px]">{{ $sites[$record->site] ?? ucfirst($record->site) }}</td><td class="px-[20px] py-[15px]">{{ trim(($record->user?->name ?? 'Usuario eliminado').' '.($record->user?->last_name ?? '')) }}</td><td class="px-[20px] py-[15px]">{{ $detectedCustomers }} clientes · {{ count($record->manifest ?? []) }} archivos</td><td class="px-[20px] py-[15px]">{{ $record->original_name }}</td>
                                        <td class="px-[20px] py-[15px]"><span class="inline-flex rounded-full border border-zinc-200 px-[10px] py-[4px] font-medium {{ $record->status === 'completed' ? 'bg-zinc-900 text-white' : ($record->status === 'failed' ? 'bg-red-50 text-red-700' : 'bg-white text-black') }}">{{ ['waiting' => 'En espera', 'uploading' => 'Cargando', 'queued' => 'En cola', 'processing' => 'Procesando', 'completed' => 'Completado', 'failed' => 'Error'][$record->status] ?? ucfirst($record->status) }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="px-[20px] py-[60px] text-center text-zinc-500">Todavía no hay respaldos registrados.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </main>
    </div>

    <script>
        (() => {
            const register = () => {
                if (window.__backupUploadManagerRegistered) return;
                window.__backupUploadManagerRegistered = true;

                const database = () => new Promise((resolve, reject) => {
                    const request = indexedDB.open('crm-backup-uploads', 1);
                    request.onupgradeneeded = () => request.result.createObjectStore('pending', { keyPath: 'id' });
                    request.onsuccess = () => resolve(request.result);
                    request.onerror = () => reject(request.error);
                });
                const pendingStore = async (mode, action) => {
                    const db = await database();
                    return new Promise((resolve, reject) => {
                        const transaction = db.transaction('pending', mode);
                        const request = action(transaction.objectStore('pending'));
                        request.onsuccess = () => resolve(request.result);
                        request.onerror = () => reject(request.error);
                    });
                };

                Alpine.data('backupUploadManager', (config) => ({
                    zoom: Number(localStorage.getItem('backup-storage-zoom') || 100), busy: false,
                    progress: 0, speed: '0 MB/s', eta: 'Tiempo restante: —', message: '', fileName: '', currentFile: null, currentHandle: null,
                    saveZoom() { localStorage.setItem('backup-storage-zoom', String(this.zoom)); },
                    async toggleFullscreen(root) { if (document.fullscreenElement) await document.exitFullscreen(); else await root.requestFullscreen(); },
                    focusUploader() { this.$refs.uploader?.scrollIntoView({ behavior: 'smooth', block: 'center' }); },
                    async init() {
                        window.addEventListener('online', () => { if (this.currentFile && !this.busy) this.start(this.currentFile, this.currentHandle); });
                        try {
                            const saved = await pendingStore('readonly', store => store.get('active'));
                            if (!saved?.handle || saved.meta?.site !== this.$root.dataset.backupSite) return;
                            if ((await saved.handle.queryPermission({ mode: 'read' })) === 'granted') {
                                await this.start(await saved.handle.getFile(), saved.handle);
                            } else {
                                this.fileName = saved.meta.fileName; this.message = 'Selecciona el archivo para autorizar la reanudación.';
                            }
                        } catch (_) {}
                    },
                    async chooseFile() {
                        if (this.busy) return;
                        if (!window.showOpenFilePicker) { this.$refs.fallbackFile.click(); return; }
                        try {
                            const [handle] = await window.showOpenFilePicker({ multiple: false, types: [{ description: 'Archivo ZIP de respaldos', accept: { 'application/zip': ['.zip'] } }] });
                            await this.start(await handle.getFile(), handle);
                        } catch (error) { if (error.name !== 'AbortError') this.message = error.message; }
                    },
                    async receiveFallback(event) { const file = event.target.files?.[0]; if (file) await this.start(file, null); event.target.value = ''; },
                    async request(url, options = {}) {
                        const response = await fetch(url, { credentials: 'same-origin', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': config.csrf, ...(options.headers || {}) }, ...options });
                        const payload = await response.json().catch(() => ({}));
                        if (!response.ok) { const error = new Error(payload.message || payload.error || 'No fue posible completar la solicitud.'); error.status = response.status; throw error; }
                        return payload;
                    },
                    async start(file, handle) {
                        if (this.busy) return;
                        if (file.name.split('.').pop().toLowerCase() !== 'zip') { this.message = 'Selecciona un archivo .zip.'; return; }
                        this.busy = true; this.currentFile = file; this.currentHandle = handle; this.fileName = file.name; this.message = 'Preparando carga reanudable…';
                        const currentSite = this.$root.dataset.backupSite;
                        const meta = { site: currentSite, fileName: file.name };
                        if (handle) await pendingStore('readwrite', store => store.put({ id: 'active', handle, meta })).catch(() => {});
                        try {
                            let upload = await this.request(config.endpoint, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ site: currentSite, file_name: file.name, mime_type: file.type || 'application/zip', size: file.size, fingerprint: `${file.name}:${file.size}:${file.lastModified}` }) });
                            while (upload.status === 'waiting') { this.message = 'En cola de espera; se iniciará automáticamente…'; await new Promise(resolve => setTimeout(resolve, 3000)); upload = await this.request(upload.statusUrl); }
                            if (upload.status === 'completed') { this.progress = 100; this.message = 'Respaldo disponible.'; await this.finishLocal(); return; }
                            const uploaded = new Set(upload.uploadedChunks.map(Number));
                            const started = performance.now(); let initialBytes = upload.receivedBytes || 0;
                            for (let index = 0; index < upload.totalChunks; index++) {
                                if (uploaded.has(index)) continue;
                                const start = index * upload.chunkSize; const blob = file.slice(start, Math.min(file.size, start + upload.chunkSize));
                                let sent = false;
                                while (!sent) {
                                    try {
                                        this.message = navigator.onLine ? `Subiendo fragmento ${index + 1} de ${upload.totalChunks}…` : 'Sin conexión; la carga se reanudará automáticamente…';
                                        if (!navigator.onLine) await new Promise(resolve => window.addEventListener('online', resolve, { once: true }));
                                        upload = await this.request(upload.chunkUrlTemplate.replace('__INDEX__', index), { method: 'POST', headers: { 'Content-Type': 'application/octet-stream' }, body: blob }); sent = true;
                                    } catch (error) {
                                        if (error.status && error.status < 500 && error.status !== 409) throw error;
                                        await new Promise(resolve => setTimeout(resolve, 2500)); upload = await this.request(upload.statusUrl);
                                        if (upload.status === 'waiting') continue;
                                        if ((upload.uploadedChunks || []).map(Number).includes(index)) sent = true;
                                    }
                                }
                                const elapsed = Math.max(1, (performance.now() - started) / 1000); const transferred = Math.max(0, upload.receivedBytes - initialBytes); const bytesPerSecond = transferred / elapsed;
                                this.progress = Math.min(100, Math.round(upload.receivedBytes * 100 / file.size)); this.speed = this.formatBytes(bytesPerSecond) + '/s';
                                this.eta = bytesPerSecond > 0 ? 'Tiempo restante: ' + this.formatTime((file.size - upload.receivedBytes) / bytesPerSecond) : 'Tiempo restante: —';
                            }
                            upload = await this.request(upload.completeUrl, { method: 'POST' }); this.progress = 100;
                            while (['queued', 'processing'].includes(upload.status)) { this.message = upload.status === 'queued' ? 'En cola para organizar el archivo…' : 'Organizando el respaldo en el servidor…'; await new Promise(resolve => setTimeout(resolve, 2500)); upload = await this.request(upload.statusUrl); }
                            if (upload.status === 'failed') throw new Error(upload.error || 'El servidor no pudo procesar el respaldo.');
                            this.message = 'ZIP procesado; los respaldos ya están disponibles.'; await this.finishLocal();
                        } catch (error) { this.message = error.message; }
                        finally { this.busy = false; }
                    },
                    async finishLocal() { await pendingStore('readwrite', store => store.delete('active')).catch(() => {}); this.busy = false; window.dispatchEvent(new CustomEvent('backup-upload-finished')); },
                    formatBytes(value) { if (!Number.isFinite(value) || value <= 0) return '0 MB'; const units = ['B','KB','MB','GB']; const unit = Math.min(units.length - 1, Math.floor(Math.log(value) / Math.log(1024))); return `${(value / 1024 ** unit).toFixed(unit > 1 ? 1 : 0)} ${units[unit]}`; },
                    formatTime(seconds) { if (!Number.isFinite(seconds)) return '—'; const minutes = Math.floor(seconds / 60); const rest = Math.max(0, Math.round(seconds % 60)); return minutes > 0 ? `${minutes} min ${rest} s` : `${rest} s`; }
                }));
            };
            if (window.Alpine) register(); else document.addEventListener('alpine:init', register, { once: true });
        })();
    </script>
</div>
