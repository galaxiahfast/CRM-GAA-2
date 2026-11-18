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
    $botName = (string) config('support.automated_user.name', 'Sofia');
    $botAvatar = asset(ltrim((string) config('support.automated_user.avatar', 'img/support/sofia-avatar.svg'), '/'));
    $currentCategory = $questionBank[$category] ?? null;
    $currentItems = $currentCategory['items'] ?? [];
    $questionsTour = [
        ['target' => 'pq-categories', 'title' => '1. Elige un tema', 'description' => 'Cada tema corresponde a un apartado del menú lateral. Elige el que te interesa y a la derecha aparecen sus preguntas.'],
        ['target' => 'pq-questions', 'title' => '2. Elige una pregunta', 'description' => 'Pulsa una pregunta de la lista. Debajo de cada una ves un adelanto de la respuesta.'],
        ['target' => 'pq-guide', 'title' => '3. Descarga la guía', 'description' => 'Con «Descargar guía PDF» guardas la guía del tema que tienes elegido: todas sus preguntas con sus pasos, listas para imprimir.'],
        ['target' => 'pq-conversation', 'title' => '4. Lee la respuesta', 'description' => 'Sofía responde en la conversación. Cada pregunta que eliges se suma aquí; la conversación es temporal y se borra al salir de este apartado.'],
    ];
@endphp

{{-- Preguntas: mismo diseño que Reloj Checador, Cronómetro, Manager Control, Gráficos generales y Chat de soporte (variables de panel,
     texto de 15px, separaciones de 20px, tarjeta con cabeceras, sin sombras ni textos grises, botones y enlaces verdes y scroll que solo
     aparece con el puntero encima). Solo cambia la presentación: las preguntas, respuestas y descargas son las mismas. --}}
<div
    data-panel
    data-productivity
    data-support-questions
    x-data="clockQuickTour(@js($questionsTour))"
    class="support-questions-page relative isolate flex h-[calc(var(--app-dvh)-123px)] min-h-[760px] w-full min-w-0 flex-col text-[15px]"
>
    <x-panel-styles />

    <div class="relative z-10 flex min-h-0 w-full min-w-0 flex-1 flex-col">
    <main class="relative z-10 mx-auto flex min-h-0 w-full min-w-0 flex-1 flex-col gap-[20px] p-[20px] sm:gap-[30px] sm:p-[30px] xl:gap-[50px] xl:px-[50px] xl:py-[50px]">
        <section class="flex shrink-0 flex-wrap items-center justify-between gap-x-[20px] gap-y-[15px]">
            <div class="min-w-0">
                <h1 class="panel-accent text-xl font-normal leading-none tracking-tight">Asistente de preguntas</h1>
                <p class="mt-[5px] leading-[30px]">Selecciona una pregunta para conocer cómo utilizar la aplicación.</p>
            </div>
            <button id="pq-guide" type="button" wire:click="downloadGuide" wire:loading.attr="disabled" wire:target="downloadGuide" title="Descargar la guía de {{ $currentCategory['label'] ?? 'Ayuda' }} en PDF" class="sq-link whitespace-nowrap leading-none focus:outline-none focus:ring-0 disabled:cursor-wait disabled:opacity-40">
                <span wire:loading.remove wire:target="downloadGuide">Descargar guía PDF</span>
                <span wire:loading wire:target="downloadGuide">Preparando…</span>
            </button>
        </section>

        <section class="support-questions-card panel-surface panel-border flex min-h-0 flex-1 flex-col overflow-hidden rounded-xl border" aria-label="Asistente de preguntas">
            <div class="support-questions-grid min-h-0 flex-1">
                {{-- Categorías --}}
                <aside class="support-questions-col" aria-label="Categorías de ayuda">
                    <header class="panel-header panel-border flex min-h-[98px] shrink-0 items-center border-b p-[20px]">
                        <div class="min-w-0">
                            <h2 class="panel-accent leading-none">Categorías ({{ count($questionBank) }})</h2>
                            <p class="sq-sub mt-[5px]">Elige un tema de ayuda</p>
                        </div>
                    </header>

                    <div id="pq-categories" data-scroll-on-hover x-data="backupOverlayScroll()" x-init="$nextTick(() => update())" class="relative min-h-0 flex-1 overflow-hidden">
                        <div x-ref="scroller" x-init="markEdgeRows($el)" @scroll="update()" @mouseenter="update()" class="panel-scroll h-full snap-y snap-mandatory overflow-y-auto">
                            <nav class="h-full">
                                @foreach ($questionBank as $categoryKey => $categoryData)
                                    @php($isActiveCategory = $category === $categoryKey)
                                    <button
                                        wire:key="support-category-{{ $categoryKey }}"
                                        wire:click="selectCategory('{{ $categoryKey }}')"
                                        type="button"
                                        data-edge-row
                                        @if ($isActiveCategory) aria-current="true" @endif
                                        class="sq-row panel-border flex w-full snap-start items-center gap-[20px] border-b px-[20px] py-[10px] text-left transition-colors focus:outline-none focus:ring-0 {{ $isActiveCategory ? 'is-selected' : '' }}"
                                        style="height:20%;min-height:80px;"
                                    >
                                        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $categoryIcons[$categoryData['icon'] ?? ''] ?? $questionIconPath }}" /></svg>
                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate leading-[30px]">{{ $categoryData['label'] }}</span>
                                            <span class="block truncate leading-[30px]">{{ count($categoryData['items']) }} {{ count($categoryData['items']) === 1 ? 'pregunta' : 'preguntas' }}</span>
                                        </span>
                                    </button>
                                @endforeach
                            </nav>
                        </div>
                        <span :class="visible ? '' : 'hidden'" class="panel-scroll-thumb support-questions-thumb" style="right: 5px;" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
                    </div>

                    <div class="panel-border shrink-0 border-t p-[20px]">
                        <button type="button" @click="startQuickTour()" class="sq-primary inline-flex min-h-[48px] w-full cursor-pointer items-center justify-center rounded-xl px-[20px] py-[15px] focus:outline-none focus:ring-0">Tutorial rápido</button>
                    </div>
                </aside>

                {{-- Preguntas de la categoría --}}
                <section class="support-questions-col" aria-label="Preguntas disponibles">
                    <header class="panel-header panel-border flex min-h-[98px] shrink-0 flex-wrap items-center justify-between gap-x-[20px] gap-y-[15px] border-b p-[20px]">
                        <div class="min-w-0">
                            <h2 class="panel-accent truncate leading-none">{{ $currentCategory['label'] ?? 'Ayuda' }} ({{ count($currentItems) }})</h2>
                            <p class="sq-sub mt-[5px]">Preguntas disponibles</p>
                        </div>
                    </header>

                    <div id="pq-questions" data-scroll-on-hover x-data="backupOverlayScroll()" x-init="$nextTick(() => update())" x-effect="$wire.category; $nextTick(() => update())" class="relative min-h-0 flex-1 overflow-hidden">
                        <div x-ref="scroller" x-init="markEdgeRows($el)" @scroll="update()" @mouseenter="update()" class="panel-scroll h-full snap-y snap-mandatory overflow-y-auto">
                            @foreach ($currentItems as $questionKey => $item)
                                @php($isSelectedQuestion = $selectedQuestion === $item['question'])
                                <button
                                    wire:key="support-question-{{ $category }}-{{ $questionKey }}"
                                    wire:click="ask('{{ $category }}', '{{ $questionKey }}')"
                                    type="button"
                                    data-edge-row
                                    title="{{ $item['question'] }}"
                                    class="sq-row panel-border flex w-full snap-start items-center gap-[20px] border-b px-[20px] py-[10px] text-left transition-colors focus:outline-none focus:ring-0 {{ $isSelectedQuestion ? 'is-selected' : '' }}"
                                    style="height:20%;min-height:80px;"
                                >
                                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $questionIconPath }}" /></svg>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate leading-[30px]">{{ $item['question'] }}</span>
                                        <span class="block truncate leading-[30px]">{{ $item['answer'] }}</span>
                                    </span>
                                </button>
                            @endforeach
                        </div>
                        <span :class="visible ? '' : 'hidden'" class="panel-scroll-thumb support-questions-thumb" style="right: 5px;" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
                    </div>

                    {{-- Pie invisible, copia exacta del botón de Categorías (mismo alto): así las filas y sus líneas quedan a la misma altura en las dos columnas --}}
                    <div class="panel-border shrink-0 border-t p-[20px]" aria-hidden="true"><span class="sq-primary invisible inline-flex min-h-[48px] w-full items-center justify-center rounded-xl px-[20px] py-[15px]">Tutorial rápido</span></div>
                </section>

                {{-- Conversación --}}
                <section class="support-questions-col" aria-label="Conversación con {{ $botName }}" x-on:livewire:navigating.window="$wire.resetConversation()" x-on:pageshow.window="if ($event.persisted) $wire.resetConversation()">
                    <header class="panel-header panel-border flex min-h-[98px] shrink-0 flex-wrap items-center justify-between gap-x-[20px] gap-y-[15px] border-b p-[20px]">
                        <div class="flex min-w-0 items-center gap-[20px]">
                            <img src="{{ $botAvatar }}" alt="{{ $botName }}" class="h-11 w-11 shrink-0 rounded-full border object-cover panel-border">
                            <div class="min-w-0 whitespace-nowrap">
                                <h2 class="panel-accent leading-none">{{ $botName }}</h2>
                                <p class="sq-sub mt-[5px]">Asistente DataMID · Disponible</p>
                            </div>
                        </div>
                    </header>

                    {{-- pending: respuestas que todavía no se muestran; mientras tanto Sofía "escribe". --}}
                    <div
                        id="pq-conversation"
                        data-scroll-on-hover
                        x-data="{
                            ...backupOverlayScroll(),
                            pending: 0,
                            typing: false,
                            timer: null,
                            greeting: false,
                            greetTimer: null,
                            scrollToLatest() { const el = this.$refs.scroller; if (el) { el.scrollTop = el.scrollHeight; this.update(); } },
                            answerVisible(index) { return index < this.$wire.conversation.length - this.pending; },
                            typingFor(index) { return this.typing && index === this.$wire.conversation.length - this.pending; },
                            pump() {
                                if (! this.greeting || this.typing || this.pending < 1) { return; }
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
                        x-init="$nextTick(() => update()); greetTimer = setTimeout(() => { greeting = true; $nextTick(() => { scrollToLatest(); pump(); }); }, 3000)"
                        x-effect="if ($wire.conversation.length === 0) reset(); pending; typing; $wire.conversation.length; $nextTick(() => update())"
                        x-on:support-answer-shown.window="pending++; pump()"
                        class="relative min-h-0 flex-1 overflow-hidden"
                    >
                        <div x-ref="scroller" @scroll="update()" @mouseenter="update()" aria-live="polite" class="panel-scroll h-full space-y-[20px] overflow-y-auto p-[20px]">
                            <div>
                                {{-- Al entrar, Sofía "escribe" unos 3 segundos y después aparece su saludo --}}
                                <div x-show="! greeting" class="flex items-start gap-[20px]" role="status">
                                    <img src="{{ $botAvatar }}" alt="{{ $botName }}" class="mt-[5px] h-11 w-11 shrink-0 rounded-full border object-cover panel-border">
                                    <div class="panel-accent mt-[5px] flex h-11 items-end pb-[5px]">
                                        <span class="flex items-center gap-[10px]" aria-hidden="true">
                                            <span class="support-typing-dot"></span>
                                            <span class="support-typing-dot"></span>
                                            <span class="support-typing-dot"></span>
                                        </span>
                                        <span class="sr-only">{{ $botName }} está escribiendo</span>
                                    </div>
                                </div>

                                <div x-show="greeting" style="display: none;" class="flex items-start gap-[20px]">
                                    <img src="{{ $botAvatar }}" alt="{{ $botName }}" class="mt-[5px] h-11 w-11 shrink-0 rounded-full border object-cover panel-border">
                                    <div class="panel-surface panel-border min-w-0 flex-1 rounded-xl border p-[20px] leading-[30px]">
                                        ¡Hola! Soy {{ $botName }}, la asistente de ayuda. Elige una de las preguntas disponibles y te explicaré cómo realizar esa acción.
                                    </div>
                                </div>
                            </div>

                            @forelse ($conversation as $conversationIndex => $exchange)
                                <div wire:key="support-exchange-{{ $conversationIndex }}" class="space-y-[20px]">
                                    <div class="flex items-start justify-end gap-[20px]">
                                        <div class="panel-subtle panel-border panel-accent max-w-[82%] rounded-xl border p-[20px] leading-[30px]">{{ $exchange['question'] }}</div>
                                        <span class="panel-subtle panel-border panel-accent mt-[5px] flex h-11 w-11 shrink-0 items-center justify-center rounded-full border">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M20 21a8 8 0 00-16 0m8-10a4 4 0 100-8 4 4 0 000 8z" /></svg>
                                        </span>
                                    </div>

                                    <div x-show="answerVisible({{ $conversationIndex }})" class="flex items-start gap-[20px]">
                                        <img src="{{ $botAvatar }}" alt="{{ $botName }}" class="mt-[5px] h-11 w-11 shrink-0 rounded-full border object-cover panel-border">
                                        <div class="panel-surface panel-border min-w-0 flex-1 rounded-xl border p-[20px] leading-[30px]">{{ $exchange['answer'] }}</div>
                                    </div>

                                    <div x-show="typingFor({{ $conversationIndex }})" style="display: none;" class="flex items-start gap-[20px]" role="status">
                                        <img src="{{ $botAvatar }}" alt="{{ $botName }}" class="mt-[5px] h-11 w-11 shrink-0 rounded-full border object-cover panel-border">
                                        <div class="panel-accent mt-[5px] flex h-11 items-end pb-[5px]">
                                            <span class="flex items-center gap-[10px]" aria-hidden="true">
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
                                    <span class="panel-surface panel-border flex h-12 w-12 items-center justify-center rounded-xl border">
                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 2M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                    </span>
                                    <p class="panel-accent mt-[20px] leading-[30px]">Esperando tu pregunta</p>
                                    <p class="leading-[30px]">Selecciona una opción de la lista para ver la respuesta.</p>
                                </div>
                            @endforelse
                        </div>
                        <span :class="visible ? '' : 'hidden'" class="panel-scroll-thumb support-questions-thumb" style="right: 5px;" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
                    </div>
                </section>
            </div>

            <footer class="panel-header panel-border flex shrink-0 flex-wrap items-start justify-between gap-x-[20px] gap-y-[10px] border-t p-[20px]">
                <p class="leading-[30px]">¿No encontraste tu respuesta? Usa el <a href="{{ route('soporte.ticket') }}" class="sq-link">Chat general de soporte</a> para conversar con otros colaboradores.</p>
                <p class="leading-[30px]">Esta conversación es temporal y se elimina al salir de este apartado.</p>
            </footer>
        </section>
    </main>
    </div>

    <template x-teleport="body">
        <div data-panel data-productivity data-support-questions x-cloak x-show="quickTourStep > 0" x-transition.opacity class="pointer-events-none fixed inset-0 z-[299999]" style="background:transparent" @click.stop @wheel.prevent @touchmove.prevent>
            <div :style="maskTopStyle" class="z-[300000]" aria-hidden="true"></div>
            <div :style="maskLeftStyle" class="z-[300000]" aria-hidden="true"></div>
            <div :style="maskRightStyle" class="z-[300000]" aria-hidden="true"></div>
            <div :style="maskBottomStyle" class="z-[300000]" aria-hidden="true"></div>
            <div :style="spotlightStyle" class="pointer-events-auto z-[300001]" aria-hidden="true"></div>
            <section :style="popoverStyle" class="panel-surface panel-border pointer-events-auto fixed left-1/2 top-1/2 z-[300002] w-[min(440px,calc(100vw-40px))] -translate-x-1/2 -translate-y-1/2 rounded-xl border p-[20px] shadow-[0_18px_55px_rgba(0,0,0,0.30)]" role="dialog" aria-modal="true" aria-labelledby="clock-tour-title">
                <p class="panel-accent leading-[30px]" x-text="quickTourStep ? 'Paso ' + quickTourStep + ' de ' + quickTourSteps.length : ''"></p>
                <h2 id="clock-tour-title" class="mt-[15px] leading-[30px]" x-text="quickTourStep ? quickTourSteps[quickTourStep - 1].title : ''"></h2>
                <p class="mt-[15px] text-justify leading-[30px]" x-text="quickTourStep ? quickTourSteps[quickTourStep - 1].description : ''"></p>
                <div class="mt-[20px] flex flex-wrap items-center justify-between gap-x-[20px] gap-y-[20px]">
                    <button type="button" @click="closeQuickTour()" class="sq-link whitespace-nowrap leading-none">Saltar recorrido</button>
                    <div class="flex items-center gap-[20px] whitespace-nowrap">
                        <button x-show="quickTourStep > 1" type="button" @click="previousQuickTourStep()" class="panel-button-secondary rounded-xl px-[20px] py-[15px] leading-none">Anterior</button>
                        <button x-ref="quickTourNext" type="button" @click="nextQuickTourStep()" class="sq-primary rounded-xl px-[20px] py-[15px] leading-none" x-text="quickTourStep === quickTourSteps.length ? 'Terminar' : 'Siguiente'"></button>
                    </div>
                </div>
            </section>
        </div>
    </template>

    <style>
        [data-support-questions] {
            --sq-green: #15803d;
            --panel-muted: var(--panel-text);
        }
        html.module-dark-theme [data-support-questions] { --sq-green: #4ade80; }
        [data-support-questions] * { font-weight: 400; }
        [data-support-questions] .sq-link { color: var(--sq-green); transition: opacity 150ms ease; }
        [data-support-questions] .sq-link:hover { opacity: .75; }
        [data-support-questions] .sq-primary {
            border: 1px solid var(--sq-green);
            background-color: color-mix(in srgb, var(--sq-green) 12%, var(--panel-surface));
            color: var(--sq-green);
            transition: background-color 150ms ease;
        }
        [data-support-questions] .sq-primary:hover { background-color: color-mix(in srgb, var(--sq-green) 20%, var(--panel-surface)); }
        /* Texto que puede partirse en varias líneas: 20px de espacio entre una línea y la siguiente */
        [data-support-questions] p.sq-sub { line-height: 35px; margin-bottom: -10px; }
        [data-support-questions] p { line-height: 30px; }
        [data-support-questions] :is(button, a):focus,
        [data-support-questions] :is(button, a):focus-visible { outline: none !important; box-shadow: none !important; }

        /* Filas de las listas: seleccionada en azul sobre fondo suave; la que toca el borde de abajo no dibuja su línea (la dibuja
           el pie), así nunca se ve doble. */
        [data-support-questions] .sq-row { background-color: var(--panel-surface); color: var(--panel-text); }
        [data-support-questions] .sq-row:hover { background-color: var(--panel-hover); }
        [data-support-questions] .sq-row.is-selected { background-color: var(--panel-subtle); color: var(--panel-accent); }
        [data-support-questions] [data-edge-row][data-edge] { border-bottom-color: transparent !important; }

        /* Tres columnas dentro de una sola tarjeta. Si la pantalla es muy angosta la tarjeta se desplaza de lado
           en lugar de apretar el texto. */
        .support-questions-card { overflow-x: auto; overflow-y: hidden; }
        .support-questions-grid {
            display: grid;
            min-width: 1090px;
            grid-template-columns: 260px 390px minmax(440px, 1fr);
            grid-template-rows: minmax(0, 1fr);
        }
        .support-questions-col { display: flex; min-width: 0; min-height: 0; flex-direction: column; }
        .support-questions-col + .support-questions-col { border-left: 1px solid var(--panel-border); }

        /* Tres puntos de "escribiendo". */
        .support-typing-dot {
            width: 6px;
            height: 6px;
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
        @media (prefers-reduced-motion: reduce) { .support-typing-dot { animation-duration: 2.4s; } }

        @media print {
            #main-nav, #logo-sidebar, .no-print { display: none !important; }
            #main-content { margin-left: 0 !important; padding-top: 0 !important; }
            .support-questions-page { min-height: 0 !important; }
            .support-questions-page main { padding: 0 !important; }
            .panel-scroll { height: auto !important; overflow: visible !important; }
            .support-questions-thumb { display: none !important; }
        }
    </style>
</div>
