@php
    $role = auth()->user()->role->role;

    $periodMonth = collect($months ?? [])->firstWhere('number', (int) $selectedMonth);
    $periodLabel = trim(($periodMonth['name'] ?? '').' '.$selectedYear);
@endphp

{{-- Detalle del cliente: mismo diseño que el Panel general, Administración y Reportes de soporte (secciones con
     cabecera gris, líneas de 1px, texto de 15px, escala de vista ajustable y tema oscuro). Solo cambia la
     presentación; la lógica vive en App\Livewire\Customer\DetalleCliente. --}}
<div
    data-panel
    data-customer-detail
    x-data="{
        viewScale: 90,
        isCompact: window.innerWidth / window.appAutoScale() < 1024,
        back: null,
        scaledHeight: null,
        observer: null,
        init() {
            this.back = this.resolveBack();
            try {
                const saved = Number(null);
                if (saved >= 70 && saved <= 100) this.viewScale = saved;
            } catch (error) {}
            this.$nextTick(() => {
                this.observer = new ResizeObserver(() => this.syncHeight());
                this.observer.observe(this.$refs.scaled);
                this.syncHeight();
            });
        },
        resolveBack() {
            /* Pantalla a la que vuelve la flecha Regresar: la anotada por el Panel general al abrir el cliente o, si no hay, la página anterior que informa el navegador. */
            const sameApp = (href) => {
                try {
                    const url = new URL(href);
                    return url.origin === window.location.origin && url.pathname !== window.location.pathname && !/login|logout/.test(url.pathname) ? url.href : null;
                } catch (error) { return null; }
            };
            try {
                const key = 'customer-detail-return';
                const reloaded = performance.getEntriesByType('navigation')[0]?.type === 'reload';
                const stored = JSON.parse(sessionStorage.getItem(key) || 'null');
                let target = reloaded && stored && stored.path === window.location.pathname ? sameApp(stored.url) : null;
                if (! target) {
                    const marker = JSON.parse(sessionStorage.getItem('customer-detail-back') || 'null');
                    if (marker && Date.now() - marker.at < 30000) target = sameApp(marker.url);
                    if (! target) target = sameApp(document.referrer);
                }
                sessionStorage.removeItem('customer-detail-back');
                if (target) sessionStorage.setItem(key, JSON.stringify({ path: window.location.pathname, url: target }));
                else sessionStorage.removeItem(key);
                return target;
            } catch (error) {
                return sameApp(document.referrer);
            }
        },
        destroy() {
            this.observer?.disconnect();
        },
        syncHeight() {
            if (this.$refs.scaled) this.scaledHeight = Math.ceil(this.$refs.scaled.offsetHeight * (this.viewScale / 100));
        },
        setScale(value) {
            this.viewScale = Math.min(100, Math.max(70, Number(value) || 90));
            try { localStorage.setItem('customer-detail-view-scale', String(this.viewScale)); } catch (error) {}
            this.$nextTick(() => this.syncHeight());
        },
    }"
    @resize.window.debounce.150ms="isCompact = window.innerWidth / window.appAutoScale() < 1024; syncHeight()"
    class="relative isolate min-h-[calc(var(--app-dvh)-123px)] w-full min-w-0 overflow-hidden text-[15px]"
>
    {{-- Los estilos van a la cabecera de la página (una sola vez): dentro del componente se enviaban completos en cada actualización de Livewire. --}}
    @push('styles')
    <x-panel-styles />
    <style>
        [data-customer-detail] .customer-guide { color: #15803d; }
        html.module-dark-theme [data-customer-detail] .customer-guide { color: #4ade80; }
    </style>
    @endpush

    {{-- Sin escala propia sobre el contenido: el zoom ya lo aplica el marco general y la escala dejaba un hueco al final. --}}
    <div class="w-full">
        <div x-ref="scaled">
            <div class="flex w-full flex-col gap-[20px] p-[20px] sm:p-[30px] xl:p-[50px]">
                @if (isset($notFound) && $notFound)
                    <section class="panel-surface panel-border overflow-hidden rounded-xl border">
                        <x-panel-empty-state subtitle="El cliente que buscas no existe o fue eliminado.">
                            <x-slot name="icon">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </x-slot>
                            Cliente no encontrado
                            <x-slot name="action">
                                <a href="{{ route('dashboard') }}"
                                    class="panel-button inline-flex h-11 items-center justify-center gap-[10px] whitespace-nowrap rounded-xl px-[20px] focus:outline-none focus:ring-0">
                                    <svg class="h-[18px] w-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 19l-7-7 7-7" /></svg>
                                    Volver al Panel general
                                </a>
                            </x-slot>
                        </x-panel-empty-state>
                    </section>
                @else
                    @php
                        $fullName = trim($customer->name.' '.($customer->last_name ? $customer->last_name.' '.$customer->maternal_last_name : ''));
                        $initials = mb_strtoupper(mb_substr($customer->name, 0, 2));
                        $tone = $percentage == 0 ? 'red' : ($percentage < 99 ? 'amber' : ($percentage == 100 ? 'green' : 'gray'));
                        $ringLength = 2 * M_PI * 28;
                        $ringFill = $ringLength * max(0, min(100, (float) $percentage)) / 100;
                        $accountants = $customer->accountants->sortByDesc(fn ($accountant) => $accountant->pivot->status);
                        // Contadores asignados: quien consulta como Administrador siempre aparece primero. Se decide por el rol
                        // (no por un usuario fijo), así funciona igual en el servidor local y en el remoto.
                        $viewerId = auth()->id();
                        $accountantItems = $accountants
                            ->map(fn ($accountant) => ['id' => $accountant->id, 'name' => trim(preg_replace('/\s+/', ' ', $accountant->name.' '.$accountant->last_name)), 'active' => (int) $accountant->pivot->status === 1])
                            ->values();
                        if ($role === 'Administrador') {
                            $viewerItem = $accountantItems->firstWhere('id', $viewerId) ?? ['id' => $viewerId, 'name' => trim(preg_replace('/\s+/', ' ', auth()->user()->name.' '.auth()->user()->last_name)), 'active' => true];
                            $accountantItems = $accountantItems->reject(fn ($item) => $item['id'] === $viewerId)->prepend($viewerItem)->values();
                        }
                        $hasContact = $customer->phone || $customer->email || $customer->address;
                    @endphp

                    @error('error')
                        <div class="panel-danger-box rounded-xl border px-[20px] py-[15px]" role="alert" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)">
                            {{ $message }}
                        </div>
                    @enderror

                    @error('pdfsValidate')
                        <div class="panel-danger-box rounded-xl border px-[20px] py-[15px]" role="alert" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)">
                            {{ $message }}
                        </div>
                    @enderror

                    <!-- Cabecera y periodo -->
                    <section class="mb-0 sm:mb-[10px] xl:mb-[30px] flex shrink-0 flex-wrap items-center justify-between gap-x-[20px] gap-y-[15px]">
                        <div class="flex min-w-0 max-w-full items-center gap-[15px]">
                            @if ($customer->url_photo)
                                <img class="panel-border h-12 w-12 shrink-0 rounded-full border object-cover"
                                    src="{{ asset('storage/photos/' . $customer->url_photo) }}"
                                    alt="{{ $fullName }}">
                            @else
                                <span class="panel-header panel-border panel-accent grid h-12 w-12 shrink-0 place-items-center rounded-full border">{{ $initials }}</span>
                            @endif

                            <div class="min-w-0">
                                <h1 class="panel-accent line-clamp-2 break-words text-xl font-normal leading-tight tracking-tight" title="{{ $fullName }}">{{ $fullName }}</h1>
                                <p class="panel-muted mt-[10px] leading-none">RFC: {{ $customer->rfc }}</p>
                            </div>
                        </div>

                        <div class="flex w-full flex-wrap items-center gap-[20px] lg:w-auto">
                            <x-panel-select id="customer-month" label="Mes" property="selectedMonth"
                                :options="collect($months)->map(fn ($month) => ['value' => $month['number'], 'label' => $month['name']])->all()"
                                class="min-w-[140px] flex-1 sm:w-44 sm:flex-none" />

                            <x-panel-select id="customer-year" label="Año" property="selectedYear"
                                :options="collect($years)->map(fn ($year) => ['value' => $year, 'label' => $year])->all()"
                                class="min-w-[110px] flex-1 sm:w-28 sm:flex-none" />
                        </div>
                    </section>

                    <!-- Información del cliente -->
                    <section class="panel-surface panel-border overflow-hidden rounded-xl border">
                        <header class="panel-header panel-border flex flex-col justify-between gap-[10px] border-b p-[20px] md:flex-row md:items-center md:gap-[20px]">
                            <div class="min-w-0">
                                <h2 class="panel-accent leading-none">Información del cliente</h2>
                                <p class="mt-[10px] leading-tight">Datos de contacto, contadores asignados y avance del periodo</p>
                            </div>
                            @if ($periodLabel !== '')
                                <div class="flex shrink-0 items-center gap-[10px] leading-none"><span class="h-2 w-2 shrink-0 rounded-full bg-emerald-500"></span>Periodo: {{ $periodLabel }}</div>
                            @endif
                        </header>

                        <div class="panel-surface grid grid-cols-1 lg:grid-cols-3">
                            <!-- Contacto -->
                            <div class="panel-surface panel-border min-w-0 border-t p-[20px] first:border-t-0 lg:border-l lg:border-t-0 lg:first:border-l-0">
                                <span class="panel-accent block leading-none">Contacto</span>
                                @if ($hasContact)
                                    <ul class="mt-[15px] space-y-[15px]">
                                        @if ($customer->phone)
                                            <li class="flex min-w-0 items-start gap-[10px] leading-none">
                                                <svg class="panel-muted -mt-[1.5px] h-[18px] w-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 18h.01M8 2h8a1 1 0 011 1v18a1 1 0 01-1 1H8a1 1 0 01-1-1V3a1 1 0 011-1z" /></svg>
                                                <span class="-my-[7.5px] min-w-0 break-words leading-[30px]">+{{ $customer->codePhone }} {{ $customer->phone }}</span>
                                            </li>
                                        @endif
                                        @if ($customer->email)
                                            <li class="flex min-w-0 items-start gap-[10px] leading-none">
                                                <svg class="panel-muted -mt-[1.5px] h-[18px] w-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 4h16a2 2 0 012 2v12a2 2 0 01-2 2H4a2 2 0 01-2-2V6a2 2 0 012-2zM22 6l-10 7L2 6" /></svg>
                                                <span class="-my-[7.5px] min-w-0 break-words leading-[30px]">{{ $customer->email }}</span>
                                            </li>
                                        @endif
                                        @if ($customer->address)
                                            <li class="flex min-w-0 items-start gap-[10px] leading-none">
                                                <svg class="panel-muted -mt-[1.5px] h-[18px] w-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0zM12 13a3 3 0 100-6 3 3 0 000 6z" /></svg>
                                                <span class="-my-[7.5px] min-w-0 break-words leading-[30px]">{{ $customer->address }}</span>
                                            </li>
                                        @endif
                                    </ul>
                                @else
                                    <p class="panel-muted mt-[15px] leading-snug">Sin datos de contacto</p>
                                @endif
                            </div>

                            <!-- Contadores asignados -->
                            <div class="panel-surface panel-border min-w-0 border-t p-[20px] first:border-t-0 lg:border-l lg:border-t-0 lg:first:border-l-0">
                                <span class="panel-accent block leading-none">Contadores asignados</span>
                                @if ($accountantItems->isNotEmpty())
                                    <ul class="mt-[15px] flex flex-wrap gap-[20px]">
                                        @foreach ($accountantItems as $accountantItem)
                                            <li class="flex min-w-0 max-w-full items-center gap-[10px] leading-none" title="{{ $accountantItem['name'] }}{{ $accountantItem['active'] ? '' : ' (inactivo)' }}">
                                                <span class="panel-header panel-border panel-accent grid h-10 w-10 shrink-0 place-items-center rounded-full border uppercase">{{ mb_substr($accountantItem['name'], 0, 1) }}{{ ($pieces = explode(' ', $accountantItem['name'])) && count($pieces) > 1 ? mb_substr(end($pieces), 0, 1) : mb_substr($accountantItem['name'], 1, 1) }}</span>
                                                <span class="{{ $accountantItem['active'] ? '' : 'panel-muted' }} min-w-0">{{ $accountantItem['name'] }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="panel-muted mt-[15px] leading-snug">Sin contadores asignados</p>
                                @endif
                            </div>

                            <!-- Avance del periodo -->
                            <div class="panel-surface panel-border min-w-0 border-t p-[20px] first:border-t-0 lg:border-l lg:border-t-0 lg:first:border-l-0">
                                <span class="panel-accent block leading-none">Avance del periodo</span>
                                <div class="mt-[15px] flex min-w-0 items-center gap-[15px]">
                                    <div class="panel-tone-{{ $tone }} relative grid h-14 w-14 shrink-0 place-items-center" role="img" aria-label="Avance {{ $percentage }}%">
                                        <svg class="h-14 w-14 -rotate-90" viewBox="0 0 64 64" fill="none" aria-hidden="true">
                                            <circle class="panel-ring-track" cx="32" cy="32" r="28" stroke-width="5" />
                                            <circle cx="32" cy="32" r="28" stroke="currentColor" stroke-width="5" stroke-linecap="round" stroke-dasharray="{{ round($ringFill, 2) }} {{ round($ringLength, 2) }}" />
                                        </svg>
                                        <span class="absolute leading-none tabular-nums">{{ $percentage }}%</span>
                                    </div>
                                    @if ($selectedServiceId)
                                        <span class="{{ $percentage == 100 ? 'panel-tone-green' : 'panel-muted' }} min-w-0 leading-snug">Formatos completados {{ $countPDFs }}/{{ $totalPdfAvailable }}</span>
                                    @else
                                        <span class="panel-muted min-w-0 leading-snug">Sin archivos</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Documentos del cliente (sin overflow-hidden para que las listas desplegables no se corten) -->
                    <section class="panel-surface panel-border mt-0 rounded-xl border sm:mt-[10px] xl:mt-[30px]">
                        <header class="panel-header panel-border flex flex-col justify-between gap-[10px] rounded-t-[11px] border-b p-[20px] md:flex-row md:items-center md:gap-[20px]">
                            <div class="min-w-0">
                                <h2 class="panel-accent leading-none">Documentos del cliente</h2>
                                <p class="mt-[10px] leading-tight">Elige un servicio y un apartado; después sube el acuse y el comprobante en PDF.</p>
                            </div>
                            <div class="flex shrink-0 flex-wrap items-center gap-x-[20px] gap-y-[15px]">
                                @if ($selectedServiceId)
                                    <div class="flex shrink-0 items-center gap-[10px] leading-none tabular-nums" title="Formatos cargados del servicio seleccionado en el periodo">
                                        <span class="{{ ['red' => 'bg-red-500', 'amber' => 'bg-amber-500', 'green' => 'bg-emerald-500'][$tone] ?? 'bg-zinc-400' }} h-2 w-2 shrink-0 rounded-full"></span>
                                        Formatos cargados: {{ $countPDFs }}/{{ $totalPdfAvailable }}
                                    </div>
                                @endif
                                    <a href="{{ route('section.guide', 'detalle-cliente') }}" title="Descargar la guía de Detalle del cliente en PDF" class="customer-guide inline-flex items-center gap-[10px] whitespace-nowrap leading-none hover:opacity-70">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 3h7l4 4v14H7V3Zm7 0v5h4M10 12h5m-5 4h5" /></svg>Guía PDF
                                    </a>
                            </div>
                        </header>

                        @php
                            $hasServices = ! (empty($services) || count($services) === 0);
                            $hasSubServices = $subServicesCustomer && $subServicesCustomer->isNotEmpty();
                        @endphp

                        <!-- Servicios (pestañas en pantallas medianas y grandes) -->
                        @if ($hasServices)
                            <div class="hidden flex-wrap items-center gap-x-[20px] px-[20px] md:flex" role="tablist" aria-label="Servicios del cliente">
                                @foreach ($serviceRelation as $service)
                                    <button type="button" role="tab" wire:key="customer-service-{{ $service->id }}"
                                        wire:click="selectButton({{ $service->id }}, {{ $customer->id }})"
                                        aria-selected="{{ $selectedServiceId === $service->id ? 'true' : 'false' }}"
                                        class="panel-tab inline-flex items-center gap-[10px] py-[20px] leading-none focus:outline-none focus:ring-0">
                                        <svg class="h-[18px] w-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 7h16a2 2 0 012 2v10a2 2 0 01-2 2H4a2 2 0 01-2-2V9a2 2 0 012-2zM16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2" /></svg>
                                        <span>{{ $service->service }}</span>
                                        <span class="panel-muted tabular-nums" title="Apartados contratados">({{ $serviceCounts[$service->id] ?? 0 }})</span>
                                    </button>
                                @endforeach
                            </div>
                        @endif

                        <!-- Apartados del servicio (pestañas en pantallas medianas y grandes) -->
                        @if ($hasSubServices)
                            <div class="panel-subtle hidden flex-wrap items-center gap-x-[20px] px-[20px] md:flex" role="tablist" aria-label="Apartados del servicio">
                                @foreach ($subServicesCustomer as $subService)
                                    <a href="#" role="tab" wire:key="customer-subservice-{{ $subService->id }}"
                                        wire:click.prevent="selectSubServiceButton({{ $subService->id }})"
                                        aria-selected="{{ $selectedSubServices === $subService->id ? 'true' : 'false' }}"
                                        class="panel-tab inline-flex items-center gap-[10px] py-[20px] leading-none focus:outline-none focus:ring-0">
                                        <svg class="h-[18px] w-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8zM14 2v6h6M16 13H8M16 17H8M10 9H8" /></svg>
                                        <span>{{ $subService->sub_service }}</span>
                                    </a>
                                @endforeach
                            </div>
                        @endif

                        <!-- Servicios y apartados en celular: listas desplegables con el mismo diseño de mes y año -->
                        @if ($hasServices || $hasSubServices)
                            <div class="flex flex-col gap-[15px] px-[20px] pt-[20px] md:hidden">
                                @if ($hasServices)
                                    <x-panel-select id="customer-service" label="Servicio" :selected="$selectedServiceId"
                                        icon="M4 7h16a2 2 0 012 2v10a2 2 0 01-2 2H4a2 2 0 01-2-2V9a2 2 0 012-2zM16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"
                                        :options="collect($serviceRelation)->map(fn ($service) => ['value' => $service->id, 'label' => $service->service])->all()"
                                        x-on:panel-select="$wire.selectButton($event.detail.value, {{ $customer->id }})" />
                                @endif

                                @if ($hasSubServices)
                                    <x-panel-select id="customer-subservice" label="Apartado" :selected="$selectedSubServices"
                                        icon="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8zM14 2v6h6M16 13H8M16 17H8M10 9H8"
                                        :options="collect($subServicesCustomer)->map(fn ($subService) => ['value' => $subService->id, 'label' => $subService->sub_service])->all()"
                                        x-on:panel-select="$wire.selectSubServiceButton($event.detail.value)" />
                                @endif
                            </div>
                        @endif

                        <!-- Sección de impuestos -->
                        @if ($selectedServiceId)
                            @php
                                $description = $selectedSubService->description ?? '';
                                $showGlobalAction = $selectedSubServiceId !== 1 && $selectedSubServiceId !== 6;
                            @endphp
                            <div class="flex flex-col gap-[20px] p-[20px]">
                                @if ($description !== '' || $showGlobalAction)
                                    <div class="flex flex-col justify-between gap-[10px] sm:flex-row sm:items-center sm:gap-[20px]">
                                        <p class="min-w-0 leading-snug">{{ $description }}</p>

                                        @if ($showGlobalAction)
                                            @php
                                                $complementariaFiles = $pdfsDB['complementaria'][
                                                    $this->selectedSubServiceId
                                                ] ?? [
                                                    'acuse' => collect(),
                                                    'comprobante' => collect(),
                                                ];

                                                $hasComplementaria =
                                                    $complementariaFiles['acuse']->isNotEmpty() ||
                                                    $complementariaFiles['comprobante']->isNotEmpty();
                                                $buttonText =
                                                    $hasComplementaria || $isComplementaria ? 'Eliminar' : 'Agregar';
                                                $isDanger = $isComplementaria || $hasComplementaria;
                                            @endphp
                                            <button type="button" wire:click="toggle()"
                                                class="{{ $isDanger ? 'panel-action-danger' : 'panel-action' }} inline-flex w-fit shrink-0 items-center gap-[10px] py-[10px] leading-none focus:outline-none focus:ring-0 sm:py-0">
                                                <svg class="h-[18px] w-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $isDanger ? 'M5 7h14M9 7V4h6v3m-8 0l1 13h8l1-13' : 'M12 5v14M5 12h14' }}" /></svg>
                                                <span>{{ $buttonText }} complementaria</span>
                                            </button>
                                        @endif
                                    </div>
                                @endif

                                @if ($selectedSubServiceId === 1)
                                    <div class="grid gap-[20px] xl:grid-cols-2 xl:gap-x-[50px]">
                                    @foreach ($states as $key => $state)
                                        @php
                                            $normalFiles = $pdfsDB['normal'][$state->id] ?? [
                                                'acuse' => collect(),
                                                'comprobante' => collect(),
                                            ];
                                            $complementariaFiles = $pdfsDB['complementaria'][$state->id] ?? [
                                                'acuse' => collect(),
                                                'comprobante' => collect(),
                                            ];

                                            $hasComplementaria =
                                                $complementariaFiles['acuse']->isNotEmpty() ||
                                                $complementariaFiles['comprobante']->isNotEmpty();
                                            $isOpen = $this->isStateOpen($state->id);
                                            $buttonText = $hasComplementaria || $isOpen ? 'Eliminar' : 'Agregar';
                                            $isDanger = $isOpen || $hasComplementaria;
                                        @endphp
                                        <section wire:key="customer-state-{{ $state->id }}" class="panel-border flex flex-col gap-[20px] border-t pt-[20px] first:border-t-0 first:pt-0 xl:border-t-0 xl:pt-0 xl:[&:nth-child(n+3)]:border-t xl:[&:nth-child(n+3)]:pt-[20px]">
                                            <div class="flex flex-col justify-between gap-[10px] sm:flex-row sm:items-center sm:gap-[20px]">
                                                <span class="panel-accent min-w-0 leading-snug">{{ $state->name }}</span>
                                                <button type="button" wire:click="toggleState({{ $state->id }})"
                                                    class="{{ $isDanger ? 'panel-action-danger' : 'panel-action' }} {{ $hasComplementaria ? 'cursor-not-allowed' : '' }} inline-flex w-fit shrink-0 items-center gap-[10px] py-[10px] leading-none focus:outline-none focus:ring-0 sm:py-0">
                                                    <svg class="h-[18px] w-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $isDanger ? 'M5 7h14M9 7V4h6v3m-8 0l1 13h8l1-13' : 'M12 5v14M5 12h14' }}" /></svg>
                                                    <span>{{ $buttonText }} complementaria</span>
                                                </button>
                                            </div>

                                            <div class="grid grid-cols-[repeat(auto-fill,minmax(min(100%,300px),1fr))] gap-[20px]">
                                                <!-- Archivos Normales -->
                                                <x-format-pdf model="pdfs.normal.acuse" :pdf="$normalFiles['acuse']"
                                                    :successMessage="$successMessage" fileType="Acuse" declarationType="Normal"
                                                    :state="$state" />

                                                <x-format-pdf model="pdfs.normal.comprobante" :pdf="$normalFiles['comprobante']"
                                                    fileType="Comprobante" declarationType="Normal"
                                                    :state="$state" :successMessage="$successMessage" :comprobante-normal-is-visible="$complementariaFiles['acuse']->isEmpty() &&
                                                        $complementariaFiles['comprobante']->isEmpty()" />

                                                <!-- Archivos Complementaria -->
                                                @if ($hasComplementaria || $isOpen)
                                                    <x-format-pdf model="pdfs.complementaria.acuse"
                                                        :pdf="$complementariaFiles['acuse']" fileType="Acuse"
                                                        declarationType="Complementaria" :state="$state" />

                                                    <x-format-pdf model="pdfs.complementaria.comprobante"
                                                        :pdf="$complementariaFiles['comprobante']" fileType="Comprobante"
                                                        declarationType="Complementaria" :state="$state" />
                                                @endif
                                            </div>
                                        </section>
                                    @endforeach
                                    </div>
                                @elseif ($selectedSubServiceId === 6)
                                    <div class="grid gap-[20px] xl:grid-cols-2 xl:gap-x-[50px]">
                                    @foreach ($statements as $statement)
                                        @php
                                            $normalFiles = $pdfsDB['normal'][$statement->id] ?? [
                                                'acuse' => collect(),
                                                'comprobante' => collect(),
                                            ];
                                            $complementariaFiles = $pdfsDB['complementaria'][$statement->id] ?? [
                                                'acuse' => collect(),
                                                'comprobante' => collect(),
                                            ];

                                            $hasComplementaria =
                                                $complementariaFiles['acuse']->isNotEmpty() ||
                                                $complementariaFiles['comprobante']->isNotEmpty();
                                            $isOpen = $this->isStatementOpen($statement->id);
                                            $buttonText = $hasComplementaria || $isOpen ? 'Eliminar' : 'Agregar';
                                            $isDanger = $isOpen || $hasComplementaria;
                                        @endphp
                                        <section wire:key="customer-statement-{{ $statement->id }}" class="panel-border flex flex-col gap-[20px] border-t pt-[20px] first:border-t-0 first:pt-0 xl:border-t-0 xl:pt-0 xl:[&:nth-child(n+3)]:border-t xl:[&:nth-child(n+3)]:pt-[20px]">
                                            <div class="flex flex-col justify-between gap-[10px] sm:flex-row sm:items-center sm:gap-[20px]">
                                                <span class="panel-accent min-w-0 leading-snug">{{ $statement->statement }}</span>
                                                <button type="button" wire:click="toggleStatement({{ $statement->id }})"
                                                    class="{{ $isDanger ? 'panel-action-danger' : 'panel-action' }} {{ $hasComplementaria ? 'cursor-not-allowed' : '' }} inline-flex w-fit shrink-0 items-center gap-[10px] py-[10px] leading-none focus:outline-none focus:ring-0 sm:py-0">
                                                    <svg class="h-[18px] w-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $isDanger ? 'M5 7h14M9 7V4h6v3m-8 0l1 13h8l1-13' : 'M12 5v14M5 12h14' }}" /></svg>
                                                    <span>{{ $buttonText }} complementaria</span>
                                                </button>
                                            </div>

                                            <div class="grid grid-cols-[repeat(auto-fill,minmax(min(100%,300px),1fr))] gap-[20px]">
                                                <!-- Archivos Normales -->
                                                <x-format-pdf model="pdfs.normal.acuse" :pdf="$normalFiles['acuse']"
                                                    :successMessage="$successMessage" fileType="Acuse" declarationType="Normal"
                                                    :statement="$statement" />

                                                <x-format-pdf model="pdfs.normal.comprobante" :pdf="$normalFiles['comprobante']"
                                                    fileType="Comprobante" declarationType="Normal"
                                                    :statement="$statement" :successMessage="$successMessage" :comprobante-normal-is-visible="$complementariaFiles['acuse']->isEmpty() &&
                                                        $complementariaFiles['comprobante']->isEmpty()" />

                                                <!-- Archivos Complementaria -->
                                                @if ($hasComplementaria || $isOpen)
                                                    <x-format-pdf model="pdfs.complementaria.acuse"
                                                        :pdf="$complementariaFiles['acuse']" fileType="Acuse"
                                                        declarationType="Complementaria" :statement="$statement" />

                                                    <x-format-pdf model="pdfs.complementaria.comprobante"
                                                        :pdf="$complementariaFiles['comprobante']" fileType="Comprobante"
                                                        declarationType="Complementaria" :statement="$statement" />
                                                @endif
                                            </div>
                                        </section>
                                    @endforeach
                                    </div>
                                @else
                                    @php
                                        $normalFiles = $pdfsDB['normal'][$selectedSubServiceId] ?? [
                                            'acuse' => collect(),
                                            'comprobante' => collect(),
                                        ];
                                        $complementariaFiles = $pdfsDB['complementaria'][$selectedSubServiceId] ?? [
                                            'acuse' => collect(),
                                            'comprobante' => collect(),
                                        ];
                                    @endphp

                                    <div class="grid grid-cols-[repeat(auto-fill,minmax(min(100%,300px),1fr))] gap-[20px]">
                                        <!-- Archivos Normales -->
                                        <x-format-pdf model="pdfs.normal.acuse" :pdf="$normalFiles['acuse']"
                                            :successMessage="$successMessage" fileType="Acuse" declarationType="Normal" />

                                        <x-format-pdf model="pdfs.normal.comprobante" :pdf="$normalFiles['comprobante']"
                                            fileType="Comprobante" declarationType="Normal" :successMessage="$successMessage"
                                            :comprobante-normal-is-visible="$complementariaFiles['acuse']->isEmpty() &&
                                                $complementariaFiles['comprobante']->isEmpty()" />

                                        <!-- Archivos Complementaria -->
                                        @if (
                                            $complementariaFiles['acuse']->isNotEmpty() ||
                                                $complementariaFiles['comprobante']->isNotEmpty() ||
                                                $isComplementaria)
                                            <x-format-pdf model="pdfs.complementaria.acuse" :pdf="$complementariaFiles['acuse']"
                                                fileType="Acuse" declarationType="Complementaria" />

                                            <x-format-pdf model="pdfs.complementaria.comprobante" :pdf="$complementariaFiles['comprobante']"
                                                fileType="Comprobante" declarationType="Complementaria" />
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @else
                            <x-panel-empty-state>
                                <x-slot name="icon">
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8zM14 2v6h6" /></svg>
                                </x-slot>
                                Sin servicios contratados seleccionados
                            </x-panel-empty-state>
                        @endif
                    </section>
                @endif
            </div>
        </div>
    </div>

    @unless (isset($notFound) && $notFound)
        <!-- Subida de PDF en curso (fuera del contenedor escalado para cubrir toda la pantalla) -->
        <div wire:loading wire:target="pdfs.normal.acuse,pdfs.normal.comprobante,pdfs.complementaria.acuse,pdfs.complementaria.comprobante">
            <div role="alert" aria-live="polite" class="fixed inset-0 z-[60] flex items-center justify-center bg-black/40 p-[20px]">
                <div class="panel-surface panel-border flex max-w-[320px] flex-col items-center gap-[15px] rounded-xl border px-[30px] py-[30px] text-center shadow-[0_8px_24px_rgba(0,0,0,0.20)]">
                    <span class="relative grid h-14 w-14 place-items-center">
                        <span class="panel-border absolute inset-0 rounded-full border-4"></span>
                        <span class="panel-spinner absolute inset-0 animate-spin rounded-full border-4"></span>
                        <svg class="panel-accent h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8zM14 2v6h6" /></svg>
                    </span>
                    <span class="panel-accent leading-none">Subiendo PDF…</span>
                    <p class="panel-muted leading-snug">Estamos cargando tu archivo de manera segura. Por favor espera un momento.</p>
                </div>
            </div>
        </div>

        @if ($successMessage)
            @php
                $toastTone = match ($typeMessage) {
                    'error' => 'panel-tone-red',
                    'alert' => 'panel-tone-amber',
                    default => 'panel-tone-green',
                };
            @endphp
            <div class="panel-surface panel-border fixed bottom-[30px] right-[20px] z-[70] flex w-[calc(100%-40px)] max-w-sm items-center gap-[15px] rounded-xl border p-[15px] shadow-[0_8px_24px_rgba(0,0,0,0.12)] sm:right-[30px] lg:bottom-[100px]"
                role="alert" x-data="{ show: true }" x-show="show"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-2"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 translate-y-2"
                @toast-hidden.window="if ($event.detail.message === @js($successMessage)) show = false"
                x-init="setTimeout(() => {
                    show = false;
                    $wire.set('successMessage', null);
                }, 3000)">
                <span class="{{ $toastTone }} panel-header panel-border grid h-9 w-9 shrink-0 place-items-center rounded-xl border">
                    @if ($iconSVG)
                        @svg($iconSVG, 'h-5 w-5')
                    @endif
                </span>
                <span class="min-w-0 flex-1 leading-snug">{{ $successMessage }}</span>
                <button type="button" aria-label="Cerrar" @click="show = false; $wire.set('successMessage', null);"
                    class="panel-icon-button grid h-9 w-9 shrink-0 place-items-center rounded-xl focus:outline-none focus:ring-0">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 6l12 12M18 6L6 18" /></svg>
                </button>
            </div>
        @endif
    @endunless

</div>
