<div
    data-clock-particle-network
    class="support-monochrome relative isolate min-h-[calc(100dvh-90px)] w-full overflow-hidden bg-white text-[15px] text-zinc-700"
    x-data="{
        viewScale: 100,
        isFullscreen: false,
        init() {
            const savedScale = Number(localStorage.getItem('support-questions-view-scale'));
            if (savedScale >= 70 && savedScale <= 100) this.viewScale = savedScale;
        },
        saveScale() { localStorage.setItem('support-questions-view-scale', String(this.viewScale)); },
        async toggleFullscreen(container) {
            if (document.fullscreenElement === container) return document.exitFullscreen();
            if (document.fullscreenElement) await document.exitFullscreen();
            await container.requestFullscreen();
        }
    }"
    @fullscreenchange.window="isFullscreen = document.fullscreenElement === $root"
>
    <canvas wire:ignore data-clock-network-canvas class="pointer-events-none absolute inset-0 z-0 h-full w-full opacity-[0.55]" aria-hidden="true"></canvas>

    <div class="no-print absolute left-1/2 top-[20px] z-30 flex -translate-x-1/2 items-center gap-[10px] rounded-xl border border-zinc-200 bg-white/95 px-[15px] py-[10px] shadow-[0_8px_24px_rgba(0,0,0,0.10)] backdrop-blur-sm">
        <input type="range" min="70" max="100" step="5" x-model.number="viewScale" @input="saveScale()" class="h-1.5 w-[130px] cursor-pointer accent-black" aria-label="Ajustar tamaño de la vista de Preguntas">
        <span class="w-[42px] text-right font-semibold tabular-nums text-black" x-text="viewScale + '%'">100%</span>
        <span class="h-5 w-px bg-zinc-200" aria-hidden="true"></span>
        <button type="button" @click="toggleFullscreen($root)" class="inline-flex cursor-pointer items-center justify-center rounded-lg p-[5px] text-black outline-none transition-colors hover:bg-zinc-100 focus:outline-none focus:ring-0" :aria-label="isFullscreen ? 'Salir de pantalla completa' : 'Ver Preguntas en pantalla completa'" :title="isFullscreen ? 'Salir de pantalla completa' : 'Pantalla completa'">
            <svg x-show="!isFullscreen" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 3H3v5M16 3h5v5M21 16v5h-5M3 16v5h5"/></svg>
            <svg x-show="isFullscreen" x-cloak class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 3v5H3M16 3v5h5M21 16h-5v5M3 16h5v5"/></svg>
        </button>
    </div>

    <div class="relative z-10 min-h-[calc(100dvh-90px)] min-w-[1200px] origin-top" :style="`width: ${10000 / viewScale}%; margin-left: ${(100 - (10000 / viewScale)) / 2}%; transform: scale(${viewScale / 100});`">
    <header class="flex items-center justify-between gap-[20px] whitespace-nowrap border-b border-zinc-200 bg-white/75 p-[50px]">
        <div class="flex items-center gap-[15px] text-zinc-500">
            <span class="font-medium">Soporte</span>
            <span class="text-gray-300">&gt;</span>
            <span class="font-medium">Centro de ayuda</span>
            <span class="text-gray-300">&gt;</span>
            <a href="{{ route('soporte.preguntas') }}" class="font-semibold text-black transition-colors hover:text-zinc-600">Preguntas</a>
        </div>
        <div class="no-print flex items-center gap-[20px]">
            <button type="button" wire:click="downloadPdf" wire:loading.attr="disabled" wire:target="downloadPdf" class="inline-flex items-center gap-[10px] border-0 bg-transparent p-0 font-medium text-black transition-colors hover:text-zinc-600 disabled:cursor-wait disabled:opacity-60">
                <svg wire:loading.remove wire:target="downloadPdf" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v12m0 0 4-4m-4 4-4-4M5 21h14" /></svg>
                <svg wire:loading wire:target="downloadPdf" class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                <span>Descargar PDF</span>
            </button>
            <button type="button" onclick="window.print()" class="inline-flex items-center gap-[10px] border-0 bg-transparent p-0 font-medium text-black transition-colors hover:text-zinc-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10zM9 7V3h6v4" /></svg>
                <span>Imprimir</span>
            </button>
        </div>
    </header>

    <main class="mx-auto w-full space-y-[20px] p-[50px]">
        <section class="flex items-center gap-[20px]">
            <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl border border-zinc-200 bg-white/75 text-black">
                <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9.1 9a3 3 0 115.8 1c0 2-2.9 2-2.9 4m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </span>
            <div>
                <h1 class="text-xl font-semibold text-black">Asistente de preguntas</h1>
                <p class="mt-[5px] text-zinc-500">Selecciona una pregunta para conocer cómo utilizar la aplicación.</p>
            </div>
        </section>

        <section data-support-chat-panel class="grid min-h-[700px] grid-cols-1 overflow-hidden rounded-xl border border-zinc-200 bg-white/65 shadow-[0_8px_24px_rgba(0,0,0,0.05)] xl:h-[700px] xl:min-h-0 xl:grid-cols-[340px_420px_minmax(0,1fr)]">
            <aside class="border-b border-zinc-200 bg-white/55 p-[20px] xl:border-b-0 xl:border-r">
                <div class="flex items-center gap-[15px] border-b border-zinc-200 pb-[20px]">
                    <span data-support-assistant-icon class="flex h-11 w-11 items-center justify-center rounded-xl border border-zinc-200 bg-white text-black">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 10h8m-8 4h5m8-2a9 9 0 0 1-9 9 9.8 9.8 0 0 1-4-.84L3 21l.84-5A9 9 0 1 1 21 12Z" /></svg>
                    </span>
                    <div>
                        <p class="font-semibold text-gray-800">Asistente DataMID</p>
                        <p class="mt-[5px] flex items-center gap-[10px] text-emerald-600"><span class="h-2 w-2 rounded-full bg-emerald-500"></span>Disponible</p>
                    </div>
                </div>

                <p class="mb-[15px] mt-[20px] font-semibold text-zinc-500">Categorías</p>
                <nav class="space-y-[10px]" aria-label="Categorías de ayuda">
                    @foreach ($questionBank as $categoryKey => $categoryData)
                        <button wire:click="selectCategory('{{ $categoryKey }}')" type="button"
                            class="flex w-full items-center justify-between rounded-xl border px-[20px] py-[15px] text-left outline-none transition focus:outline-none focus:ring-0 {{ $category === $categoryKey ? 'border-black bg-black font-semibold text-white' : 'border-zinc-200 bg-white/70 text-zinc-700 hover:bg-zinc-100' }}">
                            <span>{{ $categoryData['label'] }}</span>
                            <span class="flex h-7 min-w-7 items-center justify-center rounded-full border border-current px-[5px]">{{ count($categoryData['items']) }}</span>
                        </button>
                    @endforeach
                </nav>

                <div class="mt-[20px] rounded-xl border border-zinc-200 bg-white/70 p-[20px] text-justify leading-6 text-zinc-500">
                    ¿No encontraste tu respuesta? Utiliza el Chat general de Ticket para conversar con otros colaboradores.
                </div>
            </aside>

            <section class="border-b border-zinc-200 bg-white/45 p-[20px] xl:border-b-0 xl:border-r">
                <header class="border-b border-zinc-200 pb-[20px]">
                    <p class="font-semibold text-zinc-500">Preguntas disponibles</p>
                    <h2 class="mt-[5px] font-semibold text-black">{{ $questionBank[$category]['label'] ?? 'Ayuda' }}</h2>
                </header>

                <div class="support-questions-scrollbar mt-[20px] max-h-[595px] space-y-[10px] overflow-y-auto pr-[5px]">
                    @foreach (($questionBank[$category]['items'] ?? []) as $questionKey => $item)
                        <button wire:click="ask('{{ $category }}', '{{ $questionKey }}')" type="button"
                            class="group flex w-full items-center gap-[15px] rounded-xl border p-[20px] text-left outline-none transition focus:outline-none focus:ring-0 {{ $selectedQuestion === $item['question'] ? 'border-black bg-zinc-100' : 'border-zinc-200 bg-white/75 hover:bg-zinc-100' }}">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-zinc-200 bg-white text-black transition">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.1 9a3 3 0 115.8 1c0 2-2.9 2-2.9 4m0 3h.01" /></svg>
                            </span>
                            <span class="min-w-0 flex-1 font-medium leading-6 text-gray-700">{{ $item['question'] }}</span>
                            <svg class="h-4 w-4 shrink-0 text-zinc-400 transition group-hover:translate-x-0.5 group-hover:text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 18 6-6-6-6" /></svg>
                        </button>
                    @endforeach
                </div>
            </section>

            <section
                class="flex min-h-[560px] min-w-0 flex-col xl:min-h-0"
                x-data="{ scrollToLatest() { this.$refs.history.scrollTop = this.$refs.history.scrollHeight } }"
                x-on:support-answer-shown.window="$nextTick(() => scrollToLatest())"
                x-on:pageshow.window="if ($event.persisted) $wire.resetConversation()"
            >
                <header class="flex items-center justify-between gap-[20px] border-b border-zinc-200 p-[20px]">
                    <div>
                        <h2 class="font-semibold text-black">Conversación con el asistente</h2>
                        <p class="mt-[5px] text-zinc-500">Historial temporal de esta consulta</p>
                    </div>
                    @if (count($conversation) > 0)
                        <button wire:click="resetConversation" type="button" class="inline-flex items-center gap-[10px] rounded-xl border border-zinc-200 bg-white px-[20px] py-[15px] font-medium text-black outline-none transition hover:bg-zinc-100 focus:outline-none focus:ring-0">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 7l-.9 12.1A2 2 0 0116.1 21H7.9a2 2 0 01-2-1.9L5 7m3 0V4.8A1.8 1.8 0 019.8 3h4.4A1.8 1.8 0 0116 4.8V7M3 7h18" /></svg>
                            Limpiar historial
                        </button>
                    @endif
                </header>

                <div x-ref="history" class="support-questions-scrollbar min-h-0 flex-1 space-y-[20px] overflow-y-auto bg-white/35 p-[20px]">
                    <div class="flex items-end gap-[15px]">
                        <span data-support-assistant-icon class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-black">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 10h.01M15 10h.01M9 14c1.5 1.3 4.5 1.3 6 0M12 3a7 7 0 00-7 7v3a7 7 0 0014 0v-3a7 7 0 00-7-7z" /></svg>
                        </span>
                        <div class="max-w-[82%] rounded-xl border border-zinc-200 bg-white/90 p-[20px] leading-6 text-zinc-700 shadow-[0_3px_10px_rgba(0,0,0,0.04)]">
                            ¡Hola! Soy el asistente de ayuda. Elige una de las preguntas disponibles y te explicaré cómo realizar esa acción.
                        </div>
                    </div>

                    @forelse ($conversation as $conversationIndex => $exchange)
                        <div wire:key="support-exchange-{{ $conversationIndex }}" class="space-y-[20px]">
                            <div class="flex items-end justify-end gap-[15px]">
                                <div class="max-w-[82%] rounded-xl border border-zinc-300 bg-zinc-50 p-[20px] font-medium leading-6 text-black shadow-[0_3px_10px_rgba(0,0,0,0.04)]">
                                    {{ $exchange['question'] }}
                                </div>
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-black bg-black text-white">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 21a8 8 0 00-16 0m8-10a4 4 0 100-8 4 4 0 000 8z" /></svg>
                                </span>
                            </div>

                            <div class="flex items-end gap-[15px]">
                                <span data-support-assistant-icon class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-black">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 10h.01M15 10h.01M9 14c1.5 1.3 4.5 1.3 6 0M12 3a7 7 0 00-7 7v3a7 7 0 0014 0v-3a7 7 0 00-7-7z" /></svg>
                                </span>
                                <div class="max-w-[82%] rounded-xl border border-zinc-200 bg-white/90 p-[20px] leading-7 text-zinc-700 shadow-[0_3px_10px_rgba(0,0,0,0.04)]">
                                    {{ $exchange['answer'] }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="flex min-h-[280px] flex-col items-center justify-center px-6 text-center text-gray-400">
                            <svg class="h-12 w-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.4" d="M12 6v6l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <p class="mt-[15px] font-medium">Esperando tu pregunta</p>
                            <p class="mt-[5px]">Selecciona una opción de la lista para ver la respuesta.</p>
                        </div>
                    @endforelse
                </div>

                <footer class="border-t border-zinc-200 bg-white/70 p-[20px] text-center text-zinc-500">
                    Esta conversación es temporal y se elimina al salir de este apartado.
                </footer>
            </section>
        </section>
    </main>
    </div>

    <style>
        .support-questions-scrollbar { scrollbar-width: thin; scrollbar-color: #000 transparent; }
        .support-questions-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
        .support-questions-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .support-questions-scrollbar::-webkit-scrollbar-thumb { background: #000; border-radius: 9999px; }
    </style>
</div>
