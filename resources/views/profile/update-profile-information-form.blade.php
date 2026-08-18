<section class="profile-monochrome min-h-[calc(100dvh-90px)] bg-white text-[15px] text-zinc-900" x-data="{ profilePhotoOpen: false, friendsDirectoryOpen: false, coverDragging: false, photoDragging: false }" x-on:keydown.escape.window="profilePhotoOpen = false; friendsDirectoryOpen = false">
    @php
        $organizationalProfile = $this->user->activeOrganizationalProfile()->with(['physicalArea', 'jobPosition'])->first();
        $supervisors = $this->user->superiors()->get();
        $subordinates = $this->user->subordinates()->get();
        $totalFriends = $this->user->friends()->count();
        $allFriends = $this->user->friends()->orderBy('name')->orderBy('last_name')->get();
        $friends = $allFriends->take(8);
    @endphp

    @if ($standalone)
        <header class="no-print flex items-center justify-between gap-20 whitespace-nowrap border-b border-zinc-200 p-[40px]">
            <nav class="flex min-w-0 items-center gap-3 text-zinc-500" aria-label="Ruta de navegación">
                <span class="font-medium">Centro de Información</span>
                <span class="text-zinc-300">&gt;</span>
                <span class="font-medium text-zinc-500">Perfiles</span>
                <span class="text-zinc-300">&gt;</span>
                <span class="max-w-[320px] truncate font-semibold text-black" title="{{ trim($this->user->name.' '.$this->user->last_name) }}">{{ trim($this->user->name.' '.$this->user->last_name) }}</span>
            </nav>

            <div class="flex shrink-0 items-center gap-[30px]">
                <button type="button" aria-label="Descargar perfil en PDF" title="Descargar PDF" class="inline-flex items-center gap-[15px] border-0 bg-transparent p-0 font-medium text-zinc-500 transition-colors hover:text-black">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                    <span>Descargar PDF</span>
                </button>
                <button type="button" onclick="window.print()" aria-label="Imprimir perfil" class="inline-flex items-center gap-[15px] border-0 bg-transparent p-0 font-medium text-zinc-500 transition-colors hover:text-black">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10zM9 7V3h6v4" /></svg>
                    <span>Imprimir</span>
                </button>
            </div>
        </header>
    @endif

    @if ($this->isOwnProfile)
        <form wire:submit="updateProfileInformation">
    @else
        <div>
    @endif
        <main class="mx-auto w-full max-w-[1500px] space-y-[25px] p-[40px]">
            <section class="flex items-end justify-between gap-[20px]">
                <div class="flex items-start gap-[15px]">
                    <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl border border-zinc-200 text-black">
                        <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 21a8 8 0 00-16 0m8-10a4 4 0 100-8 4 4 0 000 8z" /></svg>
                    </span>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight text-gray-900">Perfil de usuario</h1>
                        <p class="mt-1 text-gray-500">Consulta y administra tu información personal y organizacional.</p>
                    </div>
                </div>

                <div class="no-print flex items-center gap-[15px]">
                    @if (! $this->isOwnProfile)
                        @php($friendship = $this->friendship)
                        @if (! $friendship)
                            <button type="button" wire:click="requestFriendship" class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-black px-5 font-semibold text-white transition hover:bg-zinc-800">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4m9-10a4 4 0 100-8 4 4 0 000 8zm8 3h-6m3-3v6" /></svg>
                                Seguir
                            </button>
                        @elseif ($friendship->status === \App\Models\Friendship::PENDING && $friendship->addressee_id === auth()->id())
                            <button type="button" wire:click="acceptFriendship" class="inline-flex h-10 items-center justify-center rounded-xl bg-black px-5 font-semibold text-white transition hover:bg-zinc-800">Aceptar solicitud</button>
                        @elseif ($friendship->status === \App\Models\Friendship::PENDING)
                            <button type="button" disabled class="inline-flex h-10 items-center justify-center rounded-xl border border-zinc-200 bg-zinc-100 px-5 font-semibold text-zinc-500">Solicitud enviada</button>
                        @else
                            <button type="button" wire:click="removeFriendship" wire:confirm="¿Quieres dejar de seguir a esta persona y eliminarla de tus amigos?" class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-black px-5 font-semibold text-white transition hover:bg-zinc-800">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4m9-10a4 4 0 100-8 4 4 0 000 8zm8 2h-6" /></svg>
                                Amigos
                            </button>
                        @endif
                        <a href="{{ route('profile.show') }}" class="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-zinc-200 bg-white px-5 font-semibold text-black transition hover:bg-zinc-100">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m15 18-6-6 6-6" /></svg>
                            Regresar a mi perfil
                        </a>
                    @elseif ($editing)
                        <button type="button" wire:click="cancelEditing" class="inline-flex h-10 items-center justify-center rounded-xl border border-gray-200 bg-white px-5 font-semibold text-gray-600 transition hover:border-gray-300 hover:bg-gray-50">Cancelar</button>
                        <button type="submit" wire:loading.attr="disabled" class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-black px-5 font-semibold text-white transition hover:bg-zinc-800 disabled:opacity-60">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 4h12l2 2v14H5V4zm3 0v6h8V4M8 20v-6h8v6" /></svg>
                            Guardar cambios
                        </button>
                    @else
                        <button type="button" wire:click="startEditing" class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-black px-5 font-semibold text-white transition hover:bg-zinc-800">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16.9 3.6a2.1 2.1 0 013 3L9 17.5 4 19l1.5-5L16.9 3.6z" /></svg>
                            Editar perfil
                        </button>
                    @endif
                </div>
            </section>

            <section class="grid min-h-[650px] grid-cols-[330px_minmax(0,1fr)] overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-[0_12px_32px_rgba(0,0,0,0.04)]">
                <aside class="flex h-0 min-h-full flex-col overflow-hidden border-r border-zinc-200 bg-white">
                    <div class="relative h-[150px] overflow-hidden bg-gradient-to-br from-black via-zinc-800 to-zinc-600">
                        @if ($editing)
                            <label for="cover" class="group absolute inset-0 cursor-pointer" x-on:dragenter.prevent="coverDragging = true" x-on:dragover.prevent="coverDragging = true" x-on:dragleave.prevent="coverDragging = false" x-on:drop.prevent="coverDragging = false; $refs.coverInput.files = $event.dataTransfer.files; $refs.coverInput.dispatchEvent(new Event('change', { bubbles: true }))">
                                <input x-ref="coverInput" id="cover" type="file" accept="image/png,image/jpeg" wire:model="cover" class="sr-only">
                                @if ($cover)
                                    <img src="{{ $cover->temporaryUrl() }}" alt="Vista previa de portada" class="h-full w-full object-cover">
                                @elseif ($this->user->profile_cover_path)
                                    <img src="{{ Storage::url($this->user->profile_cover_path) }}" alt="Portada actual" class="h-full w-full object-cover">
                                @endif
                                <span class="absolute inset-0 grid place-items-center bg-black/45 text-center text-white transition group-hover:bg-black/60" :class="coverDragging ? 'bg-black/65' : ''">
                                    <span class="px-5 text-[12px] font-semibold leading-5">Arrastra o selecciona una portada</span>
                                </span>
                            </label>
                        @elseif ($this->user->profile_cover_path)
                            <img src="{{ Storage::url($this->user->profile_cover_path) }}" alt="Portada del perfil" class="h-full w-full object-cover">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/25 to-transparent"></div>
                        @else
                            <div class="absolute -right-10 -top-16 h-48 w-48 rounded-full border border-white/10"></div>
                            <div class="absolute right-20 top-8 h-24 w-24 rounded-full border border-white/10"></div>
                        @endif
                    </div>

                    <div class="shrink-0 px-[25px] pb-[20px] text-center">
                        <div class="relative mx-auto -mt-[58px] h-[116px] w-[116px]">
                            @if ($editing)
                                <label for="photo" class="group block h-full w-full cursor-pointer overflow-hidden rounded-full border-[5px] border-gray-100 bg-white shadow-md" x-on:dragenter.prevent="photoDragging = true" x-on:dragover.prevent="photoDragging = true" x-on:dragleave.prevent="photoDragging = false" x-on:drop.prevent="photoDragging = false; $refs.photoInput.files = $event.dataTransfer.files; $refs.photoInput.dispatchEvent(new Event('change', { bubbles: true }))">
                                    <input x-ref="photoInput" id="photo" type="file" accept="image/png,image/jpeg" wire:model="photo" class="sr-only">
                                    @if ($photo)
                                        <img src="{{ $photo->temporaryUrl() }}" alt="Vista previa de la foto" class="h-full w-full object-cover">
                                    @elseif ($this->user->profile_photo_path)
                                        <img src="{{ $this->user->profile_photo_url }}" alt="Foto actual" class="h-full w-full object-cover">
                                    @else
                                        <span class="grid h-full w-full place-items-center bg-zinc-100 text-3xl font-bold text-black">{{ mb_strtoupper(mb_substr($this->user->name, 0, 1).mb_substr($this->user->last_name ?? '', 0, 1)) }}</span>
                                    @endif
                                    <span class="absolute inset-[5px] grid place-items-center rounded-full bg-black/60 px-3 text-[11px] font-semibold text-white opacity-0 transition group-hover:opacity-100" :class="photoDragging ? 'opacity-100' : ''">Cambiar foto</span>
                                </label>
                            @else
                                <button type="button" @click="profilePhotoOpen = true" class="block h-full w-full cursor-zoom-in overflow-hidden rounded-full border-[5px] border-gray-100 bg-white shadow-md" aria-label="Ampliar foto de perfil">
                                    @if ($this->user->profile_photo_path)
                                        <img src="{{ $this->user->profile_photo_url }}" alt="Foto de {{ $this->user->name }}" class="h-full w-full object-cover">
                                    @else
                                        <span class="grid h-full w-full place-items-center bg-zinc-100 text-3xl font-bold text-black">{{ mb_strtoupper(mb_substr($this->user->name, 0, 1).mb_substr($this->user->last_name ?? '', 0, 1)) }}</span>
                                    @endif
                                </button>
                            @endif
                            <span class="absolute bottom-2 right-2 h-4 w-4 rounded-full {{ $isOnline ? 'bg-emerald-500' : 'bg-gray-400' }} ring-[3px] ring-gray-100" title="{{ $isOnline ? 'Activo' : 'Inactivo' }}"></span>
                        </div>

                        <x-input-error for="photo" class="mt-2" />
                        <x-input-error for="cover" class="mt-2" />

                        <div class="mt-[15px]">
                            @if ($editing)
                                <div class="space-y-[15px] text-left">
                                    <div>
                                        <label for="profile-name" class="mb-2 block font-semibold text-black">Nombres</label>
                                        <input id="profile-name" type="text" wire:model="state.name" autocomplete="given-name" placeholder="Escribe tus nombres" class="h-11 w-full rounded-xl border-zinc-200 bg-white px-3 text-black focus:border-black focus:ring-black/10">
                                        <x-input-error for="name" class="mt-2" />
                                    </div>
                                    <div>
                                        <label for="profile-last-name" class="mb-2 block font-semibold text-black">Apellidos</label>
                                        <input id="profile-last-name" type="text" wire:model="state.last_name" autocomplete="family-name" placeholder="Escribe tus apellidos" class="h-11 w-full rounded-xl border-zinc-200 bg-white px-3 text-black focus:border-black focus:ring-black/10">
                                        <x-input-error for="last_name" class="mt-2" />
                                    </div>
                                </div>
                            @else
                                <h2 class="text-xl font-bold text-gray-900">{{ $this->user->name }} {{ $this->user->last_name }}</h2>
                            @endif
                            <p class="{{ $editing ? 'mt-[20px]' : 'mt-1' }} text-[12px] text-gray-500">González Alonzo y Asociados S.C.P.</p>
                            <span class="mt-3 inline-flex items-center gap-2 rounded-full border px-3 py-1 text-[12px] font-medium {{ $isOnline ? 'border-emerald-200 text-emerald-700' : 'border-gray-200 text-gray-500' }}">
                                <span class="h-2 w-2 rounded-full {{ $isOnline ? 'bg-emerald-500' : 'bg-gray-400' }}"></span>{{ $isOnline ? 'Disponible' : 'Desconectado' }}
                            </span>
                        </div>

                        <div class="mt-[20px] space-y-[6px] border-t border-zinc-200 pt-[15px] text-left">
                            <div class="flex items-start gap-3 rounded-xl px-3 py-2 text-gray-600">
                                @if ($editing)
                                    <div class="min-w-0 flex-1">
                                        <label for="profile-email" class="mb-2 block font-semibold text-black">Correo electrónico</label>
                                        <input id="profile-email" type="email" wire:model="state.email" autocomplete="email" placeholder="nombre@empresa.com" class="h-11 w-full rounded-xl border-zinc-200 bg-white px-3 text-black focus:border-black focus:ring-black/10">
                                        <x-input-error for="email" class="mt-2" />
                                    </div>
                                @else
                                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-[#1A3A6B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                    <span class="min-w-0 truncate text-[13px]" title="{{ $this->user->email }}">{{ $this->user->email }}</span>
                                @endif
                            </div>
                            <div class="flex items-start gap-3 rounded-xl px-3 py-2 text-gray-600">
                                <svg class="mt-0.5 h-4 w-4 shrink-0 text-[#1A3A6B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17.657 16.657 13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                <span class="min-w-0 truncate text-[13px]" title="Mérida, Yucatán, México">Mérida, Yucatán, México</span>
                            </div>
                            <div class="flex items-start gap-3 rounded-xl px-3 py-2 text-gray-600">
                                <svg class="mt-0.5 h-4 w-4 shrink-0 text-[#1A3A6B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                <span class="text-[13px]">{{ (int) $this->user->created_at->diffInDays(now()) }} días con la cuenta</span>
                            </div>
                        </div>
                    </div>
                    @livewire('profile.people-sidebar')
                </aside>

                <div class="min-w-0 bg-white p-[25px]">
                    <header class="flex items-center justify-between border-b border-gray-200 pb-[20px]">
                        <div>
                            <p class="text-[12px] font-semibold uppercase tracking-[0.08em] text-gray-400">Resumen del colaborador</p>
                            <h2 class="mt-1 font-semibold text-gray-800">Información del perfil</h2>
                        </div>
                        <div class="flex items-center gap-5 text-[12px] text-gray-400">
                            <span>{{ number_format($activityCount) }} actividades</span>
                            <span>{{ number_format($friendCount) }} amigos</span>
                            <span>Actualizado {{ $this->user->updated_at->locale('es')->diffForHumans() }}</span>
                        </div>
                    </header>

                    <div class="mt-[25px] grid gap-[25px] 2xl:grid-cols-[minmax(0,0.95fr)_minmax(460px,1.05fr)]">
                        <article class="rounded-2xl border border-zinc-200 bg-white p-[25px] shadow-[0_4px_14px_rgba(0,0,0,0.025)]">
                            <div class="flex items-center gap-3">
                                <span class="flex h-9 w-9 items-center justify-center rounded-lg border border-[#cbd9ed] text-[#1A3A6B]"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 10h8m-8 4h5m8-2a9 9 0 01-9 9 9.8 9.8 0 01-4-.84L3 21l.84-5A9 9 0 1121 12z" /></svg></span>
                                <div><p class="font-semibold text-gray-800">Acerca de mí</p><p class="text-[12px] text-gray-400">Presentación personal y profesional</p></div>
                            </div>
                            @if ($editing)
                                <label for="profile-description" class="mb-2 mt-[20px] block font-semibold text-black">Descripción del perfil</label>
                                <textarea id="profile-description" wire:model="state.profile_description" rows="7" maxlength="500" class="block w-full resize-none rounded-xl border-zinc-200 bg-white text-[15px] leading-7 text-zinc-700 focus:border-black focus:ring-black/10" placeholder="Cuéntanos sobre tu experiencia, funciones o intereses profesionales..."></textarea>
                                <x-input-error for="profile_description" class="mt-2" />
                            @else
                                <p class="mt-[20px] text-justify leading-7 text-gray-600">{{ $this->user->profile_description ?: 'Colaborador de González Alonzo y Asociados S.C.P., ubicado en Mérida, Yucatán, México. Este perfil reúne información profesional y organizacional para facilitar la comunicación, la colaboración y el contacto con otros integrantes del equipo.' }}</p>
                            @endif
                        </article>

                        <article class="rounded-2xl border border-zinc-200 bg-white p-[25px] shadow-[0_4px_14px_rgba(0,0,0,0.025)]">
                            <div class="flex items-center gap-3">
                                <span class="flex h-9 w-9 items-center justify-center rounded-lg border border-zinc-200 text-black"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 6h11M9 12h11M9 18h11M4 6h.01M4 12h.01M4 18h.01" /></svg></span>
                                <div><p class="font-semibold text-gray-800">Información organizacional</p><p class="text-[12px] text-gray-400">Posición dentro de la empresa</p></div>
                            </div>
                            <dl class="mt-[20px] grid grid-cols-2 gap-[12px]">
                                <div class="min-w-0 rounded-xl border border-zinc-200 bg-white p-[15px]"><dt class="text-[12px] font-semibold uppercase tracking-[0.06em] text-zinc-400">Área</dt><dd class="mt-2 truncate font-semibold text-black" title="{{ $organizationalProfile?->physicalArea?->name ?? 'Sin asignar' }}">{{ $organizationalProfile?->physicalArea?->name ?? 'Sin asignar' }}</dd></div>
                                <div class="min-w-0 rounded-xl border border-zinc-200 bg-white p-[15px]"><dt class="text-[12px] font-semibold uppercase tracking-[0.06em] text-zinc-400">Puesto</dt><dd class="mt-2 truncate font-semibold text-black" title="{{ $organizationalProfile?->jobPosition?->name ?? 'Sin asignar' }}">{{ $organizationalProfile?->jobPosition?->name ?? 'Sin asignar' }}</dd></div>
                                <div class="rounded-xl border border-zinc-200 bg-white p-[15px]"><dt class="text-[12px] font-semibold uppercase tracking-[0.06em] text-zinc-400">Colaboradores</dt><dd class="mt-2 font-semibold text-black">{{ $subordinates->isEmpty() ? 'No tiene colaboradores' : $subordinates->count() }}</dd></div>
                                <div class="min-w-0 rounded-xl border border-zinc-200 bg-white p-[15px]"><dt class="text-[12px] font-semibold uppercase tracking-[0.06em] text-zinc-400">Supervisor</dt><dd class="mt-2 truncate font-semibold text-black" title="{{ $supervisors->map(fn ($user) => trim($user->name.' '.$user->last_name))->join(', ') ?: 'Sin supervisor' }}">{{ $supervisors->map(fn ($user) => trim($user->name.' '.$user->last_name))->join(', ') ?: 'Sin supervisor' }}</dd></div>
                            </dl>
                        </article>
                    </div>

                    <article class="mt-[25px] rounded-2xl border border-zinc-200 bg-white p-[25px] shadow-[0_4px_14px_rgba(0,0,0,0.025)]">
                        <header class="flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <span class="flex h-9 w-9 items-center justify-center rounded-lg border border-zinc-200 text-black"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4m9-10a4 4 0 100-8 4 4 0 000 8zm9 1v6m3-3h-6" /></svg></span>
                                <div><p class="font-semibold text-gray-800">Amigos</p><p class="text-[12px] text-gray-400">{{ $totalFriends }} contactos agregados</p></div>
                            </div>
                            <button type="button" @click="friendsDirectoryOpen = true" class="font-semibold text-black hover:underline">Ver directorio</button>
                        </header>
                        <div class="mt-[20px] flex min-w-0 flex-wrap gap-[12px]">
                            @forelse ($friends as $friend)
                                <a href="{{ route('profiles.show', $friend) }}" class="group flex w-[145px] items-center gap-3 rounded-xl border border-zinc-200 p-3 transition hover:border-zinc-400 hover:bg-zinc-50">
                                    @if ($friend->profile_photo_path)
                                        <img src="{{ $friend->profile_photo_url }}" alt="Foto de {{ $friend->name }}" class="h-9 w-9 shrink-0 rounded-full object-cover ring-1 ring-zinc-200">
                                    @else
                                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-zinc-100 font-semibold text-black ring-1 ring-zinc-200">{{ mb_strtoupper(mb_substr($friend->name, 0, 1).mb_substr($friend->last_name ?? '', 0, 1)) }}</span>
                                    @endif
                                    <span class="min-w-0 truncate text-[12px] font-semibold text-zinc-600 group-hover:text-black" title="{{ $friend->name }} {{ $friend->last_name }}">{{ $friend->name }}</span>
                                </a>
                            @empty
                                <div class="flex w-full items-center justify-center rounded-xl border border-dashed border-gray-200 px-5 py-8 text-center text-gray-400">
                                    <span>Todavía no tienes amigos agregados.</span>
                                </div>
                            @endforelse
                        </div>
                    </article>

                    @if ($editing)
                        <article class="mt-[25px] rounded-2xl border border-zinc-200 bg-white p-[25px] shadow-[0_4px_14px_rgba(0,0,0,0.025)]">
                            <header class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-3">
                                <span class="flex h-9 w-9 items-center justify-center rounded-lg border border-zinc-200 text-black">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                                </span>
                                <div>
                                    <h3 class="font-semibold text-black">Seguridad y contraseña</h3>
                                    <p class="text-zinc-500">Este cambio requiere comprobar tu contraseña actual y repetir la nueva.</p>
                                </div>
                                </div>
                                <span class="shrink-0 rounded-full border border-zinc-300 bg-zinc-50 px-3 py-1 font-semibold text-black">Verificación doble</span>
                            </header>

                            <div class="mt-[20px] grid gap-[20px] xl:grid-cols-3">
                                <div>
                                    <label for="current-password" class="mb-2 block font-semibold text-black">1. Contraseña actual</label>
                                    <input id="current-password" type="password" wire:model="currentPassword" autocomplete="current-password" placeholder="Escribe tu contraseña actual" class="h-11 w-full rounded-xl border-zinc-200 bg-white px-3 text-black focus:border-black focus:ring-black/10">
                                    <x-input-error for="currentPassword" class="mt-2" />
                                </div>
                                <div>
                                    <label for="new-password" class="mb-2 block font-semibold text-black">2. Nueva contraseña</label>
                                    <input id="new-password" type="password" wire:model="newPassword" autocomplete="new-password" placeholder="Escribe la nueva contraseña" class="h-11 w-full rounded-xl border-zinc-200 bg-white px-3 text-black focus:border-black focus:ring-black/10">
                                    <x-input-error for="newPassword" class="mt-2" />
                                </div>
                                <div>
                                    <label for="new-password-confirmation" class="mb-2 block font-semibold text-black">3. Repite la nueva contraseña</label>
                                    <input id="new-password-confirmation" type="password" wire:model="newPasswordConfirmation" autocomplete="new-password" placeholder="Repite la nueva contraseña" class="h-11 w-full rounded-xl border-zinc-200 bg-white px-3 text-black focus:border-black focus:ring-black/10">
                                    <x-input-error for="newPasswordConfirmation" class="mt-2" />
                                </div>
                            </div>
                            <p class="mt-[15px] text-zinc-500">La nueva contraseña debe coincidir en los dos últimos campos. Se actualizará cuando presiones “Guardar cambios”.</p>
                        </article>
                    @endif

                    @if ($this->isOwnProfile)
                        @livewire('profile.logout-other-browser-sessions-form')
                    @endif

                    @if ($editing)
                        <article class="mt-[25px] rounded-2xl border border-red-200 bg-red-50/60 p-[20px]">
                            <header class="flex items-center justify-between gap-[20px]">
                                <div class="flex min-w-0 items-start gap-3">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-red-200 bg-white text-red-700">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </span>
                                    <div>
                                        <h3 class="font-semibold text-red-800">Zona de peligro: eliminar perfil</h3>
                                        <p class="mt-1 text-red-700">No es un cambio de contraseña. Esta acción es permanente y eliminará tu cuenta junto con sus datos asociados.</p>
                                    </div>
                                </div>
                                <button type="button" wire:click="deleteAccount" wire:confirm="¿Estás seguro de que deseas eliminar tu perfil? Esta acción no se puede deshacer." class="inline-flex h-11 shrink-0 items-center justify-center rounded-xl bg-red-700 px-5 font-semibold whitespace-nowrap text-white transition hover:bg-red-800 disabled:opacity-50" wire:loading.attr="disabled" wire:target="deleteAccount">
                                    Eliminar mi perfil
                                </button>
                            </header>

                            <div class="mt-[15px] grid gap-[15px] xl:grid-cols-2">
                                <div>
                                    <label for="deletion-name" class="mb-2 block font-semibold text-black">Escribe tu nombre completo para confirmar</label>
                                    <input id="deletion-name" type="text" wire:model="deletionName" autocomplete="off" placeholder="{{ trim($this->user->name.' '.$this->user->last_name) }}" class="h-11 w-full rounded-xl border-zinc-300 bg-white px-3 text-black focus:border-black focus:ring-black/10">
                                    <p class="mt-2 text-zinc-500">Debes escribir exactamente: <span class="font-semibold text-black">{{ trim($this->user->name.' '.$this->user->last_name) }}</span></p>
                                    <x-input-error for="deletionName" class="mt-2" />
                                </div>
                                <div>
                                    <label for="deletion-password" class="mb-2 block font-semibold text-black">Contraseña actual</label>
                                    <input id="deletion-password" type="password" wire:model="deletionPassword" autocomplete="current-password" placeholder="Confirma tu contraseña actual" class="h-11 w-full rounded-xl border-zinc-300 bg-white px-3 text-black focus:border-black focus:ring-black/10">
                                    <x-input-error for="deletionPassword" class="mt-2" />
                                </div>
                            </div>

                        </article>
                    @endif
                </div>
            </section>

            <x-action-message on="saved" class="text-sm font-medium text-emerald-600">Perfil actualizado correctamente.</x-action-message>
        </main>
    @if ($this->isOwnProfile)
        </form>
    @else
        </div>
    @endif

    <template x-teleport="body">
        <div x-cloak x-show="friendsDirectoryOpen" class="fixed inset-x-0 bottom-0 top-[90px] z-[100]" role="dialog" aria-modal="true" aria-label="Directorio de amigos">
            <button type="button" x-show="friendsDirectoryOpen" x-transition.opacity @click="friendsDirectoryOpen = false" class="absolute inset-0 bg-black/25 backdrop-blur-md" aria-label="Cerrar directorio"></button>
            <aside
                x-show="friendsDirectoryOpen"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="-translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="-translate-x-full"
                class="absolute inset-y-0 left-0 flex w-[420px] max-w-full flex-col border-r border-zinc-200 bg-white shadow-2xl"
            >
                <header class="flex shrink-0 items-center justify-between border-b border-zinc-200 px-[25px] py-[20px]">
                    <div>
                        <h2 class="text-[20px] font-bold text-black">Directorio de amigos</h2>
                        <p class="mt-1 text-zinc-500">{{ $totalFriends }} {{ $totalFriends === 1 ? 'amigo agregado' : 'amigos agregados' }}</p>
                    </div>
                    <button type="button" @click="friendsDirectoryOpen = false" class="grid h-10 w-10 place-items-center rounded-xl border border-zinc-200 text-black transition hover:bg-zinc-100" aria-label="Cerrar">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 6l12 12M18 6L6 18" /></svg>
                    </button>
                </header>

                <div class="profile-people-scrollbar min-h-0 flex-1 space-y-[10px] overflow-y-auto px-[25px] py-[20px]">
                    @forelse ($allFriends as $friend)
                        <a href="{{ route('profiles.show', $friend) }}" class="flex items-center gap-3 rounded-xl border border-zinc-200 p-3 transition hover:bg-zinc-50">
                            @if ($friend->profile_photo_path)
                                <img src="{{ $friend->profile_photo_url }}" alt="Foto de {{ $friend->name }}" class="h-11 w-11 shrink-0 rounded-full object-cover ring-1 ring-zinc-200">
                            @else
                                <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-zinc-100 font-semibold text-black ring-1 ring-zinc-200">{{ mb_strtoupper(mb_substr($friend->name, 0, 1).mb_substr($friend->last_name ?? '', 0, 1)) }}</span>
                            @endif
                            <span class="min-w-0 flex-1">
                                <span class="block truncate font-semibold text-black" title="{{ $friend->name }} {{ $friend->last_name }}">{{ $friend->name }} {{ $friend->last_name }}</span>
                                <span class="mt-1 block truncate text-zinc-500" title="{{ $friend->email }}">{{ $friend->email }}</span>
                            </span>
                            <svg class="h-4 w-4 shrink-0 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m9 18 6-6-6-6" /></svg>
                        </a>
                    @empty
                        <div class="rounded-xl border border-dashed border-zinc-200 px-5 py-10 text-center text-zinc-500">Todavía no tienes amigos agregados.</div>
                    @endforelse
                </div>
            </aside>
        </div>
    </template>

    <template x-teleport="body">
        <div x-show="profilePhotoOpen" x-cloak x-transition.opacity @click.self="profilePhotoOpen = false" class="fixed inset-0 z-[100] grid cursor-zoom-out place-items-center bg-gray-950/55 p-6 backdrop-blur-md" role="dialog" aria-modal="true" aria-label="Foto de perfil ampliada">
            <button type="button" @click="profilePhotoOpen = false" aria-label="Cerrar foto ampliada" class="absolute right-6 top-6 grid h-11 w-11 place-items-center rounded-full bg-black/45 text-white hover:bg-black/65"><svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 6l12 12M18 6L6 18" /></svg></button>
            <div class="cursor-default" @click.stop>
                @if ($this->user->profile_photo_path)
                    <img src="{{ $this->user->profile_photo_url }}" alt="Foto ampliada de {{ $this->user->name }}" class="max-h-[82vh] max-w-[82vw] rounded-full border-[6px] border-[#e7e7e7] object-contain shadow-2xl">
                @else
                    <span class="grid h-[min(70vw,420px)] w-[min(70vw,420px)] place-items-center rounded-full border-[6px] border-zinc-200 bg-white text-8xl font-bold text-black shadow-2xl">{{ mb_strtoupper(mb_substr($this->user->name, 0, 1).mb_substr($this->user->last_name ?? '', 0, 1)) }}</span>
                @endif
            </div>
        </div>
    </template>
    <style>
        .profile-monochrome [class*="text-[#1A3A6B]"] { color: #000 !important; }
        .profile-monochrome { font-size: 15px; }
        .profile-monochrome :is([class*="text-2xl"], [class*="text-xl"], [class*="text-3xl"]) { font-size: 20px !important; }
        .profile-monochrome :is([class*="text-[10px]"], [class*="text-[11px]"], [class*="text-[12px]"], [class*="text-[13px]"]) { font-size: 15px !important; }
        .profile-monochrome :is([class*="text-sm"], [class*="text-base"], [class*="text-lg"]) { font-size: 15px !important; }
        .profile-monochrome .uppercase { text-transform: none !important; }
        .profile-monochrome [class*="tracking-"] { letter-spacing: normal !important; }
    </style>
</section>
