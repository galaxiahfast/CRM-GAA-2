<x-app-layout>
    <style>
        .support-service-scroll {
            scrollbar-width: none;
            overscroll-behavior: contain;
        }

        .support-service-scroll::-webkit-scrollbar {
            width: 0;
            height: 0;
        }

        .support-service-scroll-thumb {
            position: absolute;
            right: 0;
            top: 0;
            z-index: 20;
            width: 3px;
            min-height: 32px;
            border-radius: 9999px;
            background: #1A3A6B;
            pointer-events: none;
        }

        .support-service-checkbox:focus,
        .support-service-checkbox:focus-visible,
        .support-quantity-input:focus,
        .support-quantity-input:focus-visible {
            outline: none !important;
            box-shadow: none !important;
            --tw-ring-offset-shadow: 0 0 #0000 !important;
            --tw-ring-shadow: 0 0 #0000 !important;
        }

        .support-quantity-input {
            appearance: textfield;
            -moz-appearance: textfield;
            text-align: center;
        }

        .support-quantity-input::-webkit-inner-spin-button,
        .support-quantity-input::-webkit-outer-spin-button {
            margin: 0;
            appearance: none;
            -webkit-appearance: none;
        }
    </style>

    <div class="min-h-[calc(100dvh-100px)] bg-white text-[15px] text-zinc-700">
        <main class="w-full space-y-[20px] p-[30px] lg:p-[50px]">
            <section class="flex flex-col justify-between gap-[20px] lg:flex-row lg:items-center">
                <div class="flex min-w-0 items-center gap-[15px]">
                    <span class="grid h-14 w-14 shrink-0 place-items-center rounded-xl border border-zinc-200 bg-white text-black">
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 3h8l4 4v14H4V3h4Zm7 0v5h5M8 13h8m-8 4h8"/></svg>
                    </span>
                    <div class="min-w-0">
                        <h1 class="text-xl font-semibold tracking-tight text-black">Reportes de soporte</h1>
                        <p class="mt-[4px] text-zinc-500">Genera y consulta los documentos de los servicios técnicos realizados.</p>
                    </div>
                </div>
            </section>

            <nav class="!mt-[8px] flex items-center gap-[5px] border-b border-zinc-200" aria-label="Secciones de reportes de soporte">
                <a href="{{ route('soporte.reportes-tecnicos') }}" class="inline-flex min-h-[44px] items-center border-b-2 px-[15px] font-medium transition {{ $activeTab === 'generate' ? 'border-[#1A3A6B] text-[#1A3A6B]' : 'border-transparent text-zinc-500 hover:text-black' }}">
                    Generar reporte
                </a>
                <a href="{{ route('soporte.reportes-tecnicos.history') }}" class="inline-flex min-h-[44px] items-center gap-[8px] border-b-2 px-[15px] font-medium transition {{ $activeTab === 'history' ? 'border-[#1A3A6B] text-[#1A3A6B]' : 'border-transparent text-zinc-500 hover:text-black' }}">
                    <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v5l3 2M4.9 4.9A10 10 0 1 1 2 12m0 0V6m0 6h6"/></svg>
                    <span>Historial</span>
                </a>
            </nav>

            @if (session('status'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-[15px] text-emerald-800">{{ session('status') }}</div>
            @endif

            @if ($activeTab === 'generate')
            <section
                class="overflow-hidden rounded-xl border border-zinc-200 bg-white"
                x-data="{
                    query: '',
                    selected: @js(old('supports', [])),
                    quantities: @js(old('support_quantities', [])),
                    targets: @js(old('support_targets', [])),
                    catalog: @js($catalog),
                    normalize(value) {
                        return value.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
                    },
                    matches(item) {
                        const term = this.normalize(this.query.trim());
                        return !term || this.normalize(item.title + ' ' + item.description).includes(term);
                    },
                    ensureQuantity(key) {
                        if (this.selected.includes(key) && !this.quantities[key]) this.quantities[key] = 1;
                    },
                    hasServerUpdate() {
                        return this.selected.some(key => key.endsWith('_server_update'));
                    }
                }"
            >
                <form method="POST" action="{{ route('soporte.reportes-tecnicos.store') }}" class="p-[20px]">
                    @csrf

                    <label class="mb-[20px] block">
                        <span class="mb-[7px] block font-medium text-black">Departamento o cliente</span>
                        <input name="service_recipient" value="{{ old('service_recipient') }}" list="support-recipient-options" maxlength="180" required placeholder="Ej. Contabilidad, Jurídico o nombre del cliente externo" class="h-12 w-full rounded-xl border border-zinc-200 bg-white px-[15px] text-[15px] text-black placeholder:text-zinc-400 focus:border-zinc-300 focus:outline-none focus:ring-0">
                        <datalist id="support-recipient-options">
                            <option value="Administración"></option>
                            <option value="Auditoría"></option>
                            <option value="Contabilidad"></option>
                            <option value="Jurídico"></option>
                            <option value="Nóminas"></option>
                            <option value="Nóminas y Contabilidad"></option>
                        </datalist>
                        <span class="mt-[5px] block text-zinc-500">Puedes escribir un departamento interno o el nombre de un cliente externo.</span>
                        @error('service_recipient')<span class="mt-[5px] block text-red-600">{{ $message }}</span>@enderror
                    </label>

                    <div class="mb-[15px] flex flex-col items-start justify-between gap-[10px] sm:flex-row sm:items-center">
                        <div>
                            <h2 class="font-semibold text-black">Servicios incluidos</h2>
                            <p class="mt-[5px] text-zinc-500">Selecciona los conceptos e indica en cuántos equipos se realizó cada uno.</p>
                        </div>
                        <span class="shrink-0 text-zinc-500"><span x-text="selected.length">0</span> de 8 seleccionados</span>
                    </div>

                    <label class="relative block">
                        <span class="sr-only">Buscar servicios de soporte</span>
                        <svg class="pointer-events-none absolute left-[15px] top-1/2 h-5 w-5 -translate-y-1/2 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/></svg>
                        <input x-model="query" type="search" placeholder="Buscar instalación, CONTPAQi, correo, Office..." class="h-12 w-full rounded-xl border border-zinc-200 bg-white pl-[45px] pr-[15px] text-[15px] text-black placeholder:text-zinc-400 focus:border-zinc-300 focus:outline-none focus:ring-0">
                    </label>

                    @error('supports')
                        <p class="mt-[10px] text-red-600">{{ $message }}</p>
                    @enderror
                    @error('supports.*')
                        <p class="mt-[10px] text-red-600">{{ $message }}</p>
                    @enderror

                    <div x-data="backupOverlayScroll()" x-effect="query; selected.length; $nextTick(() => update())" class="-mr-[20px] mt-[20px] max-h-[390px] relative">
                        <div x-ref="scroller" @scroll="update()" @mouseenter="update()" class="support-service-scroll grid max-h-[390px] grid-cols-1 gap-[10px] overflow-y-auto pr-[20px] lg:grid-cols-2">
                            <template x-for="item in catalog" :key="item.key">
                                <label x-show="matches(item)" class="block cursor-pointer rounded-xl border p-[15px] transition" :class="selected.includes(item.key) ? 'border-zinc-200 bg-zinc-50' : 'border-zinc-200 bg-white hover:bg-zinc-50'">
                                    <span class="flex items-center gap-[15px]">
                                        <input type="checkbox" name="supports[]" :value="item.key" x-model="selected" @change="$nextTick(() => ensureQuantity(item.key))" :disabled="selected.length >= 8 && !selected.includes(item.key)" class="support-service-checkbox h-4 w-4 rounded border-zinc-300 text-black focus:outline-none focus:ring-0 focus:ring-offset-0 disabled:opacity-40">
                                        <span class="min-w-0 flex-1">
                                            <span class="block font-semibold text-black" x-text="item.title"></span>
                                            <span class="mt-[4px] block text-zinc-500" x-text="item.description"></span>
                                        </span>
                                        <span x-show="selected.includes(item.key)" class="w-[95px] shrink-0" @click.stop>
                                            <span class="mb-[5px] block text-zinc-500">Cantidad</span>
                                            <input type="number" min="1" max="999" :name="`support_quantities[${item.key}]`" x-model.number="quantities[item.key]" :disabled="!selected.includes(item.key)" class="support-quantity-input h-10 w-full rounded-xl border border-zinc-200 bg-white px-[12px] text-center text-[15px] text-black focus:border-zinc-200 focus:outline-none focus:ring-0 focus:ring-offset-0">
                                        </span>
                                    </span>
                                    <span x-show="selected.includes(item.key)" x-cloak class="mt-[15px] block border-t border-zinc-200 pt-[15px]" @click.stop>
                                        <span class="mb-[7px] block font-medium text-black">Personas o equipos atendidos</span>
                                        <textarea :name="`support_targets[${item.key}]`" x-model="targets[item.key]" :disabled="!selected.includes(item.key)" rows="2" placeholder="Un nombre por línea" class="w-full resize-y rounded-xl border border-zinc-200 bg-white px-[15px] py-[10px] text-[15px] text-black placeholder:text-zinc-400 focus:border-zinc-300 focus:outline-none focus:ring-0"></textarea>
                                    </span>
                                </label>
                            </template>
                        </div>
                        <span x-show="visible" x-cloak class="support-service-scroll-thumb" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
                    </div>

                    <div x-show="hasServerUpdate()" x-cloak class="mt-[20px] rounded-xl border border-zinc-200 bg-zinc-50 p-[20px]">
                        <div class="mb-[20px]">
                            <h2 class="font-semibold text-black">Versiones aplicadas en el servidor</h2>
                            <p class="mt-[5px] text-zinc-500">Solo aparece al seleccionar una actualización de servidor.</p>
                        </div>

                        <label class="block">
                            <span class="mb-[7px] block font-medium text-black">Todas las versiones utilizadas</span>
                            <textarea name="versions_applied" rows="5" placeholder="Una versión por línea:&#10;18.3.1&#10;19.0.1&#10;19.4.0" class="w-full resize-y rounded-xl border border-zinc-200 bg-white px-[15px] py-[12px] text-[15px] text-black placeholder:text-zinc-400 focus:border-zinc-300 focus:outline-none focus:ring-0">{{ old('versions_applied') }}</textarea>
                            @error('versions_applied')<span class="mt-[5px] block text-red-600">{{ $message }}</span>@enderror
                        </label>
                    </div>

                    <div class="-mx-[20px] mt-[20px] flex flex-col items-start justify-between gap-[15px] border-t border-zinc-200 px-[20px] pt-[20px] sm:flex-row sm:items-center">
                        <p class="text-zinc-500">El folio y la fecha se asignarán automáticamente.</p>
                        <button type="submit" :disabled="selected.length === 0" class="inline-flex h-11 items-center justify-center rounded-xl bg-black px-[20px] font-semibold text-white transition hover:bg-zinc-800 disabled:cursor-not-allowed disabled:opacity-40">Generar PDF</button>
                    </div>
                </form>
            </section>
            @else
            <section
                x-data="{
                    selectedReports: [],
                    pageFolios: @js($reports->pluck('folio')->values()),
                    allPageSelected() {
                        return this.pageFolios.length > 0 && this.pageFolios.every(folio => this.selectedReports.includes(folio));
                    },
                    togglePage() {
                        if (this.allPageSelected()) {
                            this.selectedReports = this.selectedReports.filter(folio => !this.pageFolios.includes(folio));
                            return;
                        }

                        this.selectedReports = [...new Set([...this.selectedReports, ...this.pageFolios])];
                    },
                    toggleReport(folio) {
                        this.selectedReports = this.selectedReports.includes(folio)
                            ? this.selectedReports.filter(selected => selected !== folio)
                            : [...this.selectedReports, folio];
                    }
                }"
                class="flex h-[calc(100dvh-210px)] min-h-[610px] max-h-[820px] flex-col overflow-hidden rounded-xl border border-zinc-200 bg-white"
            >
                <div class="shrink-0 border-b border-zinc-200 p-[20px]">
                    <div class="flex flex-col gap-[15px] xl:flex-row xl:items-center xl:justify-between">
                        <div class="shrink-0">
                            <h2 class="text-xl font-semibold text-black">Historial de reportes</h2>
                            <p class="mt-[5px] text-zinc-500">Consulta, filtra o descarga los documentos generados.</p>
                        </div>

                        <div class="flex min-w-0 flex-1 flex-col gap-[10px] xl:items-end">
                            <span class="text-zinc-500">{{ $reports->total() }} en total</span>
                            <form method="GET" action="{{ route('soporte.reportes-tecnicos.history') }}" class="flex w-full flex-wrap items-center justify-end gap-[10px]">
                                <label class="relative min-w-[220px] flex-1 xl:max-w-[300px]">
                                    <span class="sr-only">Buscar reportes</span>
                                    <svg class="pointer-events-none absolute left-[12px] top-1/2 h-[18px] w-[18px] -translate-y-1/2 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/></svg>
                                    <input type="search" name="q" value="{{ $filters['search'] }}" placeholder="Buscar folio o servicio..." class="h-10 w-full rounded-xl border border-zinc-200 bg-white pl-[38px] pr-[12px] text-[14px] text-black placeholder:text-zinc-400 focus:border-zinc-300 focus:outline-none focus:ring-0">
                                </label>

                                <label>
                                    <span class="sr-only">Filtrar por departamento o cliente</span>
                                    <select name="recipient" class="h-10 min-w-[170px] rounded-xl border border-zinc-200 bg-white px-[12px] text-[14px] text-black focus:border-zinc-300 focus:outline-none focus:ring-0">
                                        <option value="">Todos los departamentos</option>
                                        @foreach ($recipientOptions as $option)
                                            <option value="{{ $option }}" @selected($filters['recipient'] === $option)>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                </label>

                                <label>
                                    <span class="sr-only">Filtrar por persona o equipo</span>
                                    <select name="target" class="h-10 min-w-[190px] rounded-xl border border-zinc-200 bg-white px-[12px] text-[14px] text-black focus:border-zinc-300 focus:outline-none focus:ring-0">
                                        <option value="">Todas las personas</option>
                                        @foreach ($targetOptions as $option)
                                            <option value="{{ $option }}" @selected($filters['target'] === $option)>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                </label>

                                <button type="submit" class="inline-flex h-10 items-center justify-center rounded-xl bg-black px-[15px] font-semibold text-white transition hover:bg-zinc-800">Filtrar</button>
                                @if ($filters['search'] !== '' || $filters['recipient'] !== '' || $filters['target'] !== '')
                                    <a href="{{ route('soporte.reportes-tecnicos.history') }}" class="inline-flex h-10 items-center justify-center rounded-xl border border-zinc-200 px-[15px] font-semibold text-black transition hover:bg-zinc-50">Limpiar</a>
                                @endif
                            </form>
                        </div>
                    </div>
                </div>

                <div class="flex shrink-0 flex-wrap items-center justify-between gap-[10px] border-b border-zinc-200 bg-zinc-50 px-[20px] py-[12px]">
                    <label class="inline-flex cursor-pointer items-center gap-[10px] text-black">
                        <input type="checkbox" :checked="allPageSelected()" @change="togglePage()" class="h-4 w-4 rounded border-zinc-300 text-black focus:outline-none focus:ring-0 focus:ring-offset-0">
                        <span>Seleccionar página</span>
                    </label>

                    <form method="POST" action="{{ route('soporte.reportes-tecnicos.download-selection') }}">
                        @csrf
                        <template x-for="folio in selectedReports" :key="folio">
                            <input type="hidden" name="report_folios[]" :value="folio">
                        </template>
                        <button type="submit" :disabled="selectedReports.length === 0" class="inline-flex h-10 items-center justify-center rounded-xl bg-black px-[15px] font-semibold text-white transition hover:bg-zinc-800 disabled:cursor-not-allowed disabled:opacity-40">
                            Descargar selección (<span x-text="selectedReports.length">0</span>)
                        </button>
                    </form>
                </div>

                <div class="hidden shrink-0 grid-cols-[48px_60px_210px_minmax(0,1fr)_220px] border-b border-zinc-200 bg-white text-[13px] text-zinc-500 lg:grid">
                    <div class="px-[10px] py-[10px] text-center"><span class="sr-only">Seleccionar</span></div>
                    <div class="border-r border-zinc-200 px-[10px] py-[10px] text-center">N.º</div>
                    <div class="border-r border-zinc-200 px-[15px] py-[10px] text-center">Folio y fecha</div>
                    <div class="border-r border-zinc-200 px-[20px] py-[10px]">Servicios y destinatario</div>
                    <div class="px-[20px] py-[10px] text-center">Acciones</div>
                </div>

                <div x-data="backupOverlayScroll()" x-init="$nextTick(() => update())" class="relative min-h-[300px] flex-1">
                    <div x-ref="scroller" @scroll="update()" @mouseenter="update()" class="support-service-scroll h-full divide-y divide-zinc-200 overflow-y-auto">
                        @forelse ($reports as $report)
                            @php
                                $supportSummary = collect($report->supports)
                                    ->map(fn ($support) => $support['title'].' ('.((int) ($support['quantity'] ?? 1)).')')
                                    ->join(', ');
                                $targetSummary = collect($report->supports)
                                    ->flatMap(fn ($support) => $support['targets'] ?? [])
                                    ->filter()
                                    ->unique()
                                    ->join(', ');
                                $rowNumber = ($reports->firstItem() ?? 1) + $loop->index;
                            @endphp
                            <article
                                @click="if (!$event.target.closest('a, button, input, label')) toggleReport('{{ $report->folio }}')"
                                class="grid min-h-[110px] cursor-pointer grid-cols-[48px_60px_minmax(0,1fr)] transition hover:bg-zinc-50 lg:grid-cols-[48px_60px_210px_minmax(0,1fr)_220px]"
                                :class="selectedReports.includes('{{ $report->folio }}') ? 'bg-zinc-50' : 'bg-white'"
                            >
                                <div class="row-span-3 flex items-center justify-center px-[10px] py-[15px] lg:row-span-1">
                                    <input type="checkbox" value="{{ $report->folio }}" x-model="selectedReports" @click.stop class="h-4 w-4 rounded border-zinc-300 text-black focus:outline-none focus:ring-0 focus:ring-offset-0" aria-label="Seleccionar {{ $report->folio }}">
                                </div>
                                <div class="row-span-3 flex items-center justify-center border-x border-zinc-200 px-[10px] py-[15px] text-center text-zinc-500 lg:row-span-1 lg:border-l-0">
                                    {{ $rowNumber }}
                                </div>
                                <div class="col-start-3 flex min-w-0 flex-col justify-center border-b border-zinc-200 px-[15px] py-[12px] text-center lg:col-start-auto lg:border-b-0 lg:border-r">
                                    <p class="font-semibold text-black">{{ $report->folio }}</p>
                                    <p class="mt-[5px] text-zinc-500">{{ $report->issued_at->timezone(config('support.timezone', 'America/Mexico_City'))->translatedFormat('d M Y · H:i') }}</p>
                                </div>
                                <div class="col-start-3 flex min-w-0 flex-col justify-center border-b border-zinc-200 px-[20px] py-[12px] lg:col-start-auto lg:border-b-0 lg:border-r">
                                    <p class="truncate font-semibold text-black" title="{{ $supportSummary }}">{{ $supportSummary }}</p>
                                    <p class="mt-[5px] truncate text-zinc-500" title="Departamento o cliente: {{ $report->service_recipient }}">Departamento o cliente: {{ $report->service_recipient }}</p>
                                    @if ($targetSummary !== '')
                                        <p class="mt-[3px] truncate text-zinc-500" title="Personas o equipos: {{ $targetSummary }}">Personas o equipos: {{ $targetSummary }}</p>
                                    @endif
                                </div>
                                <div class="col-start-3 flex items-center justify-center gap-[10px] px-[20px] py-[12px] lg:col-start-auto">
                                    <a @click.stop href="{{ route('soporte.reportes-tecnicos.pdf', $report) }}" target="_blank" rel="noopener" class="inline-flex h-10 items-center justify-center rounded-xl border border-zinc-200 px-[15px] font-semibold text-black transition hover:bg-white">Ver</a>
                                    <a @click.stop href="{{ route('soporte.reportes-tecnicos.pdf', ['report' => $report, 'download' => 1]) }}" class="inline-flex h-10 items-center justify-center rounded-xl bg-black px-[15px] font-semibold text-white transition hover:bg-zinc-800">Descargar</a>
                                </div>
                            </article>
                        @empty
                            <div class="grid h-full min-h-[300px] place-items-center p-[40px] text-center text-zinc-500">No hay reportes que coincidan con los filtros.</div>
                        @endforelse
                    </div>
                    <span x-show="visible" x-cloak class="support-service-scroll-thumb" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
                </div>

                @if ($reports->hasPages())
                    <div class="shrink-0 border-t border-zinc-200 p-[15px]">{{ $reports->links() }}</div>
                @endif
            </section>
            @endif
        </main>
    </div>
</x-app-layout>
