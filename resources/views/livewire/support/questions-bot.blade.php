<div class="min-h-[calc(100dvh-90px)] text-[15px] text-gray-700">
    <header class="flex items-center justify-between gap-20 whitespace-nowrap border-b border-gray-200 px-[40px] py-[25px]">
        <div class="flex items-center gap-3 text-gray-500">
            <span class="font-medium">Soporte</span>
            <span class="text-gray-300">&gt;</span>
            <span class="font-medium">Centro de ayuda</span>
            <span class="text-gray-300">&gt;</span>
            <span class="font-semibold text-[#1A3A6B]">Preguntas</span>
        </div>
    </header>

    <main class="mx-auto w-full max-w-[1500px] space-y-6 px-6 py-8 lg:px-10">
        <section class="flex items-start gap-4">
            <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl border border-[#cbd9ed] text-[#1A3A6B]">
                <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9.1 9a3 3 0 115.8 1c0 2-2.9 2-2.9 4m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </span>
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900">Asistente de preguntas</h1>
                <p class="mt-1 text-gray-500">Selecciona una pregunta para conocer cómo utilizar la aplicación.</p>
            </div>
        </section>

        <section class="grid min-h-[640px] grid-cols-1 overflow-hidden rounded-xl border border-gray-200 shadow-[0_8px_24px_rgba(15,35,66,0.06)] xl:h-[640px] xl:min-h-0 xl:grid-cols-[310px_390px_minmax(0,1fr)]">
            <aside class="border-b border-gray-200 p-5 xl:border-b-0 xl:border-r">
                <div class="flex items-center gap-3 border-b border-gray-200 pb-5">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-[#1A3A6B] text-white">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3a6 6 0 00-6 6v3a6 6 0 0012 0V9a6 6 0 00-6-6zm-2 7h.01M14 10h.01M9 14c1.5 1.3 4.5 1.3 6 0M5 21a7 7 0 0114 0" /></svg>
                    </span>
                    <div>
                        <p class="font-semibold text-gray-800">Asistente DataMID</p>
                        <p class="mt-0.5 flex items-center gap-1.5 text-[12px] text-emerald-600"><span class="h-2 w-2 rounded-full bg-emerald-500"></span>Disponible</p>
                    </div>
                </div>

                <p class="mb-3 mt-5 text-[12px] font-semibold uppercase tracking-[0.08em] text-gray-400">Categorías</p>
                <nav class="space-y-2" aria-label="Categorías de ayuda">
                    @foreach ($questionBank as $categoryKey => $categoryData)
                        <button wire:click="selectCategory('{{ $categoryKey }}')" type="button"
                            class="flex w-full items-center justify-between rounded-xl border px-4 py-3 text-left transition {{ $category === $categoryKey ? 'border-[#b9c9e2] bg-[#eef4fc] font-semibold text-[#1A3A6B]' : 'border-transparent text-gray-600 hover:border-gray-200' }}">
                            <span>{{ $categoryData['label'] }}</span>
                            <span class="flex h-6 min-w-6 items-center justify-center rounded-full border border-current px-1 text-[11px]">{{ count($categoryData['items']) }}</span>
                        </button>
                    @endforeach
                </nav>

                <div class="mt-6 rounded-xl border border-gray-200 p-4 text-[13px] leading-5 text-gray-500">
                    ¿No encontraste tu respuesta? Utiliza el Chat general de Ticket para conversar con otros colaboradores.
                </div>
            </aside>

            <section class="border-b border-gray-200 p-5 xl:border-b-0 xl:border-r">
                <header class="border-b border-gray-200 pb-4">
                    <p class="text-[12px] font-semibold uppercase tracking-[0.08em] text-gray-400">Preguntas disponibles</p>
                    <h2 class="mt-1 font-semibold text-gray-800">{{ $questionBank[$category]['label'] ?? 'Ayuda' }}</h2>
                </header>

                <div class="support-questions-scrollbar mt-4 max-h-[535px] space-y-3 overflow-y-auto pr-1">
                    @foreach (($questionBank[$category]['items'] ?? []) as $questionKey => $item)
                        <button wire:click="ask('{{ $category }}', '{{ $questionKey }}')" type="button"
                            class="group flex w-full items-start gap-3 rounded-xl border p-4 text-left transition {{ $selectedQuestion === $item['question'] ? 'border-[#b9c9e2] bg-[#eef4fc]' : 'border-gray-200 hover:border-[#b9c9e2]' }}">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-gray-200 text-[#1A3A6B] transition group-hover:border-[#b9c9e2]">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.1 9a3 3 0 115.8 1c0 2-2.9 2-2.9 4m0 3h.01" /></svg>
                            </span>
                            <span class="min-w-0 flex-1 font-medium leading-6 text-gray-700">{{ $item['question'] }}</span>
                            <svg class="mt-1 h-4 w-4 shrink-0 text-gray-300 transition group-hover:translate-x-0.5 group-hover:text-[#1A3A6B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 18 6-6-6-6" /></svg>
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
                <header class="flex min-h-[74px] items-center justify-between border-b border-gray-200 px-5 py-4">
                    <div>
                        <h2 class="font-semibold text-gray-800">Conversación con el asistente</h2>
                        <p class="mt-0.5 text-[12px] text-gray-500">Historial temporal de esta consulta</p>
                    </div>
                    @if (count($conversation) > 0)
                        <button wire:click="resetConversation" type="button" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 px-3 py-2 text-[12px] font-medium text-gray-500 transition hover:border-[#b9c9e2] hover:text-[#1A3A6B]">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 7l-.9 12.1A2 2 0 0116.1 21H7.9a2 2 0 01-2-1.9L5 7m3 0V4.8A1.8 1.8 0 019.8 3h4.4A1.8 1.8 0 0116 4.8V7M3 7h18" /></svg>
                            Limpiar historial
                        </button>
                    @endif
                </header>

                <div x-ref="history" class="support-questions-scrollbar min-h-0 flex-1 space-y-5 overflow-y-auto p-5">
                    <div class="flex items-end gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#1A3A6B] text-white">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 10h.01M15 10h.01M9 14c1.5 1.3 4.5 1.3 6 0M12 3a7 7 0 00-7 7v3a7 7 0 0014 0v-3a7 7 0 00-7-7z" /></svg>
                        </span>
                        <div class="max-w-[82%] rounded-xl border border-gray-200 bg-white px-4 py-3 leading-6 text-gray-700">
                            ¡Hola! Soy el asistente de ayuda. Elige una de las preguntas disponibles y te explicaré cómo realizar esa acción.
                        </div>
                    </div>

                    @forelse ($conversation as $conversationIndex => $exchange)
                        <div wire:key="support-exchange-{{ $conversationIndex }}" class="space-y-4">
                            <div class="flex items-end justify-end gap-3">
                                <div class="max-w-[82%] rounded-xl border border-[#b9c9e2] bg-[#eef4fc] px-4 py-3 font-medium leading-6 text-gray-800 shadow-[0_3px_10px_rgba(26,58,107,0.04)]">
                                    {{ $exchange['question'] }}
                                </div>
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-[#b9c9e2] bg-white text-[#1A3A6B]">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 21a8 8 0 00-16 0m8-10a4 4 0 100-8 4 4 0 000 8z" /></svg>
                                </span>
                            </div>

                            <div class="flex items-end gap-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#1A3A6B] text-white">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 10h.01M15 10h.01M9 14c1.5 1.3 4.5 1.3 6 0M12 3a7 7 0 00-7 7v3a7 7 0 0014 0v-3a7 7 0 00-7-7z" /></svg>
                                </span>
                                <div class="max-w-[82%] rounded-xl border border-gray-200 bg-white px-4 py-3 leading-7 text-gray-700 shadow-[0_3px_10px_rgba(15,35,66,0.04)]">
                                    {{ $exchange['answer'] }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="flex min-h-[280px] flex-col items-center justify-center px-6 text-center text-gray-400">
                            <svg class="h-12 w-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.4" d="M12 6v6l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <p class="mt-3 font-medium">Esperando tu pregunta</p>
                            <p class="mt-1 text-[13px]">Selecciona una opción de la lista para ver la respuesta.</p>
                        </div>
                    @endforelse
                </div>

                <footer class="border-t border-gray-200 px-5 py-3 text-center text-[11px] text-gray-400">
                    Esta conversación es temporal y se elimina al salir de este apartado.
                </footer>
            </section>
        </section>
    </main>

    <style>
        .support-questions-scrollbar { scrollbar-width: thin; scrollbar-color: #1A3A6B transparent; }
        .support-questions-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
        .support-questions-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .support-questions-scrollbar::-webkit-scrollbar-thumb { background: #1A3A6B; border-radius: 9999px; }
    </style>
</div>
