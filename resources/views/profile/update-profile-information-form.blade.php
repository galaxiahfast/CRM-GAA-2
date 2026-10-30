<section
    class="profile-page relative min-h-[calc(100dvh-100px)] w-full min-w-0 overflow-x-auto bg-white text-[15px] text-black"
    x-data="{
        profilePhotoOpen: false,
        friendsDirectoryOpen: false,
        coverDragging: false,
        photoDragging: false,
        viewScale: 90,
        isFullscreen: false,
        scaledContentHeight: null,
        scaleObserver: null,
        fullscreenHandler: null,
        init() {
            this.fullscreenHandler = () => { this.isFullscreen = document.fullscreenElement === this.$root; };
            document.addEventListener('fullscreenchange', this.fullscreenHandler);
            this.$nextTick(() => {
                this.scaleObserver = new ResizeObserver(() => this.syncScaledContentHeight());
                this.scaleObserver.observe(this.$refs.scaledMain);
                this.syncScaledContentHeight();
            });
        },
        destroy() {
            this.scaleObserver?.disconnect();
            document.removeEventListener('fullscreenchange', this.fullscreenHandler);
        },
        syncScaledContentHeight() {
            this.$nextTick(() => {
                if (this.$refs.scaledMain) {
                    this.scaledContentHeight = Math.ceil(this.$refs.scaledMain.offsetHeight * (this.viewScale / 100));
                }
            });
        },
        zoomOut() { this.viewScale = Math.max(70, this.viewScale - 5); this.syncScaledContentHeight(); },
        zoomIn() { this.viewScale = Math.min(100, this.viewScale + 5); this.syncScaledContentHeight(); },
        async toggleFullscreen() {
            if (document.fullscreenElement === this.$root) return document.exitFullscreen();
            if (document.fullscreenElement) await document.exitFullscreen();
            await this.$root.requestFullscreen();
        }
    }"
    x-on:keydown.escape.window="profilePhotoOpen = false; friendsDirectoryOpen = false"
    x-effect="if ((friendsDirectoryOpen || profilePhotoOpen) && document.fullscreenElement) document.exitFullscreen()"
>
    @php
        $organizationalProfile = $this->user->activeOrganizationalProfile()->with(['physicalArea', 'jobPosition'])->first();
        $supervisors = $this->user->superiors()->get();
        $subordinates = $this->user->subordinates()->get();
        $totalFriends = $this->user->friends()->count();
        $allFriends = $this->user->friends()->orderBy('name')->orderBy('last_name')->get();
        $friends = $allFriends->take(8);
        $actionButton = 'inline-flex items-center gap-[10px] whitespace-nowrap bg-transparent py-[15px] leading-none text-[#1A3A6B] transition-colors hover:text-black focus:outline-none focus:ring-0 disabled:cursor-wait disabled:opacity-40';
        $primaryButton = 'profile-primary inline-flex items-center gap-[10px] whitespace-nowrap rounded-xl border border-[#1A3A6B] bg-[#1A3A6B] px-[20px] py-[10px] leading-none text-white transition-colors focus:outline-none focus:ring-0 disabled:cursor-wait disabled:opacity-40';
        $fieldClass = 'h-11 w-full rounded-xl border border-[#d4d4d8] bg-white px-[20px] text-black';
    @endphp

    <div class="profile-zoom no-print fixed bottom-[30px] right-[30px] z-30 flex items-center gap-[10px] rounded-xl border border-zinc-200 bg-white/95 px-[15px] py-[10px] shadow-[0_8px_24px_rgba(0,0,0,0.10)] backdrop-blur-sm">
        <button type="button" @click="zoomOut()" aria-label="Alejar vista" class="inline-flex h-7 w-7 items-center justify-center text-black focus:outline-none focus:ring-0">−</button>
        <input type="range" min="70" max="100" step="5" x-model.number="viewScale" @input="syncScaledContentHeight()" class="h-1.5 w-[130px] cursor-pointer accent-black" aria-label="Ajustar tamaño de la vista de Ajustes">
        <button type="button" @click="zoomIn()" aria-label="Acercar vista" class="inline-flex h-7 w-7 items-center justify-center text-black focus:outline-none focus:ring-0">+</button>
        <span class="w-[42px] text-right tabular-nums text-black" x-text="viewScale + '%'">90%</span>
        <span class="h-5 w-px bg-zinc-200"></span>
        <button type="button" @click="toggleFullscreen()" class="inline-flex p-[5px] text-black outline-none focus:ring-0" :title="isFullscreen ? 'Salir de pantalla completa' : 'Pantalla completa'" :aria-label="isFullscreen ? 'Salir de pantalla completa' : 'Ver Ajustes en pantalla completa'">
            <svg x-show="!isFullscreen" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 3H3v5M16 3h5v5M21 16v5h-5M3 16v5h5"/></svg>
            <svg x-show="isFullscreen" x-cloak class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 3v5H3M16 3v5h5M21 16h-5v5M3 16h5v5"/></svg>
        </button>
    </div>

    <div class="w-full" :style="scaledContentHeight ? `height:${scaledContentHeight}px` : null">
    <main x-ref="scaledMain" class="profile-main flex w-full origin-top-left flex-col gap-[20px] p-[20px] sm:p-[30px] xl:p-[50px]" :style="`width:${10000 / viewScale}%;transform:scale(${viewScale / 100});`">
    @if ($this->isOwnProfile)
        <form wire:submit="updateProfileInformation" class="flex flex-col gap-[20px]">
    @else
        <div class="flex flex-col gap-[20px]">
    @endif
            <section class="profile-head flex shrink-0 flex-col justify-between gap-[20px] lg:flex-row lg:items-center">
                <div class="profile-head-title flex min-w-0 items-center gap-[15px]">
                    <span class="profile-head-icon grid shrink-0 place-items-center rounded-xl border border-[#d4d4d8] bg-white text-black">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M20 21a8 8 0 00-16 0m8-10a4 4 0 100-8 4 4 0 000 8z" /></svg>
                    </span>
                    <div class="min-w-0">
                        <h1 class="text-xl font-normal leading-none tracking-tight text-[#1A3A6B]">Perfil de usuario</h1>
                        <div class="mt-[10px] flex flex-col"><p class="profile-lines text-black">{{ $this->isOwnProfile ? 'Consulta y administra tu información personal y organizacional.' : 'Información personal y organizacional del colaborador.' }}</p></div>
                    </div>
                </div>

                <div class="no-print ml-auto flex shrink-0 flex-wrap items-center gap-x-[20px]">
                    @if (! $this->isOwnProfile)
                        @php($friendship = $this->friendship)
                        @if (! $friendship)
                            <button type="button" wire:click="requestFriendship" class="{{ $primaryButton }}">
                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM3 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 019.374 21c-2.331 0-4.512-.645-6.374-1.766z" /></svg>
                                <span>Seguir</span>
                            </button>
                        @elseif ($friendship->status === \App\Models\Friendship::PENDING && $friendship->addressee_id === auth()->id())
                            <button type="button" wire:click="acceptFriendship" class="{{ $primaryButton }}">
                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 13l4 4L19 7" /></svg>
                                <span>Aceptar solicitud</span>
                            </button>
                        @elseif ($friendship->status === \App\Models\Friendship::PENDING)
                            <button type="button" disabled class="{{ str_replace(['text-[#1A3A6B]', 'hover:text-black', 'disabled:cursor-wait', 'disabled:opacity-40'], ['text-black', '', '', ''], $actionButton) }} cursor-not-allowed">
                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                <span>Solicitud enviada</span>
                            </button>
                        @else
                            <button type="button" wire:click="removeFriendship" wire:confirm="¿Quieres dejar de seguir a esta persona y eliminarla de tus amigos?" class="{{ $actionButton }}">
                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M22 10.5h-6m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 0110.374 21c-2.331 0-4.512-.645-6.374-1.766z" /></svg>
                                <span>Amigo</span>
                            </button>
                        @endif
                        <a href="{{ route('profile.show') }}" class="{{ $actionButton }}">
                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 12H5m0 0 6-6m-6 6 6 6" /></svg>
                            <span>Regresar a mi perfil</span>
                        </a>
                    @elseif ($editing)
                        <button type="button" wire:click="cancelEditing" class="{{ $actionButton }}">
                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 6l12 12M18 6L6 18" /></svg>
                            <span>Cancelar</span>
                        </button>
                        <button type="submit" wire:loading.attr="disabled" class="{{ $actionButton }}">
                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 4h12l2 2v14H5V4zm3 0v6h8V4M8 20v-6h8v6" /></svg>
                            <span>Guardar cambios</span>
                        </button>
                    @else
                        <button type="button" wire:click="startEditing" class="{{ $actionButton }}">
                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16.9 3.6a2.1 2.1 0 013 3L9 17.5 4 19l1.5-5L16.9 3.6z" /></svg>
                            <span>Editar perfil</span>
                        </button>
                    @endif

                    <button type="button" aria-label="Descargar perfil en PDF" title="Descargar PDF" class="{{ $actionButton }}">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16v4h16v-4M12 4v12m0 0 4-4m-4 4-4-4" /></svg>
                        <span>Descargar PDF</span>
                    </button>
                    <button type="button" onclick="window.print()" aria-label="Imprimir perfil" class="{{ $actionButton }}">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10zM9 7V3h6v4" /></svg>
                        <span>Imprimir</span>
                    </button>
                </div>
            </section>

            <section class="profile-grid grid {{ $this->isOwnProfile ? 'min-h-[650px]' : '' }} min-w-[1000px] grid-cols-[330px_minmax(0,1fr)] overflow-hidden rounded-xl border border-[#d4d4d8] bg-white">
                <aside class="flex h-0 min-h-full flex-col overflow-hidden border-r border-[#d4d4d8] bg-white">
                    <div class="relative h-[150px] shrink-0 overflow-hidden bg-gradient-to-br from-black via-zinc-800 to-zinc-600">
                        @if ($editing)
                            <label for="cover" class="group absolute inset-0 cursor-pointer" x-on:dragenter.prevent="coverDragging = true" x-on:dragover.prevent="coverDragging = true" x-on:dragleave.prevent="coverDragging = false" x-on:drop.prevent="coverDragging = false; $refs.coverInput.files = $event.dataTransfer.files; $refs.coverInput.dispatchEvent(new Event('change', { bubbles: true }))">
                                <input x-ref="coverInput" id="cover" type="file" accept="image/png,image/jpeg" wire:model="cover" class="sr-only">
                                @if ($cover)
                                    <img src="{{ $cover->temporaryUrl() }}" alt="Vista previa de portada" class="h-full w-full object-cover">
                                @elseif ($this->user->profile_cover_path)
                                    <img src="{{ Storage::url($this->user->profile_cover_path) }}" alt="Portada actual" class="h-full w-full object-cover">
                                @endif
                                <span class="absolute inset-0 grid place-items-center bg-black/45 text-center text-white transition group-hover:bg-black/60" :class="coverDragging ? 'bg-black/65' : ''">
                                    <span class="profile-lines px-5">Arrastra o selecciona una portada</span>
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

                    <div class="shrink-0 px-[20px] pb-[20px] text-center">
                        <div class="relative mx-auto -mt-[58px] h-[116px] w-[116px]">
                            @if ($editing)
                                <label for="photo" class="profile-avatar-ring group block h-full w-full cursor-pointer overflow-hidden rounded-full bg-white" x-on:dragenter.prevent="photoDragging = true" x-on:dragover.prevent="photoDragging = true" x-on:dragleave.prevent="photoDragging = false" x-on:drop.prevent="photoDragging = false; $refs.photoInput.files = $event.dataTransfer.files; $refs.photoInput.dispatchEvent(new Event('change', { bubbles: true }))">
                                    <input x-ref="photoInput" id="photo" type="file" accept="image/png,image/jpeg" wire:model="photo" class="sr-only">
                                    @if ($photo)
                                        <img src="{{ $photo->temporaryUrl() }}" alt="Vista previa de la foto" class="h-full w-full object-cover">
                                    @elseif ($this->user->profile_photo_path)
                                        <img src="{{ $this->user->profile_photo_url }}" alt="Foto actual" class="h-full w-full object-cover">
                                    @else
                                        <span class="profile-initials grid h-full w-full place-items-center bg-zinc-100 text-black">{{ mb_strtoupper(mb_substr($this->user->name, 0, 1).mb_substr($this->user->last_name ?? '', 0, 1)) }}</span>
                                    @endif
                                    <span class="absolute inset-[5px] grid place-items-center rounded-full bg-black/60 px-3 text-white opacity-0 transition group-hover:opacity-100" :class="photoDragging ? 'opacity-100' : ''">Cambiar foto</span>
                                </label>
                            @else
                                <button type="button" @click="profilePhotoOpen = true" class="profile-avatar-ring block h-full w-full cursor-zoom-in overflow-hidden rounded-full bg-white" aria-label="Ampliar foto de perfil">
                                    @if ($this->user->profile_photo_path)
                                        <img src="{{ $this->user->profile_photo_url }}" alt="Foto de {{ $this->user->name }}" class="h-full w-full object-cover">
                                    @else
                                        <span class="profile-initials grid h-full w-full place-items-center bg-zinc-100 text-black">{{ mb_strtoupper(mb_substr($this->user->name, 0, 1).mb_substr($this->user->last_name ?? '', 0, 1)) }}</span>
                                    @endif
                                </button>
                            @endif
                            <span class="profile-status-dot absolute bottom-2 right-2 h-4 w-4 rounded-full {{ $isOnline ? 'bg-emerald-500' : 'bg-gray-400' }}" title="{{ $isOnline ? 'Activo' : 'Inactivo' }}"></span>
                        </div>

                        <x-input-error for="photo" class="mt-[10px]" />
                        <x-input-error for="cover" class="mt-[10px]" />

                        <div class="mt-[20px]">
                            @if ($editing)
                                <div class="flex flex-col gap-[20px] text-left">
                                    <div>
                                        <label for="profile-name" class="mb-[10px] block leading-none text-[#1A3A6B]">Nombres</label>
                                        <input id="profile-name" type="text" wire:model="state.name" autocomplete="given-name" placeholder="Escribe tus nombres" class="{{ $fieldClass }}">
                                        <x-input-error for="name" class="mt-[10px]" />
                                    </div>
                                    <div>
                                        <label for="profile-last-name" class="mb-[10px] block leading-none text-[#1A3A6B]">Apellidos</label>
                                        <input id="profile-last-name" type="text" wire:model="state.last_name" autocomplete="family-name" placeholder="Escribe tus apellidos" class="{{ $fieldClass }}">
                                        <x-input-error for="last_name" class="mt-[10px]" />
                                    </div>
                                </div>
                            @else
                                <h2 class="leading-none text-[#1A3A6B]">{{ $this->user->name }} {{ $this->user->last_name }}</h2>
                            @endif
                            <p class="{{ $editing ? 'mt-[20px]' : 'mt-[10px]' }} leading-none text-black">González Alonzo y Asociados S.C.P.</p>
                            <span class="mt-[20px] inline-flex items-center gap-[10px] leading-none text-black {{ $isOnline ? 'rounded-full border border-[#d4d4d8] px-[20px] py-[10px]' : '' }}">
                                <span class="h-2 w-2 rounded-full {{ $isOnline ? 'bg-emerald-500' : 'bg-gray-400' }}"></span>{{ $isOnline ? 'Disponible' : 'Desconectado' }}
                            </span>
                        </div>

                        <div class="mt-[20px] flex flex-col gap-[20px] border-t border-[#d4d4d8] pt-[20px] text-left">
                            @if ($editing)
                                <div class="min-w-0">
                                    <label for="profile-email" class="mb-[10px] block leading-none text-[#1A3A6B]">Correo electrónico</label>
                                    <input id="profile-email" type="email" wire:model="state.email" autocomplete="email" placeholder="nombre@empresa.com" class="{{ $fieldClass }}">
                                    <x-input-error for="email" class="mt-[10px]" />
                                </div>
                            @else
                                <div class="flex items-center gap-[10px] leading-none text-black">
                                    <svg class="h-4 w-4 shrink-0 text-[#1A3A6B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                    <span class="profile-lines min-w-0 truncate" title="{{ $this->user->email }}">{{ $this->user->email }}</span>
                                </div>
                            @endif
                            <div class="flex items-center gap-[10px] leading-none text-black">
                                <svg class="h-4 w-4 shrink-0 text-[#1A3A6B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17.657 16.657 13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                <span class="min-w-0 truncate" title="Mérida, Yucatán, México">Mérida, Yucatán, México</span>
                            </div>
                            <div class="flex items-center gap-[10px] leading-none text-black">
                                <svg class="h-4 w-4 shrink-0 text-[#1A3A6B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                <span>{{ (int) $this->user->created_at->diffInDays(now()) }} días con la cuenta</span>
                            </div>
                        </div>
                    </div>
                    @if ($this->isOwnProfile)
                        @livewire('profile.people-sidebar')
                    @endif
                </aside>

                <div class="min-w-0 bg-white">
                    <header class="profile-strip flex items-center justify-between gap-[20px] border-b border-[#d4d4d8] bg-zinc-100 p-[20px]">
                        <div class="min-w-0">
                            <h2 class="leading-none text-[#1A3A6B]">Información del perfil</h2>
                            <p class="mt-[10px] leading-none text-black">Resumen del colaborador</p>
                        </div>
                        <div class="profile-stats flex shrink-0 items-center gap-[20px] leading-none text-black">
                            <span>{{ number_format($activityCount) }} actividades</span>
                            <span>{{ number_format($friendCount) }} amigos</span>
                            <span>Actualizado {{ $this->user->updated_at->locale('es')->diffForHumans() }}</span>
                        </div>
                    </header>

                    <div class="flex flex-col gap-[20px] p-[20px]">
                        <div class="grid gap-[20px] 2xl:grid-cols-[minmax(0,0.95fr)_minmax(460px,1.05fr)]">
                            @if ($this->isOwnProfile)
                            <article class="min-w-0 overflow-hidden rounded-xl border border-[#d4d4d8] bg-white">
                                <header class="profile-strip flex items-center gap-[10px] border-b border-[#d4d4d8] bg-zinc-100 p-[20px]">
                                    <span class="profile-icon-box flex shrink-0 items-center justify-center rounded-lg border border-[#d4d4d8] bg-white text-[#1A3A6B]"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 10h8m-8 4h5m8-2a9 9 0 01-9 9 9.8 9.8 0 01-4-.84L3 21l.84-5A9 9 0 1121 12z" /></svg></span>
                                    <div class="min-w-0"><h3 class="leading-none text-[#1A3A6B]">Acerca de mí</h3><p class="mt-[10px] leading-none text-black">Presentación personal y profesional</p></div>
                                </header>
                                <div class="p-[20px]">
                                    @if ($editing)
                                        <label for="profile-description" class="mb-[10px] block leading-none text-[#1A3A6B]">Descripción del perfil</label>
                                        <textarea id="profile-description" wire:model="state.profile_description" rows="7" maxlength="500" class="block w-full resize-none rounded-xl border border-[#d4d4d8] bg-white px-[20px] py-[10px] text-black profile-field-lines" placeholder="Cuéntanos sobre tu experiencia, funciones o intereses profesionales..."></textarea>
                                        <x-input-error for="profile_description" class="mt-[10px]" />
                                    @else
                                        <p class="profile-lines text-justify text-black">{{ $this->user->profile_description ?: 'Colaborador de González Alonzo y Asociados S.C.P., ubicado en Mérida, Yucatán, México. Este perfil reúne información profesional y organizacional para facilitar la comunicación, la colaboración y el contacto con otros integrantes del equipo.' }}</p>
                                    @endif
                                </div>
                            </article>
                            @else
                            <article class="min-w-0 overflow-hidden rounded-xl border border-[#d4d4d8] bg-white">
                                <header class="profile-strip flex items-center gap-[10px] border-b border-[#d4d4d8] bg-zinc-100 p-[20px]">
                                    <span class="profile-icon-box flex shrink-0 items-center justify-center rounded-lg border border-[#d4d4d8] bg-white text-[#1A3A6B]"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 4h16v16H4zM8 9h8m-8 4h8m-8 4h5" /></svg></span>
                                    <div class="min-w-0"><h3 class="leading-none text-[#1A3A6B]">Información de contacto</h3><p class="mt-[10px] leading-none text-black">Datos de la cuenta</p></div>
                                </header>
                                <div class="flex flex-col gap-[20px] p-[20px] text-black">
                                    <span class="flex min-w-0 items-center gap-[10px] break-all"><svg class="h-5 w-5 shrink-0 text-[#1A3A6B]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 6h18v12H3zM3 7l9 7 9-7" /></svg>{{ $this->user->email }}</span>
                                    <span class="flex items-center gap-[10px]"><svg class="h-5 w-5 shrink-0 text-[#1A3A6B]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0zM12 10h.01" /></svg>Mérida, Yucatán, México</span>
                                    <span class="flex items-center gap-[10px]"><svg class="h-5 w-5 shrink-0 text-[#1A3A6B]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 5h18v16H3zM7 3v4m10-4v4M3 10h18" /></svg>{{ (int) $this->user->created_at->diffInDays(now()) }} días con la cuenta</span>
                                </div>
                            </article>
                            @endif

                            <article class="min-w-0 overflow-hidden rounded-xl border border-[#d4d4d8] bg-white">
                                <header class="profile-strip flex items-center gap-[10px] border-b border-[#d4d4d8] bg-zinc-100 p-[20px]">
                                    <span class="profile-icon-box flex shrink-0 items-center justify-center rounded-lg border border-[#d4d4d8] bg-white text-[#1A3A6B]"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 6h11M9 12h11M9 18h11M4 6h.01M4 12h.01M4 18h.01" /></svg></span>
                                    <div class="min-w-0"><h3 class="leading-none text-[#1A3A6B]">Información organizacional</h3><p class="mt-[10px] leading-none text-black">Posición dentro de la empresa</p></div>
                                </header>
                                <dl class="profile-info-grid grid grid-cols-2 gap-[20px] p-[20px]">
                                    <div class="min-w-0"><dt class="leading-none text-[#1A3A6B]">Área</dt><dd class="mt-[10px] truncate leading-none text-black" title="{{ $organizationalProfile?->physicalArea?->name ?? 'Sin asignar' }}">{{ $organizationalProfile?->physicalArea?->name ?? 'Sin asignar' }}</dd></div>
                                    <div class="min-w-0"><dt class="leading-none text-[#1A3A6B]">Puesto</dt><dd class="mt-[10px] truncate leading-none text-black" title="{{ $organizationalProfile?->jobPosition?->name ?? 'Sin asignar' }}">{{ $organizationalProfile?->jobPosition?->name ?? 'Sin asignar' }}</dd></div>
                                    <div class="min-w-0"><dt class="leading-none text-[#1A3A6B]">Colaboradores</dt><dd class="mt-[10px] leading-none text-black">{{ $subordinates->isEmpty() ? 'No tiene colaboradores' : $subordinates->count() }}</dd></div>
                                    <div class="min-w-0"><dt class="leading-none text-[#1A3A6B]">Supervisor</dt><dd class="mt-[10px] truncate leading-none text-black" title="{{ $supervisors->map(fn ($user) => trim($user->name.' '.$user->last_name))->join(', ') ?: 'Sin supervisor' }}">{{ $supervisors->map(fn ($user) => trim($user->name.' '.$user->last_name))->join(', ') ?: 'Sin supervisor' }}</dd></div>
                                </dl>
                            </article>
                        </div>

                        <article class="overflow-hidden rounded-xl border border-[#d4d4d8] bg-white">
                            <header class="profile-strip flex items-center justify-between gap-[20px] gap-y-[10px] border-b border-[#d4d4d8] bg-zinc-100 p-[20px]">
                                <div class="flex min-w-0 items-center gap-[10px]">
                                    <span class="profile-icon-box flex shrink-0 items-center justify-center rounded-lg border border-[#d4d4d8] bg-white text-[#1A3A6B]"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4m9-10a4 4 0 100-8 4 4 0 000 8zm9 1v6m3-3h-6" /></svg></span>
                                    <div class="min-w-0"><h3 class="leading-none text-[#1A3A6B]">Amigos</h3><p class="mt-[10px] leading-none text-black">{{ $totalFriends }} contactos agregados</p></div>
                                </div>
                                <button type="button" @click="friendsDirectoryOpen = true" class="{{ str_replace('py-[15px]', 'py-[10px]', $actionButton) }} shrink-0">
                                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h16M4 12h16M4 18h10" /></svg>
                                    <span>Ver directorio</span>
                                </button>
                            </header>
                            <div class="profile-chip-grid min-w-0 p-[20px]">
                                @forelse ($friends as $friend)
                                    <a href="{{ route('profiles.show', $friend) }}" class="flex min-w-0 items-center gap-[10px] rounded-xl border border-[#d4d4d8] px-[20px] py-[10px] transition hover:bg-zinc-50">
                                        @if ($friend->profile_photo_path)
                                            <img src="{{ $friend->profile_photo_url }}" alt="Foto de {{ $friend->name }}" class="h-9 w-9 shrink-0 rounded-full object-cover ring-1 ring-zinc-200">
                                        @else
                                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-zinc-100 text-black ring-1 ring-zinc-200">{{ mb_strtoupper(mb_substr($friend->name, 0, 1).mb_substr($friend->last_name ?? '', 0, 1)) }}</span>
                                        @endif
                                        <span class="min-w-0 truncate leading-none text-black" title="{{ $friend->name }} {{ $friend->last_name }}">{{ $friend->name }}</span>
                                    </a>
                                @empty
                                    <div class="flex w-full items-center justify-center rounded-xl border border-dashed border-[#d4d4d8] px-[20px] py-[30px] text-center text-black">
                                        <span class="profile-lines">Todavía no tienes amigos agregados.</span>
                                    </div>
                                @endforelse
                            </div>
                        </article>

                        @if ($editing)
                            <article class="overflow-hidden rounded-xl border border-[#d4d4d8] bg-white">
                                <header class="profile-strip flex items-center justify-between gap-[20px] border-b border-[#d4d4d8] bg-zinc-100 p-[20px]">
                                    <div class="flex min-w-0 items-center gap-[10px]">
                                        <span class="profile-icon-box flex shrink-0 items-center justify-center rounded-lg border border-[#d4d4d8] bg-white text-[#1A3A6B]">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                                        </span>
                                        <div class="min-w-0">
                                            <h3 class="leading-none text-[#1A3A6B]">Seguridad y contraseña</h3>
                                            <p class="mt-[10px] leading-none text-black">Requiere tu contraseña actual y repetir la nueva.</p>
                                        </div>
                                    </div>
                                    <span class="shrink-0 rounded-full border border-[#d4d4d8] bg-white px-[20px] py-[10px] leading-none text-black">Verificación doble</span>
                                </header>

                                <div class="p-[20px]">
                                    <div class="grid gap-[20px] xl:grid-cols-3">
                                        <div>
                                            <label for="current-password" class="mb-[10px] block leading-none text-[#1A3A6B]">1. Contraseña actual</label>
                                            <input id="current-password" type="password" wire:model="currentPassword" autocomplete="current-password" placeholder="Tu contraseña" class="{{ $fieldClass }}">
                                            <x-input-error for="currentPassword" class="mt-[10px]" />
                                        </div>
                                        <div>
                                            <label for="new-password" class="mb-[10px] block leading-none text-[#1A3A6B]">2. Nueva contraseña</label>
                                            <input id="new-password" type="password" wire:model="newPassword" autocomplete="new-password" placeholder="Escribe la nueva" class="{{ $fieldClass }}">
                                            <x-input-error for="newPassword" class="mt-[10px]" />
                                        </div>
                                        <div>
                                            <label for="new-password-confirmation" class="mb-[10px] block leading-none text-[#1A3A6B]">3. Repite la contraseña</label>
                                            <input id="new-password-confirmation" type="password" wire:model="newPasswordConfirmation" autocomplete="new-password" placeholder="Repite la nueva" class="{{ $fieldClass }}">
                                            <x-input-error for="newPasswordConfirmation" class="mt-[10px]" />
                                        </div>
                                    </div>
                                    <div class="mt-[20px] flex flex-col"><p class="profile-lines text-black">La nueva contraseña debe coincidir en los dos últimos campos. Se actualizará cuando presiones “Guardar cambios”.</p></div>
                                </div>
                            </article>
                        @endif

                        @if ($this->isOwnProfile)
                            @livewire('profile.logout-other-browser-sessions-form')
                        @endif

                        @if ($this->isOwnProfile && ! $standalone && \Laravel\Fortify\Features::canManageTwoFactorAuthentication())
                            @livewire('profile.two-factor-authentication-form')
                        @endif

                        @if ($editing)
                            <article class="profile-danger overflow-hidden rounded-xl border bg-white">
                                <header class="profile-danger-strip profile-strip flex items-center justify-between gap-[20px] gap-y-[10px] border-b p-[20px]">
                                    <div class="flex min-w-0 items-center gap-[10px]">
                                        <span class="profile-danger-icon profile-icon-box flex shrink-0 items-center justify-center rounded-lg border bg-white">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </span>
                                        <div class="min-w-0">
                                            <h3 class="profile-danger-text leading-none">Zona de peligro: eliminar perfil</h3>
                                            <p class="mt-[10px] leading-none text-black">Acción permanente: elimina tu cuenta y sus datos asociados.</p>
                                        </div>
                                    </div>
                                    <button type="button" wire:click="deleteAccount" wire:confirm="¿Estás seguro de que deseas eliminar tu perfil? Esta acción no se puede deshacer." class="profile-danger-text inline-flex shrink-0 items-center gap-[10px] whitespace-nowrap bg-transparent py-[10px] leading-none transition-colors focus:outline-none focus:ring-0 disabled:opacity-50" wire:loading.attr="disabled" wire:target="deleteAccount">
                                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        <span>Eliminar mi perfil</span>
                                    </button>
                                </header>

                                <div class="grid gap-[20px] p-[20px] xl:grid-cols-2">
                                    <div>
                                        <label for="deletion-name" class="mb-[10px] block leading-none text-[#1A3A6B]">Escribe tu nombre para confirmar</label>
                                        <input id="deletion-name" type="text" wire:model="deletionName" autocomplete="off" placeholder="{{ trim($this->user->name.' '.$this->user->last_name) }}" class="{{ $fieldClass }}">
                                        <div class="mt-[10px] flex flex-col"><p class="profile-lines text-black">Debes escribir exactamente: <span class="text-[#1A3A6B]">{{ trim($this->user->name.' '.$this->user->last_name) }}</span></p></div>
                                        <x-input-error for="deletionName" class="mt-[10px]" />
                                    </div>
                                    <div>
                                        <label for="deletion-password" class="mb-[10px] block leading-none text-[#1A3A6B]">Contraseña actual</label>
                                        <input id="deletion-password" type="password" wire:model="deletionPassword" autocomplete="current-password" placeholder="Confirma tu contraseña actual" class="{{ $fieldClass }}">
                                        <x-input-error for="deletionPassword" class="mt-[10px]" />
                                    </div>
                                </div>
                            </article>
                        @endif
                    </div>
                </div>
            </section>

            @if (! $this->isOwnProfile)
                @livewire('profile.people-sidebar', ['wide' => true])
            @endif

            <x-action-message on="saved" class="profile-message text-emerald-600">Perfil actualizado correctamente.</x-action-message>
    @if ($this->isOwnProfile)
        </form>
    @else
        </div>
    @endif

    </main>
    </div>

    <template x-teleport="body">
        <div data-profile-overlay x-cloak x-show="friendsDirectoryOpen" class="fixed inset-x-0 bottom-0 z-[100]" style="top: 100px;" role="dialog" aria-modal="true" aria-label="Directorio de amigos">
            <button type="button" x-show="friendsDirectoryOpen" x-transition.opacity @click="friendsDirectoryOpen = false" class="absolute inset-0 bg-black/25 backdrop-blur-md" aria-label="Cerrar directorio"></button>
            <aside
                x-show="friendsDirectoryOpen"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="-translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="-translate-x-full"
                class="absolute inset-y-0 left-0 flex w-[420px] max-w-full flex-col border-r border-[#d4d4d8] bg-white shadow-2xl"
            >
                <header class="profile-strip flex shrink-0 items-center justify-between gap-[20px] border-b border-[#d4d4d8] bg-zinc-100 p-[20px]">
                    <div class="min-w-0">
                        <h2 class="leading-none text-[#1A3A6B]">Directorio de amigos</h2>
                        <p class="mt-[10px] leading-none text-black">{{ $totalFriends }} {{ $totalFriends === 1 ? 'amigo agregado' : 'amigos agregados' }}</p>
                    </div>
                    <button type="button" @click="friendsDirectoryOpen = false" class="{{ $actionButton }} shrink-0" aria-label="Cerrar">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 6l12 12M18 6L6 18" /></svg>
                        <span>Cerrar</span>
                    </button>
                </header>

                <div x-data="backupOverlayScroll()" x-init="$nextTick(() => update())" class="relative min-h-0 flex-1">
                <div x-ref="scroller" @scroll="update()" @mouseenter="update()" class="profile-line-scroll flex h-full flex-col gap-[20px] overflow-y-auto p-[20px]">
                    @forelse ($allFriends as $friend)
                        <a href="{{ route('profiles.show', $friend) }}" class="flex shrink-0 items-center gap-[10px] rounded-xl border border-[#d4d4d8] p-[20px] transition hover:bg-zinc-50">
                            @if ($friend->profile_photo_path)
                                <img src="{{ $friend->profile_photo_url }}" alt="Foto de {{ $friend->name }}" class="h-10 w-10 shrink-0 rounded-full object-cover ring-1 ring-zinc-200">
                            @else
                                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-zinc-100 text-black ring-1 ring-zinc-200">{{ mb_strtoupper(mb_substr($friend->name, 0, 1).mb_substr($friend->last_name ?? '', 0, 1)) }}</span>
                            @endif
                            <span class="min-w-0 flex-1">
                                <span class="block truncate leading-none text-black" title="{{ $friend->name }} {{ $friend->last_name }}">{{ $friend->name }} {{ $friend->last_name }}</span>
                                <span class="mt-[10px] block truncate leading-none text-black" title="{{ $friend->email }}">{{ $friend->email }}</span>
                            </span>
                            <svg class="h-4 w-4 shrink-0 text-[#1A3A6B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m9 18 6-6-6-6" /></svg>
                        </a>
                    @empty
                        <div class="flex flex-col rounded-xl border border-dashed border-[#d4d4d8] px-[20px] py-[30px] text-center text-black"><p class="profile-lines">Todavía no tienes amigos agregados.</p></div>
                    @endforelse
                </div>
                <span x-show="visible" x-cloak class="profile-line-thumb" :style="`height:${height}px; transform:translateY(${top}px)`" aria-hidden="true"></span>
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
                    <span class="grid h-[min(70vw,420px)] w-[min(70vw,420px)] place-items-center rounded-full border-[6px] border-zinc-200 bg-white text-8xl text-black shadow-2xl">{{ mb_strtoupper(mb_substr($this->user->name, 0, 1).mb_substr($this->user->last_name ?? '', 0, 1)) }}</span>
                @endif
            </div>
        </div>
    </template>

    <style>
        /* Un solo tamaño y peso de letra en toda la página; solo el título principal (20px) es más grande. */
        .profile-page *,
        [data-profile-overlay] * {
            font-weight: 400;
        }

        .profile-page :is(p, span, label, button, a, input, textarea, select, h2, h3, dt, dd),
        [data-profile-overlay] :is(p, span, label, button, a, input, textarea, select, h2, h3) {
            font-size: 15px;
            letter-spacing: normal;
            text-transform: none;
        }

        .profile-page .profile-initials {
            font-size: 20px;
        }

        /* Caja del icono de cada subtítulo: cuadrada y del alto completo del título con su descripción (mínimo 44px). */
        /* Texto de varias líneas: 25px por línea, o sea 10px entre una línea y la siguiente (igual que entre título y descripción). */
        .profile-lines {
            line-height: 25px;
            margin-top: -5px;
            margin-bottom: -5px;
        }

        .profile-field-lines {
            line-height: 25px;
        }

        /* Título principal: el icono mide lo mismo que título + descripción (20 + 10 + 15) para que la distancia al contenedor sea de 20px. */
        .profile-head-icon {
            width: 45px;
            height: 45px;
        }

        /* Máximo dos líneas; si hay más, la segunda termina en "…". */
        .profile-clamp-2 {
            display: -webkit-box;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
            overflow: hidden;
        }

        /* Fila de botones de texto: se le quita el margen invisible de arriba y abajo para que la distancia visible sea la del resto del bloque. */
        .profile-actions {
            margin-top: -5px;
            margin-bottom: -15px;
        }

        /* Responsive: si lo de la derecha de una franja no cabe en la misma línea, baja (alineado a la derecha) en vez de apretar o cortar el texto. */
        .profile-page .profile-strip.justify-between,
        [data-profile-overlay] .profile-strip.justify-between {
            flex-wrap: wrap;
        }

        .profile-page .profile-strip.justify-between > :nth-child(2),
        [data-profile-overlay] .profile-strip.justify-between > :nth-child(2) {
            margin-left: auto;
        }

        /* Títulos y descripciones de las franjas: si se parten en varias líneas, 25px por línea (10px entre líneas). Con una sola línea queda exactamente igual que antes. */
        .profile-page .profile-strip :is(h2, h3),
        [data-profile-overlay] .profile-strip :is(h2, h3) {
            line-height: 25px;
            margin-top: -5px;
            margin-bottom: -5px;
        }

        .profile-page .profile-strip :is(h2, h3) + p,
        [data-profile-overlay] .profile-strip :is(h2, h3) + p {
            line-height: 25px;
            margin-top: 5px;
            margin-bottom: -5px;
        }

        /* Resumen de la franja (actividades, amigos, actualización): si no cabe, sus textos bajan en vez de salirse. */
        .profile-page .profile-stats {
            flex-wrap: wrap;
            max-width: 100%;
            row-gap: 10px;
        }

        /* Mensajes de error de los campos: 10px debajo del campo y 25px por línea (10px entre líneas si se parten). El componente trae text-sm con interlineado de 20px. */
        .profile-page p.text-red-600,
        [data-profile-overlay] p.text-red-600 {
            line-height: 25px;
            margin-top: 5px;
            margin-bottom: -5px;
        }

        /* Mensajes de confirmación: el componente trae text-sm y text-gray-600; aquí se fuerzan a 15px y verde. */
        .profile-page .profile-message {
            color: #059669;
            font-size: 15px;
            line-height: 15px;
        }

        /* Si el título y los botones no caben en una línea, los botones bajan en vez de apretar el texto. */
        @media (min-width: 1024px) {
            .profile-page .profile-head {
                flex-wrap: wrap;
            }

            /* Los botones de texto miden 46px por su margen invisible; el título se pega abajo para que su distancia al contenedor sea exactamente de 20px. */
            .profile-page .profile-head > .profile-head-title {
                align-self: flex-end;
            }
        }

        /* Botón principal (Seguir / Aceptar): azul fuerte con letra blanca. */
        .profile-page .profile-primary:hover {
            background-color: #14305a;
            border-color: #14305a;
            color: #ffffff;
        }

        /* Con interlineado 1 y texto cortado con "...", la parte baja de letras como g, y, p se recortaba: se amplía el área visible sin mover nada. */
        .profile-page .truncate,
        [data-profile-overlay] .truncate {
            padding-bottom: 3px;
            margin-bottom: -3px;
        }

        /* Amigos: cuadrícula de recuadros con 20px entre ellos. */
        .profile-chip-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
            gap: 20px;
        }

        .profile-chip-grid > div {
            grid-column: 1 / -1;
        }

        /* Buscador: icono y texto a 20px del borde. */
        .profile-search-icon {
            left: 20px;
        }

        .profile-search-input {
            padding-left: 46px;
            padding-right: 20px;
        }

        .profile-icon-box {
            align-self: stretch;
            aspect-ratio: 1 / 1;
            min-height: 44px;
        }

        /* Franja de título de cada tarjeta: 85px de alto, y crece si la descripción ocupa dos líneas. */
        .profile-page .profile-strip,
        [data-profile-overlay] .profile-strip {
            min-height: 85px;
        }

        /* El código QR siempre va sobre blanco para que se pueda escanear, también en tema oscuro. */
        .profile-qr {
            background-color: #ffffff;
        }

        /* Sin borde morado al enfocar: el borde se queda igual que en reposo. */
        .profile-page :is(input:not([type="checkbox"]):not([type="radio"]), select, textarea):focus,
        .profile-page :is(input:not([type="checkbox"]):not([type="radio"]), select, textarea):focus-visible,
        [data-profile-overlay] :is(input:not([type="checkbox"]):not([type="radio"]), select, textarea):focus,
        [data-profile-overlay] :is(input:not([type="checkbox"]):not([type="radio"]), select, textarea):focus-visible {
            border-color: #d4d4d8 !important;
            box-shadow: none !important;
            outline: none !important;
            --tw-ring-shadow: 0 0 #0000 !important;
        }

        .profile-page :is(button, a):focus,
        .profile-page :is(button, a):focus-visible {
            outline: none !important;
            box-shadow: none !important;
        }

        .profile-page input[type="range"] {
            accent-color: #000000;
        }

        /* El texto de relleno es lo único en gris. */
        .profile-page ::placeholder {
            color: #a1a1aa;
            opacity: 1;
        }

        /* Anillo del avatar: del color de la tarjeta, para separarlo de la portada. */
        .profile-page .profile-avatar-ring {
            border: 5px solid #ffffff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
        }

        .profile-page .profile-status-dot {
            box-shadow: 0 0 0 3px #ffffff;
        }

        /* Zona de peligro: rojo suave, sin negritas. */
        .profile-page .profile-danger { border-color: #fecaca; }
        .profile-page .profile-danger-strip { background-color: #fef2f2; border-bottom-color: #fecaca; }
        .profile-page .profile-danger-icon { border-color: #fecaca; color: #b91c1c; }
        .profile-page .profile-danger-text { color: #b91c1c; }
        .profile-page button.profile-danger-text:hover { color: #7f1d1d; }

        /* Scroll de las listas: sin barra nativa; solo una línea azul delgada (igual que en Preguntas). */
        .profile-line-scroll {
            overscroll-behavior: contain;
            scrollbar-width: none;
        }

        .profile-line-scroll::-webkit-scrollbar {
            width: 0;
            height: 0;
        }

        .profile-line-thumb {
            position: absolute;
            right: 0;
            top: 0;
            z-index: 20;
            width: 3px;
            min-height: 32px;
            border-radius: 9999px;
            background: #1A3A6B;
            pointer-events: none;
        }

        .profile-page:fullscreen {
            overflow-y: auto;
            background: #ffffff;
        }

        /* ===== Tema oscuro (misma paleta que Reportes de soporte y Notificaciones) ===== */
        html.module-dark-theme {
            --support-accent: #6aa9ff;
            --support-border: #3f3f46;
            --support-surface: #18181b;
            --support-header: #202023;
        }

        html.module-dark-theme #main-content .profile-page,
        html.module-dark-theme [data-profile-overlay] {
            color: #e4e4e7;
        }

        html.module-dark-theme #main-content .profile-page [class~="bg-white"],
        html.module-dark-theme [data-profile-overlay] [class~="bg-white"] {
            background-color: var(--support-surface) !important;
        }

        html.module-dark-theme #main-content .profile-page [class~="bg-zinc-100"],
        html.module-dark-theme [data-profile-overlay] [class~="bg-zinc-100"] {
            background-color: var(--support-header) !important;
        }

        html.module-dark-theme #main-content .profile-page [class~="bg-zinc-50"] {
            background-color: #1d1d21 !important;
        }

        html.module-dark-theme #main-content .profile-page [class*="hover:bg-zinc-50"]:hover,
        html.module-dark-theme [data-profile-overlay] [class*="hover:bg-zinc-50"]:hover {
            background-color: #26262b !important;
        }

        html.module-dark-theme #main-content .profile-page [class*="hover:bg-zinc-50"]:not([class~="bg-white"]):not([class~="bg-zinc-50"]):not([class~="bg-zinc-100"]):not(:hover),
        html.module-dark-theme [data-profile-overlay] [class*="hover:bg-zinc-50"]:not([class~="bg-white"]):not([class~="bg-zinc-100"]):not(:hover) {
            background-color: transparent !important;
        }

        html.module-dark-theme #main-content .profile-page [class*="border-[#d4d4d8]"],
        html.module-dark-theme [data-profile-overlay] [class*="border-[#d4d4d8]"] {
            border-color: var(--support-border) !important;
        }

        html.module-dark-theme #main-content .profile-page [class~="text-black"],
        html.module-dark-theme [data-profile-overlay] [class~="text-black"] {
            color: #e4e4e7 !important;
        }

        html.module-dark-theme #main-content .profile-page [class~="text-[#1A3A6B]"],
        html.module-dark-theme [data-profile-overlay] [class~="text-[#1A3A6B]"] {
            color: var(--support-accent) !important;
        }

        html.module-dark-theme #main-content .profile-page [class~="text-[#1A3A6B]"][class*="hover:text-black"]:hover,
        html.module-dark-theme [data-profile-overlay] [class~="text-[#1A3A6B]"][class*="hover:text-black"]:hover {
            color: #fafafa !important;
        }

        html.module-dark-theme #main-content .profile-page ::placeholder {
            color: #71717a;
        }

        html.module-dark-theme #main-content .profile-page input[type="range"] {
            accent-color: var(--support-accent);
        }

        html.module-dark-theme #main-content .profile-page [class*="bg-white/95"] {
            background-color: rgba(24, 24, 27, 0.95) !important;
            border-color: var(--support-border) !important;
        }

        html.module-dark-theme #main-content .profile-page [class~="bg-zinc-200"] {
            background-color: var(--support-border) !important;
        }

        html.module-dark-theme #main-content .profile-page .profile-avatar-ring {
            border-color: var(--support-surface);
        }

        html.module-dark-theme #main-content .profile-page .profile-status-dot {
            box-shadow: 0 0 0 3px var(--support-surface);
        }

        html.module-dark-theme .profile-qr {
            background-color: #ffffff !important;
        }

        html.module-dark-theme #main-content .profile-page [class~="ring-zinc-200"] {
            --tw-ring-color: var(--support-border);
        }

        html.module-dark-theme #main-content .profile-page [class*="text-emerald-6"] {
            color: #34d399 !important;
        }

        html.module-dark-theme #main-content .profile-page [class*="text-red-6"] {
            color: #f87171 !important;
        }

        html.module-dark-theme #main-content .profile-page .profile-danger { border-color: rgba(248, 113, 113, 0.4); }
        html.module-dark-theme #main-content .profile-page .profile-danger-strip { background-color: rgba(248, 113, 113, 0.08) !important; border-bottom-color: rgba(248, 113, 113, 0.4) !important; }
        html.module-dark-theme #main-content .profile-page .profile-danger-icon { border-color: rgba(248, 113, 113, 0.4) !important; color: #f87171; }
        html.module-dark-theme #main-content .profile-page .profile-danger-text { color: #f87171 !important; }
        html.module-dark-theme #main-content .profile-page button.profile-danger-text:hover { color: #fecaca !important; }

        html.module-dark-theme .profile-line-thumb {
            background: var(--support-accent);
        }

        html.module-dark-theme [data-profile-overlay] aside {
            background-color: var(--support-surface);
        }

        html.module-dark-theme [data-profile-overlay] [class~="ring-zinc-200"] {
            --tw-ring-color: var(--support-border);
        }

        /* El componente de la lista tiene un solo contenedor raíz (requisito de Livewire) que no debe afectar el diseño: sus hijos se acomodan como si no existiera. */
        .profile-page .people-root {
            display: contents;
        }

        /* Lista de "Conectar con personas": nunca más baja de 320px (unas 4 filas). Si el resto de la página es corta, la tarjeta del perfil crece en vez de apretar la lista. */
        .profile-page .profile-grid > aside {
            height: auto;
            min-height: 0;
        }

        .profile-page .people-list {
            flex: 1 1 320px;
            min-height: 320px;
        }

        /* El área con scroll se ajusta a la caja de la lista (no a su contenido), para que la altura mínima y el crecimiento funcionen igual en todos los casos. */
        .profile-page .people-list > .profile-line-scroll {
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            height: auto;
        }

        /* Perfil de otra persona: "Conectar con personas" va debajo, a lo ancho, en columnas (en horizontal) para no dejar un hueco en blanco ni apretar la lista. */
        .profile-page .people-wide-grid {
            --people-row: 112px;
            --people-gap: 20px;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(min(320px, 100%), 1fr));
            grid-auto-rows: var(--people-row);
            align-content: start;
            gap: var(--people-gap);
            /* Se ven exactamente tres filas (nombre de 1 línea + correo de hasta 2 líneas caben en cada una) y al desplazar la lista se detiene siempre al inicio de una fila, para que nunca quede una fila cortada. */
            max-height: calc(var(--people-row) * 3 + var(--people-gap) * 2);
            scroll-snap-type: y mandatory;
        }

        .profile-page .people-wide-grid > * {
            scroll-snap-align: start;
        }

        .profile-page .people-wide-empty {
            grid-column: 1 / -1;
            justify-content: center;
        }

        .profile-page .people-wide-search {
            width: 360px;
            max-width: 100%;
        }

        /* ===== Responsive: cuando el contenido mide menos de 1000px (ventana angosta, tableta, celular) ===== */
        /* El contenedor es la propia página (ya escalada por el zoom), así la medida es la real del contenido y no la de la ventana. */
        .profile-page .profile-main {
            container: profile / inline-size;
        }

        /* El botón de hamburguesa de "Conectar con personas" solo existe en pantallas angostas. */
        .profile-page .profile-people-toggle {
            display: none;
        }

        @media screen {
            @container profile (max-width: 999px) {
                /* Una sola columna: primero la tarjeta del perfil y su lista de personas, después la información. Sin ancho mínimo ni scroll horizontal. */
                .profile-page .profile-grid {
                    min-width: 0;
                    min-height: 0;
                    grid-template-columns: minmax(0, 1fr);
                }

                .profile-page .profile-grid > aside {
                    height: auto;
                    min-height: 0;
                    border-right-width: 0;
                    border-bottom-width: 1px;
                }

                /* La lista de personas se abre y se cierra con el botón de hamburguesa; abierta mide 420px con su propio scroll. */
                .profile-page .profile-people-toggle {
                    display: inline-flex;
                    margin-left: auto;
                }

                .profile-page .people-panel:not([data-people-open="true"]) .people-collapsible {
                    display: none;
                }

                .profile-page .people-list {
                    flex: none;
                    height: 420px;
                }
            }

            @container profile (max-width: 520px) {
                /* Información organizacional: una sola columna para que Área, Puesto y Supervisor se lean completos. */
                .profile-page .profile-info-grid {
                    grid-template-columns: minmax(0, 1fr);
                }
            }

            @container profile (max-width: 640px) {
                /* Sesiones: el botón baja debajo del texto (alineado con él) en vez de apretarlo. 5px de separación + 15px de aire del botón = 20px visibles. */
                .profile-page .profile-session {
                    flex-direction: column;
                    align-items: flex-start;
                    gap: 5px;
                }

                .profile-page .profile-session > :last-child {
                    margin-left: 55px;
                    margin-bottom: -15px;
                }
            }
        }

        /* Celulares: el control de zoom se acerca al borde y su barra se acorta para que quepa completo. */
        @media (max-width: 480px) {
            .profile-page .profile-zoom {
                right: 10px;
                bottom: 10px;
            }

            .profile-page .profile-zoom input[type="range"] {
                width: 60px;
            }
        }

        @media print {
            #main-nav, #logo-sidebar, .no-print { display: none !important; }
            #main-content { margin-left: 0 !important; padding-top: 0 !important; }
            .profile-page { min-height: 0 !important; overflow: visible !important; }
            .profile-page > div { height: auto !important; }
            .profile-main { width: 100% !important; transform: none !important; padding: 0 !important; }
        }
    </style>
</section>
