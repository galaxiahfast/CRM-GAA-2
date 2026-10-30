<div class="people-root">
@if ($wide)
    <article class="people-wide overflow-hidden rounded-xl border border-[#d4d4d8] bg-white">
        <header class="profile-strip flex items-center justify-between gap-[20px] gap-y-[10px] border-b border-[#d4d4d8] bg-zinc-100 p-[20px]">
            <div class="flex min-w-0 items-center gap-[10px]">
                <span class="profile-icon-box flex shrink-0 items-center justify-center rounded-lg border border-[#d4d4d8] bg-white text-[#1A3A6B]">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8.6 13.5l6.8 4M15.4 6.5l-6.8 4M18 8a3 3 0 100-6 3 3 0 000 6zM6 15a3 3 0 100-6 3 3 0 000 6zm12 7a3 3 0 100-6 3 3 0 000 6z" /></svg>
                </span>
                <div class="min-w-0"><h3 class="leading-none text-[#1A3A6B]">Conectar con personas</h3><p class="mt-[10px] leading-none text-black">{{ $people->count() }} colaboradores disponibles</p></div>
            </div>
            <label class="people-wide-search relative block">
                <span class="profile-search-icon pointer-events-none absolute inset-y-0 flex items-center text-[#1A3A6B]"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m21 21-4.35-4.35m2.1-5.4a7.5 7.5 0 11-15 0 7.5 7.5 0 0115 0z" /></svg></span>
                <input type="search" wire:model.live.debounce.300ms="search" placeholder="Buscar colaborador..." class="profile-search-input h-11 w-full rounded-xl border border-[#d4d4d8] bg-white text-black shadow-none">
            </label>
        </header>

        <div class="p-[20px] text-left">
            <div x-data="backupOverlayScroll()" x-init="$nextTick(() => update())" x-effect="$wire.$get('search'); $nextTick(() => update())" class="relative -mr-[20px]">
                <div x-ref="scroller" @scroll="update()" @mouseenter="update()" class="profile-line-scroll people-wide-grid overflow-y-auto overscroll-contain pr-[20px]">
                    @forelse ($people as $person)
                        @php($connection = $connections->first(fn ($item) => in_array($person->id, [$item->requester_id, $item->addressee_id])))
                        @include('livewire.profile.people-sidebar-row')
                    @empty
                        <div class="people-wide-empty flex flex-col rounded-xl border border-dashed border-[#d4d4d8] px-[20px] py-[30px] text-center text-black"><p class="profile-lines">No se encontraron personas.</p></div>
                    @endforelse
                </div>
                <span x-show="visible" x-cloak class="profile-line-thumb" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
            </div>
        </div>
    </article>
@else
<section x-data="{ peopleOpen: false }" :data-people-open="peopleOpen ? 'true' : 'false'" class="people-panel flex min-h-0 flex-1 flex-col border-t border-[#d4d4d8] p-[20px] text-left">
    <header>
        <div class="flex items-center gap-[10px]">
            <span class="profile-icon-box flex shrink-0 items-center justify-center rounded-lg border border-[#d4d4d8] bg-white text-[#1A3A6B]">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8.6 13.5l6.8 4M15.4 6.5l-6.8 4M18 8a3 3 0 100-6 3 3 0 000 6zM6 15a3 3 0 100-6 3 3 0 000 6zm12 7a3 3 0 100-6 3 3 0 000 6z" /></svg>
            </span>
            <div class="min-w-0">
                <p class="leading-none text-[#1A3A6B]">Conectar con personas</p>
                <div class="mt-[10px] flex flex-col"><p class="profile-lines text-black">{{ $people->count() }} colaboradores disponibles</p></div>
            </div>
            <button type="button" @click="peopleOpen = !peopleOpen" :aria-expanded="peopleOpen ? 'true' : 'false'" :aria-label="peopleOpen ? 'Ocultar la lista de personas' : 'Mostrar la lista de personas'" :title="peopleOpen ? 'Ocultar la lista de personas' : 'Mostrar la lista de personas'" class="profile-people-toggle profile-icon-box shrink-0 items-center justify-center rounded-lg border border-[#d4d4d8] bg-white text-[#1A3A6B] transition-colors hover:border-[#1A3A6B] focus:outline-none focus:ring-0">
                <svg x-show="!peopleOpen" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h16M4 12h16M4 18h16" /></svg>
                <svg x-show="peopleOpen" x-cloak class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 6l12 12M18 6L6 18" /></svg>
            </button>
        </div>
        <label class="people-collapsible relative mt-[20px] block">
            <span class="profile-search-icon pointer-events-none absolute inset-y-0 flex items-center text-[#1A3A6B]"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m21 21-4.35-4.35m2.1-5.4a7.5 7.5 0 11-15 0 7.5 7.5 0 0115 0z" /></svg></span>
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Buscar colaborador..." class="profile-search-input h-11 w-full rounded-xl border border-[#d4d4d8] bg-white text-black shadow-none">
        </label>
    </header>

    <div x-data="backupOverlayScroll()" x-init="$nextTick(() => update())" x-effect="$wire.$get('search'); peopleOpen; $nextTick(() => update())" class="people-collapsible people-list relative -mr-[20px] mt-[20px] min-h-0 flex-1">
    <div x-ref="scroller" @scroll="update()" @mouseenter="update()" class="profile-line-scroll flex h-full flex-col gap-[20px] overflow-y-auto overscroll-contain pr-[20px]">
        @forelse ($people as $person)
            @php($connection = $connections->first(fn ($item) => in_array($person->id, [$item->requester_id, $item->addressee_id])))
            @include('livewire.profile.people-sidebar-row')
        @empty
            <p class="rounded-xl border border-dashed border-[#d4d4d8] px-[20px] py-[30px] text-center leading-none text-black">No se encontraron personas.</p>
        @endforelse
    </div>
    <span x-show="visible" x-cloak class="profile-line-thumb" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
    </div>
</section>
@endif
</div>
