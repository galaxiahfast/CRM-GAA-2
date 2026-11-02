<x-administration-list-page title="Gestión de auxiliares" subtitle="Administra los auxiliares de la plataforma">
    <x-slot name="icon">
        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 0 0-5.36-1.86M17 20H7m10 0v-2a5 5 0 0 0-10 0v2M15 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
        </svg>
    </x-slot>

    <x-slot name="actions">
        <label for="admin-interns-search" class="sr-only">Buscar auxiliar</label>
        <input id="admin-interns-search" type="text" wire:model.live.debounce.250ms="search" placeholder="Buscar auxiliar..." autocomplete="off"
            class="admin-list-search h-11 w-full rounded-xl border border-zinc-200 bg-[#ffffff] px-[15px] text-[15px] text-black placeholder:text-zinc-500 sm:w-72">
        @unless ($embedded)
            <a href="{{ route('administracion.create.users', ['Intern' => true]) }}"
                class="inline-flex h-11 items-center justify-center whitespace-nowrap rounded-xl bg-black px-[20px] text-[15px] font-semibold text-white transition hover:bg-zinc-800 focus:outline-none focus:ring-0">Agregar auxiliar</a>
        @endunless
    </x-slot>

    <x-administration-data-table>
        <x-slot name="head">
            <th scope="col" class="px-[15px] py-[15px] font-medium">Nombre</th>
            <th scope="col" class="hidden px-[15px] py-[15px] font-medium md:table-cell">Apellido</th>
            <th scope="col" class="hidden px-[15px] py-[15px] font-medium md:table-cell">Email</th>
            <th scope="col" class="hidden px-[15px] py-[15px] font-medium md:table-cell">Rol</th>
            <th scope="col" class="hidden px-[15px] py-[15px] font-medium xl:table-cell">Creación</th>
            <th scope="col" class="hidden px-[15px] py-[15px] font-medium xl:table-cell">Actualización</th>
            <th scope="col" class="px-[15px] py-[15px] text-right font-medium">Acciones</th>
        </x-slot>

        <x-slot name="body">
            @forelse ($users as $user)
                <tr wire:key="admin-intern-{{ $user->id }}" class="transition hover:bg-zinc-50">
                    <td class="w-full max-w-0 px-[15px] py-[15px] md:w-auto md:max-w-none md:whitespace-nowrap">
                        <span class="block">{{ $user['name'] }}<span class="md:hidden"> {{ $user['last_name'] }}</span></span>
                        <span class="block truncate text-[#71717a] md:hidden" title="{{ mb_strtolower((string) $user['email']) }}">{{ mb_strtolower((string) $user['email']) }}</span>
                    </td>
                    <td class="hidden whitespace-nowrap px-[15px] py-[15px] md:table-cell">{{ $user['last_name'] }}</td>
                    <td class="hidden px-[15px] py-[15px] md:table-cell">
                        <span class="block max-w-[170px] xl:max-w-[220px] truncate" title="{{ mb_strtolower((string) $user['email']) }}">{{ mb_strtolower((string) $user['email']) }}</span>
                    </td>
                    <td class="hidden whitespace-nowrap px-[15px] py-[15px] md:table-cell">{{ $user->role?->role ?? '—' }}</td>
                    <td class="hidden whitespace-nowrap px-[15px] py-[15px] text-[#71717a] xl:table-cell">{{ $user->created_at?->format('d/m/Y') }}</td>
                    <td class="hidden whitespace-nowrap px-[15px] py-[15px] text-[#71717a] xl:table-cell">{{ $user->updated_at?->format('d/m/Y') }}</td>
                    <td class="whitespace-nowrap px-[15px] py-[15px] text-right">
                        <div class="inline-flex items-center gap-[10px]">
                            @unless ($embedded)
                                <a href="{{ route('administracion.edit.users', $user) }}"
                                    class="inline-flex h-9 items-center justify-center rounded-xl border border-zinc-200 bg-[#ffffff] px-[15px] text-[15px] text-black transition hover:bg-zinc-100 focus:outline-none focus:ring-0">Editar</a>
                            @endunless
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-[20px] py-[40px] text-center text-[#71717a]">No se encontraron auxiliares.</td>
                </tr>
            @endforelse
        </x-slot>
    </x-administration-data-table>
</x-administration-list-page>
