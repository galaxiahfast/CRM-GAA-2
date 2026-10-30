@php
    // En la lista solo se muestra el primer nombre y un apellido (en una sola línea; si no cabe, termina en "…"). El nombre completo queda en el título al pasar el cursor.
    $nameParts = preg_split('/\s+/u', trim((string) $person->name), -1, PREG_SPLIT_NO_EMPTY);
    $surnameParts = preg_split('/\s+/u', trim((string) $person->last_name), -1, PREG_SPLIT_NO_EMPTY);
    $surname = [];
    foreach ($surnameParts as $part) {
        $surname[] = $part;
        // Partículas ("de", "del", "la"…) e iniciales sueltas se acompañan con la palabra siguiente: "De la Cruz", "X Xijum".
        if (! in_array(mb_strtolower($part), ['de', 'del', 'la', 'las', 'los', 'y', 'san', 'santa', 'da', 'das', 'do', 'dos', 'van', 'von', 'di', 'le'], true) && mb_strlen($part) > 1) {
            break;
        }
    }
    $shortName = trim(($nameParts[0] ?? '').' '.implode(' ', $surname));
    $fullName = trim($person->name.' '.$person->last_name);
@endphp
<div class="{{ $wide ? 'min-w-0 rounded-xl border border-[#d4d4d8] p-[20px]' : 'shrink-0' }} flex items-center gap-[10px]">
    <a href="{{ route('profiles.show', $person) }}" class="shrink-0">
        @if ($person->profile_photo_path)
            <img src="{{ $person->profile_photo_url }}" alt="Foto de {{ $person->name }}" class="block h-10 w-10 rounded-full object-cover ring-1 ring-zinc-200">
        @else
            <span class="grid h-10 w-10 place-items-center rounded-full bg-zinc-100 text-black ring-1 ring-zinc-200">{{ mb_strtoupper(mb_substr($person->name, 0, 1).mb_substr($person->last_name ?? '', 0, 1)) }}</span>
        @endif
    </a>
    <a href="{{ route('profiles.show', $person) }}" class="flex min-w-0 flex-1 flex-col gap-[10px]">
        <p class="profile-lines truncate text-black" title="{{ $fullName }}">{{ $shortName }}</p>
        <p class="profile-lines profile-clamp-2 break-all text-black" title="{{ $person->email }}">{{ $person->email }}</p>
    </a>
    @if (! $connection)
        <button type="button" wire:click="follow({{ $person->id }})" class="ml-[10px] inline-flex shrink-0 items-center justify-end whitespace-nowrap bg-transparent text-right leading-none text-[#1A3A6B] transition-colors hover:text-black focus:outline-none focus:ring-0">Seguir</button>
    @elseif ($connection->status === \App\Models\Friendship::PENDING && $connection->addressee_id === auth()->id())
        <button type="button" wire:click="accept({{ $connection->id }})" class="ml-[10px] inline-flex shrink-0 items-center justify-end whitespace-nowrap bg-transparent text-right leading-none text-[#1A3A6B] transition-colors hover:text-black focus:outline-none focus:ring-0">Aceptar</button>
    @elseif ($connection->status === \App\Models\Friendship::PENDING)
        <span class="ml-[10px] inline-flex shrink-0 items-center justify-end whitespace-nowrap text-right leading-none text-black">Pendiente</span>
    @else
        <span class="ml-[10px] inline-flex shrink-0 items-center justify-end whitespace-nowrap text-right leading-none text-black">Amigo</span>
    @endif
</div>
