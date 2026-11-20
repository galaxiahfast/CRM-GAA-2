<section wire:poll.30s class="flex flex-col xl:absolute xl:inset-x-0 xl:top-0 xl:max-h-full" aria-label="Personas en línea">
    <header class="home-sec-head2 shrink-0">
        <div class="min-w-0">
            <h2 class="home-h2">En línea ahora</h2>
            <p class="home-muted">Qué hace cada persona y dónde está.</p>
        </div>
        <div class="home-sec-meta">
            <span class="home-chip"><span class="home-live-dot" aria-hidden="true"></span>{{ count($rows) }} en línea</span>
        </div>
    </header>

    <div class="home-online-list flex max-h-[420px] min-h-0 flex-1 flex-col overflow-y-auto overflow-x-hidden xl:max-h-none" style="overflow-anchor: none;" data-online-list
        x-data x-init="$el.scrollTop = 0; $nextTick(() => { $el.scrollTop = 0; }); setTimeout(() => { $el.scrollTop = 0; }, 700)">
        @foreach ($rows as $row)
            <div wire:key="online-{{ $row['id'] }}" style="--i: {{ min($loop->index, 12) }}" class="home-online-row shrink-0">
                <a href="{{ $row['url'] }}" class="home-row flex items-center gap-[14px]">
                    <span class="relative shrink-0">
                        @if ($row['photo'])
                            <img src="{{ $row['photo'] }}" alt="" class="h-[50px] w-[50px] rounded-full border border-[var(--panel-border)] object-cover" loading="lazy">
                        @else
                            <span class="panel-header panel-border panel-text grid h-[50px] w-[50px] place-items-center rounded-full border !text-[15px]">{{ mb_strtoupper(mb_substr($row['name'], 0, 1)) }}</span>
                        @endif
                        <span class="home-online-dot absolute bottom-[2px] right-[2px] h-[10px] w-[10px] rounded-full border-2 border-[var(--panel-surface)] bg-emerald-500" aria-hidden="true"></span>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="home-online-name break-words">{{ $row['name'] }}</span>
                        <span class="home-online-meta block break-words">
                            @if ($row['activity'])
                                {{ $row['activity'] }}@if ($row['customer']) · {{ $row['customer'] }}@endif
                            @else
                                Sin actividad en curso
                            @endif
                        </span>
                        @if ($row['section'])
                            <span class="home-online-meta block break-words">En: {{ $row['section'] }}</span>
                        @endif
                    </span>
                </a>
            </div>
        @endforeach
    </div>
</section>
