<div class="people-root contents">
@if ($wide)
    {{-- Perfil de otra persona: la lista va debajo, a lo ancho, en columnas. Se ven exactamente tres filas y el scroll se detiene siempre al inicio de una fila. --}}
    <article class="panel-surface panel-border overflow-hidden rounded-xl border">
        <header class="panel-header panel-border flex min-h-[85px] flex-wrap items-center justify-between gap-x-[20px] gap-y-[10px] border-b p-[20px]">
            <div class="min-w-0"><h3 class="panel-accent leading-none">Conectar con personas</h3><p class="pf-sub mt-[5px]">{{ $people->count() }} colaboradores disponibles</p></div>
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Buscar colaborador..." class="pf-field w-full sm:w-[360px]">
        </header>

        <div class="p-[20px]">
            <div data-scroll-on-hover x-data="backupOverlayScroll()" x-init="$nextTick(() => update())" x-effect="$wire.$get('search'); $nextTick(() => update())" class="relative -mr-[20px]">
                <div x-ref="scroller" @scroll="update()" @mouseenter="update()" class="panel-scroll people-wide-grid overflow-y-auto overscroll-contain pr-[20px]">
                    @forelse ($people as $person)
                        @php($connection = $connections->first(fn ($item) => in_array($person->id, [$item->requester_id, $item->addressee_id])))
                        @include('livewire.profile.people-sidebar-row')
                    @empty
                        <p class="people-wide-empty panel-border rounded-xl border border-dashed px-[20px] py-[30px] text-center leading-[30px]">No se encontraron personas.</p>
                    @endforelse
                </div>
                <span :class="visible ? '' : 'hidden'" class="panel-scroll-thumb" style="right: 5px;" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
            </div>
        </div>
    </article>
@else
    <section class="people-panel panel-border flex min-h-0 flex-1 flex-col border-t p-[20px] text-left">
        <header>
            <h3 class="panel-accent leading-none">Conectar con personas</h3>
            <p class="pf-sub mt-[5px]">{{ $people->count() }} colaboradores disponibles</p>
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Buscar colaborador..." class="pf-field mt-[20px]">
        </header>

        <div data-scroll-on-hover x-data="backupOverlayScroll()" x-init="$nextTick(() => update())" x-effect="$wire.$get('search'); $nextTick(() => update())" class="people-list relative -mr-[20px] mt-[20px] min-h-[320px] flex-1">
            <div x-ref="scroller" @scroll="update()" @mouseenter="update()" class="panel-scroll absolute inset-0 flex flex-col gap-[20px] overflow-y-auto overscroll-contain pr-[20px]">
                @forelse ($people as $person)
                    @php($connection = $connections->first(fn ($item) => in_array($person->id, [$item->requester_id, $item->addressee_id])))
                    @include('livewire.profile.people-sidebar-row')
                @empty
                    <p class="panel-border rounded-xl border border-dashed px-[20px] py-[30px] text-center leading-[30px]">No se encontraron personas.</p>
                @endforelse
            </div>
            <span :class="visible ? '' : 'hidden'" class="panel-scroll-thumb" style="right: 5px;" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
        </div>
    </section>
@endif

    <style>
        [data-profile] .people-wide-grid {
            --people-row: 112px;
            --people-gap: 20px;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(min(320px, 100%), 1fr));
            grid-auto-rows: var(--people-row);
            align-content: start;
            gap: var(--people-gap);
            max-height: calc(var(--people-row) * 3 + var(--people-gap) * 2);
            scroll-snap-type: y mandatory;
        }
        [data-profile] .people-wide-grid > * { scroll-snap-align: start; }
        [data-profile] .people-wide-empty { grid-column: 1 / -1; }
        [data-profile] .people-clamp-2 { display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 2; overflow: hidden; }
    </style>
</div>
