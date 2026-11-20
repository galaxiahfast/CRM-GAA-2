<section wire:poll.30s class="flex flex-col xl:absolute xl:inset-x-0 xl:top-0 xl:max-h-full" aria-label="Personas en línea">
    <header class="mb-[20px] shrink-0">
        <h2 class="home-sec-title panel-accent">En línea ahora</h2>
        <p class="home-sec-text">Qué hace cada persona y dónde está.</p>
        <p class="home-sec-text">{{ count($rows) }} en línea</p>
    </header>

    <div class="flex max-h-[420px] min-h-0 flex-1 flex-col gap-[20px] overflow-y-auto overflow-x-hidden xl:max-h-none" style="overflow-anchor: none;" data-online-list
        x-data x-init="$el.scrollTop = 0; $nextTick(() => { $el.scrollTop = 0; }); setTimeout(() => { $el.scrollTop = 0; }, 700)">
        @foreach ($rows as $row)
            <div wire:key="online-{{ $row['id'] }}" style="--i: {{ min($loop->index, 12) }}" class="home-online-row panel-border shrink-0 overflow-hidden rounded-[20px] border">
                <a href="{{ $row['url'] }}" class="home-row flex items-center gap-[20px] p-[20px]">
                    <span class="relative shrink-0">
                        @if ($row['photo'])
                            <img src="{{ $row['photo'] }}" alt="" class="h-[40px] w-[40px] rounded-full object-cover" loading="lazy">
                        @else
                            <span class="panel-header panel-border panel-text grid h-[40px] w-[40px] place-items-center rounded-full border !text-[13px]">{{ mb_strtoupper(mb_substr($row['name'], 0, 1)) }}</span>
                        @endif
                        <span class="home-online-dot absolute -bottom-px -right-px h-[10px] w-[10px] rounded-full border-2 border-[var(--panel-surface)] bg-emerald-500" aria-hidden="true"></span>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="home-sec-title home-link break-words">{{ $row['name'] }}</span>
                        <span class="home-sec-text block break-words">
                            @if ($row['activity'])
                                {{ $row['activity'] }}@if ($row['customer']) · {{ $row['customer'] }}@endif
                            @else
                                Sin actividad en curso
                            @endif
                        </span>
                        @if ($row['section'])
                            <span class="home-sec-text block break-words">En: {{ $row['section'] }}</span>
                        @endif
                    </span>
                </a>
            </div>
        @endforeach
    </div>
</section>
