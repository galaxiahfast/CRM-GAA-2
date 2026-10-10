<div
    data-clock-particle-network
    data-backup-site="{{ $site }}"
    class="relative isolate min-h-[calc(100dvh-90px)] w-full overflow-hidden bg-white text-[15px] text-zinc-700"
    x-data="backupUploadManager({
                endpoint: @js($uploadEndpoint),
                site: @js($site),
                csrf: @js(csrf_token()),
                maxConcurrent: @js($maxConcurrentUploads),
            })"
    x-init="init()"
    x-on:backup-reupload.window="chooseFiles($event.detail)"
    x-on:backup-upload-finished.window="$wire.$refresh()"
>
    <style>
        .backup-scrollbar {
            scrollbar-color: #18181b #f4f4f5;
            scrollbar-width: thin;
        }

        .backup-scrollbar::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }

        .backup-scrollbar::-webkit-scrollbar-track {
            background: #f4f4f5;
        }

        .backup-scrollbar::-webkit-scrollbar-thumb {
            border: 2px solid #f4f4f5;
            border-radius: 999px;
            background: #18181b;
        }
    </style>

    <canvas
        wire:ignore
        data-clock-network-canvas
        class="pointer-events-none absolute inset-0 z-0 h-full w-full opacity-[0.45]"
        aria-hidden="true"
    ></canvas>

    <div
        class="no-print fixed bottom-[30px] right-[30px] z-30 flex items-center gap-[10px] rounded-xl border border-zinc-200 bg-white/95 px-[15px] py-[10px] shadow-[0_8px_24px_rgba(0,0,0,0.10)] backdrop-blur-sm"
    >
        <button
            type="button"
            @click="zoom = Math.max(70, zoom - 5); saveZoom()"
            class="inline-flex h-7 w-7 items-center justify-center text-black focus:outline-none"
        >
            −
        </button>
        <input
            type="range"
            min="70"
            max="100"
            step="5"
            x-model.number="zoom"
            @input="saveZoom()"
            class="h-1.5 w-[130px] cursor-pointer accent-black"
            aria-label="Ajustar tamaño de Gestión de Respaldos"
        />
        <button
            type="button"
            @click="zoom = Math.min(100, zoom + 5); saveZoom()"
            class="inline-flex h-7 w-7 items-center justify-center text-black focus:outline-none"
        >
            +
        </button>
        <span
            class="w-[42px] text-right font-semibold tabular-nums text-black"
            x-text="zoom + '%'"
        >
            100%
        </span>
        <span class="h-5 w-px bg-zinc-200"></span>
        <button
            type="button"
            @click="toggleFullscreen($root)"
            class="p-[5px] text-black focus:outline-none"
            title="Pantalla completa"
        >
            <svg
                class="h-4 w-4"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1.8"
                    d="M8 3H3v5M16 3h5v5M21 16v5h-5M3 16v5h5"
                />
            </svg>
        </button>
    </div>

    <div
        class="relative z-10 min-h-[calc(100dvh-90px)] min-w-[1080px] origin-top"
        :style="`width:${10000 / zoom}%;margin-left:${(100 - (10000 / zoom)) / 2}%;transform:scale(${zoom / 100});`"
    >
        <header
            class="flex min-h-[122px] items-center justify-between gap-[40px] border-b border-zinc-200 bg-white/80 px-[50px]"
        >
            <div class="flex items-center gap-[15px] text-zinc-500">
                <span>Actividades</span>
                <span class="text-zinc-300">&gt;</span>
                <span class="font-semibold text-black">
                    Gestión de Respaldos
                </span>
            </div>
            <div class="flex items-center gap-[30px]">
                <button
                    type="button"
                    @click="showUploader()"
                    class="inline-flex items-center gap-[10px] border-0 bg-transparent p-0 font-semibold text-black focus:outline-none"
                >
                    <svg
                        class="h-5 w-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.8"
                            d="M12 16V4m0 0L7 9m5-5 5 5M4 15v5h16v-5"
                        />
                    </svg>
                    Subir respaldos
                </button>
                <button
                    type="button"
                    @click="$wire.$refresh()"
                    class="inline-flex items-center gap-[10px] border-0 bg-transparent p-0 text-zinc-500 focus:outline-none"
                >
                    <svg
                        class="h-5 w-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.8"
                            d="M20 11a8 8 0 0 0-15.5-2M4 4v5h5m-5 4a8 8 0 0 0 15.5 2M20 20v-5h-5"
                        />
                    </svg>
                    Actualizar
                </button>
            </div>
        </header>

        <main class="space-y-[20px] p-[50px]">
            <section class="flex items-center justify-between gap-[30px]">
                <div class="flex items-center gap-[20px]">
                    <span
                        class="flex h-14 w-14 items-center justify-center rounded-xl border border-zinc-200 bg-white text-black"
                    >
                        <svg
                            class="h-7 w-7"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.8"
                                d="M3 7h7l2 2h9v11H3V7Zm0 0V4h7l2 3"
                            />
                        </svg>
                    </span>
                    <div>
                        <h1 class="text-xl font-semibold text-black">
                            Gestión de Respaldos
                        </h1>
                        <p class="mt-[5px] text-zinc-500">
                            Carga varios ZIP por sede y consulta su contenido
                            sin salir de la vista.
                        </p>
                    </div>
                </div>
                <nav
                    class="flex items-center gap-[20px]"
                    aria-label="Secciones de respaldos"
                >
                    <button
                        type="button"
                        wire:click="selectTab('backups')"
                        @click="showTree()"
                        class="inline-flex items-center gap-[10px] border-0 bg-transparent p-0 {{ $activeTab === "backups" ? "font-semibold text-black" : "text-zinc-500" }} focus:outline-none"
                    >
                        <svg
                            class="h-5 w-5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.8"
                                d="M3 7h7l2 2h9v11H3V7Z"
                            />
                        </svg>
                        Respaldos
                    </button>
                    <button
                        type="button"
                        wire:click="selectTab('history')"
                        class="inline-flex items-center gap-[10px] border-0 bg-transparent p-0 {{ $activeTab === "history" ? "font-semibold text-black" : "text-zinc-500" }} focus:outline-none"
                    >
                        <svg
                            class="h-5 w-5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.8"
                                d="M3 12a9 9 0 1 0 3-6.7M3 4v5h5M12 7v5l3 2"
                            />
                        </svg>
                        Historial de registros
                    </button>
                </nav>
            </section>

            <section
                class="overflow-hidden rounded-xl border border-zinc-200 bg-white"
            >
                <div
                    class="flex min-h-[80px] items-center justify-between gap-[30px] border-b border-zinc-200 px-[20px] py-[15px]"
                >
                    <div>
                        @if ($activeTab === "history")
                            <h2 class="font-semibold text-black">
                                Historial de registros
                            </h2>
                            <p class="mt-[5px] text-zinc-500">
                                Inspecciona, descarga, reemplaza, edita o
                                elimina cada respaldo.
                            </p>
                        @else
                            <h2
                                class="font-semibold text-black"
                                x-text="workspaceMode === 'upload' ? @js("Cargar respaldos · " . ($sites[$site] ?? ucfirst($site))) : @js("Explorador de respaldos · " . ($sites[$site] ?? ucfirst($site)))"
                            ></h2>
                            <p
                                class="mt-[5px] text-zinc-500"
                                x-text="
                                    workspaceMode === 'upload'
                                        ? 'Arrastra tus paquetes, revisa su progreso o cancela la carga.'
                                        : 'Abre hasta tres clientes al mismo tiempo para consultar sus archivos.'
                                "
                            ></p>
                        @endif
                    </div>
                    <div
                        class="flex items-center gap-[20px] font-medium text-black"
                    >
                        <span>
                            {{ $statusCounts["completed"] ?? 0 }} completados
                        </span>
                        <span>
                            {{ ($statusCounts["uploading"] ?? 0) + ($statusCounts["waiting"] ?? 0) + ($statusCounts["queued"] ?? 0) + ($statusCounts["processing"] ?? 0) }}
                            activos
                        </span>
                    </div>
                </div>
                <div
                    @if ($activeTab !== "history")
                        x-show="workspaceMode === 'tree'"
                        x-cloak
                    @endif
                    class="flex min-h-[76px] items-center gap-[20px] border-b border-zinc-200 px-[20px] py-[15px]"
                >
                    <label class="relative block flex-1">
                        <span class="sr-only">Buscar cliente o archivo</span>
                        <svg
                            class="pointer-events-none absolute left-[15px] top-1/2 h-5 w-5 -translate-y-1/2 text-zinc-400"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.8"
                                d="m21 21-4.35-4.35m2.35-5.65a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z"
                            />
                        </svg>
                        <input
                            type="search"
                            wire:model.live.debounce.300ms="search"
                            placeholder="Buscar cliente, usuario o archivo..."
                            class="h-[50px] w-full rounded-xl border border-zinc-200 bg-white pl-[45px] pr-[20px] text-[15px] text-black shadow-none outline-none focus:border-zinc-200 focus:ring-0"
                        />
                    </label>
                </div>

                @if ($activeTab !== "history")
                    <div
                        class="grid min-h-[610px] grid-cols-[370px_minmax(0,1fr)]"
                    >
                        <aside
                            x-ref="uploader"
                            wire:key="backup-uploader-{{ $site }}"
                            class="border-r border-zinc-200 p-[20px]"
                        >
                            <h3 class="font-semibold text-black">
                                Ubicación del respaldo
                            </h3>
                            <p class="mt-[5px] text-zinc-500">
                                Elige la sede donde se guardará el paquete.
                            </p>
                            <div class="mt-[20px] space-y-[20px]">
                                <label class="block">
                                    <span
                                        class="mb-[10px] block font-semibold text-black"
                                    >
                                        Sede del respaldo
                                    </span>
                                    <select
                                        wire:change="selectSite($event.target.value)"
                                        class="h-[50px] w-full rounded-xl border border-zinc-200 bg-white px-[20px] text-[15px] text-black shadow-none outline-none focus:border-zinc-200 focus:ring-0"
                                    >
                                        @foreach ($sites as $siteKey => $siteName)
                                            <option
                                                value="{{ $siteKey }}"
                                                @selected($site === $siteKey)
                                            >
                                                {{ $siteName }}
                                            </option>
                                        @endforeach
                                    </select>
                                </label>
                            </div>
                            <p class="mt-[20px] leading-6 text-zinc-500">
                                Estructura esperada:
                                <strong class="text-black">
                                    Cliente/Index/*.index
                                </strong>
                                y
                                <strong class="text-black">
                                    Cliente/Bak/*.bak
                                </strong>
                                . La carga se reanuda tras recuperar la
                                conexión.
                            </p>
                        </aside>

                        <div class="min-w-0">
                            <section
                                x-show="workspaceMode === 'upload'"
                                x-ref="uploadWorkspace"
                                @dragenter.prevent="dragging = true"
                                @dragover.prevent="dragging = true"
                                @dragleave.prevent="dragging = false"
                                @drop.prevent="handleDrop($event)"
                                class="flex min-h-[610px] flex-col p-[20px]"
                            >
                                <input
                                    x-ref="fallbackFiles"
                                    type="file"
                                    class="hidden"
                                    accept=".zip,application/zip"
                                    multiple
                                    @change="receiveFallback($event)"
                                />
                                <input
                                    x-ref="fallbackFolder"
                                    type="file"
                                    class="hidden"
                                    accept=".zip,application/zip"
                                    multiple
                                    webkitdirectory
                                    @change="receiveFallback($event)"
                                />

                                <div x-show="!uploads.length">
                                    <div
                                        class="flex items-start justify-between gap-[20px]"
                                    >
                                        <div>
                                            <h3
                                                class="font-semibold text-black"
                                            >
                                                Cargar respaldos
                                            </h3>
                                            <p class="mt-[5px] text-zinc-500">
                                                Arrastra uno o varios ZIP. Aquí
                                                verás el progreso y podrás
                                                cancelar cada carga.
                                            </p>
                                        </div>
                                        @if ($backupTree->isNotEmpty())
                                            <button
                                                type="button"
                                                @click="showTree()"
                                                class="inline-flex min-h-[50px] shrink-0 items-center justify-center gap-[10px] rounded-xl border border-zinc-200 bg-white px-[20px] py-[15px] font-semibold text-black focus:outline-none"
                                                title="Ver respaldos ya cargados"
                                            >
                                                Ver respaldos existentes
                                            </button>
                                        @endif
                                    </div>

                                    <button
                                        type="button"
                                        @click="chooseFiles()"
                                        :class="dragging ? 'border-zinc-900 bg-zinc-100' : 'border-zinc-300 bg-zinc-50/60'"
                                        class="mt-[20px] flex min-h-[220px] w-full flex-col items-center justify-center gap-[10px] rounded-xl border-2 border-dashed px-[20px] py-[30px] text-center transition-colors focus:outline-none"
                                        title="Arrastra paquetes ZIP aquí o haz clic para seleccionarlos"
                                    >
                                        <svg
                                            class="h-10 w-10 text-zinc-500"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="1.8"
                                                d="M12 16V4m0 0L7 9m5-5 5 5M4 15v5h16v-5"
                                            />
                                        </svg>
                                        <strong class="text-black">
                                            Arrastra tus ZIP aquí
                                        </strong>
                                        <span class="text-zinc-500">
                                            o haz clic para seleccionar varios
                                            archivos
                                        </span>
                                    </button>

                                    <div
                                        class="mt-[20px] grid grid-cols-2 gap-[10px]"
                                    >
                                        <button
                                            type="button"
                                            @click="chooseFiles()"
                                            class="inline-flex min-h-[50px] items-center justify-center gap-[10px] rounded-xl border-0 bg-zinc-900 px-[20px] py-[15px] font-semibold text-white focus:outline-none"
                                            title="Seleccionar uno o varios paquetes ZIP"
                                        >
                                            Seleccionar ZIP
                                        </button>
                                        <button
                                            type="button"
                                            @click="chooseFolder()"
                                            class="inline-flex min-h-[50px] items-center justify-center gap-[10px] rounded-xl border border-zinc-200 bg-white px-[20px] py-[15px] font-semibold text-black focus:outline-none"
                                            title="Seleccionar una carpeta que contenga paquetes ZIP"
                                        >
                                            Seleccionar carpeta
                                        </button>
                                    </div>
                                </div>

                                <div
                                    x-show="uploads.length"
                                    x-cloak
                                    class="backup-scrollbar space-y-[10px] overflow-y-auto pr-[5px]"
                                >
                                    <template
                                        x-for="item in uploads"
                                        :key="item.localId"
                                    >
                                        <article
                                            class="rounded-xl border border-zinc-200 p-[15px]"
                                        >
                                            <div
                                                class="flex items-start justify-between gap-[20px]"
                                            >
                                                <div class="min-w-0 flex-1">
                                                    <strong
                                                        class="block truncate text-black"
                                                        x-text="item.fileName"
                                                    ></strong>
                                                    <span
                                                        class="mt-[3px] block text-zinc-500"
                                                        x-text="statusLabel(item.status)"
                                                    ></span>
                                                </div>
                                                <div
                                                    class="flex shrink-0 items-center gap-[15px]"
                                                >
                                                    <span
                                                        class="font-semibold tabular-nums text-black"
                                                        x-text="item.progress + '%'"
                                                    ></span>
                                                    <button
                                                        x-show="canCancel(item)"
                                                        type="button"
                                                        @click="cancelUpload(item)"
                                                        class="font-semibold text-red-600 focus:outline-none"
                                                        title="Cancelar esta carga y eliminar sus fragmentos"
                                                    >
                                                        Cancelar subida
                                                    </button>
                                                </div>
                                            </div>
                                            <div
                                                class="mt-[12px] h-2 overflow-hidden rounded-full bg-zinc-100"
                                            >
                                                <div
                                                    class="h-full rounded-full transition-all"
                                                    :class="item.status === 'error' ? 'bg-red-500' : 'bg-black'"
                                                    :style="`width:${item.progress}%`"
                                                ></div>
                                            </div>
                                            <div
                                                class="mt-[10px] flex items-center justify-between gap-[10px] text-zinc-500"
                                            >
                                                <span
                                                    x-text="item.speed"
                                                ></span>
                                                <span
                                                    class="text-right"
                                                    x-text="item.eta"
                                                ></span>
                                            </div>
                                            <span
                                                class="mt-[5px] block text-zinc-500"
                                                x-text="item.message"
                                            ></span>
                                            <button
                                                x-show="item.status === 'error'"
                                                type="button"
                                                @click="retry(item)"
                                                class="mt-[10px] p-0 font-semibold text-black focus:outline-none"
                                            >
                                                Reintentar
                                            </button>
                                        </article>
                                    </template>
                                </div>
                            </section>

                            <section
                                x-show="workspaceMode === 'tree'"
                                x-cloak
                                class="flex min-h-[610px] min-w-0 flex-col"
                            >
                                <div
                                    class="grid min-h-[58px] shrink-0 grid-cols-[minmax(260px,1fr)_150px_180px] items-center border-b border-zinc-200 bg-zinc-100 px-[20px] font-semibold text-zinc-700"
                                >
                                    <span>Cliente / archivo</span>
                                    <span>Tamaño</span>
                                    <span>Fecha de respaldo</span>
                                </div>
                                <div
                                    class="backup-scrollbar max-h-[610px] min-h-0 flex-1 overflow-y-auto overscroll-contain"
                                >
                                    <div
                                        class="flex min-h-[64px] items-center gap-[10px] border-b border-zinc-200 bg-zinc-50 px-[20px] font-semibold text-black"
                                    >
                                        <svg
                                            class="h-5 w-5 text-zinc-500"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="1.8"
                                                d="M3 7h7l2 2h9v11H3V7Z"
                                            />
                                        </svg>
                                        Respaldos /
                                        {{ $sites[$site] ?? ucfirst($site) }}
                                    </div>
                                    @foreach ($activeUploads as $activeUpload)
                                        <div
                                            class="grid min-h-[64px] grid-cols-[minmax(260px,1fr)_150px_180px] items-center border-b border-zinc-200 bg-amber-50/40 px-[20px]"
                                        >
                                            <span
                                                class="flex min-w-0 items-center gap-[10px]"
                                            >
                                                <svg
                                                    class="h-5 w-5 shrink-0 animate-pulse text-amber-600"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke="currentColor"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="1.8"
                                                        d="M12 3v9l4 2M21 12a9 9 0 1 1-9-9"
                                                    />
                                                </svg>
                                                <span class="truncate">
                                                    <strong class="text-black">
                                                        {{ $activeUpload->original_name }}
                                                    </strong>
                                                    <span
                                                        class="mt-[3px] block text-zinc-500"
                                                    >
                                                        {{ ["waiting" => "Pendiente", "uploading" => "Subiendo", "queued" => "En cola", "processing" => "Procesando"][$activeUpload->status] ?? ucfirst($activeUpload->status) }}
                                                    </span>
                                                </span>
                                            </span>
                                            <span class="tabular-nums">
                                                {{ number_format($activeUpload->received_bytes / 1048576, 2) }}
                                                /
                                                {{ number_format($activeUpload->size / 1048576, 2) }}
                                                MB
                                            </span>
                                            <span class="tabular-nums">
                                                {{ $activeUpload->last_activity_at?->format("d/m/Y H:i:s") }}
                                            </span>
                                        </div>
                                    @endforeach

                                    @forelse ($backupTree as $customerName => $customerFolders)
                                        @php
                                            $customerFileCount = $customerFolders->flatten(1)->count();
                                        @endphp

                                        <details
                                            class="group/customer border-b border-zinc-200"
                                            wire:key="backup-customer-{{ md5($site . $customerName) }}"
                                            data-customer-key="{{ $customerName }}"
                                            :open="isCustomerOpen(@js($customerName))"
                                            @toggle="toggleCustomer(@js($customerName), $event)"
                                        >
                                            <summary
                                                class="flex min-h-[64px] cursor-pointer list-none items-center gap-[10px] px-[20px] font-semibold text-black hover:bg-zinc-50"
                                                title="Desplegar carpetas Index y Bak de {{ $customerName }}"
                                            >
                                                <svg
                                                    class="h-4 w-4 shrink-0 text-zinc-500 transition-transform group-open/customer:rotate-90"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke="currentColor"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="1.8"
                                                        d="m9 5 7 7-7 7"
                                                    />
                                                </svg>
                                                <svg
                                                    class="h-5 w-5 text-zinc-500"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke="currentColor"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="1.8"
                                                        d="M3 7h7l2 2h9v11H3V7Z"
                                                    />
                                                </svg>
                                                <span
                                                    class="min-w-0 flex-1 truncate"
                                                >
                                                    {{ $customerName }}
                                                </span>
                                                <span
                                                    class="font-normal text-zinc-500"
                                                >
                                                    {{ $customerFileCount }}
                                                    archivos
                                                </span>
                                            </summary>
                                            <div
                                                class="relative ml-[48px] border-l border-zinc-300"
                                            >
                                                @foreach (["index" => "Index", "bak" => "Bak"] as $categoryKey => $categoryLabel)
                                                    @php
                                                        $categoryFiles = $customerFolders->get($categoryKey, collect());
                                                    @endphp

                                                    <div
                                                        class="relative border-t border-zinc-200"
                                                    >
                                                        <span
                                                            class="absolute -left-px top-[27px] h-px w-[24px] bg-zinc-300"
                                                            aria-hidden="true"
                                                        ></span>
                                                        <div
                                                            class="flex min-h-[54px] items-center gap-[10px] bg-zinc-50/60 pl-[38px] pr-[20px] text-black"
                                                        >
                                                            <svg
                                                                class="h-4 w-4 text-zinc-500"
                                                                fill="none"
                                                                viewBox="0 0 24 24"
                                                                stroke="currentColor"
                                                            >
                                                                <path
                                                                    stroke-linecap="round"
                                                                    stroke-linejoin="round"
                                                                    stroke-width="1.8"
                                                                    d="M3 7h7l2 2h9v11H3V7Z"
                                                                />
                                                            </svg>
                                                            <span
                                                                class="font-medium"
                                                            >
                                                                {{ $categoryLabel }}
                                                            </span>
                                                            <span
                                                                class="text-zinc-400"
                                                            >
                                                                {{ $categoryFiles->count() }}
                                                            </span>
                                                        </div>
                                                        @forelse ($categoryFiles as $file)
                                                            <a
                                                                href="{{ $file["download_url"] }}"
                                                                class="relative grid min-h-[58px] grid-cols-[minmax(240px,1fr)_150px_180px] items-center border-t border-zinc-200 pl-[58px] pr-[20px] text-zinc-600 hover:bg-zinc-50 hover:text-black"
                                                                title="Haz clic para descargar {{ $file["name"] }}"
                                                            >
                                                                <span
                                                                    class="absolute -left-px top-1/2 h-px w-[42px] bg-zinc-300"
                                                                    aria-hidden="true"
                                                                ></span>
                                                                <span
                                                                    class="flex min-w-0 items-center gap-[10px]"
                                                                >
                                                                    <svg
                                                                        class="h-4 w-4 shrink-0"
                                                                        fill="none"
                                                                        viewBox="0 0 24 24"
                                                                        stroke="currentColor"
                                                                    >
                                                                        <path
                                                                            stroke-linecap="round"
                                                                            stroke-linejoin="round"
                                                                            stroke-width="1.8"
                                                                            d="M6 3h8l4 4v14H6V3Zm8 0v5h5"
                                                                        />
                                                                    </svg>
                                                                    <span
                                                                        class="truncate"
                                                                    >
                                                                        {{ $file["name"] }}
                                                                    </span>
                                                                    <svg
                                                                        class="ml-auto h-4 w-4 shrink-0 text-black"
                                                                        fill="none"
                                                                        viewBox="0 0 24 24"
                                                                        stroke="currentColor"
                                                                        aria-label="Descargar archivo"
                                                                    >
                                                                        <path
                                                                            stroke-linecap="round"
                                                                            stroke-linejoin="round"
                                                                            stroke-width="1.8"
                                                                            d="M12 3v12m0 0 4-4m-4 4-4-4M4 19h16"
                                                                        />
                                                                    </svg>
                                                                </span>
                                                                <span
                                                                    class="tabular-nums"
                                                                >
                                                                    {{ number_format($file["size"] / 1048576, 2) }}
                                                                    MB
                                                                </span>
                                                                <span
                                                                    class="tabular-nums"
                                                                >
                                                                    {{ $file["completed_at"]?->format("d/m/Y H:i") }}
                                                                </span>
                                                            </a>
                                                        @empty
                                                            <div
                                                                class="relative border-t border-zinc-200 py-[15px] pl-[58px] pr-[20px] text-zinc-400"
                                                            >
                                                                <span
                                                                    class="absolute -left-px top-1/2 h-px w-[42px] bg-zinc-300"
                                                                    aria-hidden="true"
                                                                ></span>
                                                                Sin archivos en
                                                                esta carpeta.
                                                            </div>
                                                        @endforelse
                                                    </div>
                                                @endforeach
                                            </div>
                                        </details>
                                    @empty
                                        @if ($activeUploads->isEmpty())
                                            <div
                                                class="flex min-h-[220px] items-center justify-center text-zinc-500"
                                            >
                                                No hay respaldos que coincidan
                                                con la búsqueda.
                                            </div>
                                        @endif
                                    @endforelse
                                </div>
                                <div
                                    class="grid min-h-[58px] shrink-0 grid-cols-[minmax(260px,1fr)_150px_180px] items-center border-t border-zinc-200 bg-zinc-100 px-[20px] font-semibold text-black"
                                >
                                    <span>
                                        {{ $backupTree->count() }} clientes
                                    </span>
                                    <span>
                                        {{ $backupTree->flatten(2)->count() }}
                                        archivos
                                    </span>
                                    <span>
                                        {{ $sites[$site] ?? ucfirst($site) }}
                                    </span>
                                </div>
                            </section>
                        </div>
                    </div>
                @else
                    @php
                        $historyBytes = $history->sum("size");
                        $historyFiles = $history->sum(fn ($record) => count($record->manifest ?? []));
                    @endphp

                    <div
                        class="backup-scrollbar max-h-[650px] overflow-auto overscroll-contain"
                    >
                        <table
                            class="w-full min-w-[1380px] table-fixed border-collapse text-left"
                        >
                            <colgroup>
                                <col class="w-[120px]" />
                                <col class="w-[95px]" />
                                <col class="w-[110px]" />
                                <col class="w-[190px]" />
                                <col class="w-[220px]" />
                                <col class="w-[250px]" />
                                <col class="w-[135px]" />
                                <col class="w-[230px]" />
                            </colgroup>
                            <thead
                                class="sticky top-0 z-20 bg-zinc-100 shadow-[0_1px_0_#d4d4d8]"
                            >
                                <tr>
                                    @foreach (["Fecha", "Hora", "Sede", "Usuario", "Contenido detectado", "ZIP subido", "Estado", "Acciones"] as $heading)
                                        <th
                                            class="border-r border-zinc-200 px-[20px] py-[15px] font-semibold last:sticky last:right-0 last:z-30 last:border-l last:border-r-0 last:bg-zinc-100"
                                        >
                                            {{ $heading }}
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            @forelse ($history as $record)
                                @php
                                    $manifest = collect($record->manifest ?? []);
                                    $detectedCustomers = $manifest->pluck("customer")->unique();
                                    $statusLabel =
                                        [
                                            "waiting" => "Pendiente",
                                            "uploading" => "Subiendo",
                                            "queued" => "En cola",
                                            "processing" => "Procesando",
                                            "completed" => "Completado",
                                            "failed" => "Error",
                                        ][$record->status] ?? ucfirst($record->status);
                                @endphp

                                <tbody
                                    x-data="{ open: false }"
                                    class="bg-white"
                                >
                                    <tr class="border-b border-zinc-200">
                                        <td
                                            class="border-r border-zinc-200 px-[20px] py-[15px] tabular-nums"
                                        >
                                            {{ $record->created_at->format("d/m/Y") }}
                                        </td>
                                        <td
                                            class="border-r border-zinc-200 px-[20px] py-[15px] tabular-nums"
                                        >
                                            {{ $record->created_at->format("H:i:s") }}
                                        </td>
                                        <td
                                            class="border-r border-zinc-200 px-[20px] py-[15px]"
                                        >
                                            {{ $sites[$record->site] ?? ucfirst($record->site) }}
                                        </td>
                                        <td
                                            class="border-r border-zinc-200 px-[20px] py-[15px]"
                                        >
                                            <span class="line-clamp-2">
                                                {{ trim(($record->user?->name ?? "Usuario eliminado") . " " . ($record->user?->last_name ?? "")) }}
                                            </span>
                                        </td>
                                        <td
                                            class="border-r border-zinc-200 px-[20px] py-[15px]"
                                        >
                                            <button
                                                type="button"
                                                @click="open = !open"
                                                :aria-expanded="open"
                                                class="flex w-full items-center gap-[10px] p-0 text-left focus:outline-none"
                                                title="Desplegar contenido del respaldo"
                                            >
                                                <svg
                                                    class="h-4 w-4 shrink-0 text-zinc-500 transition-transform"
                                                    :class="open ? 'rotate-90' : ''"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke="currentColor"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="1.8"
                                                        d="m9 5 7 7-7 7"
                                                    />
                                                </svg>
                                                <span class="min-w-0">
                                                    <span
                                                        class="block font-medium text-black"
                                                    >
                                                        {{ $detectedCustomers->count() }}
                                                        clientes ·
                                                        {{ $manifest->count() }}
                                                        archivos
                                                    </span>
                                                    <span
                                                        class="mt-[3px] block truncate text-zinc-500"
                                                    >
                                                        {{ $record->assigned_customer ?: $detectedCustomers->take(2)->implode(", ") }}
                                                    </span>
                                                </span>
                                            </button>
                                        </td>
                                        <td
                                            class="border-r border-zinc-200 px-[20px] py-[15px]"
                                        >
                                            <span
                                                class="block truncate text-black"
                                            >
                                                {{ $record->original_name }}
                                            </span>
                                            <span
                                                class="mt-[3px] block tabular-nums text-zinc-500"
                                            >
                                                {{ number_format($record->size / 1048576, 2) }}
                                                MB
                                            </span>
                                        </td>
                                        <td
                                            class="border-r border-zinc-200 px-[20px] py-[15px]"
                                        >
                                            <span
                                                class="inline-flex rounded-full border border-zinc-200 px-[10px] py-[4px] font-medium {{ $record->status === "completed" ? "bg-zinc-900 text-white" : ($record->status === "failed" ? "bg-red-50 text-red-700" : "bg-white text-black") }}"
                                            >
                                                {{ $record->superseded_at ? "Reemplazado" : $statusLabel }}
                                            </span>
                                        </td>
                                        <td
                                            class="sticky right-0 z-10 border-l border-zinc-200 bg-white px-[10px] py-[10px]"
                                        >
                                            <div
                                                class="flex items-center justify-center gap-[5px]"
                                            >
                                                <button
                                                    type="button"
                                                    @click="open = !open"
                                                    :aria-expanded="open"
                                                    class="inline-flex h-10 w-10 items-center justify-center rounded-xl text-black hover:bg-zinc-100 focus:outline-none"
                                                    title="Desplegar o plegar detalles"
                                                    aria-label="Desplegar o plegar detalles"
                                                >
                                                    <svg
                                                        class="h-4 w-4 transition-transform"
                                                        :class="open ? 'rotate-90' : ''"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        stroke="currentColor"
                                                    >
                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            stroke-width="1.8"
                                                            d="m9 5 7 7-7 7"
                                                        />
                                                    </svg>
                                                </button>
                                                @if ($record->status === "completed")
                                                    <a
                                                        href="{{ route("activity-backups.download", $record) }}"
                                                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl text-black hover:bg-zinc-100"
                                                        title="Descargar paquete ZIP completo"
                                                        aria-label="Descargar paquete ZIP completo"
                                                    >
                                                        <svg
                                                            class="h-4 w-4"
                                                            fill="none"
                                                            viewBox="0 0 24 24"
                                                            stroke="currentColor"
                                                        >
                                                            <path
                                                                stroke-linecap="round"
                                                                stroke-linejoin="round"
                                                                stroke-width="1.8"
                                                                d="M12 3v12m0 0 4-4m-4 4-4-4M4 19h16"
                                                            />
                                                        </svg>
                                                    </a>
                                                    <button
                                                        type="button"
                                                        @click="openReplace({ id: @js($record->id), site: @js($record->site), fileName: @js($record->original_name) })"
                                                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl text-black hover:bg-zinc-100 focus:outline-none"
                                                        title="Re-subir y reemplazar este respaldo"
                                                        aria-label="Re-subir y reemplazar este respaldo"
                                                    >
                                                        <svg
                                                            class="h-4 w-4"
                                                            fill="none"
                                                            viewBox="0 0 24 24"
                                                            stroke="currentColor"
                                                        >
                                                            <path
                                                                stroke-linecap="round"
                                                                stroke-linejoin="round"
                                                                stroke-width="1.8"
                                                                d="M20 11a8 8 0 0 0-15.5-2M4 4v5h5m-5 4a8 8 0 0 0 15.5 2M20 20v-5h-5"
                                                            />
                                                        </svg>
                                                    </button>
                                                @endif

                                                <button
                                                    type="button"
                                                    @click="openEdit({ id: @js($record->id), site: @js($record->site), fileName: @js($record->original_name), assignedCustomer: @js($record->assigned_customer ?? ""), notes: @js($record->notes ?? ""), files: @js($manifest->values()->map(fn ($file, $index) => ["index" => $index, "name" => $file["name"] ?? "archivo", "category" => ucfirst($file["category"] ?? ""), "downloadUrl" => route("activity-backups.files.download", [$record, $index])])->all()) })"
                                                    class="inline-flex h-10 w-10 items-center justify-center rounded-xl text-black hover:bg-zinc-100 focus:outline-none"
                                                    title="Editar metadatos y archivos"
                                                    aria-label="Editar metadatos y archivos"
                                                >
                                                    <svg
                                                        class="h-4 w-4"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        stroke="currentColor"
                                                    >
                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            stroke-width="1.8"
                                                            d="m4 16-1 5 5-1L19 9l-4-4L4 16Z"
                                                        />
                                                    </svg>
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="openDelete({ id: @js($record->id), fileName: @js($record->original_name) })"
                                                    class="inline-flex h-10 w-10 items-center justify-center rounded-xl text-red-600 hover:bg-red-50 focus:outline-none"
                                                    title="Eliminar respaldo"
                                                    aria-label="Eliminar respaldo completo"
                                                >
                                                    <svg
                                                        class="h-4 w-4"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        stroke="currentColor"
                                                    >
                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            stroke-width="1.8"
                                                            d="M4 7h16M9 7V4h6v3m-8 0 1 13h8l1-13M10 11v5m4-5v5"
                                                        />
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr
                                        x-show="open"
                                        x-cloak
                                        class="border-b border-zinc-200 bg-zinc-50/60"
                                    >
                                        <td colspan="8" class="p-[20px]">
                                            <div
                                                class="grid grid-cols-[280px_minmax(0,1fr)] gap-[20px]"
                                            >
                                                <div
                                                    class="rounded-xl border border-zinc-200 bg-white p-[20px]"
                                                >
                                                    <strong class="text-black">
                                                        Detalle del respaldo
                                                    </strong>
                                                    <dl
                                                        class="mt-[15px] space-y-[10px]"
                                                    >
                                                        <div>
                                                            <dt
                                                                class="text-zinc-500"
                                                            >
                                                                Cliente asignado
                                                            </dt>
                                                            <dd
                                                                class="mt-[2px] text-black"
                                                            >
                                                                {{ $record->assigned_customer ?: "Detectado automáticamente" }}
                                                            </dd>
                                                        </div>
                                                        <div>
                                                            <dt
                                                                class="text-zinc-500"
                                                            >
                                                                Observaciones
                                                            </dt>
                                                            <dd
                                                                class="mt-[2px] text-black"
                                                            >
                                                                {{ $record->notes ?: "Sin observaciones." }}
                                                            </dd>
                                                        </div>
                                                        <div>
                                                            <dt
                                                                class="text-zinc-500"
                                                            >
                                                                Identificador
                                                            </dt>
                                                            <dd
                                                                class="mt-[2px] truncate font-mono text-[13px] text-black"
                                                            >
                                                                {{ $record->id }}
                                                            </dd>
                                                        </div>
                                                    </dl>
                                                </div>
                                                <div
                                                    class="backup-scrollbar overflow-auto rounded-xl border border-zinc-200 bg-white"
                                                >
                                                    <div
                                                        class="grid min-w-[650px] grid-cols-[minmax(260px,1fr)_110px_220px] border-b border-zinc-200 bg-zinc-100 px-[15px] py-[12px] font-semibold text-black"
                                                    >
                                                        <span>Archivo</span>
                                                        <span>Carpeta</span>
                                                        <span>Acciones</span>
                                                    </div>
                                                    @forelse ($manifest as $fileIndex => $file)
                                                        <div
                                                            class="grid min-h-[50px] min-w-[650px] grid-cols-[minmax(260px,1fr)_110px_220px] items-center border-b border-zinc-200 px-[15px] last:border-b-0"
                                                        >
                                                            <span
                                                                class="truncate"
                                                            >
                                                                {{ $file["customer"] ?? "Cliente" }}
                                                                /
                                                                {{ $file["name"] ?? "archivo" }}
                                                            </span>
                                                            <span>
                                                                {{ ucfirst($file["category"] ?? "") }}
                                                            </span>
                                                            <span
                                                                class="flex items-center gap-[15px]"
                                                            >
                                                                <a
                                                                    href="{{ route("activity-backups.files.download", [$record, $fileIndex]) }}"
                                                                    class="inline-flex items-center gap-[7px] font-semibold text-black"
                                                                    title="Descargar {{ $file["name"] ?? "archivo" }}"
                                                                >
                                                                    <svg
                                                                        class="h-4 w-4"
                                                                        fill="none"
                                                                        viewBox="0 0 24 24"
                                                                        stroke="currentColor"
                                                                    >
                                                                        <path
                                                                            stroke-linecap="round"
                                                                            stroke-linejoin="round"
                                                                            stroke-width="1.8"
                                                                            d="M12 3v12m0 0 4-4m-4 4-4-4M4 19h16"
                                                                        />
                                                                    </svg>
                                                                    Descargar
                                                                </a>
                                                                <button
                                                                    type="button"
                                                                    @click="openFileDelete({ uploadId: @js($record->id), fileIndex: @js($fileIndex), name: @js($file["name"] ?? "archivo") })"
                                                                    class="inline-flex items-center gap-[7px] text-red-600 focus:outline-none"
                                                                    title="Eliminar este archivo del paquete"
                                                                >
                                                                    <svg
                                                                        class="h-4 w-4"
                                                                        fill="none"
                                                                        viewBox="0 0 24 24"
                                                                        stroke="currentColor"
                                                                    >
                                                                        <path
                                                                            stroke-linecap="round"
                                                                            stroke-linejoin="round"
                                                                            stroke-width="1.8"
                                                                            d="M4 7h16M9 7V4h6v3m-8 0 1 13h8l1-13"
                                                                        />
                                                                    </svg>
                                                                    Eliminar
                                                                </button>
                                                            </span>
                                                        </div>
                                                    @empty
                                                        <div
                                                            class="p-[20px] text-zinc-500"
                                                        >
                                                            No hay archivos
                                                            extraídos para
                                                            mostrar.
                                                        </div>
                                                    @endforelse
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            @empty
                                <tbody>
                                    <tr>
                                        <td
                                            colspan="8"
                                            class="px-[20px] py-[60px] text-center text-zinc-500"
                                        >
                                            Todavía no hay respaldos
                                            registrados.
                                        </td>
                                    </tr>
                                </tbody>
                            @endforelse
                            <tfoot
                                class="sticky bottom-0 z-20 bg-zinc-100 font-semibold text-black shadow-[0_-1px_0_#d4d4d8]"
                            >
                                <tr>
                                    <td
                                        colspan="4"
                                        class="border-r border-zinc-200 px-[20px] py-[15px]"
                                    >
                                        TOTAL DEL HISTORIAL
                                    </td>
                                    <td
                                        class="border-r border-zinc-200 px-[20px] py-[15px]"
                                    >
                                        {{ $historyFiles }} archivos
                                    </td>
                                    <td
                                        class="border-r border-zinc-200 px-[20px] py-[15px] tabular-nums"
                                    >
                                        {{ number_format($historyBytes / 1048576, 2) }}
                                        MB
                                    </td>
                                    <td
                                        class="border-r border-zinc-200 px-[20px] py-[15px]"
                                    >
                                        {{ $history->count() }} registros
                                    </td>
                                    <td class="px-[20px] py-[15px]"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </section>
        </main>
    </div>

    <div
        x-show="editRecord.id"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/35 p-[20px]"
        @keydown.escape.window="resetEdit()"
    >
        <div
            class="max-h-[90vh] w-full max-w-[760px] overflow-y-auto rounded-xl border border-zinc-200 bg-white p-[20px] shadow-2xl"
            @click.outside="resetEdit()"
        >
            <div class="flex items-start justify-between gap-[20px]">
                <div class="min-w-0">
                    <h3 class="font-semibold text-black">Editar respaldo</h3>
                    <p
                        class="mt-[5px] truncate text-zinc-500"
                        x-text="editRecord?.fileName"
                    ></p>
                </div>
                <button
                    type="button"
                    @click="resetEdit()"
                    class="text-xl text-black focus:outline-none"
                >
                    ×
                </button>
            </div>
            <label class="mt-[20px] block">
                <span class="mb-[10px] block font-semibold text-black">
                    Cliente asignado
                </span>
                <input
                    x-model="editRecord.assignedCustomer"
                    class="h-[50px] w-full rounded-xl border border-zinc-200 px-[20px] text-[15px] outline-none focus:border-zinc-200 focus:ring-0"
                />
            </label>
            <label class="mt-[20px] block">
                <span class="mb-[10px] block font-semibold text-black">
                    Observaciones
                </span>
                <textarea
                    x-model="editRecord.notes"
                    rows="4"
                    class="w-full rounded-xl border border-zinc-200 px-[20px] py-[15px] text-[15px] outline-none focus:border-zinc-200 focus:ring-0"
                ></textarea>
            </label>
            <div class="mt-[20px] border-t border-zinc-200 pt-[20px]">
                <div class="flex items-center justify-between gap-[20px]">
                    <div>
                        <strong class="block text-black">
                            Archivos del paquete
                        </strong>
                        <span class="mt-[3px] block text-zinc-500">
                            Descarga o elimina elementos individuales.
                        </span>
                    </div>
                    <button
                        type="button"
                        @click="replaceFromEdit()"
                        class="rounded-xl border border-zinc-200 bg-white px-[20px] py-[15px] font-semibold text-black"
                        title="Seleccionar un ZIP que sustituirá el contenido actual"
                    >
                        Reemplazar con ZIP
                    </button>
                </div>
                <div
                    class="mt-[15px] overflow-hidden rounded-xl border border-zinc-200"
                >
                    <template
                        x-for="file in editRecord.files"
                        :key="`${editRecord.id}-${file.index}`"
                    >
                        <div
                            class="grid min-h-[52px] grid-cols-[minmax(0,1fr)_100px_170px] items-center border-b border-zinc-200 px-[15px] last:border-b-0"
                        >
                            <span
                                class="truncate text-black"
                                x-text="file.name"
                            ></span>
                            <span
                                class="text-zinc-500"
                                x-text="file.category"
                            ></span>
                            <span class="flex items-center gap-[15px]">
                                <a
                                    :href="file.downloadUrl"
                                    class="font-semibold text-black"
                                    title="Descargar archivo"
                                >
                                    Descargar
                                </a>
                                <button
                                    type="button"
                                    @click="openFileDelete({ uploadId: editRecord.id, fileIndex: file.index, name: file.name })"
                                    class="text-red-600 focus:outline-none"
                                    title="Eliminar archivo del paquete"
                                >
                                    Eliminar
                                </button>
                            </span>
                        </div>
                    </template>
                    <p
                        x-show="!editRecord.files.length"
                        class="p-[20px] text-zinc-500"
                    >
                        Este paquete ya no contiene archivos extraídos.
                    </p>
                </div>
            </div>
            <div class="mt-[20px] flex justify-end gap-[20px]">
                <button
                    type="button"
                    @click="resetEdit()"
                    class="rounded-xl border border-zinc-200 bg-white px-[20px] py-[15px] font-semibold text-black"
                >
                    Cancelar
                </button>
                <button
                    type="button"
                    @click="saveEdit()"
                    class="rounded-xl bg-zinc-900 px-[20px] py-[15px] font-semibold text-white"
                >
                    Guardar cambios
                </button>
            </div>
        </div>
    </div>
    <div
        x-show="deleteRecord"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/35 p-[20px]"
        @keydown.escape.window="deleteRecord = null"
    >
        <div
            class="w-full max-w-[500px] rounded-xl border border-zinc-200 bg-white p-[20px] shadow-2xl"
            @click.outside="deleteRecord = null"
        >
            <h3 class="font-semibold text-black">Eliminar respaldo</h3>
            <p class="mt-[10px] leading-6 text-zinc-600">
                Se eliminarán el ZIP, los archivos extraídos y el registro
                <strong
                    class="text-black"
                    x-text="deleteRecord?.fileName"
                ></strong>
                . Esta acción no se puede deshacer.
            </p>
            <div class="mt-[20px] flex justify-end gap-[20px]">
                <button
                    type="button"
                    @click="deleteRecord = null"
                    class="rounded-xl border border-zinc-200 bg-white px-[20px] py-[15px] font-semibold text-black"
                >
                    Cancelar
                </button>
                <button
                    type="button"
                    @click="confirmDelete()"
                    class="rounded-xl bg-red-600 px-[20px] py-[15px] font-semibold text-white"
                >
                    Eliminar definitivamente
                </button>
            </div>
        </div>
    </div>
    <div
        x-show="replaceRecord"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/35 p-[20px]"
        @keydown.escape.window="replaceRecord = null"
    >
        <div
            class="w-full max-w-[540px] rounded-xl border border-zinc-200 bg-white p-[20px] shadow-2xl"
            @click.outside="replaceRecord = null"
        >
            <h3 class="font-semibold text-black">Reemplazar respaldo</h3>
            <p class="mt-[10px] leading-6 text-zinc-600">
                Vas a seleccionar un nuevo ZIP para reemplazar
                <strong
                    class="text-black"
                    x-text="replaceRecord?.fileName"
                ></strong>
                . La versión actual seguirá disponible hasta que el nuevo
                paquete termine de subirse y procesarse correctamente.
            </p>
            <div class="mt-[20px] flex justify-end gap-[20px]">
                <button
                    type="button"
                    @click="replaceRecord = null"
                    class="rounded-xl border border-zinc-200 bg-white px-[20px] py-[15px] font-semibold text-black"
                >
                    Cancelar
                </button>
                <button
                    type="button"
                    @click="confirmReplace()"
                    class="rounded-xl bg-zinc-900 px-[20px] py-[15px] font-semibold text-white"
                >
                    Seleccionar nuevo ZIP
                </button>
            </div>
        </div>
    </div>
    <div
        x-show="fileDeleteRecord"
        x-cloak
        class="fixed inset-0 z-[60] flex items-center justify-center bg-black/35 p-[20px]"
        @keydown.escape.window="fileDeleteRecord = null"
    >
        <div
            class="w-full max-w-[500px] rounded-xl border border-zinc-200 bg-white p-[20px] shadow-2xl"
            @click.outside="fileDeleteRecord = null"
        >
            <h3 class="font-semibold text-black">Eliminar archivo</h3>
            <p class="mt-[10px] leading-6 text-zinc-600">
                Se eliminará
                <strong
                    class="text-black"
                    x-text="fileDeleteRecord?.name"
                ></strong>
                del paquete y se reconstruirá el ZIP descargable.
            </p>
            <div class="mt-[20px] flex justify-end gap-[20px]">
                <button
                    type="button"
                    @click="fileDeleteRecord = null"
                    class="rounded-xl border border-zinc-200 bg-white px-[20px] py-[15px] font-semibold text-black"
                >
                    Cancelar
                </button>
                <button
                    type="button"
                    @click="confirmFileDelete()"
                    class="rounded-xl bg-red-600 px-[20px] py-[15px] font-semibold text-white"
                >
                    Eliminar archivo
                </button>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const register = () => {
                if (window.__backupUploadManagerRegistered) return;
                window.__backupUploadManagerRegistered = true;
                const database = () =>
                    new Promise((resolve, reject) => {
                        const request = indexedDB.open('crm-backup-uploads', 2);
                        request.onupgradeneeded = () => {
                            if (
                                !request.result.objectStoreNames.contains(
                                    'pending',
                                )
                            )
                                request.result.createObjectStore('pending', {
                                    keyPath: 'id',
                                });
                        };
                        request.onsuccess = () => resolve(request.result);
                        request.onerror = () => reject(request.error);
                    });
                const pendingRequest = async (mode, action) => {
                    const db = await database();
                    return new Promise((resolve, reject) => {
                        const transaction = db.transaction('pending', mode);
                        const request = action(
                            transaction.objectStore('pending'),
                        );
                        request.onsuccess = () => resolve(request.result);
                        request.onerror = () => reject(request.error);
                    });
                };
                const uploadControllers = new Map();

                Alpine.data('backupUploadManager', (config) => ({
                    zoom: Number(
                        localStorage.getItem('backup-storage-zoom') || 100,
                    ),
                    uploads: [],
                    activeCount: 0,
                    dragging: false,
                    workspaceMode: 'upload',
                    openCustomers: [],
                    fallbackReplacement: null,
                    editRecord: {
                        id: null,
                        site: '',
                        fileName: '',
                        assignedCustomer: '',
                        notes: '',
                        files: [],
                    },
                    deleteRecord: null,
                    replaceRecord: null,
                    fileDeleteRecord: null,
                    saveZoom() {
                        localStorage.setItem(
                            'backup-storage-zoom',
                            String(this.zoom),
                        );
                    },
                    async toggleFullscreen(root) {
                        if (document.fullscreenElement)
                            await document.exitFullscreen();
                        else await root.requestFullscreen();
                    },
                    focusUploader() {
                        this.$refs.uploadWorkspace?.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center',
                        });
                    },
                    showUploader() {
                        this.workspaceMode = 'upload';
                        this.$nextTick(() => this.focusUploader());
                    },
                    showTree() {
                        this.workspaceMode = 'tree';
                    },
                    isCustomerOpen(customer) {
                        return this.openCustomers.includes(customer);
                    },
                    toggleCustomer(customer, event) {
                        if (!event.target.open) {
                            this.openCustomers = this.openCustomers.filter(
                                (item) => item !== customer,
                            );
                            return;
                        }

                        if (!this.openCustomers.includes(customer))
                            this.openCustomers.push(customer);
                        if (this.openCustomers.length <= 3) return;

                        const oldest = this.openCustomers.shift();
                        this.$nextTick(() => {
                            const detail = Array.from(
                                this.$root.querySelectorAll(
                                    'details[data-customer-key]',
                                ),
                            ).find(
                                (element) =>
                                    element.dataset.customerKey === oldest,
                            );
                            if (detail) detail.open = false;
                        });
                    },
                    openEdit(record) {
                        this.editRecord = { ...record };
                    },
                    resetEdit() {
                        this.editRecord = {
                            id: null,
                            site: '',
                            fileName: '',
                            assignedCustomer: '',
                            notes: '',
                            files: [],
                        };
                    },
                    openDelete(record) {
                        this.deleteRecord = { ...record };
                    },
                    openReplace(record) {
                        this.replaceRecord = { ...record };
                    },
                    openFileDelete(record) {
                        this.fileDeleteRecord = { ...record };
                    },
                    replaceFromEdit() {
                        const replacement = {
                            id: this.editRecord.id,
                            site: this.editRecord.site,
                            fileName: this.editRecord.fileName,
                        };
                        this.resetEdit();
                        this.openReplace(replacement);
                    },
                    async confirmReplace() {
                        if (!this.replaceRecord) return;
                        const replacement = { ...this.replaceRecord };
                        this.replaceRecord = null;
                        await this.chooseFiles(replacement);
                    },
                    async saveEdit() {
                        if (!this.editRecord) return;
                        await this.$wire.updateMetadata(
                            this.editRecord.id,
                            this.editRecord.assignedCustomer,
                            this.editRecord.notes,
                        );
                        this.resetEdit();
                    },
                    async confirmDelete() {
                        if (!this.deleteRecord) return;
                        await this.$wire.deleteBackup(this.deleteRecord.id);
                        this.deleteRecord = null;
                    },
                    async confirmFileDelete() {
                        if (!this.fileDeleteRecord) return;
                        const target = { ...this.fileDeleteRecord };
                        await this.$wire.deleteBackupFile(
                            target.uploadId,
                            target.fileIndex,
                        );
                        if (this.editRecord?.id === target.uploadId) {
                            this.editRecord.files = this.editRecord.files
                                .filter(
                                    (file) => file.index !== target.fileIndex,
                                )
                                .map((file, index) => ({
                                    ...file,
                                    index,
                                    downloadUrl: file.downloadUrl.replace(
                                        /\/\d+\/download$/,
                                        `/${index}/download`,
                                    ),
                                }));
                        }
                        this.fileDeleteRecord = null;
                    },
                    async init() {
                        window.addEventListener('online', () => this.pump());
                        try {
                            const saved = await pendingRequest(
                                'readonly',
                                (store) => store.getAll(),
                            );
                            for (const entry of saved.filter(
                                (entry) =>
                                    entry?.meta?.site ===
                                        this.$root.dataset.backupSite &&
                                    entry.id !== 'active',
                            )) {
                                let file = entry.file || null;
                                if (
                                    !file &&
                                    entry.handle &&
                                    (await entry.handle.queryPermission({
                                        mode: 'read',
                                    })) === 'granted'
                                )
                                    file = await entry.handle.getFile();
                                if (file)
                                    this.addQueueItem(
                                        file,
                                        entry.handle || null,
                                        entry.meta.replaceUploadId || null,
                                        entry.id,
                                        false,
                                        entry.meta.site,
                                    );
                            }
                            this.pump();
                        } catch (_) {}
                    },
                    async chooseFiles(replacement = null) {
                        if (this.uploads.length) return;
                        this.focusUploader();
                        if (!window.showOpenFilePicker) {
                            this.fallbackReplacement = replacement;
                            this.$refs.fallbackFiles.click();
                            return;
                        }
                        try {
                            const handles = await window.showOpenFilePicker({
                                multiple: !replacement,
                                types: [
                                    {
                                        description:
                                            'Archivos ZIP de respaldos',
                                        accept: { 'application/zip': ['.zip'] },
                                    },
                                ],
                            });
                            const entries = await Promise.all(
                                handles.map(async (handle) => ({
                                    file: await handle.getFile(),
                                    handle,
                                })),
                            );
                            await this.enqueue(entries, replacement);
                        } catch (error) {
                            if (error.name !== 'AbortError')
                                this.notifyError(error.message);
                        }
                    },
                    async chooseFolder() {
                        if (this.uploads.length) return;
                        this.focusUploader();
                        if (!window.showDirectoryPicker) {
                            this.$refs.fallbackFolder.click();
                            return;
                        }
                        try {
                            const directory =
                                await window.showDirectoryPicker();
                            await this.enqueue(
                                await this.directoryZips(directory),
                                null,
                            );
                        } catch (error) {
                            if (error.name !== 'AbortError')
                                this.notifyError(error.message);
                        }
                    },
                    async directoryZips(directory) {
                        const entries = [];
                        for await (const handle of directory.values()) {
                            if (
                                handle.kind === 'file' &&
                                handle.name.toLowerCase().endsWith('.zip')
                            )
                                entries.push({
                                    file: await handle.getFile(),
                                    handle,
                                });
                            if (handle.kind === 'directory')
                                entries.push(
                                    ...(await this.directoryZips(handle)),
                                );
                        }
                        return entries;
                    },
                    async receiveFallback(event) {
                        const files = Array.from(
                            event.target.files || [],
                        ).filter((file) =>
                            file.name.toLowerCase().endsWith('.zip'),
                        );
                        await this.enqueue(
                            files.map((file) => ({ file, handle: null })),
                            this.fallbackReplacement,
                        );
                        this.fallbackReplacement = null;
                        event.target.value = '';
                    },
                    async handleDrop(event) {
                        this.dragging = false;
                        if (this.uploads.length) return;
                        const files = Array.from(
                            event.dataTransfer?.files || [],
                        );
                        await this.enqueue(
                            files.map((file) => ({ file, handle: null })),
                            null,
                        );
                    },
                    async enqueue(entries, replacement = null) {
                        const zips = entries.filter((entry) =>
                            entry.file?.name.toLowerCase().endsWith('.zip'),
                        );
                        if (!zips.length) {
                            this.notifyError(
                                'No se encontraron archivos .zip.',
                            );
                            return;
                        }
                        for (const entry of zips)
                            this.addQueueItem(
                                entry.file,
                                entry.handle,
                                replacement?.id || null,
                                null,
                                true,
                                replacement?.site ||
                                    this.$root.dataset.backupSite,
                            );
                        this.pump();
                    },
                    addQueueItem(
                        file,
                        handle,
                        replaceUploadId = null,
                        savedId = null,
                        persist = true,
                        site = null,
                    ) {
                        site = site || this.$root.dataset.backupSite;
                        const fingerprint = `${file.name}:${file.size}:${file.lastModified}`;
                        const localId =
                            savedId ||
                            `${site}:${fingerprint}:${replaceUploadId || 'new'}`;
                        if (
                            this.uploads.some(
                                (item) =>
                                    item.localId === localId &&
                                    item.status !== 'error',
                            )
                        )
                            return;
                        const item = {
                            localId,
                            file,
                            handle,
                            fileName: file.name,
                            site,
                            replaceUploadId,
                            fingerprint,
                            progress: 0,
                            speed: '0 MB/s',
                            eta: 'Tiempo restante: —',
                            message: '',
                            status: 'pending',
                            running: false,
                            cancelled: false,
                            cancelUrl: null,
                        };
                        this.uploads.unshift(item);
                        if (persist) this.persist(item);
                    },
                    async persist(item) {
                        await pendingRequest('readwrite', (store) =>
                            store.put({
                                id: item.localId,
                                file: item.handle ? null : item.file,
                                handle: item.handle,
                                meta: {
                                    site: item.site,
                                    fileName: item.fileName,
                                    replaceUploadId: item.replaceUploadId,
                                },
                            }),
                        ).catch(() => {});
                    },
                    pump() {
                        if (!navigator.onLine) return;
                        while (
                            this.activeCount <
                            Math.max(1, Number(config.maxConcurrent || 2))
                        ) {
                            const item = this.uploads.find(
                                (candidate) =>
                                    candidate.status === 'pending' &&
                                    !candidate.running &&
                                    !candidate.cancelled,
                            );
                            if (!item) break;
                            item.running = true;
                            item.status = 'uploading';
                            this.activeCount++;
                            this.uploadItem(item).finally(() => {
                                uploadControllers.delete(item.localId);
                                item.running = false;
                                this.activeCount--;
                                this.pump();
                            });
                        }
                    },
                    async uploadItem(item) {
                        try {
                            const controller = new AbortController();
                            uploadControllers.set(item.localId, controller);
                            let upload = await this.request(config.endpoint, {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                signal: controller.signal,
                                body: JSON.stringify({
                                    site: item.site,
                                    file_name: item.file.name,
                                    mime_type:
                                        item.file.type || 'application/zip',
                                    size: item.file.size,
                                    fingerprint: item.fingerprint,
                                    replace_upload_id: item.replaceUploadId,
                                }),
                            });
                            item.cancelUrl = upload.cancelUrl;
                            while (upload.status === 'waiting') {
                                item.status = 'waiting';
                                item.message = 'Esperando turno';
                                await this.pause(2500);
                                upload = await this.request(upload.statusUrl, {
                                    signal: controller.signal,
                                });
                            }
                            if (upload.status === 'completed') {
                                await this.completeItem(item);
                                return;
                            }
                            item.status = 'uploading';
                            const uploaded = new Set(
                                (upload.uploadedChunks || []).map(Number),
                            );
                            const started = performance.now();
                            const initialBytes = upload.receivedBytes || 0;
                            for (
                                let index = 0;
                                index < upload.totalChunks;
                                index++
                            ) {
                                if (uploaded.has(index)) continue;
                                const start = index * upload.chunkSize;
                                const blob = item.file.slice(
                                    start,
                                    Math.min(
                                        item.file.size,
                                        start + upload.chunkSize,
                                    ),
                                );
                                let sent = false;
                                while (!sent) {
                                    try {
                                        if (!navigator.onLine) {
                                            item.status = 'waiting';
                                            item.message = 'Sin conexión';
                                            await new Promise((resolve) =>
                                                window.addEventListener(
                                                    'online',
                                                    resolve,
                                                    { once: true },
                                                ),
                                            );
                                            item.status = 'uploading';
                                        }
                                        upload = await this.request(
                                            upload.chunkUrlTemplate.replace(
                                                '__INDEX__',
                                                index,
                                            ),
                                            {
                                                method: 'POST',
                                                headers: {
                                                    'Content-Type':
                                                        'application/octet-stream',
                                                },
                                                body: blob,
                                                signal: controller.signal,
                                            },
                                        );
                                        sent = true;
                                    } catch (error) {
                                        if (
                                            error.status &&
                                            error.status < 500 &&
                                            error.status !== 409
                                        )
                                            throw error;
                                        item.status = 'waiting';
                                        item.message = 'Reconectando';
                                        await this.pause(2500);
                                        upload = await this.request(
                                            upload.statusUrl,
                                            {
                                                signal: controller.signal,
                                            },
                                        );
                                        if (
                                            (upload.uploadedChunks || [])
                                                .map(Number)
                                                .includes(index)
                                        )
                                            sent = true;
                                    }
                                }
                                const elapsed = Math.max(
                                    1,
                                    (performance.now() - started) / 1000,
                                );
                                const transferred = Math.max(
                                    0,
                                    upload.receivedBytes - initialBytes,
                                );
                                const bytesPerSecond = transferred / elapsed;
                                item.progress = Math.min(
                                    99,
                                    Math.round(
                                        (upload.receivedBytes * 100) /
                                            item.file.size,
                                    ),
                                );
                                item.speed =
                                    this.formatBytes(bytesPerSecond) + '/s';
                                item.eta =
                                    bytesPerSecond > 0
                                        ? 'Restante: ' +
                                          this.formatTime(
                                              (item.file.size -
                                                  upload.receivedBytes) /
                                                  bytesPerSecond,
                                          )
                                        : 'Tiempo restante: —';
                                item.message = `Fragmento ${index + 1} de ${upload.totalChunks}`;
                            }
                            upload = await this.request(upload.completeUrl, {
                                method: 'POST',
                                signal: controller.signal,
                            });
                            item.progress = 100;
                            while (
                                ['queued', 'processing'].includes(upload.status)
                            ) {
                                item.status = upload.status;
                                item.message =
                                    upload.status === 'queued'
                                        ? 'En cola del servidor'
                                        : 'Organizando contenido';
                                await this.pause(2500);
                                upload = await this.request(upload.statusUrl, {
                                    signal: controller.signal,
                                });
                            }
                            if (upload.status === 'failed')
                                throw new Error(
                                    upload.error ||
                                        'El servidor no pudo procesar el respaldo.',
                                );
                            await this.completeItem(item);
                        } catch (error) {
                            if (item.cancelled) return;
                            item.status = 'error';
                            item.message = this.describeError(error);
                            item.eta = 'Revisión necesaria';
                        }
                    },
                    async completeItem(item) {
                        item.progress = 100;
                        item.status = 'completed';
                        item.message = 'Disponible';
                        item.speed = 'Completado';
                        item.eta = '';
                        await pendingRequest('readwrite', (store) =>
                            store.delete(item.localId),
                        ).catch(() => {});
                        window.dispatchEvent(
                            new CustomEvent('backup-upload-finished'),
                        );
                        this.uploads = this.uploads.filter(
                            (candidate) => candidate.localId !== item.localId,
                        );
                        if (!this.uploads.length) this.showTree();
                    },
                    canCancel(item) {
                        return [
                            'pending',
                            'waiting',
                            'uploading',
                            'error',
                        ].includes(item.status);
                    },
                    async cancelUpload(item) {
                        if (!this.canCancel(item)) return;
                        item.cancelled = true;
                        uploadControllers.get(item.localId)?.abort();
                        uploadControllers.delete(item.localId);
                        item.status = 'cancelled';
                        item.message = 'Cancelando carga';

                        try {
                            if (item.cancelUrl)
                                await this.request(item.cancelUrl, {
                                    method: 'DELETE',
                                });
                            await pendingRequest('readwrite', (store) =>
                                store.delete(item.localId),
                            ).catch(() => {});
                            this.uploads = this.uploads.filter(
                                (candidate) =>
                                    candidate.localId !== item.localId,
                            );
                        } catch (error) {
                            item.cancelled = false;
                            item.status = 'error';
                            item.message = this.describeError(error);
                        }
                    },
                    retry(item) {
                        item.cancelled = false;
                        item.status = 'pending';
                        item.progress = 0;
                        item.message = '';
                        item.eta = 'Tiempo restante: —';
                        this.persist(item);
                        this.pump();
                    },
                    notifyError(message) {
                        this.uploads.unshift({
                            localId: crypto.randomUUID(),
                            fileName: 'Selección no válida',
                            progress: 0,
                            speed: '',
                            eta: '',
                            message,
                            status: 'error',
                            running: false,
                        });
                    },
                    statusLabel(status) {
                        return (
                            {
                                pending: 'Pendiente',
                                waiting: 'En espera',
                                uploading: 'Subiendo',
                                queued: 'En cola',
                                processing: 'Procesando',
                                completed: 'Completado',
                                cancelled: 'Cancelado',
                                error: 'Error',
                            }[status] || status
                        );
                    },
                    describeError(error) {
                        const message =
                            error?.message ||
                            'No fue posible completar la carga.';
                        if (error?.status === 419)
                            return 'Sesión vencida (419). Recarga la página e inténtalo nuevamente.';
                        if (error?.status === 413)
                            return 'El ZIP supera el tamaño permitido por el servidor (413).';
                        if (error?.status)
                            return `Error del servidor (${error.status}): ${message}`;
                        if (
                            error instanceof TypeError ||
                            /failed to fetch|networkerror/i.test(message)
                        )
                            return 'No se pudo conectar con el servidor. Revisa la red e inténtalo nuevamente.';
                        return message;
                    },
                    async request(url, options = {}) {
                        const response = await fetch(url, {
                            credentials: 'same-origin',
                            headers: {
                                Accept: 'application/json',
                                'X-CSRF-TOKEN': config.csrf,
                                ...(options.headers || {}),
                            },
                            ...options,
                        });
                        const payload = await response.json().catch(() => ({}));
                        if (!response.ok) {
                            const validationMessage = payload?.errors
                                ? Object.values(payload.errors).flat()[0]
                                : null;
                            const error = new Error(
                                validationMessage ||
                                    payload.message ||
                                    payload.error ||
                                    `La solicitud devolvió HTTP ${response.status}.`,
                            );
                            error.status = response.status;
                            throw error;
                        }
                        return payload;
                    },
                    pause(milliseconds) {
                        return new Promise((resolve) =>
                            setTimeout(resolve, milliseconds),
                        );
                    },
                    formatBytes(value) {
                        if (!Number.isFinite(value) || value <= 0)
                            return '0 MB';
                        const units = ['B', 'KB', 'MB', 'GB'];
                        const unit = Math.min(
                            units.length - 1,
                            Math.floor(Math.log(value) / Math.log(1024)),
                        );
                        return `${(value / 1024 ** unit).toFixed(unit > 1 ? 1 : 0)} ${units[unit]}`;
                    },
                    formatTime(seconds) {
                        if (!Number.isFinite(seconds)) return '—';
                        const minutes = Math.floor(seconds / 60);
                        const rest = Math.max(0, Math.round(seconds % 60));
                        return minutes > 0
                            ? `${minutes} min ${rest} s`
                            : `${rest} s`;
                    },
                }));
            };
            if (window.Alpine) register();
            else
                document.addEventListener('alpine:init', register, {
                    once: true,
                });
        })();
    </script>
</div>
