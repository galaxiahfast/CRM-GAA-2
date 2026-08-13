<aside class="flex min-w-0 flex-col border-t border-gray-300 bg-gray-100 min-[1462px]:h-0 min-[1462px]:min-h-full min-[1462px]:border-l min-[1462px]:border-t-0">
    <header class="mx-[25px] shrink-0 border-b border-gray-300 pb-[20px] pt-[25px]">
        <p class="text-[16px] font-semibold text-gray-800">Personas</p>
        <p class="mt-1 text-[12px] text-gray-500">{{ $people->count() }} colaboradores registrados</p>
        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Buscar persona..." class="mt-[15px] h-10 w-full rounded-xl border-gray-300 bg-transparent text-[12px] focus:border-[#1A3A6B] focus:ring-[#1A3A6B]/20">
    </header>
    <div class="profile-people-scrollbar min-h-0 flex-1 divide-y divide-gray-300 overflow-y-auto overscroll-contain pl-[25px]">
        @forelse ($people as $person)
            @php($connection = $connections->first(fn ($item) => in_array($person->id, [$item->requester_id, $item->addressee_id])))
            <div class="mr-[25px] flex items-center gap-[10px] py-[15px]">
                <a href="{{ route('profiles.show', $person) }}" class="shrink-0"><img src="{{ $person->profile_photo_url }}" alt="Foto de {{ $person->name }}" class="h-10 w-10 rounded-full border-2 border-white object-cover shadow-sm"></a>
                <a href="{{ route('profiles.show', $person) }}" class="min-w-0 flex-1">
                    <p class="truncate text-[15px] font-semibold text-gray-700">{{ $person->name }} {{ $person->last_name }}</p>
                    <p class="truncate text-[12px] text-gray-500">{{ $person->email }}</p>
                </a>
                @if (! $connection)
                    <button wire:click="follow({{ $person->id }})" class="shrink-0 rounded-lg bg-[#1A3A6B] px-2.5 py-2 text-[11px] font-semibold text-white">Seguir</button>
                @elseif ($connection->status === \App\Models\Friendship::PENDING && $connection->addressee_id === auth()->id())
                    <button wire:click="accept({{ $connection->id }})" class="shrink-0 rounded-lg bg-emerald-600 px-2.5 py-2 text-[11px] font-semibold text-white">Aceptar</button>
                @elseif ($connection->status === \App\Models\Friendship::PENDING)
                    <span class="shrink-0 rounded-lg border border-amber-300 px-2 py-1.5 text-[10px] font-semibold text-amber-700">Pendiente</span>
                @else
                    <span class="shrink-0 rounded-lg border border-[#b9c9e2] bg-[#eef4fc] px-2 py-1.5 text-[10px] font-semibold text-[#1A3A6B]">Amigos</span>
                @endif
            </div>
        @empty
            <p class="mr-[25px] py-8 text-center text-[12px] text-gray-400">No se encontraron personas.</p>
        @endforelse
    </div>
    <div class="mx-[25px] h-[25px] shrink-0 border-t border-gray-300" aria-hidden="true"></div>
</aside>
