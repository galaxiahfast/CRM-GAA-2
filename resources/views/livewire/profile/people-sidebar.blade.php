<section class="flex min-h-0 flex-1 flex-col border-t border-[#d4d4d8] p-[20px] text-left">
    <header>
        <div class="flex items-center gap-[10px]">
            <span class="profile-icon-box flex shrink-0 items-center justify-center rounded-lg border border-[#d4d4d8] bg-white text-[#1A3A6B]">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8.6 13.5l6.8 4M15.4 6.5l-6.8 4M18 8a3 3 0 100-6 3 3 0 000 6zM6 15a3 3 0 100-6 3 3 0 000 6zm12 7a3 3 0 100-6 3 3 0 000 6z" /></svg>
            </span>
            <div class="min-w-0">
                <p class="leading-none text-[#1A3A6B]">Conectar con personas</p>
                <p class="mt-[10px] truncate leading-none text-black">{{ $people->count() }} colaboradores disponibles</p>
            </div>
        </div>
        <label class="relative mt-[20px] block">
            <span class="profile-search-icon pointer-events-none absolute inset-y-0 flex items-center text-[#1A3A6B]"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m21 21-4.35-4.35m2.1-5.4a7.5 7.5 0 11-15 0 7.5 7.5 0 0115 0z" /></svg></span>
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Buscar colaborador..." class="profile-search-input h-11 w-full rounded-xl border border-[#d4d4d8] bg-white text-black shadow-none">
        </label>
    </header>

    <div x-data="backupOverlayScroll()" x-init="$nextTick(() => update())" x-effect="$wire.search; $nextTick(() => update())" class="relative -mr-[20px] mt-[20px] min-h-0 flex-1">
    <div x-ref="scroller" @scroll="update()" @mouseenter="update()" class="profile-line-scroll flex h-full flex-col gap-[20px] overflow-y-auto overscroll-contain pr-[20px]">
        @forelse ($people as $person)
            @php($connection = $connections->first(fn ($item) => in_array($person->id, [$item->requester_id, $item->addressee_id])))
            <div class="flex shrink-0 items-center gap-[10px]">
                <a href="{{ route('profiles.show', $person) }}" class="shrink-0">
                    @if ($person->profile_photo_path)
                        <img src="{{ $person->profile_photo_url }}" alt="Foto de {{ $person->name }}" class="block h-10 w-10 rounded-full object-cover ring-1 ring-zinc-200">
                    @else
                        <span class="grid h-10 w-10 place-items-center rounded-full bg-zinc-100 text-black ring-1 ring-zinc-200">{{ mb_strtoupper(mb_substr($person->name, 0, 1).mb_substr($person->last_name ?? '', 0, 1)) }}</span>
                    @endif
                </a>
                <a href="{{ route('profiles.show', $person) }}" class="flex min-w-0 flex-1 flex-col gap-[10px]">
                    <p class="profile-lines break-words text-black">{{ $person->name }} {{ $person->last_name }}</p>
                    <p class="profile-lines profile-clamp-2 break-words text-black" title="{{ $person->email }}">{!! str_replace(['@', '.', '_'], ['@<wbr>', '.<wbr>', '_<wbr>'], e($person->email)) !!}</p>
                </a>
                @if (! $connection)
                    <button type="button" wire:click="follow({{ $person->id }})" class="ml-[10px] inline-flex shrink-0 items-center justify-end whitespace-nowrap bg-transparent text-right leading-none text-[#1A3A6B] transition-colors hover:text-black focus:outline-none focus:ring-0">Seguir</button>
                @elseif ($connection->status === \App\Models\Friendship::PENDING && $connection->addressee_id === auth()->id())
                    <button type="button" wire:click="accept({{ $connection->id }})" class="ml-[10px] inline-flex shrink-0 items-center justify-end whitespace-nowrap bg-transparent text-right leading-none text-[#1A3A6B] transition-colors hover:text-black focus:outline-none focus:ring-0">Aceptar</button>
                @elseif ($connection->status === \App\Models\Friendship::PENDING)
                    <span class="ml-[10px] inline-flex shrink-0 items-center justify-end whitespace-nowrap text-right leading-none text-black">Pendiente</span>
                @else
                    <span class="ml-[10px] inline-flex shrink-0 items-center justify-end whitespace-nowrap text-right leading-none text-black">Amigos</span>
                @endif
            </div>
        @empty
            <p class="rounded-xl border border-dashed border-[#d4d4d8] px-[20px] py-[30px] text-center leading-none text-black">No se encontraron personas.</p>
        @endforelse
    </div>
    <span x-show="visible" x-cloak class="profile-line-thumb" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
    </div>
</section>
