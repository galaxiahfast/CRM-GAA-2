<section wire:poll.30s class="panel-surface panel-border overflow-hidden rounded-xl border" aria-label="Personas en línea">
    <header class="panel-header panel-border flex flex-wrap items-center justify-between gap-x-[20px] gap-y-[15px] border-b p-[20px]">
        <div class="min-w-0">
            <p class="panel-accent flex items-center gap-[10px] leading-none">
                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" /></svg>
                <span>En línea ahora</span>
            </p>
            <p class="mt-[15px] leading-none">Qué hace cada persona y dónde está.</p>
        </div>
        <span class="leading-none">{{ count($rows) }} en línea</span>
    </header>

    <div class="max-h-[420px] overflow-y-auto overflow-x-hidden" data-online-list>
        @foreach ($rows as $row)
            <div wire:key="online-{{ $row['id'] }}" class="panel-border border-b last:border-b-0">
                <a href="{{ $row['url'] }}" class="home-row flex items-center gap-[15px] p-[20px]">
                    <span class="relative shrink-0">
                        @if ($row['photo'])
                            <img src="{{ $row['photo'] }}" alt="" class="h-[40px] w-[40px] rounded-full object-cover" loading="lazy">
                        @else
                            <span class="panel-header panel-border panel-text grid h-[40px] w-[40px] place-items-center rounded-full border !text-[12px]">{{ mb_strtoupper(mb_substr($row['name'], 0, 1)) }}</span>
                        @endif
                        <span class="absolute -bottom-px -right-px h-[10px] w-[10px] rounded-full border-2 border-[var(--panel-surface)] bg-emerald-500" aria-hidden="true"></span>
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
