@php
    $inputClasses = 'mt-2 block h-11 w-full rounded-lg border border-gray-300 bg-[#F3F3F3] px-3 text-[15px] text-gray-800 shadow-none focus:border-[#1A3A6B] focus:outline-none focus:ring-0';
    $textareaClasses = 'mt-2 block w-full rounded-lg border border-gray-300 bg-[#F3F3F3] px-3 py-3 text-[15px] text-gray-800 shadow-none focus:border-[#1A3A6B] focus:outline-none focus:ring-0';
@endphp

<div>
    <button
        type="button"
        role="menuitem"
        wire:click="openModal"
        wire:loading.attr="disabled"
        wire:target="openModal"
        @click="open = false"
        class="flex w-full items-center gap-3 rounded-lg px-4 py-3 text-left text-[15px] font-medium text-gray-700 transition hover:bg-gray-50 hover:text-[#1A3A6B] focus:outline-none focus:ring-0 disabled:cursor-wait disabled:opacity-60"
    >
        <svg class="h-5 w-5 shrink-0 text-[#1A3A6B]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6v6l4 2m5-2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span>Agregar Actividad</span>
    </button>

    @if ($showModal)
        @teleport('body')
            <div>
                <x-administration-form-modal
                    submit="save"
                    cancel-action="closeModal"
                    modal-id="activity-catalog-management"
                    title="Actividades del reloj checador"
                    subtitle="Administra el catálogo operativo disponible en Control de Horas."
                >
                    <x-slot name="icon">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6v6l4 2m5-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </x-slot>

                    <x-slot name="navigation">
                        @foreach (['crear' => 'Crear', 'editar' => 'Editar', 'eliminar' => 'Eliminar'] as $tab => $label)
                            <button
                                type="button"
                                wire:click="setActiveTab('{{ $tab }}')"
                                class="border-b-2 px-4 py-3 text-[15px] font-medium transition focus:outline-none focus:ring-0 {{ $activeTab === $tab ? ($tab === 'eliminar' ? 'border-red-600 text-red-700' : 'border-[#1A3A6B] text-[#1A3A6B]') : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}"
                            >
                                {{ $label }}
                            </button>
                        @endforeach
                    </x-slot>

                    <x-slot name="form">
                        <div class="space-y-5">
                            @if ($notice)
                                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-[15px] text-emerald-800" role="status">
                                    {{ $notice }}
                                </div>
                            @endif

                            @error('catalog')
                                <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-[15px] text-red-700" role="alert">
                                    {{ $message }}
                                </div>
                            @enderror

                            <div class="rounded-xl border border-blue-100 bg-blue-50 p-4 text-[15px] text-blue-900">
                                Estas actividades son un catálogo global: también pueden estar vinculadas con servicios y documentos de clientes.
                            </div>

                            @if (in_array($activeTab, ['editar', 'eliminar'], true))
                                <section class="rounded-xl border border-gray-200 bg-[#F3F3F3] p-5 shadow-sm">
                                    <label for="managed-activity-id" class="block text-[15px] font-medium text-gray-700">Actividad</label>
                                    @php
                                        $activityOptions = $activities->map(function ($activity) {
                                            $category = $activity->service?->service ?? 'Sin categoría';

                                            return [
                                                'id' => (int) $activity->id,
                                                'name' => (string) $activity->sub_service,
                                                'category' => $category,
                                                'label' => $activity->sub_service.' — '.$category.($activity->isProtectedCatalogEntry() ? ' (actividad base)' : ''),
                                            ];
                                        })->values();
                                        $selectedActivityLabel = $selectedActivity
                                            ? $selectedActivity->sub_service.' — '.($selectedActivity->service?->service ?? 'Sin categoría').($selectedActivity->isProtectedCatalogEntry() ? ' (actividad base)' : '')
                                            : '';
                                    @endphp
                                    <div
                                        wire:key="activity-catalog-picker-{{ $activeTab }}"
                                        data-catalog-search="activity"
                                        class="relative mt-2"
                                        x-data="{
                                            open: false,
                                            query: '',
                                            selectedId: @js($selectedActivityId),
                                            selectedLabel: @js($selectedActivityLabel),
                                            options: @js($activityOptions),
                                            normalize(value) {
                                                return String(value ?? '')
                                                    .normalize('NFD')
                                                    .replace(/[\u0300-\u036f]/g, '')
                                                    .toLocaleLowerCase('es');
                                            },
                                            get filteredOptions() {
                                                const term = this.normalize(this.query).trim();

                                                if (!term) return this.options;

                                                return this.options.filter((option) =>
                                                    this.normalize(option.name).includes(term)
                                                    || this.normalize(option.category).includes(term)
                                                );
                                            },
                                            openList() {
                                                this.query = '';
                                                this.open = true;
                                                this.$nextTick(() => this.$refs.searchInput.focus());
                                            },
                                            closeList() {
                                                this.query = '';
                                                this.open = false;
                                            },
                                            choose(option) {
                                                this.selectedId = option.id;
                                                this.selectedLabel = option.label;
                                                this.query = '';
                                                this.open = false;
                                            },
                                        }"
                                        @click.outside="closeList()"
                                        @activity-catalog-updated.window="if ($event.detail.action === 'deleted') { options = options.filter(option => String(option.id) !== String($event.detail.activityId)); selectedId = null; selectedLabel = ''; query = ''; open = false; }"
                                    >
                                        <input
                                            id="managed-activity-id"
                                            x-ref="searchInput"
                                            type="text"
                                            autocomplete="off"
                                            placeholder="Buscar por actividad o categoría..."
                                            class="block h-11 w-full rounded-lg border bg-[#F3F3F3] px-3 pr-12 text-[15px] text-gray-800 shadow-none focus:outline-none focus:ring-0 {{ $activeTab === 'eliminar' ? 'border-red-300 focus:border-red-500' : 'border-gray-300 focus:border-[#1A3A6B]' }}"
                                            x-bind:value="open ? query : selectedLabel"
                                            @focus="openList()"
                                            @click="openList()"
                                            @input="query = $event.target.value; open = true; if (selectedId !== null) { selectedId = null; selectedLabel = ''; $wire.set('selectedActivityId', null); }"
                                            @keydown.escape.prevent="closeList(); $el.blur()"
                                            @keydown.arrow-down.prevent="open = true; $nextTick(() => $refs.results?.querySelector('[role=option]')?.focus())"
                                            role="combobox"
                                            aria-autocomplete="list"
                                            aria-controls="managed-activity-results"
                                            x-bind:aria-expanded="open"
                                        >

                                        <button
                                            type="button"
                                            class="absolute right-0 top-0 flex h-11 w-12 items-center justify-center text-gray-500 hover:text-[#1A3A6B] focus:outline-none focus:ring-0"
                                            @click="open ? closeList() : openList()"
                                            aria-label="Mostrar todas las actividades"
                                            x-bind:aria-expanded="open"
                                        >
                                            <svg class="h-4 w-4" x-bind:class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>

                                        <div
                                            id="managed-activity-results"
                                            x-ref="results"
                                            wire:ignore
                                            x-cloak
                                            x-show="open"
                                            class="administration-form-scrollbar absolute z-[70] mt-2 max-h-60 w-full overflow-y-auto overscroll-contain rounded-xl border border-gray-200 bg-white p-1.5 shadow-lg"
                                            role="listbox"
                                            aria-label="Actividades disponibles"
                                        >
                                            <template x-for="option in filteredOptions" x-bind:key="option.id">
                                                <button
                                                    type="button"
                                                    class="flex w-full min-w-0 items-start rounded-lg px-4 py-3 text-left hover:bg-blue-50 focus:bg-blue-50 focus:outline-none focus:ring-0"
                                                    role="option"
                                                    x-bind:aria-selected="String(selectedId) === String(option.id)"
                                                    @click="choose(option); $wire.set('selectedActivityId', option.id)"
                                                    @keydown.escape.prevent="closeList(); $refs.searchInput.focus()"
                                                >
                                                    <span class="min-w-0 flex-1">
                                                        <span class="block truncate text-[15px] font-medium text-gray-800" x-text="option.name"></span>
                                                        <span class="mt-1 block truncate text-[15px] text-gray-500" x-text="option.category"></span>
                                                    </span>
                                                    <svg x-show="String(selectedId) === String(option.id)" class="ml-3 mt-0.5 h-5 w-5 shrink-0 text-[#1A3A6B]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                </button>
                                            </template>

                                            <p x-show="filteredOptions.length === 0" class="px-4 py-4 text-[15px] text-gray-500">
                                                No se encontraron actividades con esa búsqueda.
                                            </p>
                                        </div>
                                    </div>
                                    <x-input-error for="selectedActivityId" class="mt-2 text-[15px]" />
                                </section>
                            @endif

                            @if ($activeTab === 'crear' || ($activeTab === 'editar' && $selectedActivityId))
                                <section class="rounded-xl border border-gray-200 bg-[#F3F3F3] p-5 shadow-sm">
                                    <div class="mb-5">
                                        <h3 class="text-[15px] font-semibold text-gray-900">Información de la actividad</h3>
                                        <p class="mt-2 text-[15px] text-gray-500">Define cómo aparecerá en el selector del reloj checador.</p>
                                    </div>

                                    <div class="space-y-5">
                                        <div>
                                            <label for="activity-name" class="block text-[15px] font-medium text-gray-700">Nombre</label>
                                            <input id="activity-name" type="text" maxlength="255" wire:model.defer="name" class="{{ $inputClasses }}" autocomplete="off" placeholder="Ej. Revisión de declaraciones">
                                            <x-input-error for="name" class="mt-2 text-[15px]" />
                                        </div>

                                        <div>
                                            <label for="activity-service" class="block text-[15px] font-medium text-gray-700">Categoría de servicio</label>
                                            @php
                                                $serviceOptions = $services->map(fn ($service) => [
                                                    'id' => (int) $service->id,
                                                    'label' => (string) $service->service,
                                                ])->values();
                                                $selectedServiceLabel = $services->firstWhere('id', $serviceId)?->service ?? '';
                                                $serviceSelectionDisabled = $activeTab === 'editar' && $selectedActivity?->isProtectedCatalogEntry();
                                            @endphp
                                            <div
                                                wire:key="activity-service-picker-{{ $activeTab }}-{{ $selectedActivityId ?? 'new' }}"
                                                data-catalog-search="service"
                                                class="relative mt-2"
                                                x-data="{
                                                    open: false,
                                                    query: '',
                                                    selectedId: @js($serviceId),
                                                    selectedLabel: @js($selectedServiceLabel),
                                                    disabled: @js($serviceSelectionDisabled),
                                                    options: @js($serviceOptions),
                                                    normalize(value) {
                                                        return String(value ?? '')
                                                            .normalize('NFD')
                                                            .replace(/[\u0300-\u036f]/g, '')
                                                            .toLocaleLowerCase('es');
                                                    },
                                                    get filteredOptions() {
                                                        const term = this.normalize(this.query).trim();

                                                        return term
                                                            ? this.options.filter((option) => this.normalize(option.label).includes(term))
                                                            : this.options;
                                                    },
                                                    openList() {
                                                        if (this.disabled) return;

                                                        this.query = '';
                                                        this.open = true;
                                                        this.$nextTick(() => this.$refs.searchInput.focus());
                                                    },
                                                    closeList() {
                                                        this.query = '';
                                                        this.open = false;
                                                    },
                                                    choose(option) {
                                                        this.selectedId = option.id;
                                                        this.selectedLabel = option.label;
                                                        this.query = '';
                                                        this.open = false;
                                                    },
                                                }"
                                                @click.outside="closeList()"
                                                @activity-catalog-updated.window="if ($event.detail.action === 'created') { selectedId = null; selectedLabel = ''; query = ''; open = false; }"
                                            >
                                                <input
                                                    id="activity-service"
                                                    x-ref="searchInput"
                                                    type="text"
                                                    autocomplete="off"
                                                    placeholder="Buscar una categoría..."
                                                    class="block h-11 w-full rounded-lg border border-gray-300 bg-[#F3F3F3] px-3 pr-12 text-[15px] text-gray-800 shadow-none focus:border-[#1A3A6B] focus:outline-none focus:ring-0 disabled:cursor-not-allowed disabled:bg-gray-200 disabled:text-gray-500"
                                                    x-bind:disabled="disabled"
                                                    x-bind:value="open ? query : selectedLabel"
                                                    @focus="openList()"
                                                    @click="openList()"
                                                    @input="query = $event.target.value; open = true; if (selectedId !== null) { selectedId = null; selectedLabel = ''; $wire.set('serviceId', null, false); }"
                                                    @keydown.escape.prevent="closeList(); $el.blur()"
                                                    @keydown.arrow-down.prevent="open = true; $nextTick(() => $refs.results?.querySelector('[role=option]')?.focus())"
                                                    role="combobox"
                                                    aria-autocomplete="list"
                                                    aria-controls="activity-service-results"
                                                    x-bind:aria-expanded="open"
                                                >

                                                <button
                                                    type="button"
                                                    class="absolute right-0 top-0 flex h-11 w-12 items-center justify-center text-gray-500 hover:text-[#1A3A6B] focus:outline-none focus:ring-0 disabled:cursor-not-allowed disabled:text-gray-400"
                                                    x-bind:disabled="disabled"
                                                    @click="open ? closeList() : openList()"
                                                    aria-label="Mostrar todas las categorías"
                                                    x-bind:aria-expanded="open"
                                                >
                                                    <svg class="h-4 w-4" x-bind:class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                    </svg>
                                                </button>

                                                <div
                                                    id="activity-service-results"
                                                    x-ref="results"
                                                    wire:ignore
                                                    x-cloak
                                                    x-show="open"
                                                    class="administration-form-scrollbar absolute z-[70] mt-2 max-h-60 w-full overflow-y-auto overscroll-contain rounded-xl border border-gray-200 bg-white p-1.5 shadow-lg"
                                                    role="listbox"
                                                    aria-label="Categorías disponibles"
                                                >
                                                    <template x-for="option in filteredOptions" x-bind:key="option.id">
                                                        <button
                                                            type="button"
                                                            class="flex w-full min-w-0 items-center rounded-lg px-4 py-3 text-left hover:bg-blue-50 focus:bg-blue-50 focus:outline-none focus:ring-0"
                                                            role="option"
                                                            x-bind:aria-selected="String(selectedId) === String(option.id)"
                                                            @click="choose(option); $wire.set('serviceId', option.id, false)"
                                                            @keydown.escape.prevent="closeList(); $refs.searchInput.focus()"
                                                        >
                                                            <span class="min-w-0 flex-1 truncate text-[15px] font-medium text-gray-800" x-text="option.label"></span>
                                                            <svg x-show="String(selectedId) === String(option.id)" class="ml-3 h-5 w-5 shrink-0 text-[#1A3A6B]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                            </svg>
                                                        </button>
                                                    </template>

                                                    <p x-show="filteredOptions.length === 0" class="px-4 py-4 text-[15px] text-gray-500">
                                                        No se encontraron categorías con esa búsqueda.
                                                    </p>
                                                </div>
                                            </div>
                                            <x-input-error for="serviceId" class="mt-2 text-[15px]" />
                                            @if ($activeTab === 'editar' && $selectedActivity?->isProtectedCatalogEntry())
                                                <p class="mt-2 text-[15px] text-blue-800">La categoría se conserva porque esta actividad participa en procesos base del sistema.</p>
                                            @endif
                                        </div>

                                        <div>
                                            <label for="activity-description" class="block text-[15px] font-medium text-gray-700">Descripción</label>
                                            <textarea id="activity-description" rows="4" maxlength="255" wire:model.defer="description" class="{{ $textareaClasses }}" placeholder="Describe brevemente el alcance de la actividad."></textarea>
                                            <x-input-error for="description" class="mt-2 text-[15px]" />
                                        </div>
                                    </div>
                                </section>
                            @elseif ($activeTab === 'editar')
                                <div class="rounded-xl border border-blue-100 bg-blue-50 p-5 text-[15px] text-blue-900">
                                    Selecciona la actividad que deseas modificar.
                                </div>
                            @endif

                            @if ($activeTab === 'eliminar')
                                <div class="rounded-xl border border-red-200 bg-red-50 p-5 text-[15px] text-red-800">
                                    <p class="font-semibold">Eliminación protegida</p>
                                    <p class="mt-2">La actividad solo se eliminará si no es una actividad base y no tiene registros históricos de tiempo ni archivos de clientes asociados.</p>
                                    @if ($selectedActivity)
                                        <div class="mt-4 rounded-lg border border-red-200 bg-white p-4 text-gray-700">
                                            <p class="font-medium text-gray-900">{{ $selectedActivity->sub_service }}</p>
                                            <p class="mt-1">Categoría: {{ $selectedActivity->service?->service ?? 'Sin categoría' }}</p>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </x-slot>

                    <x-slot name="actions">
                        <button type="button" wire:click="closeModal" class="inline-flex min-w-28 items-center justify-center rounded-lg border border-[#1A3A6B] bg-transparent px-5 py-3 text-[15px] font-medium text-[#1A3A6B] transition hover:bg-blue-50 focus:outline-none focus:ring-0">
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            wire:target="save"
                            @disabled($activeTab === 'eliminar' && $selectedActivity?->isProtectedCatalogEntry())
                            class="inline-flex min-w-28 items-center justify-center rounded-lg px-5 py-3 text-[15px] font-medium text-white transition focus:outline-none focus:ring-0 disabled:cursor-wait disabled:opacity-60 {{ $activeTab === 'eliminar' ? 'bg-red-600 hover:bg-red-700' : 'bg-[#1A3A6B] hover:bg-[#15305a]' }}"
                        >
                            <span wire:loading.remove wire:target="save">
                                {{ $activeTab === 'crear' ? 'Guardar' : ($activeTab === 'editar' ? 'Guardar cambios' : 'Eliminar') }}
                            </span>
                            <span wire:loading wire:target="save">Procesando...</span>
                        </button>
                    </x-slot>
                </x-administration-form-modal>
            </div>
        @endteleport
    @endif
</div>
