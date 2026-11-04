@php
    $role = auth()->user()->role->role;
    $canManageCustomers = app(\App\Services\Authorization\PermissionAccessService::class)
        ->allows(auth()->user(), 'customers.manage');
@endphp

{{-- Panel general: mismo diseño que Administración y Reportes de soporte (tarjetas con cabecera gris, líneas
     de 1px, texto de 15px, escala de vista ajustable y tema oscuro). Solo cambia la presentación; la lógica
     vive en App\Livewire\Dashboard. --}}
<div
    data-panel
    data-dashboard
    x-data="{
        viewScale: 90,
        isFullscreen: false,
        isCompact: window.innerWidth < 1024,
        scaledHeight: null,
        observer: null,
        onFullscreen: null,
        init() {
            try {
                const saved = Number(localStorage.getItem('dashboard-view-scale'));
                if (saved >= 70 && saved <= 100) this.viewScale = saved;
            } catch (error) {}
            this.onFullscreen = () => { this.isFullscreen = document.fullscreenElement === this.$root; };
            document.addEventListener('fullscreenchange', this.onFullscreen);
            this.$nextTick(() => {
                this.observer = new ResizeObserver(() => this.syncHeight());
                this.observer.observe(this.$refs.scaled);
                this.syncHeight();
            });
        },
        destroy() {
            this.observer?.disconnect();
            document.removeEventListener('fullscreenchange', this.onFullscreen);
        },
        syncHeight() {
            if (this.$refs.scaled) this.scaledHeight = Math.ceil(this.$refs.scaled.offsetHeight * (this.viewScale / 100));
        },
        setScale(value) {
            this.viewScale = Math.min(100, Math.max(70, Number(value) || 90));
            try { localStorage.setItem('dashboard-view-scale', String(this.viewScale)); } catch (error) {}
            this.$nextTick(() => this.syncHeight());
        },
        async toggleFullscreen() {
            if (document.fullscreenElement === this.$root) return document.exitFullscreen();
            if (document.fullscreenElement) await document.exitFullscreen();
            await this.$root.requestFullscreen();
        },
        openCustomer(url) {
            /* Anota esta pantalla para que la flecha Regresar del cliente vuelva aquí aunque el navegador no informe la página anterior. */
            try { sessionStorage.setItem('customer-detail-back', JSON.stringify({ url: window.location.href, at: Date.now() })); } catch (error) {}
            window.location.href = url;
        },
    }"
    @resize.window.debounce.150ms="isCompact = window.innerWidth < 1024; syncHeight()"
    class="relative isolate min-h-[calc(100dvh-100px)] w-full min-w-0 overflow-hidden text-[15px]"
>
    <x-panel-styles />

    <div class="w-full" :style="(!isCompact && scaledHeight) ? 'height:' + scaledHeight + 'px' : null">
        <div x-ref="scaled" class="origin-top-left" :style="isCompact ? 'width:100%;transform:none' : 'width:' + (10000 / viewScale) + '%;transform:scale(' + (viewScale / 100) + ')'">
            <div class="flex w-full flex-col gap-[20px] p-[20px] sm:p-[30px] xl:p-[50px]">
                @if (! $dashboardReady)
                    <div class="animate-pulse space-y-[20px]" role="status" aria-label="Cargando panel">
                        <div class="flex items-center gap-[15px]">
                            <div class="panel-header h-14 w-14 rounded-xl"></div>
                            <div class="space-y-[10px]">
                                <div class="panel-header h-5 w-40 rounded-lg"></div>
                                <div class="panel-header h-4 w-64 max-w-full rounded-lg"></div>
                            </div>
                        </div>
                        <div class="panel-border panel-surface h-[170px] rounded-xl border"></div>
                        <div class="panel-border panel-surface h-[420px] rounded-xl border"></div>
                        <span class="sr-only">Cargando información del dashboard...</span>
                    </div>
                @else
                    @php
                        $periodMonth = collect($months)->firstWhere('number', (int) $selectedMonth);
                        $periodLabel = trim(($periodMonth['name'] ?? '').' '.$selectedYear);
                    @endphp

                    <!-- Cabecera y filtros -->
                    <section class="flex shrink-0 flex-wrap items-center justify-between gap-x-[20px] gap-y-[15px]">
                        <div class="flex min-w-0 max-w-full items-center gap-[15px]">
                            <span class="panel-surface panel-border panel-text grid h-14 w-14 shrink-0 place-items-center rounded-xl border">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z" /></svg>
                            </span>
                            <div class="min-w-0">
                                <h1 class="panel-accent text-xl font-normal leading-none tracking-tight">Panel general</h1>
                                <p class="mt-[10px] leading-tight">Consulta el avance de tus clientes y los archivos cargados.</p>
                            </div>
                        </div>

                        <div class="flex w-full flex-wrap items-center gap-[10px] lg:w-auto">
                            <x-panel-select id="dashboard-month" label="Mes" property="selectedMonth"
                                :options="collect($months)->map(fn ($item) => ['value' => $item['number'], 'label' => $item['name']])->all()"
                                class="min-w-[140px] flex-1 sm:w-44 sm:flex-none" />

                            <x-panel-select id="dashboard-year" label="Año" property="selectedYear"
                                :options="collect($years)->map(fn ($year) => ['value' => $year, 'label' => $year])->all()"
                                class="min-w-[110px] flex-1 sm:w-28 sm:flex-none" />

                            <div class="relative w-full sm:w-72">
                                <label for="dashboard-search" class="sr-only">Buscar cliente</label>
                                <svg class="panel-muted pointer-events-none absolute left-[15px] top-1/2 h-[18px] w-[18px] -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m21 21-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" /></svg>
                                <input id="dashboard-search" wire:model.live.debounce.500ms="search" type="text" autocomplete="off" placeholder="Buscar cliente o RFC..."
                                    class="panel-field h-11 w-full rounded-xl border pl-[44px] pr-[15px]">
                            </div>
                        </div>
                    </section>

                    <!-- Resumen de archivos -->
                    @if ($canManageCustomers)
                        @php
                            $stats = [
                                ['label' => 'Archivos mensuales subidos', 'value' => $totalFilesMonth, 'icon' => 'M8 7V3m8 4V3M5 11h14M6 5h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V7a2 2 0 012-2z'],
                                ['label' => 'Archivos anuales subidos', 'value' => $totalFilesYear, 'icon' => 'M4 20h16M7 16v-5m5 5V6m5 10v-8'],
                                ['label' => 'Archivos totales', 'value' => $totalFiles, 'icon' => 'M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z'],
                            ];
                        @endphp

                        <section class="panel-surface panel-border overflow-hidden rounded-xl border">
                            <header class="panel-header panel-border flex flex-col justify-between gap-[10px] border-b p-[20px] md:flex-row md:items-center md:gap-[20px]">
                                <div class="min-w-0">
                                    <h2 class="panel-accent leading-none">Resumen de archivos</h2>
                                    <p class="mt-[10px] leading-tight">Documentos cargados por tus clientes</p>
                                </div>
                                @if ($periodLabel !== '')
                                    <div class="flex shrink-0 items-center gap-[10px] leading-none"><span class="h-2 w-2 shrink-0 rounded-full bg-emerald-500"></span>Periodo: {{ $periodLabel }}</div>
                                @endif
                            </header>

                            <div class="panel-line grid grid-cols-1 gap-px lg:grid-cols-3">
                                @foreach ($stats as $stat)
                                    <div class="panel-surface flex min-w-0 items-center gap-[15px] p-[20px]">
                                        <span class="panel-surface panel-border panel-text grid h-10 w-10 shrink-0 place-items-center rounded-xl border">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $stat['icon'] }}" /></svg>
                                        </span>
                                        <div class="min-w-0">
                                            <span class="panel-accent block leading-none tabular-nums">{{ number_format((int) $stat['value']) }}</span>
                                            <span class="mt-[10px] block leading-snug">{{ $stat['label'] }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    <!-- Informe de clientes -->
                    @php
                        $filteredCustomers = $this->filteredCustomers;
                    @endphp

                    <section class="panel-surface panel-border overflow-hidden rounded-xl border">
                        <header class="panel-header panel-border flex flex-col justify-between gap-[10px] border-b p-[20px] md:flex-row md:items-center md:gap-[20px]">
                            <div class="min-w-0">
                                <h2 class="panel-accent leading-none">Informe de clientes</h2>
                                <p class="mt-[10px] leading-tight">Avance de archivos por cliente y servicio</p>
                            </div>
                            @if ($this->customers->isNotEmpty())
                                <span class="shrink-0 leading-none tabular-nums">{{ $filteredCustomers->count() }} {{ $filteredCustomers->count() === 1 ? 'cliente' : 'clientes' }}</span>
                            @endif
                        </header>

                        <div class="flex flex-wrap items-center justify-center gap-x-[15px] sm:justify-between sm:gap-x-[20px] sm:px-[20px]">
                            <div class="flex flex-wrap items-center justify-center gap-x-[15px] sm:justify-start sm:gap-x-[20px]" role="tablist" aria-label="Estatus de los clientes">
                                <button type="button" role="tab" wire:click.prevent="customersPorcentageIncomplete"
                                    aria-selected="{{ $filterType === 'incomplete' ? 'true' : 'false' }}"
                                    class="panel-tab inline-flex items-center gap-[10px] py-[20px] leading-none focus:outline-none focus:ring-0">
                                    <svg class="h-[18px] w-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6v6l4 2m5-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    <span>Incompletos</span>
                                </button>

                                <button type="button" role="tab" wire:click.prevent="customersPorcentageComplete"
                                    aria-selected="{{ $filterType === 'complete' ? 'true' : 'false' }}"
                                    class="panel-tab inline-flex items-center gap-[10px] py-[20px] leading-none focus:outline-none focus:ring-0">
                                    <svg class="h-[18px] w-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    <span>Completos</span>
                                </button>
                            </div>

                            {{-- Guía en PDF: cómo usar el Panel general y cómo está configurado el programador de tareas --}}
                            {{-- Si las rutas están en caché y aún no incluyen la guía, el botón se oculta en lugar de romper el panel. --}}
                            @if (\Illuminate\Support\Facades\Route::has('dashboard.guide'))
                                <a href="{{ route('dashboard.guide') }}" title="Descargar la guía del Panel general en PDF"
                                    class="panel-tab inline-flex items-center gap-[10px] py-[20px] leading-none focus:outline-none focus:ring-0">
                                    <svg class="h-[18px] w-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 3h7l4 4v14H7V3Zm7 0v5h4M10 12h5m-5 4h5" /></svg>
                                    <span>Guía</span>
                                </a>
                            @endif
                        </div>

                        <div class="transition-opacity" wire:loading.class="opacity-60" wire:target="search,selectedMonth,selectedYear,customersPorcentageIncomplete,customersPorcentageComplete,showMoreCustomers">
                            @if ($this->customers->isEmpty())
                                @if ($canManageCustomers)
                                    <x-panel-empty-state subtitle="No se encontraron clientes para gestionar">
                                        <x-slot name="icon">
                                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM5 21a7 7 0 0114 0" /></svg>
                                        </x-slot>
                                        No hay clientes
                                        <x-slot name="action">
                                            <button type="button" wire:click="redirectToCreateCustomer"
                                                class="panel-button inline-flex h-11 items-center justify-center whitespace-nowrap rounded-xl px-[20px] focus:outline-none focus:ring-0">Agregar</button>
                                        </x-slot>
                                    </x-panel-empty-state>
                                @else
                                    <x-panel-empty-state>
                                        <x-slot name="icon">
                                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM5 21a7 7 0 0114 0" /></svg>
                                        </x-slot>
                                        Sin clientes asignados
                                    </x-panel-empty-state>
                                @endif
                            @elseif ($filteredCustomers->isEmpty())
                                <x-panel-empty-state>
                                    <x-slot name="icon">
                                        @if ($filterType === 'complete')
                                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        @else
                                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6v6l4 2m5-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        @endif
                                    </x-slot>
                                    Sin clientes con estatus {{ $filterType === 'complete' ? 'Completo' : 'Incompleto' }}
                                </x-panel-empty-state>
                            @else
                                <div class="grid grid-cols-[repeat(auto-fill,minmax(min(100%,280px),1fr))] gap-[20px] xl:grid-cols-[repeat(auto-fill,minmax(min(100%,320px),1fr))] px-[15px] pb-[20px] pt-0 sm:px-[20px]">
                                    @foreach ($filteredCustomers->take($visibleCustomerLimit) as $customer)
                                        @php
                                            $uniqueServiceIds = $customer->services->pluck('service_id')->unique();
                                            $displayName = trim(Str::limit($customer['name'], 50).' '.($customer['last_name'] ? Str::limit($customer['last_name'], 50).' '.Str::limit($customer['maternal_last_name'], 50) : ''));
                                        @endphp
                                        <div wire:key="dashboard-customer-{{ $customer->id }}" role="link" tabindex="0" @click="openCustomer(@js(route('customers.view', $customer->id)))" @keydown.enter="openCustomer(@js(route('customers.view', $customer->id)))"
                                            class="panel-card panel-surface panel-border relative flex min-w-0 cursor-pointer flex-col gap-[15px] rounded-xl border p-[15px] focus:outline-none sm:p-[20px]">
                                            @if ($customer->services->isNotEmpty())
                                                <div data-card-actions @click.stop @keydown.enter.stop class="absolute right-[10px] top-[10px] z-10 sm:right-[15px] sm:top-[15px]">
                                                    <x-dropdown align="right" width="48" content-classes="panel-surface panel-border border p-[5px] !rounded-xl" dropdown-classes="!top-full !mt-[8px] !rounded-xl">
                                                        <x-slot name="trigger">
                                                            <button type="button" wire:click.prevent aria-label="Opciones del cliente"
                                                                class="panel-icon-button flex h-9 w-9 cursor-pointer items-center justify-center rounded-xl focus:outline-none focus:ring-0">
                                                                @svg('feathericon-more-vertical', 'h-5 w-5')
                                                            </button>
                                                        </x-slot>

                                                        <x-slot name="content">
                                                            <button type="button" wire:click.prevent='annualReport({{ $customer->id }})'
                                                                class="panel-menu-item block w-full rounded-lg px-[15px] py-[10px] text-left focus:outline-none focus:ring-0">Resumen anual</button>
                                                        </x-slot>
                                                    </x-dropdown>
                                                </div>
                                            @endif

                                            <div class="flex items-center gap-[15px] sm:h-[64px] {{ $customer->services->isNotEmpty() ? 'pr-[40px]' : '' }}">
                                                @if ($customer['url_photo'])
                                                    <img class="panel-border hidden h-10 w-10 shrink-0 rounded-full border object-cover sm:block"
                                                        src="{{ asset('storage/photos/' . $customer['url_photo']) }}"
                                                        alt="{{ $customer['name'] }}" loading="lazy">
                                                @else
                                                    <span class="panel-header panel-border panel-accent hidden h-10 w-10 shrink-0 place-items-center rounded-full border sm:grid">
                                                        {{ mb_strtoupper(mb_substr($customer['name'], 0, 2)) }}
                                                    </span>
                                                @endif

                                                <div class="min-w-0">
                                                    <span class="line-clamp-2 break-words text-justify leading-snug" title="{{ trim($customer['name'].' '.$customer['last_name'].' '.$customer['maternal_last_name']) }}">
                                                        <x-hyphenated-text :text="$displayName" />
                                                    </span>
                                                    <span class="panel-muted mt-[5px] block truncate leading-none">{{ $customer['rfc'] }}</span>
                                                </div>
                                            </div>

                                            @if ($customer->services->isNotEmpty())
                                                <div class="panel-border flex flex-wrap justify-center gap-x-[10px] gap-y-[20px] border-t pt-[15px] sm:gap-x-[20px]">
                                                    @foreach ($uniqueServiceIds as $serviceId)
                                                        @php
                                                            $inService = $this->services[$serviceId] ?? null;
                                                            $percentageKey = "{$customer->id}-{$serviceId}";
                                                        @endphp
                                                        <div class="flex w-[96px] flex-col items-center justify-between gap-[10px] text-center sm:w-[110px]">
                                                            <div class="flex w-full flex-1 items-end justify-center sm:h-[38px] sm:flex-none">
                                                                <span class="panel-muted line-clamp-2 leading-tight" title="{{ $inService?->service ?? 'Sin servicio' }}">{{ $inService?->service ?? 'Sin servicio' }}</span>
                                                            </div>
                                                            <x-radial-progress :month="$this->selectedMonth" :customerId="$customer->id"
                                                                :percentageKey="$percentageKey" :serviceId="$serviceId" />
                                                        </div>
                                                    @endforeach

                                                    {{-- Siempre dos aros por cliente: si solo tiene un servicio, el segundo queda con guiones --}}
                                                    @for ($emptySlot = $uniqueServiceIds->count(); $emptySlot < 2; $emptySlot++)
                                                        <div class="flex w-[96px] flex-col items-center justify-between gap-[10px] text-center sm:w-[110px]" aria-hidden="true">
                                                            <div class="flex w-full flex-1 items-end justify-center sm:h-[38px] sm:flex-none">
                                                                <span class="panel-muted leading-tight">--</span>
                                                            </div>
                                                            <div class="radial-progress relative flex items-center justify-center">
                                                                <svg width="56" height="56" viewBox="0 0 100 100" class="-rotate-90 transform">
                                                                    <circle cx="50" cy="50" r="46" stroke="#e5e7eb" stroke-width="8" fill="none" />
                                                                </svg>
                                                                <span class="percentage-text absolute" style="color: var(--panel-muted)">--</span>
                                                            </div>
                                                        </div>
                                                    @endfor
                                                </div>
                                            @else
                                                <div class="panel-danger-box rounded-xl border px-[15px] py-[20px] text-center">
                                                    <p>Sin servicios contratados</p>
                                                    <p class="mt-[6px]">Este cliente aún no tiene servicios activos.</p>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        @if ($filteredCustomers->isNotEmpty() && $filteredCustomers->count() > $visibleCustomerLimit)
                            <div class="flex flex-col items-center gap-[20px] px-[20px] pb-[20px]">
                                <span class="panel-muted leading-none">Mostrando {{ $visibleCustomerLimit }} de {{ $filteredCustomers->count() }}</span>
                                <button wire:click="showMoreCustomers" type="button"
                                    class="panel-button-secondary inline-flex h-11 items-center justify-center whitespace-nowrap rounded-xl px-[20px] focus:outline-none focus:ring-0">
                                    Mostrar más
                                </button>
                            </div>
                        @endif
                    </section>
                @endif
            </div>
        </div>
    </div>

    <!-- Control de zoom y pantalla completa (igual que en Administración y Reportes de soporte) -->
    <div class="panel-surface panel-border fixed bottom-[30px] right-[30px] z-30 hidden items-center gap-[10px] rounded-xl border px-[15px] py-[10px] shadow-[0_8px_24px_rgba(0,0,0,0.10)] lg:flex">
        <button type="button" @click="setScale(viewScale - 5)" aria-label="Alejar vista" class="inline-flex h-7 w-7 items-center justify-center focus:outline-none focus:ring-0">−</button>
        <input type="range" min="70" max="100" step="5" :value="viewScale" @input="setScale($event.target.value)" class="h-1.5 w-[130px] cursor-pointer" style="accent-color: var(--panel-accent)" aria-label="Ajustar tamaño de la vista">
        <button type="button" @click="setScale(viewScale + 5)" aria-label="Acercar vista" class="inline-flex h-7 w-7 items-center justify-center focus:outline-none focus:ring-0">+</button>
        <span class="w-[42px] text-right tabular-nums" x-text="viewScale + '%'">90%</span>
        <span class="panel-line h-5 w-px"></span>
        <button type="button" @click="toggleFullscreen()" class="inline-flex p-[5px] outline-none focus:ring-0" :title="isFullscreen ? 'Salir de pantalla completa' : 'Pantalla completa'" :aria-label="isFullscreen ? 'Salir de pantalla completa' : 'Pantalla completa'">
            <svg x-show="!isFullscreen" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 3H3v5M16 3h5v5M21 16v5h-5M3 16v5h5" /></svg>
            <svg x-show="isFullscreen" x-cloak class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 3v5H3M16 3v5h5M21 16h-5v5M3 16h5v5" /></svg>
        </button>
    </div>
</div>
