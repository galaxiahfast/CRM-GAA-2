<section wire:poll.30s class="panel-surface panel-border flex flex-col overflow-hidden rounded-xl border xl:absolute xl:inset-x-0 xl:top-0 xl:max-h-full" aria-label="Personas en línea">
    <header class="panel-header panel-border flex flex-wrap items-center justify-between gap-x-[20px] gap-y-[15px] border-b p-[20px]">
        <div class="min-w-0">
            <p class="panel-accent flex items-center gap-[10px] leading-none">
                
                <span>En línea ahora</span>
            </p>
            <p class="mt-[15px] leading-none">Qué hace cada persona y dónde está.</p>
        </div>
        <span class="leading-none">{{ count($rows) }} en línea</span>
    </header>

    <div class="max-h-[420px] min-h-0 flex-1 overflow-y-auto overflow-x-hidden xl:max-h-none" style="overflow-anchor: none;" data-online-list
        x-data x-init="$el.scrollTop = 0; $nextTick(() => { $el.scrollTop = 0; }); setTimeout(() => { $el.scrollTop = 0; }, 700)">
        @foreach ($rows as $row)
            <div wire:key="online-{{ $row['id'] }}" style="--i: {{ min($loop->index, 12) }}" class="home-online-row panel-border border-b last:border-b-0">
                <a href="{{ $row['url'] }}" class="home-row flex items-center gap-[15px] p-[20px]">
                    <span class="relative shrink-0">
                        @if ($row['photo'])
                            <img src="{{ $row['photo'] }}" alt="" class="h-[40px] w-[40px] rounded-full object-cover" loading="lazy">
                        @else
                            <span class="panel-header panel-border panel-text grid h-[40px] w-[40px] place-items-center rounded-full border !text-[13px]">{{ mb_strtoupper(mb_substr($row['name'], 0, 1)) }}</span>
                        @endif
                        <span class="home-online-dot absolute -bottom-px -right-px h-[10px] w-[10px] rounded-full border-2 border-[var(--panel-surface)] bg-emerald-500" aria-hidden="true"></span>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="home-link block truncate leading-none">{{ $row['name'] }}</span>
                        <span class="mt-[15px] block truncate leading-none">
                            @if ($row['activity'])
                                {{ $row['activity'] }}@if ($row['customer']) · {{ $row['customer'] }}@endif
                            @else
                                Sin actividad en curso
                            @endif
                        </span>
                        @if ($row['section'])
                            <span class="mt-[15px] block truncate leading-none">En: {{ $row['section'] }}</span>
                        @endif
                    </span>
                </a>
            </div>
        @endforeach
    </div>
</section>
