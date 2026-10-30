<div
    class="support-questions-page h-[calc(100dvh-100px)] overflow-hidden bg-white text-[15px] text-black"
    x-data="{
        viewScale: Number(localStorage.getItem('support-questions-view-scale') || 90),
        isFullscreen: false,
        init() {
            if (this.viewScale < 70 || this.viewScale > 100) this.viewScale = 90;
        },
        saveScale() {
            localStorage.setItem('support-questions-view-scale', String(this.viewScale));
        },
        zoomOut() { this.viewScale = Math.max(70, this.viewScale - 5); this.saveScale() },
        zoomIn() { this.viewScale = Math.min(100, this.viewScale + 5); this.saveScale() },
        async toggleFullscreen() {
            if (document.fullscreenElement === this.$root) return document.exitFullscreen();
            if (document.fullscreenElement) await document.exitFullscreen();
            await this.$root.requestFullscreen();
        }
    }"
    @fullscreenchange.window="isFullscreen = document.fullscreenElement === $root"
>
    @php
        $categoryIcons = [
            'inicio' => 'M3 11.5 12 4l9 7.5M5 10v10h5v-6h4v6h5V10',
            'clientes' => 'M17 20v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2M10 10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm11 10v-2a4 4 0 0 0-3-3.87M16 2.13a4 4 0 0 1 0 7.75',
            'reloj' => 'M12 7v5l3 2M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
            'administracion' => 'M12 3 4 6v6c0 4.5 3.2 7.7 8 9 4.8-1.3 8-4.5 8-9V6l-8-3Zm-3 9 2 2 4-4',
            'reporte' => 'M8 3h8l4 4v14H4V3h4Zm7 0v5h5M8 13h8m-8 4h8',
            'conciliacion' => 'M4 7h16M6 11h12M5 15h6m2 0h6M5 19h6m2 0h6',
            'respaldo' => 'M4 5c0 1.7 3.6 3 8 3s8-1.3 8-3-3.6-3-8-3-8 1.3-8 3Zm0 0v7c0 1.7 3.6 3 8 3s8-1.3 8-3V5m-16 7v7c0 1.7 3.6 3 8 3s8-1.3 8-3v-7',
            'documento' => 'M7 3h7l4 4v14H7V3Zm7 0v5h4M10 12h5m-5 4h5',
            'herramientas' => 'M4 6h10m4 0h2M4 12h2m4 0h10M4 18h8m4 0h4M14 4v4M8 10v4m6 2v4',
            'panel' => 'M5 4h4a1 1 0 0 1 1 1v4a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1Zm10 0h4a1 1 0 0 1 1 1v4a1 1 0 0 1-1 1h-4a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1ZM5 14h4a1 1 0 0 1 1 1v4a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-4a1 1 0 0 1 1-1Zm10 0h4a1 1 0 0 1 1 1v4a1 1 0 0 1-1 1h-4a1 1 0 0 1-1-1v-4a1 1 0 0 1 1-1Z',
            'ocr' => 'M4 7V4h3m10 0h3v3M4 17v3h3m10 0h3v-3M7 12h10M8 9h8M8 15h8',
            'bancaria' => 'M3 6h18M6 10h12M5 14h5m4 0h5M5 18h5m4 0h5M4 3h16a1 1 0 0 1 1 1v16a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Z',
            'hoja' => 'M9 12h6m-6 4h6M8 3h8l4 4v14H4V3h4Zm7 0v5h5',
            'reporte_respaldos' => 'M4 19V9m5 10V5m5 14v-7m5 7V7M3 21h18',
            'chat' => 'M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z',
            'preguntas' => 'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        ];
        $questionIconPath = 'M8.4 8.2a3.8 3.8 0 1 1 5.5 3.4c-1.2.6-1.9 1.4-1.9 2.8M12 19.4h.01';
        $pageIconPath = 'M4 5.5A2.5 2.5 0 0 1 6.5 3H11a2 2 0 0 1 2 2v14a2 2 0 0 0-2-2H6.5A2.5 2.5 0 0 0 4 19.5v-14ZM20 5.5A2.5 2.5 0 0 0 17.5 3H13v16a2 2 0 0 1 2-2h2.5a2.5 2.5 0 0 1 2.5 2.5v-14Z';
        $botName = (string) config('support.automated_user.name', 'Sofia');
        $botAvatar = asset(ltrim((string) config('support.automated_user.avatar', 'img/support/sofia-avatar.svg'), '/'));
        $currentCategory = $questionBank[$category] ?? null;
        $currentItems = $currentCategory['items'] ?? [];
    @endphp

    <div class="no-print fixed bottom-[30px] right-[30px] z-30 flex items-center gap-[10px] rounded-xl border border-zinc-200 bg-white/95 px-[15px] py-[10px] shadow-[0_8px_24px_rgba(0,0,0,0.10)] backdrop-blur-sm">
        <button type="button" @click="zoomOut()" aria-label="Alejar vista" class="inline-flex h-7 w-7 items-center justify-center text-black focus:outline-none focus:ring-0">−</button>
        <input type="range" min="70" max="100" step="5" x-model.number="viewScale" @input="saveScale()" class="h-1.5 w-[130px] cursor-pointer accent-black" aria-label="Ajustar tamaño de la vista de Preguntas">
        <button type="button" @click="zoomIn()" aria-label="Acercar vista" class="inline-flex h-7 w-7 items-center justify-center text-black focus:outline-none focus:ring-0">+</button>
        <span class="w-[42px] text-right tabular-nums text-black" x-text="viewScale + '%'">90%</span>
        <span class="h-5 w-px bg-zinc-200"></span>
        <button type="button" @click="toggleFullscreen()" class="inline-flex p-[5px] text-black outline-none focus:ring-0" :title="isFullscreen ? 'Salir de pantalla completa' : 'Pantalla completa'" :aria-label="isFullscreen ? 'Salir de pantalla completa' : 'Ver Preguntas en pantalla completa'">
            <svg x-show="!isFullscreen" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 3H3v5M16 3h5v5M21 16v5h-5M3 16v5h5"/></svg>
            <svg x-show="isFullscreen" x-cloak class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 3v5H3M16 3v5h5M21 16h-5v5M3 16h5v5"/></svg>
        </button>
    </div>

    <div class="relative h-full w-full overflow-hidden">
    <main x-ref="scaledMain" class="flex w-full origin-top-left flex-col gap-[20px] p-[20px] sm:p-[30px] xl:p-[50px]" :style="`width:${10000 / viewScale}%;height:${10000 / viewScale}%;transform:scale(${viewScale / 100});`">
        <section class="flex shrink-0 flex-col justify-between gap-[20px] lg:flex-row lg:items-center">
            <div class="flex min-w-0 items-center gap-[15px]">
                <span class="grid h-14 w-14 shrink-0 place-items-center rounded-xl border border-[#d4d4d8] bg-white text-black">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $pageIconPath }}" /></svg>
                </span>
                <div class="min-w-0">
                    <h1 class="text-xl font-normal leading-none tracking-tight text-[#1A3A6B]">Asistente de preguntas</h1>
                    <p class="mt-[10px] leading-none text-black">Selecciona una pregunta para conocer cómo utilizar la aplicación.</p>
                </div>
            </div>

            <div class="no-print flex shrink-0 items-center gap-[20px]">
                <button type="button" wire:click="downloadPdf" wire:loading.attr="disabled" wire:target="downloadPdf" class="inline-flex items-center gap-[10px] whitespace-nowrap bg-transparent py-[15px] leading-none text-[#1A3A6B] transition-colors hover:text-black focus:outline-none focus:ring-0 disabled:cursor-wait disabled:opacity-40">
                    <svg wire:loading.remove wire:target="downloadPdf" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v4h16v-4M12 4v12m0 0 4-4m-4 4-4-4"/></svg>
                    <svg wire:loading wire:target="downloadPdf" class="h-4 w-4 shrink-0 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                    <span>Descargar PDF</span>
                </button>
                <button type="button" onclick="window.print()" class="inline-flex items-center gap-[10px] whitespace-nowrap bg-transparent py-[15px] leading-none text-[#1A3A6B] transition-colors hover:text-black focus:outline-none focus:ring-0">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10zM9 7V3h6v4" /></svg>
                    <span>Imprimir</span>
                </button>
            </div>
        </section>

        <section class="support-questions-card flex min-h-0 flex-1 flex-col rounded-xl border border-[#d4d4d8] bg-white" aria-label="Asistente de preguntas">
            <div class="support-questions-grid min-h-0 flex-1">
                {{-- Categorías --}}
                <aside class="support-questions-col" aria-label="Categorías de ayuda">
                    <div class="flex h-[85px] shrink-0 items-center gap-[20px] border-b border-[#d4d4d8] bg-zinc-100 p-[20px]">
                        <div class="min-w-0">
                            <div class="flex items-center gap-[10px]">
                                <h2 class="leading-none text-[#1A3A6B]">Categorías</h2>
                                <span class="leading-none text-[#1A3A6B]">({{ count($questionBank) }})</span>
                            </div>
                            <p class="mt-[10px] leading-none text-black">Elige un tema de ayuda</p>
                        </div>
                    </div>

                    <div x-data="backupOverlayScroll()" x-init="$nextTick(() => update())" class="relative min-h-0 flex-1">
                        <div x-ref="scroller" @scroll="update()" @mouseenter="update()" class="support-questions-scroll h-full overflow-y-auto">
                            <nav>
                                @foreach ($questionBank as $categoryKey => $categoryData)
                                    @php($isActiveCategory = $category === $categoryKey)
                                    <button
                                        wire:key="support-category-{{ $categoryKey }}"
                                        wire:click="selectCategory('{{ $categoryKey }}')"
                                        type="button"
                                        @if ($isActiveCategory) aria-current="true" @endif
                                        class="flex w-full items-center gap-[20px] border-b border-[#d4d4d8] p-[20px] text-left transition-colors focus:outline-none focus:ring-0 {{ $isActiveCategory ? 'bg-zinc-100 support-row-selected text-[#1A3A6B]' : 'bg-white text-black hover:bg-zinc-50' }}"
                                    >
                                        <span class="flex h-[20px] w-[20px] shrink-0 items-center justify-center">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $categoryIcons[$categoryData['icon'] ?? ''] ?? $questionIconPath }}" /></svg>
                                        </span>
                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate leading-[20px]">{{ $categoryData['label'] }}</span>
                                            <span class="mt-[10px] block truncate leading-[20px] text-black">{{ count($categoryData['items']) }} {{ count($categoryData['items']) === 1 ? 'pregunta' : 'preguntas' }}</span>
                                        </span>
                                    </button>
                                @endforeach
                            </nav>
                        </div>
                        <span x-show="visible" x-cloak class="support-questions-thumb" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
                    </div>
                </aside>

                {{-- Preguntas de la categoría --}}
                <section class="support-questions-col" aria-label="Preguntas disponibles">
                    <div class="flex h-[85px] shrink-0 items-center justify-between gap-[20px] border-b border-[#d4d4d8] bg-zinc-100 p-[20px]">
                        <div class="min-w-0">
                            <div class="flex items-center gap-[10px]">
                                <h2 class="truncate leading-none text-[#1A3A6B]">{{ $currentCategory['label'] ?? 'Ayuda' }}</h2>
                                <span class="shrink-0 leading-none text-[#1A3A6B]">({{ count($currentItems) }})</span>
                            </div>
                            <p class="mt-[10px] leading-none text-black">Preguntas disponibles</p>
                        </div>
                        @if ($currentCategory)
                            <button type="button" wire:click="downloadGuide" wire:loading.attr="disabled" wire:target="downloadGuide" title="Descargar la guía de {{ $currentCategory['label'] }} en PDF" class="no-print inline-flex shrink-0 items-center gap-[10px] whitespace-nowrap bg-transparent leading-none text-[#1A3A6B] transition-colors hover:text-black focus:outline-none focus:ring-0 disabled:cursor-wait disabled:opacity-40">
                                <svg wire:loading.remove wire:target="downloadGuide" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 3h7l4 4v14H7V3Zm7 0v5h4M10 12h5m-5 4h5"/></svg>
                                <svg wire:loading wire:target="downloadGuide" class="h-4 w-4 shrink-0 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                                <span>Guía PDF</span>
                            </button>
                        @endif
                    </div>

                    <div x-data="backupOverlayScroll()" x-init="$nextTick(() => update())" x-effect="$wire.category; $nextTick(() => update())" class="relative min-h-0 flex-1">
                        <div x-ref="scroller" @scroll="update()" @mouseenter="update()" class="support-questions-scroll h-full overflow-y-auto">
                            @foreach ($currentItems as $questionKey => $item)
                                @php($isSelectedQuestion = $selectedQuestion === $item['question'])
                                <button
                                    wire:key="support-question-{{ $category }}-{{ $questionKey }}"
                                    wire:click="ask('{{ $category }}', '{{ $questionKey }}')"
                                    type="button"
                                    title="{{ $item['question'] }}"
                                    class="flex w-full items-center gap-[20px] border-b border-[#d4d4d8] p-[20px] text-left transition-colors focus:outline-none focus:ring-0 {{ $isSelectedQuestion ? 'bg-zinc-100 support-row-selected text-[#1A3A6B]' : 'bg-white text-black hover:bg-zinc-50' }}"
                                >
                                    <span class="flex h-[20px] w-[20px] shrink-0 items-center justify-center">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $questionIconPath }}" /></svg>
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate leading-[20px]">{{ $item['question'] }}</span>
                                        <span class="mt-[10px] block truncate leading-[20px] text-black">{{ $item['answer'] }}</span>
                                    </span>
                                </button>
                            @endforeach
                        </div>
                        <span x-show="visible" x-cloak class="support-questions-thumb" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
                    </div>
                </section>

                {{-- Conversación --}}
                <section class="support-questions-col" aria-label="Conversación con {{ $botName }}" x-on:livewire:navigating.window="$wire.resetConversation()" x-on:pageshow.window="if ($event.persisted) $wire.resetConversation()">
                    <div class="flex h-[85px] shrink-0 items-center justify-between gap-[20px] border-b border-[#d4d4d8] bg-zinc-100 p-[20px]">
                        <div class="flex min-w-0 items-center gap-[20px]">
                            <img src="{{ $botAvatar }}" alt="{{ $botName }}" class="h-11 w-11 shrink-0 rounded-full border border-[#d4d4d8] object-cover">
                            <div class="min-w-0 whitespace-nowrap">
                                <h2 class="leading-none text-[#1A3A6B]">{{ $botName }}</h2>
                                <p class="mt-[10px] leading-none text-black">Asistente DataMID</p>
                            </div>
                        </div>
                        <div class="flex shrink-0 flex-col items-end whitespace-nowrap">
                            <p class="flex items-center gap-[10px] leading-none text-black">
                                <span class="h-[10px] w-[10px] shrink-0 rounded-full bg-emerald-500" aria-hidden="true"></span>
                                <span>Disponible</span>
                            </p>
                        </div>
                    </div>

                    {{-- pending: respuestas que todavía no se muestran; mientras tanto Sofía "escribe". --}}
                    <div
                        x-data="{
                            ...backupOverlayScroll(),
                            pending: 0,
                            typing: false,
                            timer: null,
                            scrollToLatest() { const el = this.$refs.scroller; if (el) { el.scrollTop = el.scrollHeight; this.update(); } },
                            answerVisible(index) { return index < this.$wire.conversation.length - this.pending; },
                            typingFor(index) { return this.typing && index === this.$wire.conversation.length - this.pending; },
                            pump() {
                                if (this.typing || this.pending < 1) { return; }
                                this.typing = true;
                                this.$nextTick(() => this.scrollToLatest());
                                const answer = String(this.$wire.conversation[this.$wire.conversation.length - this.pending]?.answer ?? '');
                                this.timer = setTimeout(() => {
                                    this.pending = Math.max(0, this.pending - 1);
                                    this.typing = false;
                                    this.$nextTick(() => this.scrollToLatest());
                                    this.pump();
                                }, Math.min(2400, 1000 + answer.length * 4));
                            },
                            reset() { clearTimeout(this.timer); this.pending = 0; this.typing = false; }
                        }"
                        x-init="$nextTick(() => update())"
                        x-effect="if ($wire.conversation.length === 0) reset(); pending; typing; $wire.conversation.length; $nextTick(() => update())"
                        x-on:support-answer-shown.window="pending++; pump()"
                        class="relative min-h-0 flex-1"
                    >
                        <div x-ref="scroller" @scroll="update()" @mouseenter="update()" aria-live="polite" class="support-questions-scroll h-full space-y-[20px] overflow-y-auto p-[20px]">
                            <div class="flex items-start gap-[20px]">
                                <img src="{{ $botAvatar }}" alt="{{ $botName }}" class="mt-[10px] h-11 w-11 shrink-0 rounded-full border border-[#d4d4d8] object-cover">
                                <div class="min-w-0 flex-1 rounded-xl border border-[#d4d4d8] bg-white p-[20px] leading-[25px] text-black">
                                    ¡Hola! Soy {{ $botName }}, la asistente de ayuda. Elige una de las preguntas disponibles y te explicaré cómo realizar esa acción.
                                </div>
                            </div>

                            @forelse ($conversation as $conversationIndex => $exchange)
                                <div wire:key="support-exchange-{{ $conversationIndex }}" class="space-y-[20px]">
                                    <div class="flex items-start justify-end gap-[20px]">
                                        <div class="max-w-[82%] rounded-xl border border-[#d4d4d8] bg-zinc-100 p-[20px] leading-[25px] text-[#1A3A6B]">{{ $exchange['question'] }}</div>
                                        <span class="mt-[10px] flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-[#d4d4d8] bg-zinc-100 text-[#1A3A6B]">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M20 21a8 8 0 00-16 0m8-10a4 4 0 100-8 4 4 0 000 8z" /></svg>
                                        </span>
                                    </div>

                                    <div x-show="answerVisible({{ $conversationIndex }})" class="flex items-start gap-[20px]">
                                        <img src="{{ $botAvatar }}" alt="{{ $botName }}" class="mt-[10px] h-11 w-11 shrink-0 rounded-full border border-[#d4d4d8] object-cover">
                                        <div class="min-w-0 flex-1 rounded-xl border border-[#d4d4d8] bg-white p-[20px] leading-[25px] text-black">{{ $exchange['answer'] }}</div>
                                    </div>

                                    <div x-show="typingFor({{ $conversationIndex }})" style="display: none;" class="flex items-start gap-[20px]" role="status">
                                        <img src="{{ $botAvatar }}" alt="{{ $botName }}" class="mt-[10px] h-11 w-11 shrink-0 rounded-full border border-[#d4d4d8] object-cover">
                                        <div class="rounded-xl border border-[#d4d4d8] bg-white p-[20px] text-[#1A3A6B]">
                                            <span class="flex h-[25px] items-center gap-[10px]" aria-hidden="true">
                                                <span class="support-typing-dot"></span>
                                                <span class="support-typing-dot"></span>
                                                <span class="support-typing-dot"></span>
                                            </span>
                                            <span class="sr-only">{{ $botName }} está escribiendo</span>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="flex flex-col items-center px-[20px] py-[40px] text-center">
                                    <span class="flex h-12 w-12 items-center justify-center rounded-xl border border-[#d4d4d8] bg-white text-black">
                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 2M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                    </span>
                                    <p class="mt-[20px] leading-none text-[#1A3A6B]">Esperando tu pregunta</p>
                                    <p class="mt-[10px] leading-none text-black">Selecciona una opción de la lista para ver la respuesta.</p>
                                </div>
                            @endforelse
                        </div>
                        <span x-show="visible" x-cloak class="support-questions-thumb" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
                    </div>
                </section>
            </div>

            <footer class="flex shrink-0 flex-wrap items-start justify-between gap-x-[20px] gap-y-[10px] border-t border-[#d4d4d8] bg-zinc-100 p-[20px] text-black">
                <p class="flex items-start gap-[10px] leading-[25px]">
                    <svg class="mt-[4px] h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4v8Z"/><path stroke-linecap="round" d="M8 9h8M8 13h5"/></svg>
                    <span>¿No encontraste tu respuesta? Utiliza el Chat general de Ticket para conversar con otros colaboradores.</span>
                </p>
                <p class="flex items-start gap-[10px] leading-[25px]">
                    <svg class="mt-[4px] h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span>Esta conversación es temporal y se elimina al salir de este apartado.</span>
                </p>
            </footer>
        </section>
    </main>
    </div>

    <style>
        /* Un solo tamaño y peso de letra en toda la página; solo el título principal (20px) es más grande. */
        .support-questions-page :is(h1, h2, h3, p, span, label, button, a, input) {
            font-weight: 400;
        }

        .support-questions-page :is(p, span, label, button, a, input, h2, h3) {
            font-size: 15px;
        }

        .support-questions-page :is(button, a, input):focus,
        .support-questions-page :is(button, a, input):focus-visible {
            outline: none !important;
            box-shadow: none !important;
        }

        .support-questions-page input[type="range"] {
            accent-color: #000000;
        }

        /* Tres columnas dentro de una sola tarjeta. Si la pantalla es muy angosta la tarjeta se desplaza de lado
           en lugar de apretar el texto. */
        .support-questions-card {
            overflow-x: auto;
            overflow-y: hidden;
        }

        .support-questions-grid {
            display: grid;
            min-width: 1030px;
            grid-template-columns: 240px 350px minmax(440px, 1fr);
        }

        .support-questions-col {
            display: flex;
            min-width: 0;
            min-height: 0;
            flex-direction: column;
        }

        .support-questions-col + .support-questions-col {
            border-left: 1px solid #d4d4d8;
        }

        @media (min-width: 1280px) {
            .support-questions-grid {
                min-width: 1090px;
                grid-template-columns: 260px 390px minmax(440px, 1fr);
            }
        }

        /* Scroll de las listas: una línea azul delgada, sin flechas. */
        .support-questions-scroll {
            scrollbar-width: none;
            overscroll-behavior: contain;
        }

        .support-questions-scroll::-webkit-scrollbar {
            width: 0;
            height: 0;
        }

        .support-questions-thumb {
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

        /* Tres puntos de "escribiendo". */
        .support-typing-dot {
            width: 8px;
            height: 8px;
            border-radius: 9999px;
            background: currentColor;
            animation: support-typing 1.2s infinite ease-in-out;
        }

        .support-typing-dot:nth-child(2) { animation-delay: 0.18s; }
        .support-typing-dot:nth-child(3) { animation-delay: 0.36s; }

        @keyframes support-typing {
            0%, 60%, 100% { transform: translateY(0); opacity: 0.35; }
            30% { transform: translateY(-5px); opacity: 1; }
        }

        @media (prefers-reduced-motion: reduce) {
            .support-typing-dot { animation-duration: 2.4s; }
        }

        .support-questions-page:fullscreen {
            height: 100dvh;
        }

        /* ===== Tema oscuro (misma paleta que Reportes de soporte y Notificaciones) ===== */
        html.module-dark-theme {
            --support-accent: #6aa9ff;
            --support-border: #3f3f46;
            --support-surface: #18181b;
            --support-header: #202023;
        }

        html.module-dark-theme #main-content .support-questions-page [class~="bg-white"] {
            background-color: var(--support-surface) !important;
        }

        html.module-dark-theme #main-content .support-questions-page [class~="bg-zinc-100"] {
            background-color: var(--support-header) !important;
        }

        html.module-dark-theme #main-content .support-questions-page [class~="bg-zinc-50"] {
            background-color: #1d1d21 !important;
        }

        html.module-dark-theme #main-content .support-questions-page .support-row-selected {
            background-color: rgba(106, 169, 255, 0.1) !important;
        }

        html.module-dark-theme #main-content .support-questions-page [class*="hover:bg-zinc-50"]:hover {
            background-color: #26262b !important;
        }

        html.module-dark-theme #main-content .support-questions-page [class*="hover:bg-zinc-50"]:not([class~="bg-white"]):not([class~="bg-zinc-50"]):not([class~="bg-zinc-100"]):not(:hover) {
            background-color: transparent !important;
        }

        html.module-dark-theme #main-content .support-questions-page [class*="border-[#d4d4d8]"] {
            border-color: var(--support-border) !important;
        }

        html.module-dark-theme #main-content .support-questions-page [class~="text-black"] {
            color: #e4e4e7 !important;
        }

        html.module-dark-theme #main-content .support-questions-page [class~="text-[#1A3A6B]"] {
            color: var(--support-accent) !important;
        }

        html.module-dark-theme #main-content .support-questions-page [class~="text-[#1A3A6B]"][class*="hover:text-black"]:hover {
            color: #fafafa !important;
        }

        html.module-dark-theme #main-content .support-questions-page [class~="text-[#1A3A6B]"][class*="hover:text-red-600"]:hover {
            color: #f87171 !important;
        }

        html.module-dark-theme #main-content .support-questions-page input[type="range"] {
            accent-color: var(--support-accent);
        }

        html.module-dark-theme .support-questions-col + .support-questions-col {
            border-left-color: var(--support-border);
        }

        html.module-dark-theme .support-questions-thumb {
            background: var(--support-accent);
        }

        @media print {
            #main-nav, #logo-sidebar, .no-print { display: none !important; }
            #main-content { margin-left: 0 !important; padding-top: 0 !important; }
            .support-questions-page { height: auto !important; overflow: visible !important; }
            .support-questions-page main { width: 100% !important; height: auto !important; transform: none !important; padding: 0 !important; }
            .support-questions-scroll { height: auto !important; overflow: visible !important; }
            .support-questions-thumb { display: none !important; }
        }
    </style>
</div>
