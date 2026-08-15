<section class="flex min-h-0 flex-1 flex-col border-t border-zinc-200 px-[20px] pb-[20px] pt-[20px] text-left">
    <header>
        <div class="flex items-center gap-3">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-zinc-100 text-black">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2m3-10a4 4 0 100-8 4 4 0 000 8zm11 10v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75" /></svg>
            </span>
            <div class="min-w-0">
                <p class="font-semibold text-gray-800">Conectar con personas</p>
                <p class="truncate text-[12px] text-gray-400">{{ $people->count() }} colaboradores disponibles</p>
            </div>
        </div>
        <label class="relative mt-[15px] block">
            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-gray-400"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m21 21-4.35-4.35m2.1-5.4a7.5 7.5 0 11-15 0 7.5 7.5 0 0115 0z" /></svg></span>
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Buscar colaborador..." class="h-10 w-full rounded-xl border-zinc-200 bg-white pl-9 text-[12px] shadow-none focus:border-black focus:ring-black/10">
        </label>
    </header>

    <div class="profile-people-scrollbar -mr-[20px] mt-[20px] min-h-0 flex-1 space-y-[8px] overflow-y-auto overscroll-contain pr-[14px]">
        @forelse ($people as $person)
            @php($connection = $connections->first(fn ($item) => in_array($person->id, [$item->requester_id, $item->addressee_id])))
            <div class="group flex items-center gap-[10px] rounded-xl border border-transparent px-[10px] py-[9px] transition hover:border-zinc-200 hover:bg-zinc-50">
                <a href="{{ route('profiles.show', $person) }}" class="shrink-0">
                    @if ($person->profile_photo_path)
                        <img src="{{ $person->profile_photo_url }}" alt="Foto de {{ $person->name }}" class="h-9 w-9 rounded-full object-cover ring-1 ring-zinc-200">
                    @else
                        <span class="grid h-9 w-9 place-items-center rounded-full bg-zinc-100 font-semibold text-black ring-1 ring-zinc-200">{{ mb_strtoupper(mb_substr($person->name, 0, 1).mb_substr($person->last_name ?? '', 0, 1)) }}</span>
                    @endif
                </a>
                <a href="{{ route('profiles.show', $person) }}" class="min-w-0 flex-1">
                    <p class="truncate text-[13px] font-semibold text-gray-700" title="{{ $person->name }} {{ $person->last_name }}">{{ $person->name }} {{ $person->last_name }}</p>
                    <p class="truncate text-[11px] text-gray-400" title="{{ $person->email }}">{{ $person->email }}</p>
                </a>
                @if (! $connection)
                    <button type="button" wire:click="follow({{ $person->id }})" class="inline-flex h-9 w-[72px] shrink-0 items-center justify-center whitespace-nowrap rounded-xl bg-black px-2 font-semibold text-white transition hover:bg-zinc-800">Seguir</button>
                @elseif ($connection->status === \App\Models\Friendship::PENDING && $connection->addressee_id === auth()->id())
                    <button type="button" wire:click="accept({{ $connection->id }})" class="inline-flex h-9 w-[72px] shrink-0 items-center justify-center whitespace-nowrap rounded-xl bg-black px-2 font-semibold text-white transition hover:bg-zinc-800">Aceptar</button>
                @elseif ($connection->status === \App\Models\Friendship::PENDING)
                    <span class="inline-flex h-9 w-[72px] shrink-0 items-center justify-center whitespace-nowrap rounded-xl border border-zinc-200 bg-white px-2 font-semibold text-zinc-500">Pendiente</span>
                @else
                    <span class="inline-flex h-9 w-[72px] shrink-0 items-center justify-center whitespace-nowrap rounded-xl border border-zinc-200 bg-white px-2 font-semibold text-black">Amigos</span>
                @endif
            </div>
        @empty
            <p class="rounded-xl bg-zinc-50 px-3 py-6 text-center text-[12px] text-zinc-400">No se encontraron personas.</p>
        @endforelse
    </div>
</section>
