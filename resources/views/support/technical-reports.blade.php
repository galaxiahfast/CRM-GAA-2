<x-app-layout>
    <div class="min-h-[calc(100dvh-100px)] bg-white text-[15px] text-zinc-700">
        <header class="border-b border-zinc-200 bg-white p-[30px] lg:p-[50px]">
            <div class="flex items-center gap-[15px] text-zinc-500">
                <span class="font-medium">Documentos</span>
                <span class="text-zinc-300">&gt;</span>
                <span class="font-semibold text-black">Reportes de soporte</span>
            </div>
        </header>

        <nav class="flex items-center gap-[30px] border-b border-zinc-200 bg-white px-[30px] lg:px-[50px]" aria-label="Secciones de reportes de soporte">
            <a href="{{ route('soporte.reportes-tecnicos') }}" class="flex h-[58px] items-center gap-[10px] border-b-2 font-medium transition {{ $activeTab === 'generate' ? 'border-[#214574] text-[#214574]' : 'border-transparent text-zinc-500 hover:text-black' }}">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m-7-7h14"/></svg>
                Generar reporte
            </a>
            <a href="{{ route('soporte.reportes-tecnicos.history') }}" class="flex h-[58px] items-center gap-[10px] border-b-2 font-medium transition {{ $activeTab === 'history' ? 'border-[#214574] text-[#214574]' : 'border-transparent text-zinc-500 hover:text-black' }}">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 2m6-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                Historial
            </a>
        </nav>

        <main class="space-y-[20px] p-[30px] lg:p-[50px]">
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
                <div class="flex flex-col items-start justify-between gap-[20px] border-b border-zinc-200 p-[20px] lg:flex-row lg:items-center">
                    <div class="flex items-center gap-[15px]">
                        <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl border border-zinc-200 bg-white text-black">
                            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8 3h8l4 4v14H4V3h4Zm7 0v5h5M8 13h8m-8 4h8"/></svg>
                        </span>
                        <div>
                            <h1 class="text-xl font-semibold text-black">Generar reporte de soporte</h1>
                            <p class="mt-[5px] text-zinc-500">Selecciona servicios e indica cuántas veces se realizó cada uno.</p>
                        </div>
                    </div>
                    <span class="text-zinc-500"><span x-text="selected.length">0</span> de 8 seleccionados</span>
                </div>

                <form method="POST" action="{{ route('soporte.reportes-tecnicos.store') }}" class="p-[20px]">
                    @csrf

                    <div class="mb-[15px]">
                        <h2 class="font-semibold text-black">Servicios incluidos</h2>
                        <p class="mt-[5px] text-zinc-500">Selecciona los conceptos e indica en cuántos equipos se realizó cada uno.</p>
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

                    <div class="mt-[20px] grid max-h-[390px] grid-cols-1 gap-[10px] overflow-y-auto pr-[5px] [scrollbar-color:#214574_transparent] [scrollbar-width:thin] lg:grid-cols-2">
                        <template x-for="item in catalog" :key="item.key">
                            <label x-show="matches(item)" class="flex cursor-pointer items-start gap-[15px] rounded-xl border p-[15px] transition" :class="selected.includes(item.key) ? 'border-black bg-zinc-50' : 'border-zinc-200 bg-white hover:bg-zinc-50'">
                                <input type="checkbox" name="supports[]" :value="item.key" x-model="selected" @change="$nextTick(() => ensureQuantity(item.key))" :disabled="selected.length >= 8 && !selected.includes(item.key)" class="mt-[3px] h-4 w-4 rounded border-zinc-300 text-black focus:ring-0 disabled:opacity-40">
                                <span class="min-w-0 flex-1">
                                    <span class="block font-semibold text-black" x-text="item.title"></span>
                                    <span class="mt-[4px] block text-zinc-500" x-text="item.description"></span>
                                </span>
                                <span x-show="selected.includes(item.key)" class="w-[95px] shrink-0" @click.stop>
                                    <span class="mb-[5px] block text-zinc-500">Cantidad</span>
                                    <input type="number" min="1" max="999" :name="`support_quantities[${item.key}]`" x-model.number="quantities[item.key]" :disabled="!selected.includes(item.key)" class="h-10 w-full rounded-xl border border-zinc-200 bg-white px-[12px] text-[15px] text-black focus:border-zinc-300 focus:outline-none focus:ring-0">
                                </span>
                            </label>
                        </template>
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

                    <div class="mt-[20px] flex flex-col items-start justify-between gap-[15px] border-t border-zinc-200 pt-[20px] sm:flex-row sm:items-center">
                        <p class="text-zinc-500">El folio y la fecha se asignarán automáticamente.</p>
                        <button type="submit" :disabled="selected.length === 0" class="inline-flex h-11 items-center justify-center rounded-xl bg-black px-[20px] font-semibold text-white transition hover:bg-zinc-800 disabled:cursor-not-allowed disabled:opacity-40">Generar PDF</button>
                    </div>
                </form>
            </section>
            @else
            <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white">
                <div class="flex items-center justify-between gap-[20px] border-b border-zinc-200 p-[20px]">
                    <div>
                        <h2 class="text-xl font-semibold text-black">Historial de reportes</h2>
                        <p class="mt-[5px] text-zinc-500">Consulta o descarga cualquier documento generado.</p>
                    </div>
                    <span class="text-zinc-500">{{ $reports->total() }} en total</span>
                </div>

                <div class="divide-y divide-zinc-200">
                    @forelse ($reports as $report)
                        <article class="grid grid-cols-1 items-center gap-[20px] p-[20px] lg:grid-cols-[190px_minmax(0,1fr)_auto]">
                            <div>
                                <p class="font-semibold text-black">{{ $report->folio }}</p>
                                <p class="mt-[5px] text-zinc-500">{{ $report->issued_at->timezone(config('support.timezone', 'America/Mexico_City'))->translatedFormat('d M Y · H:i') }}</p>
                            </div>
                            <div class="min-w-0">
                                <p class="font-semibold text-black [text-align:justify]">{{ collect($report->supports)->map(fn ($support) => $support['title'].' ('.((int) ($support['quantity'] ?? 1)).')')->join(', ') }}</p>
                                <p class="mt-[5px] text-zinc-500">{{ $report->responsible }}</p>
                            </div>
                            <div class="flex items-center gap-[10px]">
                                <a href="{{ route('soporte.reportes-tecnicos.pdf', $report) }}" target="_blank" rel="noopener" class="inline-flex h-10 items-center justify-center rounded-xl border border-zinc-200 px-[15px] font-semibold text-black transition hover:bg-zinc-50">Ver</a>
                                <a href="{{ route('soporte.reportes-tecnicos.pdf', ['report' => $report, 'download' => 1]) }}" class="inline-flex h-10 items-center justify-center rounded-xl bg-black px-[15px] font-semibold text-white transition hover:bg-zinc-800">Descargar</a>
                            </div>
                        </article>
                    @empty
                        <div class="p-[40px] text-center text-zinc-500">Todavía no hay reportes generados.</div>
                    @endforelse
                </div>

                @if ($reports->hasPages())
                    <div class="border-t border-zinc-200 p-[20px]">{{ $reports->links() }}</div>
                @endif
            </section>
            @endif
        </main>
    </div>
</x-app-layout>
