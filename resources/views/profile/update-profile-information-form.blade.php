<section class="profile-monochrome min-h-[calc(100dvh-90px)] bg-white text-[15px] text-zinc-900" x-data="{ profilePhotoOpen: false, friendsDirectoryOpen: false, coverDragging: false, photoDragging: false }" x-on:keydown.escape.window="profilePhotoOpen = false; friendsDirectoryOpen = false">
    @php
        $organizationalProfile = $this->user->activeOrganizationalProfile()->with(['physicalArea', 'jobPosition'])->first();
        $supervisors = $this->user->superiors()->get();
        $subordinates = $this->user->subordinates()->get();
        $totalFriends = $this->user->friends()->count();
        $allFriends = $this->user->friends()->orderBy('name')->orderBy('last_name')->get();
        $friends = $allFriends->take(8);
    @endphp

    <form wire:submit="updateProfileInformation">
        <main class="mx-auto w-full max-w-[1500px] space-y-[25px] px-[40px] py-[25px]">
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
                    @if ($editing)
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
                                <div class="space-y-2">
                                    <input aria-label="Nombre" type="text" wire:model="state.name" class="h-10 w-full rounded-xl border-zinc-200 bg-white text-center font-semibold text-black focus:border-black focus:ring-black/10">
                                    <input aria-label="Apellido" type="text" wire:model="state.last_name" class="h-10 w-full rounded-xl border-zinc-200 bg-white text-center font-semibold text-black focus:border-black focus:ring-black/10">
                                    <x-input-error for="name" /><x-input-error for="last_name" />
                                </div>
                            @else
                                <h2 class="text-xl font-bold text-gray-900">{{ $this->user->name }} {{ $this->user->last_name }}</h2>
                            @endif
                            <p class="mt-1 text-[12px] text-gray-500">González Alonzo y Asociados S.C.P.</p>
                            <span class="mt-3 inline-flex items-center gap-2 rounded-full border px-3 py-1 text-[12px] font-medium {{ $isOnline ? 'border-emerald-200 text-emerald-700' : 'border-gray-200 text-gray-500' }}">
                                <span class="h-2 w-2 rounded-full {{ $isOnline ? 'bg-emerald-500' : 'bg-gray-400' }}"></span>{{ $isOnline ? 'Disponible' : 'Desconectado' }}
                            </span>
                        </div>

                        <div class="mt-[20px] space-y-[6px] border-t border-zinc-200 pt-[15px] text-left">
                            <div class="flex items-start gap-3 rounded-xl px-3 py-2 text-gray-600">
                                <svg class="mt-0.5 h-4 w-4 shrink-0 text-[#1A3A6B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                @if ($editing)
                                    <input aria-label="Correo electrónico" type="email" wire:model="state.email" class="min-w-0 flex-1 border-0 border-b border-dashed border-zinc-400 bg-transparent p-0 pb-1 text-[13px] focus:border-black focus:ring-0">
                                @else
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
                                <textarea aria-label="Descripción" wire:model="state.profile_description" rows="7" maxlength="500" class="mt-[20px] block w-full resize-none rounded-xl border-zinc-200 bg-white text-[15px] leading-7 text-zinc-700 focus:border-black focus:ring-black/10" placeholder="Escribe una descripción sobre ti..."></textarea>
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

                    @livewire('profile.logout-other-browser-sessions-form')
                </div>
            </section>

            <x-action-message on="saved" class="text-sm font-medium text-emerald-600">Perfil actualizado correctamente.</x-action-message>
        </main>
    </form>

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
