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
<div class="panel-border flex min-w-0 items-center gap-[10px] {{ $wide ? 'rounded-xl border p-[20px]' : 'shrink-0' }}">
    <a href="{{ route('profiles.show', $person) }}" class="shrink-0">
        <img src="{{ $person->avatarUrl() }}" alt="Foto de {{ $person->name }}" class="panel-border block h-10 w-10 rounded-full border object-cover">
    </a>
    <a href="{{ route('profiles.show', $person) }}" class="flex min-w-0 flex-1 flex-col">
        <span class="truncate leading-[30px]" title="{{ $fullName }}">{{ $shortName }}</span>
        <span class="people-clamp-2 break-all leading-[30px]" title="{{ $person->email }}">{{ $person->email }}</span>
    </a>
    @if (! $connection)
        <button type="button" wire:click="follow({{ $person->id }})" class="pf-link ml-[10px] shrink-0 whitespace-nowrap text-right leading-none focus:outline-none focus:ring-0">Seguir</button>
    @elseif ($connection->status === \App\Models\Friendship::PENDING && $connection->addressee_id === auth()->id())
        <button type="button" wire:click="accept({{ $connection->id }})" class="pf-link ml-[10px] shrink-0 whitespace-nowrap text-right leading-none focus:outline-none focus:ring-0">Aceptar</button>
    @elseif ($connection->status === \App\Models\Friendship::PENDING)
        <span class="ml-[10px] shrink-0 whitespace-nowrap text-right leading-none">Pendiente</span>
    @else
        <span class="ml-[10px] shrink-0 whitespace-nowrap text-right leading-none">Amigo</span>
    @endif
</div>
