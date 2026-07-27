@props(['active' => 'group'])

<div class="space-y-0 bg-[#f4f4f4]">
    <!-- Menú de pestañas superior -->
    <div class="overflow-hidden bg-transparent px-0 sm:px-20">
        <div class="flex flex-nowrap gap-0 p-0 m-0">
            <a href="{{ route('time.admin.dashboard') }}" class="flex flex-1 items-center justify-center gap-2.5 whitespace-nowrap border-b-2 p-5 text-[15px] font-medium transition {{ $active === 'group' ? 'border-[#1A3A6B] text-[#1A3A6B]' : 'border-gray-300 text-gray-500 hover:text-[#1A3A6B]' }}">
                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                Informe General
            </a>
            <a href="{{ route('time.admin.corrections') }}" class="flex flex-1 items-center justify-center gap-2.5 whitespace-nowrap border-b-2 p-5 text-[15px] font-medium transition {{ $active === 'corrections' ? 'border-[#1A3A6B] text-[#1A3A6B]' : 'border-gray-300 text-gray-500 hover:text-[#1A3A6B]' }}">
                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                Corrección de Actividades
            </a>
        </div>
    </div>

</div>
