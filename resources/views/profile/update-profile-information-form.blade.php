<section class="w-full min-w-0 overflow-hidden bg-gray-100" x-data="{ profilePhotoOpen: false, coverDragging: false, photoDragging: false }" x-on:keydown.escape.window="profilePhotoOpen = false">
    <form wire:submit="updateProfileInformation">
        <div class="relative overflow-hidden bg-gradient-to-br from-[#1A3A6B] via-[#28568f] to-[#6f92bd]" style="height: 250px; border-bottom-right-radius: 16px; border-bottom-left-radius: 16px;">
            @if ($editing)
                <label
                    for="cover"
                    class="group absolute inset-0 cursor-pointer"
                    x-on:dragenter.prevent="coverDragging = true"
                    x-on:dragover.prevent="coverDragging = true"
                    x-on:dragleave.prevent="coverDragging = false"
                    x-on:drop.prevent="coverDragging = false; $refs.coverInput.files = $event.dataTransfer.files; $refs.coverInput.dispatchEvent(new Event('change', { bubbles: true }))"
                >
                    <input x-ref="coverInput" id="cover" type="file" accept="image/png,image/jpeg" wire:model="cover" class="sr-only">
                    @if ($cover)
                        <img src="{{ $cover->temporaryUrl() }}" alt="Vista previa de la portada" class="h-full w-full object-cover">
                    @elseif ($this->user->profile_cover_path)
                        <img src="{{ Storage::url($this->user->profile_cover_path) }}" alt="Portada actual del perfil" class="h-full w-full object-cover">
                    @else
                        <div class="absolute -right-16 -top-24 h-72 w-72 rounded-full border border-white/10"></div>
                        <div class="absolute right-28 top-12 h-40 w-40 rounded-full border border-white/10"></div>
                    @endif
                    <span class="absolute inset-0 bg-[#102c55]/35 transition group-hover:bg-[#102c55]/45" :class="coverDragging ? 'bg-[#102c55]/55' : ''"></span>
                    <span class="absolute inset-3 grid place-items-center rounded-xl border-2 border-dashed text-white transition" :class="coverDragging ? 'border-white bg-white/10' : 'border-white/55'">
                        <span class="flex items-center gap-3 rounded-xl bg-[#102c55]/70 px-5 py-3 text-[15px] font-semibold shadow-lg backdrop-blur-sm">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16.5V19a1 1 0 001 1h14a1 1 0 001-1v-2.5M8 8l4-4 4 4m-4-4v12" /></svg>
                            Arrastra una portada o selecciona un archivo
                        </span>
                    </span>
                </label>
                <x-input-error for="cover" class="absolute bottom-3 left-1/2 z-10 -translate-x-1/2 rounded-lg bg-red-50 px-3 py-2" />
            @else
                @if ($this->user->profile_cover_path)
                    <img src="{{ Storage::url($this->user->profile_cover_path) }}" alt="Portada del perfil" class="h-full w-full object-cover">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/30 to-transparent"></div>
                @else
                    <div class="absolute -right-16 -top-24 h-72 w-72 rounded-full border border-white/10"></div>
                    <div class="absolute right-28 top-12 h-40 w-40 rounded-full border border-white/10"></div>
                @endif
            @endif
        </div>

        <div class="relative min-w-0 px-[80px]">
        @php
            $organizationalProfile = $this->user->activeOrganizationalProfile()->with(['physicalArea', 'jobPosition'])->first();
            $supervisors = $this->user->superiors()->get();
            $subordinates = $this->user->subordinates()->get();
            $totalFriends = $this->user->friends()->count();
            $friends = $this->user->friends()->orderBy('name')->orderBy('last_name')->limit(5)->get();
        @endphp
        <div class="flex items-start justify-between gap-[20px]">
            <div class="min-w-0">
                @if ($editing)
                    <label
                        for="photo"
                        aria-label="Cambiar foto de perfil"
                        class="group relative block shrink-0 cursor-pointer rounded-full border-[5px] border-[#e7e7e7] bg-gray-100 p-0"
                        style="width: 144px; height: 144px; margin-top: -72px;"
                        x-on:dragenter.prevent="photoDragging = true"
                        x-on:dragover.prevent="photoDragging = true"
                        x-on:dragleave.prevent="photoDragging = false"
                        x-on:drop.prevent="photoDragging = false; $refs.photoInput.files = $event.dataTransfer.files; $refs.photoInput.dispatchEvent(new Event('change', { bubbles: true }))"
                    >
                        <input x-ref="photoInput" id="photo" type="file" accept="image/png,image/jpeg" wire:model="photo" class="sr-only">
                        @if ($photo)
                            <img src="{{ $photo->temporaryUrl() }}" alt="Vista previa de la foto" class="h-full w-full rounded-full object-cover">
                        @elseif ($this->user->profile_photo_path)
                            <img src="{{ $this->user->profile_photo_url }}" alt="Foto actual de {{ $this->user->name }}" class="h-full w-full rounded-full object-cover">
                        @else
                            <span class="grid h-full w-full place-items-center rounded-full bg-[#eaf1fa] text-2xl font-bold text-[#1A3A6B]">{{ mb_strtoupper(mb_substr($this->user->name, 0, 1).mb_substr($this->user->last_name ?? '', 0, 1)) }}</span>
                        @endif
                        <span class="absolute inset-0 grid place-items-center rounded-full bg-[#102c55]/55 text-center text-white opacity-0 transition group-hover:opacity-100" :class="photoDragging ? 'opacity-100' : ''">
                            <span class="px-4 text-[12px] font-semibold leading-5">Arrastra o selecciona una foto</span>
                        </span>
                        <span class="absolute h-4 w-4 rounded-full {{ $isOnline ? 'bg-emerald-500' : 'bg-gray-400' }} ring-[3px] ring-gray-100" style="right: 10px; bottom: 10px;" title="{{ $isOnline ? 'Activo' : 'Inactivo' }}" aria-label="{{ $isOnline ? 'Activo' : 'Inactivo' }}"></span>
                    </label>
                    <x-input-error for="photo" class="mt-2" />
                @else
                    <button type="button" @click="profilePhotoOpen = true" aria-label="Ampliar foto de perfil" class="relative block shrink-0 cursor-zoom-in rounded-full border-[5px] border-[#e7e7e7] bg-gray-100 p-0" style="width: 144px; height: 144px; margin-top: -72px;">
                        @if ($this->user->profile_photo_path)
                            <img src="{{ $this->user->profile_photo_url }}" alt="Foto de {{ $this->user->name }}" class="h-full w-full rounded-full object-cover">
                        @else
                            <span class="grid h-full w-full place-items-center rounded-full bg-[#eaf1fa] text-2xl font-bold text-[#1A3A6B]">{{ mb_strtoupper(mb_substr($this->user->name, 0, 1).mb_substr($this->user->last_name ?? '', 0, 1)) }}</span>
                        @endif
                        <span class="absolute h-4 w-4 rounded-full {{ $isOnline ? 'bg-emerald-500' : 'bg-gray-400' }} ring-[3px] ring-gray-100" style="right: 10px; bottom: 10px;" title="{{ $isOnline ? 'Activo' : 'Inactivo' }}" aria-label="{{ $isOnline ? 'Activo' : 'Inactivo' }}"></span>
                    </button>
                @endif
                <div class="mt-[15px] min-w-0 space-y-[15px] text-left">
                    <div class="space-y-[15px]">
                        <h2 class="flex min-w-0 items-center gap-2 text-xl font-bold text-gray-900">
                            @if ($editing)
                                <span class="flex min-w-0 items-center gap-2">
                                    <input aria-label="Nombre" type="text" wire:model="state.name" class="min-w-[90px] max-w-[240px] border-0 border-b border-dashed border-[#9eb4d3] bg-transparent p-0 pb-1 text-xl font-bold text-gray-900 shadow-none focus:border-[#1A3A6B] focus:ring-0" style="field-sizing: content;">
                                    <input aria-label="Apellido" type="text" wire:model="state.last_name" class="min-w-[90px] max-w-[280px] border-0 border-b border-dashed border-[#9eb4d3] bg-transparent p-0 pb-1 text-xl font-bold text-gray-900 shadow-none focus:border-[#1A3A6B] focus:ring-0" style="field-sizing: content;">
                                </span>
                            @else
                                <span class="truncate">{{ $this->user->name }} {{ $this->user->last_name }}</span>
                            @endif
                            <svg class="h-5 w-5 shrink-0 text-[#1A3A6B]" viewBox="0 0 24 24" fill="currentColor" aria-label="Perfil verificado"><path d="M12 2.5l2.1 1.45 2.55-.2.95 2.38 2.27 1.18-.2 2.55L21.12 12l-1.45 2.14.2 2.55-2.27 1.18-.95 2.38-2.55-.2L12 21.5l-2.1-1.45-2.55.2-.95-2.38-2.27-1.18.2-2.55L2.88 12l1.45-2.14-.2-2.55L6.4 6.13l.95-2.38 2.55.2L12 2.5zm3.53 7.03l-1.06-1.06L11 11.94 9.53 10.47l-1.06 1.06L11 14.06l4.53-4.53z" /></svg>
                        </h2>
                        @if ($editing)
                            <div class="flex gap-3"><x-input-error for="name" /><x-input-error for="last_name" /></div>
                        @endif
                        <p class="truncate text-[15px] text-gray-600">González Alonzo y Asociados S.C.P.</p>
                    </div>

                    <div class="flex flex-nowrap items-center gap-x-[16px] whitespace-nowrap text-[15px] text-gray-600">
                        <div class="flex items-center gap-[5px] whitespace-nowrap">
                            <svg class="h-3.5 w-3.5 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657 13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span>Mérida, Yucatán, México</span>
                        </div>
                        <div class="flex shrink-0 items-center gap-[5px]">
                            <svg class="h-3.5 w-3.5 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            @if ($editing)
                                <input aria-label="Correo electrónico" type="email" wire:model="state.email" class="w-[280px] border-0 border-b border-dashed border-[#9eb4d3] bg-transparent p-0 pb-1 text-[15px] text-gray-600 shadow-none focus:border-[#1A3A6B] focus:ring-0">
                            @else
                                <span>{{ $this->user->email }}</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-[5px] whitespace-nowrap">
                            <svg class="h-3.5 w-3.5 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <circle cx="12" cy="12" r="9" stroke-width="1.5" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.5 9h17M3.5 15h17M12 3c2.2 2.45 3.3 5.45 3.3 9S14.2 18.55 12 21M12 3C9.8 5.45 8.7 8.45 8.7 12S9.8 18.55 12 21" />
                            </svg>
                            <span>Español</span>
                        </div>
                    </div>
                    <div class="flex flex-nowrap items-center justify-start gap-x-[8px] whitespace-nowrap text-left text-[15px] text-gray-500">
                        <span>{{ number_format($activityCount) }} actividades</span><span aria-hidden="true">·</span>
                        <span>{{ number_format($friendCount) }} amigos</span><span aria-hidden="true">·</span>
                        <span>{{ (int) $this->user->created_at->diffInDays(now()) }} días con la cuenta</span><span aria-hidden="true">·</span>
                        <span>Actualizado {{ $this->user->updated_at->locale('es')->diffForHumans() }}</span>
                    </div>
                </div>
            </div>
            <div class="mt-[40px] flex shrink-0 items-center {{ $editing ? 'gap-[10px]' : 'gap-[30px]' }}">
                @if ($editing)
                    <button type="button" wire:click="cancelEditing" class="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-gray-300 bg-gray-100 px-4 text-[15px] font-semibold text-gray-600 hover:bg-gray-200" style="width: 190px;">
                        Cancelar
                    </button>
                    <button type="submit" wire:loading.attr="disabled" class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-[#1A3A6B] px-4 text-[15px] font-semibold text-white hover:bg-[#142f58] disabled:opacity-60" style="width: 190px;">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 4h12l2 2v14H5V4zm3 0v6h8V4M8 20v-6h8v6" /></svg>
                        Guardar cambios
                    </button>
                @else
                    <a href="{{ route('inicio') }}" class="inline-flex items-center gap-[15px] border-0 bg-transparent p-0 text-[15px] font-medium text-gray-500 transition-colors hover:text-[#1A3A6B]">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 11.5L12 4l9 7.5M5.5 10v10h13V10M9 20v-6h6v6" /></svg>
                        Regresar al inicio
                    </a>
                    <button type="button" @click="$dispatch('open-people-drawer')" class="inline-flex items-center gap-[15px] border-0 bg-transparent p-0 text-[15px] font-medium text-gray-500 transition-colors hover:text-[#1A3A6B]">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2m7-10a4 4 0 100-8 4 4 0 000 8zm10 0v6m3-3h-6" /></svg>
                        Agregar amigos
                    </button>
                    <button type="button" wire:click="startEditing" class="inline-flex items-center gap-[15px] border-0 bg-transparent p-0 text-[15px] font-medium text-gray-500 transition-colors hover:text-[#1A3A6B]">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.9 3.6a2.1 2.1 0 013 3L9 17.5 4 19l1.5-5L16.9 3.6z" /></svg>
                        Editar perfil
                    </button>
                @endif
            </div>
        </div>

            <div class="grid gap-[40px]" style="grid-template-columns: minmax(0, 40%) minmax(0, 60%); padding-top: 75px;">
                <article class="min-w-0">
                    <p class="text-[16px] font-semibold text-gray-800">Información organizacional</p>
                    <dl class="mt-[25px] grid grid-cols-2 gap-x-[30px] gap-y-[25px]">
                        <div class="min-w-0"><dt class="text-[15px] font-semibold text-gray-600">Área organizacional asignada</dt><dd class="mt-1 break-words text-[15px] text-gray-700">{{ $organizationalProfile?->physicalArea?->name ?? 'Sin asignar' }}</dd></div>
                        <div class="min-w-0"><dt class="text-[15px] font-semibold text-gray-600">Puesto dentro de la organización</dt><dd class="mt-1 break-words text-[15px] text-gray-700">{{ $organizationalProfile?->jobPosition?->name ?? 'Sin asignar' }}</dd></div>
                        <div class="min-w-0"><dt class="text-[15px] font-semibold text-gray-600">Colaboradores subordinados</dt><dd class="mt-1 break-words text-[15px] text-gray-700">{{ $subordinates->isEmpty() ? 'No tiene colaboradores aún' : $subordinates->count() }}</dd></div>
                        <div class="min-w-0"><dt class="text-[15px] font-semibold text-gray-600">Supervisor directo asignado</dt><dd class="mt-1 break-words text-[15px] text-gray-700">{{ $supervisors->map(fn ($user) => trim($user->name.' '.$user->last_name))->join(', ') ?: 'Sin supervisor' }}</dd></div>
                    </dl>
                </article>
                <article class="min-w-0 pl-[40px]">
                    <p class="text-[16px] font-semibold text-gray-800">Acerca de mí</p>
                    @if ($editing)
                        <textarea aria-label="Descripción" wire:model="state.profile_description" rows="3" maxlength="500" class="mt-[12px] block w-full resize-none border-0 border-b border-dashed border-[#9eb4d3] bg-transparent p-0 pb-2 text-[15px] text-gray-600 shadow-none focus:border-[#1A3A6B] focus:ring-0" style="max-width: 620px; line-height: 2.5; text-align: left;" placeholder="Escribe una descripción sobre ti..."></textarea>
                        <x-input-error for="profile_description" class="mt-2" />
                    @else
                        <p class="mt-[12px] text-gray-600" style="max-width: 620px; line-height: 2.5; text-align: left;">{{ $this->user->profile_description ?: 'Colaborador de González Alonzo y Asociados S.C.P., ubicado en Mérida, Yucatán, México. Este perfil reúne información profesional y organizacional para facilitar la comunicación, la colaboración y el contacto con otros integrantes del equipo.' }}</p>
                    @endif
                </article>
            </div>
            <article class="mt-[50px] min-w-0">
                <p class="text-[16px] font-semibold text-gray-800">Amigos</p>
                <div class="mt-[15px] flex min-w-0 items-center overflow-hidden pl-1">
                        @forelse ($friends as $friend)
                            <a href="{{ route('profiles.show', $friend) }}" title="{{ $friend->name }} {{ $friend->last_name }}" class="relative -ml-[14px] first:ml-0">
                                <img src="{{ $friend->profile_photo_url }}" alt="Foto de {{ $friend->name }}" class="h-10 w-10 rounded-full border border-[#e7e7e7] object-cover ring-1 ring-[#e7e7e7]">
                            </a>
                        @empty
                            <p class="text-gray-400">Todavía no tienes amigos agregados.</p>
                        @endforelse
                        @if ($totalFriends > 5)
                            <a href="{{ route('profiles.index') }}" title="{{ $totalFriends - 5 }} amigos más" class="relative -ml-[14px] grid h-10 w-10 shrink-0 place-items-center rounded-full border border-[#e7e7e7] bg-[#1A3A6B] text-[11px] font-semibold text-white ring-1 ring-[#e7e7e7]">
                                +{{ $totalFriends - 5 }}
                            </a>
                        @endif
                </div>
            </article>
        <x-action-message on="saved" class="mb-[25px] mt-4 text-sm font-medium text-emerald-600">Perfil actualizado correctamente.</x-action-message>
        </div>
    </form>
    <template x-teleport="body">
        <div x-show="profilePhotoOpen" x-cloak x-transition.opacity @click.self="profilePhotoOpen = false" class="fixed inset-0 z-[100] grid cursor-zoom-out place-items-center bg-gray-950/55 p-6 backdrop-blur-md" role="dialog" aria-modal="true" aria-label="Foto de perfil ampliada">
            <button type="button" @click="profilePhotoOpen = false" aria-label="Cerrar foto ampliada" class="absolute right-6 top-6 grid h-11 w-11 place-items-center rounded-full bg-black/45 text-white hover:bg-black/65">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 6l12 12M18 6 6 18" /></svg>
            </button>
            <div class="cursor-default" @click.stop>
                @if ($this->user->profile_photo_path)
                    <img src="{{ $this->user->profile_photo_url }}" alt="Foto ampliada de {{ $this->user->name }}" class="max-h-[82vh] max-w-[82vw] rounded-full border-[6px] border-[#e7e7e7] object-contain shadow-2xl">
                @else
                    <span class="grid h-[min(70vw,420px)] w-[min(70vw,420px)] place-items-center rounded-full border-[6px] border-[#e7e7e7] bg-[#eaf1fa] text-8xl font-bold text-[#1A3A6B] shadow-2xl">{{ mb_strtoupper(mb_substr($this->user->name, 0, 1).mb_substr($this->user->last_name ?? '', 0, 1)) }}</span>
                @endif
            </div>
        </div>
    </template>
</section>
