@php
    $organizationalProfile = $this->user->activeOrganizationalProfile()->with(['physicalArea', 'jobPosition'])->first();
    $supervisors = $this->user->superiors()->get();
    $subordinates = $this->user->subordinates()->get();
    $totalFriends = $this->user->friends()->count();
    $allFriends = $this->user->friends()->orderBy('name')->orderBy('last_name')->get();
    $friends = $allFriends;
    $profileUser = $this->user;
    $fullName = trim($profileUser->name.' '.$profileUser->last_name);
    $initials = mb_strtoupper(mb_substr($profileUser->name, 0, 1).mb_substr($profileUser->last_name ?? '', 0, 1));
    $age = $profileUser->approximateAge();
    $daysWithAccount = (int) $profileUser->created_at->diffInDays(now());
    $maxBirthYear = now()->year - 14;
    $supervisorNames = $supervisors->map(fn ($user) => trim($user->name.' '.$user->last_name))->join(', ') ?: 'Sin supervisor';
    $errorMessage = collect($errors->all())->unique()->implode(' ');
@endphp

{{-- Perfil de usuario (propio y de otras personas): mismo diseño que Reloj Checador, Cronómetro, Centro de ayuda y Chat de soporte
     (variables de panel, texto de 15px, separaciones de 20px, tarjetas con cabecera, sin sombras ni textos grises, sin iconos
     decorativos, botones y enlaces en verde y errores como aviso emergente). Solo cambia la presentación: la lógica es la misma. --}}
<section
    data-panel
    data-productivity
    data-profile
    class="profile-page relative isolate flex min-h-[calc(var(--app-dvh)-123px)] w-full min-w-0 flex-col text-[15px]"
    x-data="{ profilePhotoOpen: false, coverDragging: false, photoDragging: false, saved: false }"
    x-on:keydown.escape.window="profilePhotoOpen = false"
    x-on:saved.window="saved = true; setTimeout(() => saved = false, 3000)"
>
    <x-panel-styles />

    @if ($errorMessage !== '')
        <x-error-toast :wire:key="'profile-error-'.$errorSeq" :title="$editing ? 'No se guardaron los cambios' : 'No se pudo completar la acción'" :message="$errorMessage" />
    @endif

    {{-- Aviso de perfil guardado: mismo lugar y diseño que los avisos de error, en verde --}}
    <template x-teleport="body">
        <div data-panel data-productivity data-profile x-cloak x-show="saved" x-transition.opacity.duration.200ms style="background: transparent; min-height: 0" class="pointer-events-none fixed inset-x-0 top-[40px] z-[300100] flex justify-center px-[20px]" role="status" aria-live="polite">
            <section class="app-zoom-overlay panel-surface relative w-full overflow-hidden rounded-xl border p-[20px] shadow-[0_18px_55px_rgba(0,0,0,0.30)]" style="max-width: 620px; border-color: var(--pf-green); min-height: 0">
                <p class="pf-green-text leading-none">Perfil actualizado correctamente.</p>
            </section>
        </div>
    </template>

    <main class="relative z-10 mx-auto flex w-full min-w-0 flex-1 flex-col gap-[20px] p-[20px] sm:gap-[30px] sm:p-[30px] xl:gap-[50px] xl:px-[50px] xl:py-[50px]">
    @if ($this->isOwnProfile)
        <form wire:submit="updateProfileInformation" class="contents">
    @else
        <div class="contents">
    @endif
            <section class="flex shrink-0 flex-wrap items-center justify-between gap-x-[20px] gap-y-[15px]">
                <div class="min-w-0">
                    <h1 class="panel-accent text-xl font-normal leading-none tracking-tight">Perfil de usuario</h1>
                    <p class="mt-[5px] leading-[30px]">{{ $this->isOwnProfile ? 'Consulta y administra tu información personal y organizacional.' : 'Información personal y organizacional del colaborador.' }}</p>
                </div>

                <div class="no-print flex flex-wrap items-center gap-x-[20px] gap-y-[15px]">
                    @if (! $this->isOwnProfile)
                        <a href="{{ route('profile.show') }}" class="pf-link leading-none">Volver a mi perfil</a>
                        <button type="button" x-on:click="$dispatch('open-direct-chat', { userId: {{ $profileUser->id }} })" class="pf-link leading-none focus:outline-none focus:ring-0">Enviar mensaje</button>
                        @php($friendship = $this->friendship)
                        @if (! $friendship)
                            <button type="button" wire:click="requestFriendship" class="pf-primary rounded-xl px-[20px] py-[15px] leading-none">Seguir</button>
                        @elseif ($friendship->status === \App\Models\Friendship::PENDING && $friendship->addressee_id === auth()->id())
                            <button type="button" wire:click="acceptFriendship" class="pf-primary rounded-xl px-[20px] py-[15px] leading-none">Aceptar solicitud</button>
                        @elseif ($friendship->status === \App\Models\Friendship::PENDING)
                            <span class="leading-none">Solicitud enviada</span>
                        @else
                            <button type="button" wire:click="removeFriendship" wire:confirm="¿Quieres dejar de seguir a esta persona y eliminarla de tus amigos?" class="panel-button-secondary rounded-xl px-[20px] py-[15px] leading-none">Amigo</button>
                        @endif
                    @endif

                    {{-- Solo la propia persona descarga su ficha, y no mientras la edita --}}
                    @if ($this->isOwnProfile && ! $editing)
                        <button type="button" wire:click="downloadPdf" wire:loading.attr="disabled" wire:target="downloadPdf" title="Descargar tu ficha de perfil en PDF" class="pf-link leading-none focus:outline-none focus:ring-0 disabled:cursor-wait disabled:opacity-40">
                            <span wire:loading.remove wire:target="downloadPdf">Descargar perfil PDF</span>
                            <span wire:loading wire:target="downloadPdf">Preparando…</span>
                        </button>
                    @endif

                    @if ($this->isOwnProfile && $editing)
                        <button type="button" wire:click="cancelEditing" class="panel-button-secondary rounded-xl px-[20px] py-[15px] leading-none">Cancelar</button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="updateProfileInformation" class="pf-primary rounded-xl px-[20px] py-[15px] leading-none disabled:cursor-wait disabled:opacity-60">
                            <span wire:loading.remove wire:target="updateProfileInformation">Guardar cambios</span>
                            <span wire:loading wire:target="updateProfileInformation">Guardando…</span>
                        </button>
                    @elseif ($this->isOwnProfile)
                        <button type="button" wire:click="startEditing" class="pf-primary rounded-xl px-[20px] py-[15px] leading-none">Editar perfil</button>
                    @endif
                </div>
            </section>

            <section class="profile-grid panel-surface panel-border grid grid-cols-1 overflow-hidden rounded-xl border lg:grid-cols-[330px_minmax(0,1fr)]">
                <aside class="panel-border flex min-w-0 flex-col border-b lg:border-b-0 lg:border-r">
                    <div class="relative h-[150px] shrink-0 overflow-hidden bg-gradient-to-br from-[#1A3A6B] via-[#24508a] to-[#3b6ca8]">
                        @if ($editing)
                            <label for="cover" class="group absolute inset-0 cursor-pointer" x-on:dragenter.prevent="coverDragging = true" x-on:dragover.prevent="coverDragging = true" x-on:dragleave.prevent="coverDragging = false" x-on:drop.prevent="coverDragging = false; $refs.coverInput.files = $event.dataTransfer.files; $refs.coverInput.dispatchEvent(new Event('change', { bubbles: true }))">
                                <input x-ref="coverInput" id="cover" type="file" accept="image/png,image/jpeg" wire:model="cover" class="sr-only">
                                @if ($cover)
                                    <img src="{{ $cover->temporaryUrl() }}" alt="Vista previa de portada" class="h-full w-full object-cover">
                                @elseif ($profileUser->profile_cover_path)
                                    <img src="{{ Storage::url($profileUser->profile_cover_path) }}" alt="Portada actual" class="h-full w-full object-cover">
                                @endif
                                <span class="absolute inset-0 grid place-items-center bg-black/45 text-center text-white transition group-hover:bg-black/60" :class="coverDragging ? 'bg-black/65' : ''">
                                    <span class="px-[20px] leading-[30px]">Arrastra o selecciona una portada</span>
                                </span>
                            </label>
                        @elseif ($profileUser->profile_cover_path)
                            <img src="{{ Storage::url($profileUser->profile_cover_path) }}" alt="Portada del perfil" class="h-full w-full object-cover">
                        @endif
                    </div>

                    <div class="shrink-0 px-[20px] pb-[20px] text-center">
                        <div class="relative mx-auto -mt-[58px] h-[116px] w-[116px]">
                            @if ($editing)
                                <label for="photo" class="profile-avatar-ring group block h-full w-full cursor-pointer overflow-hidden rounded-full" x-on:dragenter.prevent="photoDragging = true" x-on:dragover.prevent="photoDragging = true" x-on:dragleave.prevent="photoDragging = false" x-on:drop.prevent="photoDragging = false; $refs.photoInput.files = $event.dataTransfer.files; $refs.photoInput.dispatchEvent(new Event('change', { bubbles: true }))">
                                    <input x-ref="photoInput" id="photo" type="file" accept="image/png,image/jpeg" wire:model="photo" class="sr-only">
                                    @if ($photo)
                                        <img src="{{ $photo->temporaryUrl() }}" alt="Vista previa de la foto" class="h-full w-full object-cover">
                                    @elseif ($profileUser->hasProfilePhotoFile())
                                        <img src="{{ $profileUser->profile_photo_url }}" alt="Foto actual" class="h-full w-full object-cover">
                                    @else
                                        <img src="{{ $profileUser->avatarUrl() }}" alt="Ilustración de perfil" class="h-full w-full object-cover">
                                    @endif
                                    <span class="absolute inset-[5px] grid place-items-center rounded-full bg-black/60 px-[10px] text-white opacity-0 transition group-hover:opacity-100" :class="photoDragging ? 'opacity-100' : ''">Cambiar foto</span>
                                </label>
                            @else
                                <button type="button" @click="profilePhotoOpen = true" class="profile-avatar-ring block h-full w-full cursor-zoom-in overflow-hidden rounded-full" aria-label="Ampliar foto de perfil">
                                    <img src="{{ $profileUser->avatarUrl() }}" alt="Foto de {{ $profileUser->name }}" class="h-full w-full object-cover">
                                </button>
                            @endif
                            <span class="profile-status-dot absolute bottom-2 right-2 h-4 w-4 rounded-full {{ $isOnline ? 'bg-emerald-500' : 'bg-gray-400' }}" title="{{ $isOnline ? 'Activo' : 'Inactivo' }}"></span>
                        </div>

                        <div class="mt-[20px]">
                            @if ($editing)
                                <div class="flex flex-col gap-[20px] text-left">
                                    <div>
                                        <label for="profile-name" class="pf-label">Nombres</label>
                                        <input id="profile-name" type="text" wire:model="state.name" autocomplete="given-name" placeholder="Escribe tus nombres" class="pf-field">
                                    </div>
                                    <div>
                                        <label for="profile-last-name" class="pf-label">Apellidos</label>
                                        <input id="profile-last-name" type="text" wire:model="state.last_name" autocomplete="family-name" placeholder="Escribe tus apellidos" class="pf-field">
                                    </div>
                                </div>
                            @else
                                <h2 class="panel-accent leading-none">{{ $fullName }}</h2>
                            @endif
                            <p class="mt-[15px] leading-none">González Alonzo y Asociados S.C.P.</p>
                            <p class="mt-[15px] inline-flex items-center gap-[10px] leading-none"><span class="h-2 w-2 rounded-full {{ $isOnline ? 'bg-emerald-500' : 'bg-gray-400' }}"></span>{{ $isOnline ? 'Disponible' : 'Desconectado' }}</p>
                        </div>
                    </div>

                    @if ($this->isOwnProfile)
                        @livewire('profile.people-sidebar')
                    @else
                        <dl class="mt-[20px] flex flex-col gap-[20px] border-t border-[color:var(--panel-border)] p-[20px] text-left">
                            <div class="min-w-0"><dt class="pf-label">Correo electrónico</dt><dd class="pf-dd break-all">{{ $profileUser->email }}</dd></div>
                            <div class="min-w-0"><dt class="pf-label">Año de nacimiento</dt><dd class="pf-dd">{{ $profileUser->birth_year ? $profileUser->birth_year.' · '.$age.' '.($age === 1 ? 'año' : 'años') : 'No registrado' }}</dd></div>
                            <div class="min-w-0"><dt class="pf-label">Ubicación</dt><dd class="pf-dd">Mérida, Yucatán, México</dd></div>
                        </dl>
                    @endif
                </aside>

                <div class="flex min-w-0 flex-col">
                    <header class="panel-header panel-border flex min-h-[85px] flex-wrap items-center justify-between gap-x-[20px] gap-y-[10px] border-b p-[20px]">
                        <div class="min-w-0">
                            <h2 class="panel-accent leading-none">Información del perfil</h2>
                            <p class="pf-sub mt-[5px]">Resumen del colaborador</p>
                        </div>
                        <p class="flex flex-wrap items-center gap-x-[20px] gap-y-[5px] leading-none">
                            <span>{{ number_format($activityCount) }} actividades</span>
                            <span>{{ number_format($friendCount) }} amigos</span>
                            @if (! $this->isOwnProfile)
                                <span>{{ number_format($daysWithAccount) }} {{ $daysWithAccount === 1 ? 'día' : 'días' }} con la cuenta</span>
                            @endif
                            <span>Actualizado {{ $profileUser->updated_at->locale('es')->diffForHumans() }}</span>
                        </p>
                    </header>

                    <div class="flex flex-1 flex-col gap-[20px] p-[20px]">
                        <div class="grid gap-[20px] {{ $this->isOwnProfile ? 'xl:grid-cols-2' : '' }}">
                            @if ($this->isOwnProfile)
                            <article class="panel-surface panel-border min-w-0 overflow-hidden rounded-xl border">
                                <header class="panel-header panel-border flex min-h-[85px] items-center border-b p-[20px]">
                                    <div class="min-w-0"><h3 class="panel-accent leading-none">Información personal</h3><p class="pf-sub mt-[5px]">Datos de la cuenta</p></div>
                                </header>
                                <dl class="grid gap-[20px] p-[20px] sm:grid-cols-2">
                                    <div class="min-w-0 sm:col-span-2">
                                        <dt class="pf-label">Correo electrónico</dt>
                                        @if ($this->isOwnProfile && $editing)
                                            <dd class="pf-dd-edit"><input id="profile-email" type="email" wire:model="state.email" autocomplete="email" placeholder="nombre@empresa.com" class="pf-field"></dd>
                                        @else
                                            <dd class="pf-dd truncate" title="{{ $profileUser->email }}">{{ $profileUser->email }}</dd>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <dt class="pf-label">Año de nacimiento</dt>
                                        @if ($this->isOwnProfile && $editing)
                                            <dd class="pf-dd-edit"><input id="profile-birth-year" type="number" inputmode="numeric" min="1930" max="{{ $maxBirthYear }}" step="1" wire:model="state.birth_year" placeholder="Ej. 1998" class="pf-field"></dd>
                                        @else
                                            <dd class="pf-dd">{{ $profileUser->birth_year ? $profileUser->birth_year.' · '.$age.' '.($age === 1 ? 'año' : 'años') : 'No registrado' }}</dd>
                                        @endif
                                    </div>
                                    {{-- Género (opcional): elige la ilustración que se muestra mientras no haya foto --}}
                                    <div class="min-w-0">
                                        <dt class="pf-label">Género</dt>
                                        @if ($this->isOwnProfile && $editing)
                                            <dd class="pf-dd-edit">
                                                <select id="profile-gender" wire:model="state.gender" class="pf-field">
                                                    <option value="">Prefiero no indicarlo</option>
                                                    <option value="hombre">Hombre</option>
                                                    <option value="mujer">Mujer</option>
                                                </select>
                                            </dd>
                                        @else
                                            <dd class="pf-dd">{{ ['hombre' => 'Hombre', 'mujer' => 'Mujer'][$profileUser->gender] ?? 'No registrado' }}</dd>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <dt class="pf-label">Ubicación</dt>
                                        <dd class="pf-dd">Mérida, Yucatán, México</dd>
                                    </div>
                                    <div class="min-w-0 sm:col-span-2">
                                        <dt class="pf-label">Cuenta</dt>
                                        <dd class="pf-dd">{{ number_format($daysWithAccount) }} {{ $daysWithAccount === 1 ? 'día' : 'días' }} con la cuenta · desde el {{ $profileUser->created_at->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}</dd>
                                    </div>
                                </dl>
                            </article>
                            @endif

                            <article class="panel-surface panel-border min-w-0 overflow-hidden rounded-xl border">
                                <header class="panel-header panel-border flex min-h-[85px] items-center border-b p-[20px]">
                                    <div class="min-w-0"><h3 class="panel-accent leading-none">Información organizacional</h3><p class="pf-sub mt-[5px]">Posición dentro de la empresa</p></div>
                                </header>
                                <dl class="grid gap-[20px] p-[20px] sm:grid-cols-2">
                                    <div class="min-w-0"><dt class="pf-label">Área</dt><dd class="pf-dd truncate" title="{{ $organizationalProfile?->physicalArea?->name ?? 'Sin asignar' }}">{{ $organizationalProfile?->physicalArea?->name ?? 'Sin asignar' }}</dd></div>
                                    <div class="min-w-0"><dt class="pf-label">Puesto</dt><dd class="pf-dd truncate" title="{{ $organizationalProfile?->jobPosition?->name ?? 'Sin asignar' }}">{{ $organizationalProfile?->jobPosition?->name ?? 'Sin asignar' }}</dd></div>
                                    <div class="min-w-0"><dt class="pf-label">Colaboradores</dt><dd class="pf-dd">{{ $subordinates->isEmpty() ? 'No tiene colaboradores' : $subordinates->count() }}</dd></div>
                                    <div class="min-w-0"><dt class="pf-label">Supervisor</dt><dd class="pf-dd truncate" title="{{ $supervisorNames }}">{{ $supervisorNames }}</dd></div>
                                </dl>
                            </article>
                        </div>

                        <article class="panel-surface panel-border flex flex-1 flex-col overflow-hidden rounded-xl border">
                            <header class="panel-header panel-border flex min-h-[85px] items-center border-b p-[20px]">
                                <div class="min-w-0"><h3 class="panel-accent leading-none">Acerca de {{ $this->isOwnProfile ? 'mí' : \Illuminate\Support\Str::before($profileUser->name, ' ') }}</h3><p class="pf-sub mt-[5px]">Presentación personal y profesional</p></div>
                            </header>
                            <div class="flex-1 p-[20px]">
                                @if ($this->isOwnProfile && $editing)
                                    <label for="profile-description" class="pf-label">Descripción del perfil</label>
                                    <textarea id="profile-description" wire:model="state.profile_description" rows="5" maxlength="500" class="panel-field block w-full resize-none rounded-xl border px-[20px] py-[10px] leading-[30px]" placeholder="Cuéntanos sobre tu experiencia, funciones o intereses profesionales..."></textarea>
                                    <p class="mt-[10px] leading-[30px]">Máximo 500 caracteres.</p>
                                @else
                                    <p class="text-justify leading-[30px]">{{ $profileUser->profile_description ?: 'Colaborador de González Alonzo y Asociados S.C.P., ubicado en Mérida, Yucatán, México. Este perfil reúne información profesional y organizacional para facilitar la comunicación, la colaboración y el contacto con otros integrantes del equipo.' }}</p>
                                @endif
                            </div>
                        </article>

                        @if ($this->isOwnProfile)
                            <article class="panel-surface panel-border overflow-hidden rounded-xl border">
                                <header class="panel-header panel-border flex min-h-[85px] items-center border-b p-[20px]">
                                    <div class="min-w-0"><h3 class="panel-accent leading-none">Amigos</h3><p class="pf-sub mt-[5px]">{{ $totalFriends }} {{ $totalFriends === 1 ? 'contacto agregado' : 'contactos agregados' }}</p></div>
                                </header>
                                <div class="profile-chip-grid min-w-0 p-[20px]">
                                    @forelse ($friends as $friend)
                                        <a href="{{ route('profiles.show', $friend) }}" class="profile-chip panel-border flex min-w-0 items-center gap-[10px] rounded-xl border px-[20px] py-[10px]">
                                            <img src="{{ $friend->avatarUrl() }}" alt="Foto de {{ $friend->name }}" class="panel-border h-9 w-9 shrink-0 rounded-full border object-cover">
                                            <span class="min-w-0 truncate leading-none" title="{{ $friend->name }} {{ $friend->last_name }}">{{ $friend->name }}</span>
                                        </a>
                                    @empty
                                        <p class="panel-border rounded-xl border border-dashed px-[20px] py-[30px] text-center leading-[30px]">Todavía no tienes amigos agregados.</p>
                                    @endforelse
                                </div>
                            </article>
                        @endif

                        @if ($editing)
                            <article class="panel-surface panel-border overflow-hidden rounded-xl border">
                                <header class="panel-header panel-border flex min-h-[85px] items-center border-b p-[20px]">
                                    <div class="min-w-0"><h3 class="panel-accent leading-none">Seguridad y contraseña</h3><p class="pf-sub mt-[5px]">Requiere tu contraseña actual y repetir la nueva.</p></div>
                                </header>
                                <div class="p-[20px]">
                                    <div class="grid gap-[20px] xl:grid-cols-3">
                                        <div>
                                            <label for="current-password" class="pf-label">1. Contraseña actual</label>
                                            <input id="current-password" type="password" wire:model="currentPassword" autocomplete="new-password" placeholder="Tu contraseña" class="pf-field">
                                        </div>
                                        <div>
                                            <label for="new-password" class="pf-label">2. Nueva contraseña</label>
                                            <input id="new-password" type="password" wire:model="newPassword" autocomplete="new-password" placeholder="Escribe la nueva" class="pf-field">
                                        </div>
                                        <div>
                                            <label for="new-password-confirmation" class="pf-label">3. Repite la contraseña</label>
                                            <input id="new-password-confirmation" type="password" wire:model="newPasswordConfirmation" autocomplete="new-password" placeholder="Repite la nueva" class="pf-field">
                                        </div>
                                    </div>
                                    <p class="mt-[20px] leading-[30px]">Solo se cambia si escribes una contraseña nueva. Se actualizará cuando presiones “Guardar cambios”.</p>
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
                            <article class="profile-danger overflow-hidden rounded-xl border">
                                <header class="panel-header flex min-h-[85px] flex-wrap items-center justify-between gap-x-[20px] gap-y-[10px] border-b p-[20px]" style="border-color: var(--panel-danger)">
                                    <div class="min-w-0">
                                        <h3 class="leading-none" style="color: var(--panel-danger)">Zona de peligro: eliminar perfil</h3>
                                        <p class="pf-sub mt-[5px]">Acción permanente: elimina tu cuenta y sus datos asociados.</p>
                                    </div>
                                    <button type="button" wire:click="deleteAccount" wire:confirm="¿Estás seguro de que deseas eliminar tu perfil? Esta acción no se puede deshacer." class="whitespace-nowrap leading-none focus:outline-none focus:ring-0 disabled:opacity-50" style="color: var(--panel-danger)" wire:loading.attr="disabled" wire:target="deleteAccount">Eliminar mi perfil</button>
                                </header>
                                <div class="panel-surface grid gap-[20px] p-[20px] xl:grid-cols-2">
                                    <div>
                                        <label for="deletion-name" class="pf-label">Escribe tu nombre para confirmar</label>
                                        <input id="deletion-name" type="text" wire:model="deletionName" autocomplete="off" placeholder="{{ $fullName }}" class="pf-field">
                                        <p class="mt-[10px] leading-[30px]">Debes escribir exactamente: <span class="panel-accent">{{ $fullName }}</span></p>
                                    </div>
                                    <div>
                                        <label for="deletion-password" class="pf-label">Contraseña actual</label>
                                        <input id="deletion-password" type="password" wire:model="deletionPassword" autocomplete="current-password" placeholder="Confirma tu contraseña actual" class="pf-field">
                                    </div>
                                </div>
                            </article>
                        @endif
                    </div>
                </div>

                @if (! $this->isOwnProfile)
                    <div class="panel-border min-w-0 border-t p-[20px] lg:col-span-2">
                        @livewire('profile.people-sidebar', ['wide' => true])
                    </div>
                @endif
            </section>
    @if ($this->isOwnProfile)
        </form>
    @else
        </div>
    @endif
    </main>

    {{-- Foto ampliada --}}
    <template x-teleport="body">
        <div x-show="profilePhotoOpen" x-cloak x-transition.opacity @click.self="profilePhotoOpen = false" class="fixed inset-0 z-[100] grid cursor-zoom-out place-items-center bg-gray-950/55 p-[20px] backdrop-blur-md" role="dialog" aria-modal="true" aria-label="Foto de perfil ampliada">
            <button type="button" @click="profilePhotoOpen = false" aria-label="Cerrar foto ampliada" class="absolute right-[20px] top-[20px] rounded-xl bg-black/45 px-[20px] py-[15px] leading-none text-white hover:bg-black/65">Cerrar</button>
            <div class="cursor-default" @click.stop>
                <img src="{{ $profileUser->avatarUrl() }}" alt="Foto ampliada de {{ $profileUser->name }}" class="max-h-[82vh] max-w-[82vw] rounded-full border-[6px] border-[#e7e7e7] object-contain shadow-2xl">
            </div>
        </div>
    </template>

    <style>
        [data-profile] {
            --pf-green: #15803d;
            --panel-muted: var(--panel-text);
        }
        html.module-dark-theme [data-profile] { --pf-green: #4ade80; }
        [data-profile] * { font-weight: 400; }
        [data-profile] .pf-link { color: var(--pf-green); transition: opacity 150ms ease; }
        [data-profile] .pf-link:hover { opacity: .75; }
        [data-profile] .pf-green-text { color: var(--pf-green); }
        [data-profile] .pf-primary {
            border: 1px solid var(--pf-green);
            background-color: color-mix(in srgb, var(--pf-green) 12%, var(--panel-surface));
            color: var(--pf-green);
            transition: background-color 150ms ease;
        }
        [data-profile] .pf-primary:hover { background-color: color-mix(in srgb, var(--pf-green) 20%, var(--panel-surface)); }
        /* Texto que puede partirse en varias líneas: 20px de espacio entre una línea y la siguiente */
        [data-profile] p.pf-sub { line-height: 35px; margin-bottom: -10px; }
        [data-profile] p { line-height: 30px; }
        /* Etiqueta y dato: 20px entre una línea de texto y la siguiente (igual que en el resto de los módulos). En los campos
           editables la etiqueta queda a 20px del campo y cada campo a 20px del siguiente. */
        [data-profile] .pf-label { display: block; margin-bottom: 10px; line-height: 15px; color: var(--panel-accent); }
        [data-profile] label.pf-label { margin-bottom: 20px; }
        [data-profile] dd.pf-dd { line-height: 35px; margin-bottom: -10px; }
        [data-profile] dd.pf-dd-edit { margin-top: 20px; }
        [data-profile] .pf-field {
            display: block;
            height: 50px;
            width: 100%;
            border-radius: 12px;
            border: 1px solid var(--panel-border);
            background-color: var(--panel-surface);
            color: var(--panel-text);
            padding: 0 20px;
        }
        [data-profile] .pf-field::placeholder { color: var(--panel-muted); opacity: .6; }
        [data-profile] :is(button, a):focus,
        [data-profile] :is(button, a):focus-visible { outline: none !important; box-shadow: none !important; }
        [data-profile] .profile-chip:hover { background-color: var(--panel-hover); }

        /* Amigos: cuadrícula de recuadros con 20px entre ellos */
        .profile-chip-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: 20px; }
        .profile-chip-grid > p { grid-column: 1 / -1; }

        /* Anillo del avatar: del color de la tarjeta, para separarlo de la portada */
        .profile-page .profile-avatar-ring { border: 5px solid var(--panel-surface); box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12); background: var(--panel-surface); }
        .profile-page .profile-status-dot { box-shadow: 0 0 0 3px var(--panel-surface); }

        /* Zona de peligro */
        .profile-page .profile-danger { border-color: var(--panel-danger); }
        .profile-page .profile-danger > header { background-color: var(--panel-danger-soft); }

        /* El código QR siempre va sobre blanco para que se pueda escanear, también en tema oscuro */
        .profile-qr { background-color: #ffffff !important; }

        @media print {
            #main-nav, #logo-sidebar, .no-print { display: none !important; }
            #main-content { margin-left: 0 !important; padding-top: 0 !important; }
        }
    </style>
</section>
