@php
    $movementContexts = [
        'recepcion' => [
            'summary' => 'Documenta el equipo que una persona o empresa entrega a DataMID.',
            'responsible' => 'Cliente, empresa o persona que entrega',
            'contact' => 'Contacto de quien entrega',
            'equipmentTitle' => 'Equipo recibido',
            'equipmentHelp' => 'Identifica lo que ingresa y deja constancia de las condiciones en que se recibe.',
            'observations' => 'Condiciones al recibirlo',
            'observationsPlaceholder' => 'Describe golpes, rayones, fallas o detalles visibles al recibirlo.',
            'accessories' => 'Accesorios recibidos',
            'accessoriesPlaceholder' => 'Ej. cargador, cable USB, funda o adaptador.',
            'photoTitle' => 'Evidencia de recepción',
            'photoHelp' => 'Fotografía opcional del equipo tal como fue recibido.',
            'action' => 'Generar recepción',
            'pdfHelp' => 'Se generará la constancia de recepción para DataMID y el cliente.',
        ],
        'entrega' => [
            'summary' => 'Documenta el equipo que DataMID entrega o devuelve a una persona o empresa.',
            'responsible' => 'Cliente, empresa o persona que recibe',
            'contact' => 'Contacto de quien recibe',
            'equipmentTitle' => 'Equipo entregado',
            'equipmentHelp' => 'Identifica lo que sale y deja constancia de las condiciones en que se entrega.',
            'observations' => 'Condiciones al entregarlo',
            'observationsPlaceholder' => 'Describe el estado, fallas o detalles visibles al entregarlo.',
            'accessories' => 'Accesorios entregados',
            'accessoriesPlaceholder' => 'Ej. cargador, cable USB, funda o adaptador.',
            'photoTitle' => 'Evidencia de entrega',
            'photoHelp' => 'Fotografía opcional del equipo al momento de la entrega.',
            'action' => 'Generar entrega',
            'pdfHelp' => 'Se generará la constancia de entrega para DataMID y quien recibe.',
        ],
        'prestamo' => [
            'summary' => 'Registra una asignación temporal y quién queda responsable del equipo.',
            'responsible' => 'Persona, cliente o área responsable',
            'contact' => 'Contacto de la persona responsable',
            'equipmentTitle' => 'Equipo en préstamo',
            'equipmentHelp' => 'Identifica el equipo prestado y deja constancia de su estado antes de asignarlo.',
            'observations' => 'Condiciones del préstamo',
            'observationsPlaceholder' => 'Describe el estado del equipo y cualquier acuerdo o detalle del préstamo.',
            'accessories' => 'Accesorios incluidos',
            'accessoriesPlaceholder' => 'Ej. cargador, cable USB, funda o adaptador.',
            'photoTitle' => 'Evidencia del préstamo',
            'photoHelp' => 'Fotografía opcional del equipo antes de entregarlo en préstamo.',
            'action' => 'Generar préstamo',
            'pdfHelp' => 'Se generará la constancia de préstamo para DataMID y la persona responsable.',
        ],
        'compra' => [
            'summary' => 'Registra un equipo adquirido recientemente y los datos de su proveedor.',
            'responsible' => 'Proveedor o responsable de la compra',
            'contact' => 'Contacto del proveedor',
            'equipmentTitle' => 'Equipo adquirido',
            'equipmentHelp' => 'Identifica la nueva adquisición y documenta las condiciones en que se recibió.',
            'observations' => 'Condiciones de la compra',
            'observationsPlaceholder' => 'Describe el estado de recepción, garantía o cualquier detalle de la compra.',
            'accessories' => 'Accesorios adquiridos',
            'accessoriesPlaceholder' => 'Ej. cargador, cable USB, funda o adaptador.',
            'photoTitle' => 'Evidencia de compra',
            'photoHelp' => 'Fotografía opcional del equipo adquirido y recibido.',
            'action' => 'Generar compra',
            'pdfHelp' => 'Se generará la constancia de compra para el control interno de DataMID.',
        ],
    ];
@endphp

<div
    data-clock-particle-network
    class="support-monochrome relative isolate min-h-[calc(100dvh-90px)] w-full overflow-hidden bg-[#F3F3F3] text-[15px] text-zinc-700"
    x-data="{ viewScale: 100, isFullscreen: false, init() { const saved = Number(localStorage.getItem('delivery-notes-view-scale')); if (saved >= 70 && saved <= 100) this.viewScale = saved; }, saveScale() { localStorage.setItem('delivery-notes-view-scale', String(this.viewScale)); }, async toggleFullscreen(container) { if (document.fullscreenElement === container) return document.exitFullscreen(); if (document.fullscreenElement) await document.exitFullscreen(); await container.requestFullscreen(); } }"
    @fullscreenchange.window="isFullscreen = document.fullscreenElement === $root"
>
    <canvas wire:ignore data-clock-network-canvas class="pointer-events-none absolute inset-0 z-0 h-full w-full opacity-[0.55]" aria-hidden="true"></canvas>

    <div class="no-print absolute left-1/2 top-[20px] z-30 flex -translate-x-1/2 items-center gap-[10px] rounded-xl border border-zinc-200 bg-white/95 px-[15px] py-[10px] shadow-[0_8px_24px_rgba(0,0,0,0.10)] backdrop-blur-sm">
        <input type="range" min="70" max="100" step="5" x-model.number="viewScale" @input="saveScale()" class="h-1.5 w-[130px] cursor-pointer accent-black" aria-label="Ajustar tamaño de la vista">
        <span class="w-[42px] text-right font-semibold tabular-nums text-black" x-text="viewScale + '%'">100%</span>
        <span class="h-5 w-px bg-zinc-200"></span>
        <button type="button" @click="toggleFullscreen($root)" class="rounded-lg p-[5px] text-black transition hover:bg-zinc-100" :title="isFullscreen ? 'Salir de pantalla completa' : 'Pantalla completa'">
            <svg x-show="!isFullscreen" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 3H3v5M16 3h5v5M21 16v5h-5M3 16v5h5"/></svg>
            <svg x-show="isFullscreen" x-cloak class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 3v5H3M16 3v5h5M21 16h-5v5M3 16h5v5"/></svg>
        </button>
    </div>

    <div class="relative z-10 min-h-[calc(100dvh-90px)] min-w-[1180px] origin-top" :style="`width: ${10000 / viewScale}%; margin-left: ${(100 - (10000 / viewScale)) / 2}%; transform: scale(${viewScale / 100});`">
        <header class="flex items-center justify-between gap-[20px] whitespace-nowrap border-b border-zinc-200 bg-white/75 p-[50px]">
            <div class="flex items-center gap-[15px] text-zinc-500">
                <span class="font-medium">Soporte</span>
                <span class="text-gray-300">&gt;</span>
                <span class="font-medium">Centro de ayuda</span>
                <span class="text-gray-300">&gt;</span>
                <span class="font-semibold text-black">Hoja de entrega</span>
            </div>
            <div class="flex items-center gap-[10px] rounded-xl border border-zinc-200 bg-white/80 p-[5px]">
                <button type="button" wire:click="showCreate" class="rounded-lg px-[15px] py-[10px] font-medium transition {{ $activeView === 'create' ? 'bg-black text-white' : 'text-zinc-600 hover:bg-zinc-100' }}">Nueva hoja</button>
                <button type="button" wire:click="showHistory" class="rounded-lg px-[15px] py-[10px] font-medium transition {{ $activeView === 'history' ? 'bg-black text-white' : 'text-zinc-600 hover:bg-zinc-100' }}">Historial</button>
            </div>
        </header>

        <main class="mx-auto w-full space-y-[20px] p-[50px]">
            <section class="flex items-center justify-between gap-[20px]">
                <div class="flex items-center gap-[20px]">
                    <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl border border-zinc-200 bg-white/80 text-black">
                        <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6M8 3h8l4 4v14H4V3h4Zm7 0v5h5"/></svg>
                    </span>
                    <div>
                        <h1 class="text-xl font-semibold text-black">Hojas de entrega y recepción</h1>
                        <p class="mt-[5px] text-zinc-500">Registra equipos, conserva la evidencia y genera las copias para DataMID y el cliente.</p>
                    </div>
                </div>
                <div class="flex items-center gap-[10px] text-zinc-500">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                    Acceso exclusivo para administradores
                </div>
            </section>

            @if ($successMessage)
                <div class="flex items-center justify-between rounded-xl border border-emerald-200 bg-emerald-50/90 p-[20px] text-emerald-800" role="status">
                    <span>{{ $successMessage }}</span>
                    <button type="button" wire:click="$set('successMessage', '')" class="font-semibold">Cerrar</button>
                </div>
            @endif

            @error('form')
                <div class="rounded-xl border border-red-200 bg-red-50/90 p-[20px] text-red-700" role="alert">{{ $message }}</div>
            @enderror

            @if ($activeView === 'create')
                <form
                    wire:submit="save"
                    class="space-y-[20px]"
                    x-data="{
                        withoutSerial: @entangle('withoutSerial'),
                        condition: @entangle('physicalCondition'),
                        movement: @entangle('movementType'),
                        contexts: @js($movementContexts),
                        get context() { return this.contexts[this.movement] || this.contexts.recepcion; }
                    }"
                >
                    <section data-form-order="movement" class="rounded-xl border border-zinc-200 bg-white/80 p-[25px] shadow-[0_8px_24px_rgba(0,0,0,0.05)]">
                        <header class="mb-[20px] flex items-center gap-[15px] border-b border-zinc-200 pb-[20px]">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-black font-semibold text-white">1</span>
                            <div><h2 class="font-semibold text-black">Tipo de movimiento</h2><p class="mt-[5px] text-zinc-500">Indica qué ocurre con el equipo.</p></div>
                        </header>

                        <div class="grid grid-cols-4 gap-[10px]">
                            @foreach ([
                                'recepcion' => ['Recepción', 'Equipo que ingresa a DataMID', 'M12 4v16m0 0-5-5m5 5 5-5'],
                                'entrega' => ['Entrega', 'Equipo que se devuelve o entrega', 'M12 20V4m0 0-5 5m5-5 5 5'],
                                'prestamo' => ['Préstamo', 'Asignación temporal', 'M7 7h10m0 0-3-3m3 3-3 3M17 17H7m0 0 3-3m-3 3 3 3'],
                                'compra' => ['Compra', 'Equipo adquirido recientemente', 'M12 5v14m-7-7h14'],
                            ] as $value => [$label, $description, $path])
                                <label class="cursor-pointer">
                                    <input type="radio" x-model="movement" value="{{ $value }}" class="peer sr-only">
                                    <span class="flex min-h-[92px] items-center gap-[15px] rounded-xl border border-zinc-200 bg-white p-[15px] transition peer-checked:border-black peer-checked:bg-zinc-100">
                                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-zinc-200 bg-white"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $path }}"/></svg></span>
                                        <span><strong class="block text-black">{{ $label }}</strong><small class="mt-[5px] block text-zinc-500">{{ $description }}</small></span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        @error('movementType') <p class="mt-[10px] text-red-600">{{ $message }}</p> @enderror

                        <div class="mt-[15px] flex items-start gap-[12px] rounded-xl border border-zinc-200 bg-zinc-50 px-[15px] py-[12px]">
                            <span class="mt-[2px] flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-black text-[12px] font-semibold text-white">i</span>
                            <p class="leading-relaxed text-zinc-600" x-text="context.summary">Documenta el equipo que una persona o empresa entrega a DataMID.</p>
                        </div>
                    </section>

                    <section class="rounded-xl border border-zinc-200 bg-white/80 p-[25px] shadow-[0_8px_24px_rgba(0,0,0,0.05)]">
                        <header class="mb-[20px] flex items-center gap-[15px] border-b border-zinc-200 pb-[20px]">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-black font-semibold text-white">2</span>
                            <div><h2 class="font-semibold text-black">Cliente y entrega</h2><p class="mt-[5px] text-zinc-500">Relaciona el movimiento con un cliente y con la persona de DataMID que entrega.</p></div>
                        </header>

                        <div data-form-order="client" class="relative" x-data="{ active: -1, options() { return [...this.$root.querySelectorAll('[data-client-option]')]; }, move(step) { const total = this.options().length; if (total) this.active = (this.active + step + total) % total; }, choose() { this.options()[this.active]?.click(); } }" @click.outside="$wire.set('showClientDropdown', false)">
                            <label class="block"><span class="mb-[10px] block font-medium text-black">Cliente *</span><input wire:model.live.debounce.300ms="customerName" wire:focus="openClientSuggestions" type="text" maxlength="120" autocomplete="off" role="combobox" aria-autocomplete="list" :aria-expanded="$wire.showClientDropdown ? 'true' : 'false'" @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)" @keydown.enter.prevent="choose()" @keydown.escape="$wire.set('showClientDropdown', false)" class="w-full rounded-xl border-zinc-300 bg-white px-[15px] py-[12px] outline-none transition focus:border-black focus:ring-0" placeholder="Busca por nombre, razón social o RFC"></label>
                            <span wire:loading wire:target="customerName,openClientSuggestions" class="absolute right-[15px] top-[48px] text-[12px] text-zinc-500">Buscando...</span>
                            @if ($showClientDropdown)
                                <div class="absolute z-40 mt-[6px] max-h-[280px] w-full overflow-y-auto rounded-xl border border-zinc-200 bg-white p-[5px] shadow-[0_12px_30px_rgba(0,0,0,0.14)]" role="listbox">
                                    @forelse ($customerSuggestions as $index => $suggestion)
                                        <button data-client-option type="button" wire:key="delivery-client-suggestion-{{ $suggestion['id'] }}" wire:click="selectCustomer({{ $suggestion['id'] }})" :class="active === {{ $index }} ? 'bg-zinc-100' : ''" class="flex w-full items-center justify-between gap-[15px] rounded-lg px-[12px] py-[10px] text-left hover:bg-zinc-100" role="option"><span><strong class="block text-black">{{ $suggestion['name'] }}</strong><small class="mt-[3px] block text-zinc-500">RFC: {{ $suggestion['rfc'] ?: 'No registrado' }}</small></span><span class="shrink-0 font-mono text-[12px] text-zinc-400">ID {{ $suggestion['id'] }}</span></button>
                                    @empty
                                        <p class="px-[12px] py-[15px] text-zinc-500">Sin coincidencias — puedes escribir un valor nuevo.</p>
                                    @endforelse
                                </div>
                            @endif
                            @error('customerName') <span class="mt-[5px] block text-red-600">{{ $message }}</span> @enderror
                            @error('customerId') <span class="mt-[5px] block text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <div data-form-order="contact" class="relative mt-[20px]" x-data="{ active: -1, options() { return [...this.$root.querySelectorAll('[data-contact-option]')]; }, move(step) { const total = this.options().length; if (total) this.active = (this.active + step + total) % total; }, choose() { this.options()[this.active]?.click(); } }" @click.outside="$wire.set('showContactDropdown', false)">
                            <label class="block"><span class="mb-[10px] flex min-h-[24px] items-center justify-between gap-[10px] font-medium text-black"><span>Contacto</span><small class="rounded-full bg-zinc-100 px-[8px] py-[4px] text-[11px] font-medium text-zinc-500">Opcional</small></span><input wire:model.live.debounce.300ms="customerContact" wire:focus="openContactSuggestions" type="text" maxlength="120" autocomplete="off" role="combobox" aria-autocomplete="list" :aria-expanded="$wire.showContactDropdown ? 'true' : 'false'" @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)" @keydown.enter.prevent="choose()" @keydown.escape="$wire.set('showContactDropdown', false)" class="w-full rounded-xl border-zinc-300 bg-white px-[15px] py-[12px] outline-none transition focus:border-black focus:ring-0" placeholder="Correo o teléfono; también puedes escribir uno nuevo"></label>
                            <span wire:loading wire:target="customerContact,openContactSuggestions" class="absolute right-[15px] top-[48px] text-[12px] text-zinc-500">Buscando...</span>
                            @if ($showContactDropdown)
                                <div class="absolute z-40 mt-[6px] max-h-[230px] w-full overflow-y-auto rounded-xl border border-zinc-200 bg-white p-[5px] shadow-[0_12px_30px_rgba(0,0,0,0.14)]" role="listbox">
                                    @forelse ($contactSuggestions as $index => $contact)
                                        <button data-contact-option type="button" wire:key="delivery-contact-suggestion-{{ md5($contact) }}" wire:click="selectContactSuggestion({{ $index }})" :class="active === {{ $index }} ? 'bg-zinc-100' : ''" class="block w-full rounded-lg px-[12px] py-[10px] text-left font-medium text-black hover:bg-zinc-100" role="option">{{ $contact }}</button>
                                    @empty
                                        <p class="px-[12px] py-[15px] text-zinc-500">Sin coincidencias — puedes escribir un valor nuevo.</p>
                                    @endforelse
                                </div>
                            @endif
                            @error('customerContact') <span class="mt-[5px] block text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <label data-form-order="delivered-by" class="mt-[20px] block"><span class="mb-[10px] block font-medium text-black">Quién entrega *</span><select wire:model="deliveredBy" class="w-full rounded-xl border-zinc-300 bg-white px-[15px] py-[12px] focus:border-black focus:ring-0">@foreach (\App\Livewire\Support\EquipmentDeliveryNotes::DELIVERERS as $deliverer)<option value="{{ $deliverer }}">{{ $deliverer }}</option>@endforeach</select>@error('deliveredBy') <span class="mt-[5px] block text-red-600">{{ $message }}</span> @enderror</label>
                    </section>

                    <section data-form-order="autofill" class="rounded-xl border border-zinc-200 bg-white/80 p-[25px] shadow-[0_8px_24px_rgba(0,0,0,0.05)]">
                        <header class="mb-[20px] flex items-center gap-[15px] border-b border-zinc-200 pb-[20px]"><span class="flex h-10 w-10 items-center justify-center rounded-xl bg-black font-semibold text-white">3</span><div><h2 class="font-semibold text-black">Identificación automática del equipo</h2><p class="mt-[5px] text-zinc-500">Busca equipos registrados, interpreta texto de una etiqueta o toma una fotografía.</p></div></header>

                        <div class="relative" x-data="{ active: -1, options() { return [...this.$root.querySelectorAll('[data-equipment-option]')]; }, move(step) { const total = this.options().length; if (total) this.active = (this.active + step + total) % total; }, choose() { this.options()[this.active]?.click(); } }" @click.outside="$wire.set('showEquipmentDropdown', false)">
                            <div class="grid grid-cols-[minmax(0,1fr)_auto] gap-[10px]">
                                <input wire:model.live.debounce.300ms="autofillInput" wire:focus="openEquipmentSuggestions" type="text" maxlength="1000" autocomplete="off" role="combobox" aria-autocomplete="list" :aria-expanded="$wire.showEquipmentDropdown ? 'true' : 'false'" @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)" @keydown.enter.prevent="choose()" @keydown.escape="$wire.set('showEquipmentDropdown', false)" class="w-full rounded-xl border-zinc-300 bg-white px-[15px] py-[12px] outline-none transition focus:border-black focus:ring-0" placeholder="Busca por marca, modelo, tipo o número de serie">
                                <button type="button" wire:click="autofillFromText" wire:loading.attr="disabled" wire:target="autofillFromText" class="inline-flex min-w-[155px] items-center justify-center gap-[8px] rounded-xl border border-black bg-white px-[18px] py-[12px] font-semibold text-black transition hover:bg-zinc-100 disabled:cursor-wait disabled:opacity-60"><span wire:loading.remove wire:target="autofillFromText">Autocompletar</span><span wire:loading wire:target="autofillFromText">Analizando...</span></button>
                            </div>
                            <span wire:loading wire:target="autofillInput,openEquipmentSuggestions" class="absolute right-[180px] top-[14px] text-[12px] text-zinc-500">Buscando...</span>
                            @if ($showEquipmentDropdown)
                                <div class="absolute z-40 mt-[6px] max-h-[280px] w-[calc(100%-165px)] overflow-y-auto rounded-xl border border-zinc-200 bg-white p-[5px] shadow-[0_12px_30px_rgba(0,0,0,0.14)]" role="listbox">
                                    @forelse ($equipmentSuggestions as $index => $suggestion)
                                        <button data-equipment-option type="button" wire:key="delivery-equipment-suggestion-{{ $suggestion['source'] }}-{{ $suggestion['id'] }}" wire:click="selectEquipmentSuggestion('{{ $suggestion['source'] }}', {{ $suggestion['id'] }})" :class="active === {{ $index }} ? 'bg-zinc-100' : ''" class="flex w-full items-center justify-between gap-[15px] rounded-lg px-[12px] py-[10px] text-left hover:bg-zinc-100" role="option"><span><strong class="block text-black">{{ $suggestion['title'] ?: $suggestion['type'] }}</strong><small class="mt-[3px] block text-zinc-500">@if ($suggestion['serial']) Serie {{ $suggestion['serial'] }} · @endif{{ $suggestion['type'] }}</small></span><span class="shrink-0 rounded-full bg-zinc-100 px-[8px] py-[4px] text-[11px] text-zinc-500">{{ $suggestion['folio'] }}</span></button>
                                    @empty
                                        <p class="px-[12px] py-[15px] text-zinc-500">Sin coincidencias — puedes escribir un valor nuevo.</p>
                                    @endforelse
                                </div>
                            @endif
                        </div>
                        @error('autofillInput') <span class="mt-[7px] block text-red-600">{{ $message }}</span> @enderror
                        <p class="mt-[8px] text-[13px] text-zinc-500">Las sugerencias combinan el catálogo local de equipos con las hojas anteriores. El OCR mantiene la fotografía dentro del servidor.</p>

                        <div class="mt-[20px]"><div class="mb-[10px] flex items-center justify-between"><span class="font-medium text-black" x-text="context.photoTitle">Evidencia de recepción</span><small class="rounded-full bg-zinc-100 px-[8px] py-[4px] text-[11px] font-medium text-zinc-500">Opcional</small></div><label class="flex min-h-[150px] cursor-pointer items-center justify-center rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-[20px] text-center transition hover:border-black hover:bg-zinc-100"><input wire:model="photo" type="file" accept="image/jpeg,image/png,image/webp" capture="environment" class="sr-only">@if ($photo)<div class="flex items-center gap-[20px]"><img src="{{ $photo->temporaryUrl() }}" alt="Vista previa" class="h-24 w-32 rounded-xl object-cover"><div class="text-left"><strong class="block text-black">Fotografía seleccionada</strong><span class="mt-[5px] block text-zinc-500">La etiqueta se analiza automáticamente.</span><div class="mt-[10px] flex items-center gap-[15px]"><button type="button" wire:click.stop.prevent="autofillFromPhoto" class="font-medium text-black">Analizar de nuevo</button><button type="button" wire:click.stop.prevent="$set('photo', null)" class="font-medium text-red-600">Quitar fotografía</button></div></div></div>@else<div><svg class="mx-auto h-9 w-9 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M3 7h4l2-2h6l2 2h4v12H3V7Zm9 9a4 4 0 100-8 4 4 0 000 8Z"/></svg><strong class="mt-[10px] block text-black">Tomar o subir fotografía</strong><span class="mt-[5px] block text-zinc-500">JPEG, PNG o WebP, máximo 10 MB.</span></div>@endif</label><div wire:loading wire:target="photo,autofillFromPhoto" class="mt-[10px] text-zinc-500">Leyendo etiqueta y buscando datos del equipo...</div>@error('photo') <span class="mt-[10px] block text-red-600">{{ $message }}</span> @enderror</div>

                        @if ($autofillMessage)
                            <div class="mb-[20px] rounded-xl border border-emerald-200 bg-emerald-50 px-[15px] py-[12px] text-emerald-800" role="status">{{ $autofillMessage }}</div>
                        @endif
                        @if ($autofillWarning)
                            <div class="mb-[20px] rounded-xl border border-amber-200 bg-amber-50 px-[15px] py-[12px] text-amber-900" role="status">{{ $autofillWarning }}</div>
                        @endif
                        @if ($autofillRecognizedText)
                            <details class="mb-[20px] rounded-xl border border-zinc-200 bg-white px-[15px] py-[12px]"><summary class="cursor-pointer font-medium text-black">Ver texto detectado</summary><p class="mt-[10px] whitespace-pre-line text-[13px] leading-relaxed text-zinc-600">{{ $autofillRecognizedText }}</p></details>
                        @endif
                    </section>

                    <section data-form-order="equipment" class="rounded-xl border border-zinc-200 bg-white/80 p-[25px] shadow-[0_8px_24px_rgba(0,0,0,0.05)]">
                        <header class="mb-[20px] flex items-center gap-[15px] border-b border-zinc-200 pb-[20px]"><span class="flex h-10 w-10 items-center justify-center rounded-xl bg-black font-semibold text-white">4</span><div><h2 class="font-semibold text-black" x-text="context.equipmentTitle">Equipo recibido</h2><p class="mt-[5px] text-zinc-500" x-text="context.equipmentHelp">Identifica lo que ingresa y deja constancia de las condiciones en que se recibe.</p></div></header>
                        <div class="grid grid-cols-3 gap-[20px]">
                            <label class="block"><span class="mb-[10px] block font-medium text-black">Tipo de equipo *</span><select wire:model="equipmentType" class="w-full rounded-xl border-zinc-300 bg-white px-[15px] py-[12px] focus:border-black focus:ring-0"><option value="">Selecciona una opción</option>@foreach (['Laptop', 'PC de escritorio', 'Impresora', 'Monitor', 'Servidor', 'Tablet', 'Teléfono', 'Otro'] as $type)<option value="{{ $type }}">{{ $type }}</option>@endforeach</select>@error('equipmentType') <span class="mt-[5px] block text-red-600">{{ $message }}</span> @enderror</label>
                            <div class="relative" x-data="{ active: -1, options() { return [...this.$root.querySelectorAll('[data-field-option]')]; }, move(step) { const total = this.options().length; if (total) this.active = (this.active + step + total) % total; }, choose() { this.options()[this.active]?.click(); } }" @click.outside="$wire.set('showBrandDropdown', false)"><label class="block"><span class="mb-[10px] flex min-h-[24px] items-center justify-between gap-[10px] font-medium text-black"><span>Marca</span><small class="shrink-0 rounded-full bg-zinc-100 px-[8px] py-[4px] text-[11px] font-medium text-zinc-500">Opcional</small></span><input wire:model.live.debounce.300ms="brand" wire:focus="openEquipmentFieldSuggestions('brand')" type="text" maxlength="100" autocomplete="off" @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)" @keydown.enter.prevent="choose()" @keydown.escape="$wire.set('showBrandDropdown', false)" class="w-full rounded-xl border-zinc-300 bg-white px-[15px] py-[12px] focus:border-black focus:ring-0"></label>@include('livewire.support.partials.equipment-field-suggestions', ['field' => 'brand', 'suggestions' => $brandSuggestions, 'show' => $showBrandDropdown])@error('brand') <span class="mt-[5px] block text-red-600">{{ $message }}</span> @enderror</div>
                            <div class="relative" x-data="{ active: -1, options() { return [...this.$root.querySelectorAll('[data-field-option]')]; }, move(step) { const total = this.options().length; if (total) this.active = (this.active + step + total) % total; }, choose() { this.options()[this.active]?.click(); } }" @click.outside="$wire.set('showModelDropdown', false)"><label class="block"><span class="mb-[10px] block font-medium text-black">Modelo *</span><input wire:model.live.debounce.300ms="model" wire:focus="openEquipmentFieldSuggestions('model')" type="text" maxlength="100" autocomplete="off" @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)" @keydown.enter.prevent="choose()" @keydown.escape="$wire.set('showModelDropdown', false)" class="w-full rounded-xl border-zinc-300 bg-white px-[15px] py-[12px] focus:border-black focus:ring-0"></label>@include('livewire.support.partials.equipment-field-suggestions', ['field' => 'model', 'suggestions' => $modelSuggestions, 'show' => $showModelDropdown])@error('model') <span class="mt-[5px] block text-red-600">{{ $message }}</span> @enderror</div>
                        </div>

                        <div class="mt-[20px] grid grid-cols-[minmax(0,1fr)_auto] items-end gap-[20px]">
                            <div class="relative" x-data="{ active: -1, options() { return [...this.$root.querySelectorAll('[data-field-option]')]; }, move(step) { const total = this.options().length; if (total) this.active = (this.active + step + total) % total; }, choose() { this.options()[this.active]?.click(); } }" @click.outside="$wire.set('showSerialDropdown', false)"><label class="block"><span class="mb-[10px] block font-medium text-black">Número de serie *</span><input wire:model.live.debounce.300ms="serialNumber" wire:focus="openEquipmentFieldSuggestions('serial')" type="text" maxlength="100" autocomplete="off" :disabled="withoutSerial" :class="withoutSerial ? 'bg-zinc-100 text-zinc-400' : 'bg-white'" @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)" @keydown.enter.prevent="choose()" @keydown.escape="$wire.set('showSerialDropdown', false)" class="w-full rounded-xl border-zinc-300 px-[15px] py-[12px] uppercase focus:border-black focus:ring-0"></label>@include('livewire.support.partials.equipment-field-suggestions', ['field' => 'serial', 'suggestions' => $serialSuggestions, 'show' => $showSerialDropdown])@error('serialNumber') <span class="mt-[5px] block text-red-600">{{ $message }}</span> @enderror</div>
                            <label class="mb-[2px] flex cursor-pointer items-center gap-[10px] rounded-xl border border-zinc-200 bg-zinc-50 px-[15px] py-[12px]"><input wire:model.live="withoutSerial" type="checkbox" class="rounded border-zinc-300 text-black focus:ring-black"><span class="font-medium text-black">Sin número de serie</span></label>
                        </div>

                        <div class="relative mt-[20px]" x-data="{ active: -1, options() { return [...this.$root.querySelectorAll('[data-field-option]')]; }, move(step) { const total = this.options().length; if (total) this.active = (this.active + step + total) % total; }, choose() { this.options()[this.active]?.click(); } }" @click.outside="$wire.set('showAccessoryDropdown', false)"><label class="block"><span class="mb-[10px] flex min-h-[24px] items-center justify-between gap-[10px] font-medium text-black"><span x-text="context.accessories">Accesorios recibidos</span><span class="flex items-center gap-[8px]"><small class="rounded-full bg-zinc-100 px-[8px] py-[4px] text-[11px] font-medium text-zinc-500">Opcional</small><small class="font-normal text-zinc-400">{{ mb_strlen($accessories) }}/300</small></span></span><textarea wire:model.live.debounce.300ms="accessories" wire:focus="openEquipmentFieldSuggestions('accessories')" rows="3" maxlength="300" autocomplete="off" @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)" @keydown.enter.prevent="choose()" @keydown.escape="$wire.set('showAccessoryDropdown', false)" class="w-full rounded-xl border-zinc-300 bg-white px-[15px] py-[12px] focus:border-black focus:ring-0" :placeholder="context.accessoriesPlaceholder" placeholder="Ej. cargador, cable USB, funda o adaptador."></textarea></label>@include('livewire.support.partials.equipment-field-suggestions', ['field' => 'accessories', 'suggestions' => $accessorySuggestions, 'show' => $showAccessoryDropdown])@error('accessories') <span class="mt-[5px] block text-red-600">{{ $message }}</span> @enderror</div>
                    </section>

                    <section data-form-order="conditions" class="rounded-xl border border-zinc-200 bg-white/80 p-[25px] shadow-[0_8px_24px_rgba(0,0,0,0.05)]">
                        <header class="mb-[20px] flex items-center gap-[15px] border-b border-zinc-200 pb-[20px]"><span class="flex h-10 w-10 items-center justify-center rounded-xl bg-black font-semibold text-white">5</span><div><h2 class="font-semibold text-black">Condiciones y explicación</h2><p class="mt-[5px] text-zinc-500">Describe el estado físico y cualquier detalle importante.</p></div></header>
                        <fieldset class="mt-[20px]"><legend class="mb-[10px] font-medium text-black">Estado físico *</legend><div class="grid grid-cols-3 overflow-hidden rounded-xl border border-zinc-200 bg-zinc-50">@foreach ($conditions as $value => $label)<label class="cursor-pointer border-r border-zinc-200 last:border-r-0"><input type="radio" wire:model.live="physicalCondition" value="{{ $value }}" class="peer sr-only"><span class="flex min-h-[46px] items-center justify-center px-[15px] font-medium transition peer-checked:bg-black peer-checked:text-white">{{ $label }}</span></label>@endforeach</div>@error('physicalCondition') <span class="mt-[5px] block text-red-600">{{ $message }}</span> @enderror</fieldset>

                        <div class="mt-[20px]">
                            <label class="block"><span class="mb-[10px] flex min-h-[24px] items-center justify-between gap-[10px] font-medium text-black"><span x-text="context.observations">Condiciones al recibirlo</span><span class="flex items-center gap-[8px]"><small class="rounded-full px-[8px] py-[4px] text-[11px] font-medium" :class="condition === 'bueno' ? 'bg-zinc-100 text-zinc-500' : 'bg-red-50 text-red-600'" x-text="condition === 'bueno' ? 'Opcional' : 'Obligatorio'">Opcional</small><small class="font-normal text-zinc-400">{{ mb_strlen($observations) }}/600</small></span></span><textarea wire:model.live.debounce.300ms="observations" rows="5" maxlength="600" class="w-full rounded-xl border-zinc-300 bg-white px-[15px] py-[12px] focus:border-black focus:ring-0" :placeholder="context.observationsPlaceholder" placeholder="Describe golpes, rayones, fallas o detalles visibles al recibirlo."></textarea>@error('observations') <span class="mt-[5px] block text-red-600">{{ $message }}</span> @enderror</label>
                        </div>
                    </section>

                    <section class="sticky bottom-[15px] flex items-center justify-between rounded-xl border border-zinc-200 bg-white/95 p-[15px] shadow-[0_10px_30px_rgba(0,0,0,0.12)] backdrop-blur-sm">
                        <div><strong class="block text-black">Documento listo para generar</strong><span class="mt-[5px] block text-zinc-500" x-text="context.pdfHelp">Se generará la constancia de recepción para DataMID y el cliente.</span></div>
                        <button type="submit" wire:loading.attr="disabled" wire:target="save,photo,autofillFromPhoto,autofillFromText" class="inline-flex min-w-[190px] items-center justify-center gap-[10px] rounded-xl bg-black px-[20px] py-[15px] font-semibold text-white transition hover:bg-zinc-800 disabled:cursor-wait disabled:opacity-60">
                            <svg wire:loading wire:target="save" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-30" cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3"/><path fill="currentColor" d="M12 3a9 9 0 00-9 9h3a6 6 0 016-6V3Z"/></svg>
                            <span wire:loading.remove wire:target="save" x-text="context.action">Generar recepción</span><span wire:loading wire:target="save">Guardando...</span>
                        </button>
                    </section>

                    @if ($recentReports->isNotEmpty())
                        <section class="rounded-xl border border-zinc-200 bg-white/80 p-[25px] shadow-[0_8px_24px_rgba(0,0,0,0.05)]"><header class="mb-[15px] flex items-center justify-between"><div><h2 class="font-semibold text-black">Últimas hojas generadas</h2><p class="mt-[5px] text-zinc-500">Actividad reciente del módulo.</p></div><button type="button" wire:click="showHistory" class="font-medium text-black hover:text-zinc-600">Ver historial completo</button></header><div class="divide-y divide-zinc-200">@foreach ($recentReports as $report)<button type="button" wire:click="openReport({{ $report->id }})" class="flex w-full items-center gap-[15px] py-[15px] text-left first:pt-0 last:pb-0"><span class="flex h-10 w-10 items-center justify-center rounded-xl bg-zinc-100 font-semibold text-black">{{ mb_strtoupper(mb_substr($report->equipment_type, 0, 1)) }}</span><span class="min-w-0 flex-1"><strong class="block text-black">{{ $report->folio }}</strong><span class="mt-[3px] block truncate text-zinc-500">{{ $report->customer_name }} · {{ $report->equipment_type }}</span></span><span class="rounded-full bg-zinc-100 px-[10px] py-[5px] text-zinc-600">{{ $report->movementLabel() }}</span><span class="text-xl text-zinc-400">›</span></button>@endforeach</div></section>
                    @endif
                </form>
            @elseif ($activeView === 'history')
                <section class="rounded-xl border border-zinc-200 bg-white/80 p-[25px] shadow-[0_8px_24px_rgba(0,0,0,0.05)]">
                    <header class="flex items-center justify-between border-b border-zinc-200 pb-[20px]"><div><h2 class="font-semibold text-black">Historial de hojas</h2><p class="mt-[5px] text-zinc-500">Busca, consulta y vuelve a descargar cualquier documento.</p></div><button type="button" wire:click="showCreate" class="rounded-xl bg-black px-[20px] py-[12px] font-medium text-white">Nueva hoja</button></header>
                    <div class="grid grid-cols-[minmax(0,1fr)_260px] gap-[15px] py-[20px]"><label><span class="mb-[10px] block font-medium text-black">Buscar por folio, serie o cliente</span><input type="search" wire:model.live.debounce.350ms="search" placeholder="Ej. DM-20260922-0001" class="w-full rounded-xl border-zinc-300 bg-white px-[15px] py-[12px] focus:border-black focus:ring-0"></label><label><span class="mb-[10px] block font-medium text-black">Movimiento</span><select wire:model.live="movementFilter" class="w-full rounded-xl border-zinc-300 bg-white px-[15px] py-[12px] focus:border-black focus:ring-0"><option value="">Todos</option>@foreach ($movements as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label></div>

                    @if ($reports->isEmpty())
                        <div class="flex min-h-[360px] flex-col items-center justify-center rounded-xl border border-dashed border-zinc-300 bg-zinc-50 text-center"><svg class="h-12 w-12 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6M8 3h8l4 4v14H4V3h4Zm7 0v5h5"/></svg><h3 class="mt-[15px] font-semibold text-black">No se encontraron hojas</h3><p class="mt-[5px] text-zinc-500">Prueba con otros filtros o crea un registro nuevo.</p></div>
                    @else
                        <div class="overflow-hidden rounded-xl border border-zinc-200"><table class="w-full text-left"><thead class="bg-zinc-100 text-zinc-500"><tr><th class="px-[15px] py-[12px] font-medium">Folio</th><th class="px-[15px] py-[12px] font-medium">Cliente</th><th class="px-[15px] py-[12px] font-medium">Equipo</th><th class="px-[15px] py-[12px] font-medium">Movimiento</th><th class="px-[15px] py-[12px] font-medium">Fecha</th><th class="px-[15px] py-[12px]"></th></tr></thead><tbody class="divide-y divide-zinc-200 bg-white/70">@foreach ($reports as $report)<tr wire:key="delivery-report-{{ $report->id }}" class="transition hover:bg-zinc-50"><td class="px-[15px] py-[15px] font-semibold text-black">{{ $report->folio }}</td><td class="px-[15px] py-[15px]"><strong class="block text-black">{{ $report->customer_name }}</strong><span class="mt-[3px] block text-zinc-500">{{ $report->customer_contact ?: 'Sin contacto' }}</span></td><td class="px-[15px] py-[15px]"><strong class="block text-black">{{ $report->equipment_type }} {{ $report->brand }}</strong><span class="mt-[3px] block text-zinc-500">{{ $report->model }} · {{ $report->serial_number ?: 'Sin serie' }}</span></td><td class="px-[15px] py-[15px]"><span class="rounded-full bg-zinc-100 px-[10px] py-[5px]">{{ $report->movementLabel() }}</span></td><td class="px-[15px] py-[15px] whitespace-nowrap">{{ $report->created_at->timezone(config('support.timezone'))->format('d/m/Y H:i') }}</td><td class="px-[15px] py-[15px] text-right"><button type="button" wire:click="openReport({{ $report->id }})" class="rounded-lg border border-zinc-200 bg-white px-[12px] py-[8px] font-medium text-black hover:bg-zinc-100">Ver</button></td></tr>@endforeach</tbody></table></div>
                        <div class="mt-[20px]">{{ $reports->links() }}</div>
                    @endif
                </section>
            @elseif ($selectedReport)
                <section class="space-y-[20px]">
                    <div class="flex items-start justify-between gap-[20px]"><div class="flex items-center gap-[15px]"><span class="flex h-14 w-14 items-center justify-center rounded-xl bg-zinc-100 text-xl font-semibold text-black">{{ mb_strtoupper(mb_substr($selectedReport->equipment_type, 0, 1)) }}</span><div><div class="flex items-center gap-[10px]"><span class="rounded-full bg-zinc-100 px-[10px] py-[5px] text-zinc-600">{{ $selectedReport->movementLabel() }}</span><span class="text-zinc-400">{{ $selectedReport->created_at->timezone(config('support.timezone'))->format('d/m/Y · H:i') }}</span></div><h2 class="mt-[8px] text-2xl font-semibold text-black">{{ $selectedReport->folio }}</h2><p class="mt-[5px] text-zinc-500">{{ $selectedReport->customer_name }}</p></div></div><div class="flex gap-[10px]"><button type="button" wire:click="showHistory" class="rounded-xl border border-zinc-200 bg-white px-[18px] py-[12px] font-medium text-black hover:bg-zinc-100">Volver al historial</button><a href="{{ route('soporte.hoja-entrega.pdf', ['report' => $selectedReport, 'copy' => 'both']) }}" class="rounded-xl bg-black px-[18px] py-[12px] font-medium text-white hover:bg-zinc-800">Descargar PDF doble</a></div></div>

                    <div class="grid grid-cols-[minmax(0,1fr)_360px] items-start gap-[20px]">
                        <div class="space-y-[20px]"><section class="rounded-xl border border-zinc-200 bg-white/80 p-[25px] shadow-[0_8px_24px_rgba(0,0,0,0.05)]"><h3 class="border-b border-zinc-200 pb-[15px] font-semibold text-black">Información del equipo</h3><dl class="mt-[20px] grid grid-cols-2 gap-[20px]"><div><dt class="text-zinc-500">Cliente o responsable</dt><dd class="mt-[5px] font-medium text-black">{{ $selectedReport->customer_name }}</dd></div><div><dt class="text-zinc-500">Contacto</dt><dd class="mt-[5px] font-medium text-black">{{ $selectedReport->customer_contact ?: 'No indicado' }}</dd></div><div class="col-span-2"><dt class="text-zinc-500">Quién entrega</dt><dd class="mt-[5px] font-medium text-black">{{ $selectedReport->delivered_by }}</dd></div><div><dt class="text-zinc-500">Equipo</dt><dd class="mt-[5px] font-medium text-black">{{ $selectedReport->equipment_type }}</dd></div><div><dt class="text-zinc-500">Marca y modelo</dt><dd class="mt-[5px] font-medium text-black">{{ trim($selectedReport->brand.' '.$selectedReport->model) }}</dd></div><div><dt class="text-zinc-500">Número de serie</dt><dd class="mt-[5px] font-mono font-medium text-black">{{ $selectedReport->serial_number ?: 'SIN SERIE' }}</dd></div><div><dt class="text-zinc-500">Estado físico</dt><dd class="mt-[5px] font-medium text-black">{{ $selectedReport->conditionLabel() }}</dd></div><div class="col-span-2"><dt class="text-zinc-500">Accesorios</dt><dd class="mt-[5px] whitespace-pre-line font-medium text-black">{{ $selectedReport->accessories ?: 'Sin accesorios registrados' }}</dd></div><div class="col-span-2"><dt class="text-zinc-500">Observaciones</dt><dd class="mt-[5px] whitespace-pre-line font-medium text-black">{{ $selectedReport->observations ?: 'Sin observaciones' }}</dd></div></dl></section></div>
                        <aside class="space-y-[20px]"><section class="rounded-xl border border-zinc-200 bg-white/80 p-[20px] shadow-[0_8px_24px_rgba(0,0,0,0.05)]"><h3 class="mb-[15px] font-semibold text-black">Evidencia</h3>@if ($selectedReport->photo_path)<a href="{{ route('soporte.hoja-entrega.photo', $selectedReport) }}" target="_blank"><img src="{{ route('soporte.hoja-entrega.photo', $selectedReport) }}" alt="Fotografía del equipo" class="aspect-[4/3] w-full rounded-xl object-cover"></a>@else<div class="flex min-h-[190px] flex-col items-center justify-center rounded-xl border border-dashed border-zinc-300 bg-zinc-50 text-zinc-400"><svg class="h-9 w-9" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7h4l2-2h6l2 2h4v12H3V7Zm9 9a4 4 0 100-8 4 4 0 000 8Z"/></svg><span class="mt-[10px]">Sin fotografía</span></div>@endif</section><section class="rounded-xl border border-zinc-200 bg-white/80 p-[20px] shadow-[0_8px_24px_rgba(0,0,0,0.05)]"><h3 class="mb-[10px] font-semibold text-black">Copias disponibles</h3><div class="divide-y divide-zinc-200"><a href="{{ route('soporte.hoja-entrega.pdf', ['report' => $selectedReport, 'copy' => 'both']) }}" class="flex items-center justify-between py-[12px] text-black"><span><strong class="block">Documento doble</strong><small class="mt-[3px] block text-zinc-500">DataMID y cliente</small></span><span>↓</span></a><a href="{{ route('soporte.hoja-entrega.pdf', ['report' => $selectedReport, 'copy' => 'datamid']) }}" class="flex items-center justify-between py-[12px] text-black"><span><strong class="block">Copia DataMID</strong><small class="mt-[3px] block text-zinc-500">Una página</small></span><span>↓</span></a><a href="{{ route('soporte.hoja-entrega.pdf', ['report' => $selectedReport, 'copy' => 'cliente']) }}" class="flex items-center justify-between py-[12px] text-black"><span><strong class="block">Copia cliente</strong><small class="mt-[3px] block text-zinc-500">Una página</small></span><span>↓</span></a><a href="{{ route('soporte.hoja-entrega.pdf', ['report' => $selectedReport, 'copy' => 'both', 'print' => 1]) }}" target="_blank" class="flex items-center justify-between py-[12px] text-black"><span><strong class="block">Abrir para imprimir</strong><small class="mt-[3px] block text-zinc-500">Vista previa en el navegador</small></span><span>↗</span></a></div></section></aside>
                    </div>
                </section>
            @endif
        </main>
    </div>
</div>
