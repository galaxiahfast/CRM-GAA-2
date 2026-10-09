@props(['active' => 'group'])

<div class="space-y-0 bg-white">
    <!-- Menú de pestañas superior -->
    <div class="overflow-hidden border-b border-zinc-200 bg-white px-[50px]">
        <div class="flex flex-nowrap gap-0 p-0 m-0">
            <a href="{{ route('time.admin.dashboard') }}" class="flex flex-1 items-center justify-center gap-[10px] whitespace-nowrap border-b-2 px-[20px] py-[15px] text-[15px] transition {{ $active === 'group' ? 'border-black font-semibold text-black' : 'border-transparent font-normal text-zinc-500' }} focus:outline-none focus:ring-0">
                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                Informe general
            </a>
            <a href="{{ route('time.admin.online') }}" class="flex flex-1 items-center justify-center gap-[10px] whitespace-nowrap border-b-2 px-[20px] py-[15px] text-[15px] transition {{ $active === 'online' ? 'border-black font-semibold text-black' : 'border-transparent font-normal text-zinc-500' }} focus:outline-none focus:ring-0">
                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l2.5 1.5M19 5l-2 2M5 5l2 2m5-4v2m0 16a8 8 0 100-16 8 8 0 000 16z" /></svg>
                Actividad en línea
            </a>
        </div>
    </div>

</div>
