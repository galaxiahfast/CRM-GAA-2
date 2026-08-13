<div class="min-h-[calc(100dvh-90px)] bg-gray-100 text-[15px] text-gray-700" x-data="{ profilePhotoOpen: false }" x-on:keydown.escape.window="profilePhotoOpen = false">
    <header class="flex items-center gap-3 border-b border-gray-200 px-6 py-6 lg:px-10">
        <a href="{{ route('profile.show') }}" class="font-medium text-gray-500 hover:text-[#1A3A6B]">Mi perfil</a>
        <span class="text-gray-300">&gt;</span>
        <span class="font-semibold text-[#1A3A6B]">{{ $profile ? 'Perfil de '.$profile->name : 'Personas' }}</span>
    </header>

    <main class="mx-auto w-full max-w-[1280px] space-y-6 px-6 py-8 lg:px-10">
        @if ($profile)
            @php
                $friendship = $friendships->first(fn ($item) => in_array($profile->id, [$item->requester_id, $item->addressee_id]));
                $isFollowing = $followingIds->contains($profile->id);
            @endphp
            <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-[0_8px_24px_rgba(15,35,66,0.06)]">
                <div class="relative h-52 bg-gradient-to-br from-[#1A3A6B] via-[#28568f] to-[#6f92bd]">
                    @if ($profile->profile_cover_path)
                        <img src="{{ Storage::url($profile->profile_cover_path) }}" alt="Portada de {{ $profile->name }}" class="h-full w-full object-cover">
                    @endif
                </div>
                <div class="relative px-6 pb-8 sm:px-8">
                    <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <button type="button" @click="profilePhotoOpen = true" aria-label="Ampliar foto de perfil" class="relative -mt-16 block h-32 w-32 cursor-zoom-in rounded-full border-[5px] border-[#4f4f4f] bg-gray-100 p-0 shadow-lg">
                                @if ($profile->profile_photo_path)
                                    <img src="{{ $profile->profile_photo_url }}" alt="Foto de {{ $profile->name }}" class="h-full w-full rounded-full object-cover">
                                @else
                                    <span class="grid h-full w-full place-items-center rounded-full bg-[#eaf1fa] text-3xl font-bold text-[#1A3A6B]">{{ mb_strtoupper(mb_substr($profile->name, 0, 1).mb_substr($profile->last_name ?? '', 0, 1)) }}</span>
                                @endif
                                <span class="absolute bottom-1 right-1 h-4 w-4 rounded-full {{ $profileIsOnline ? 'bg-emerald-500' : 'bg-gray-400' }} ring-[3px] ring-gray-100" title="{{ $profileIsOnline ? 'Activo' : 'Inactivo' }}"></span>
                            </button>
                            <div class="mt-[15px] space-y-[15px]">
                                <h1 class="flex items-center gap-2 text-2xl font-bold text-gray-900"><span>{{ $profile->name }} {{ $profile->last_name }}</span><svg class="h-5 w-5 shrink-0 text-[#1A3A6B]" viewBox="0 0 24 24" fill="currentColor" aria-label="Perfil verificado"><path d="M12 2.5l2.1 1.45 2.55-.2.95 2.38 2.27 1.18-.2 2.55L21.12 12l-1.45 2.14.2 2.55-2.27 1.18-.95 2.38-2.55-.2L12 21.5l-2.1-1.45-2.55.2-.95-2.38-2.27-1.18.2-2.55L2.88 12l1.45-2.14-.2-2.55L6.4 6.13l.95-2.38 2.55.2L12 2.5zm3.53 7.03l-1.06-1.06L11 11.94 9.53 10.47l-1.06 1.06L11 14.06l4.53-4.53z" /></svg></h1>
                                <p class="text-[15px] text-gray-600">González Alonzo y Asociados S.C.P.</p>
                                <p class="text-[15px] text-gray-600">Mérida, Yucatán, México · {{ $profile->email }} · Español</p>
                                <p class="flex flex-wrap gap-x-[8px] text-[12px] text-gray-500">
                                    <span>{{ number_format($profileActivityCount) }} actividades</span><span>·</span>
                                    <span>{{ number_format($profileFriendCount) }} amigos</span><span>·</span>
                                    <span>{{ $profile->created_at ? (int) $profile->created_at->diffInDays(now()) : 0 }} días con la cuenta</span><span>·</span>
                                    <span>{{ $profile->updated_at ? 'Actualizado '.$profile->updated_at->diffForHumans() : 'Sin actualizaciones' }}</span>
                                </p>
                            </div>
                        </div>
                        <div class="mt-[25px] flex flex-wrap gap-2">
                            @if (! $friendship)
                                <button wire:click="requestFriendship({{ $profile->id }})" class="h-11 rounded-xl bg-[#1A3A6B] px-5 font-semibold text-white hover:bg-[#142f58]">Seguir</button>
                            @elseif ($friendship->status === \App\Models\Friendship::PENDING && $friendship->addressee_id === auth()->id())
                                <button wire:click="acceptFriendship({{ $friendship->id }})" class="h-11 rounded-xl bg-emerald-600 px-5 font-semibold text-white hover:bg-emerald-700">Aceptar solicitud</button>
                            @elseif ($friendship->status === \App\Models\Friendship::PENDING)
                                <button disabled class="h-11 rounded-xl border border-gray-200 px-5 font-semibold text-gray-400">Solicitud enviada</button>
                            @else
                                <button wire:click="removeFriendship({{ $profile->id }})" wire:confirm="¿Quieres eliminar esta amistad?" class="h-11 rounded-xl border border-[#b9c9e2] bg-[#eef4fc] px-5 font-semibold text-[#1A3A6B]">Amigos</button>
                            @endif
                        </div>
                    </div>

                    <div class="mt-8 grid gap-5 lg:grid-cols-3">
                        <article class="rounded-xl border border-gray-200 p-5 lg:col-span-2">
                            <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-400">Acerca de</h2>
                            <p class="mt-3 text-gray-600" style="max-width: 620px; line-height: 2.5; text-align: justify;">{{ $profile->profile_description ?: 'Colaborador de González Alonzo y Asociados S.C.P., ubicado en Mérida, Yucatán, México. Este perfil reúne información profesional y organizacional para facilitar la comunicación, la colaboración y el contacto con otros integrantes del equipo.' }}</p>
                        </article>
                        <article class="rounded-xl border border-gray-200 p-5">
                            <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-400">Organización</h2>
                            <dl class="mt-3 space-y-3 text-sm">
                                <div><dt class="text-gray-400">Área</dt><dd class="font-semibold text-gray-700">{{ $profile->activeOrganizationalProfile?->physicalArea?->name ?? 'Sin asignar' }}</dd></div>
                                <div><dt class="text-gray-400">Supervisor</dt><dd class="font-semibold text-gray-700">{{ $profile->superiors->map(fn ($user) => trim($user->name.' '.$user->last_name))->join(', ') ?: 'Sin supervisor' }}</dd></div>
                                <div><dt class="text-gray-400">Subordinados</dt><dd class="font-semibold text-gray-700">{{ $profile->subordinates->count() }}</dd></div>
                            </dl>
                        </article>
                    </div>

                    <article class="mt-5 rounded-xl border border-gray-200 p-5">
                        <h2 class="font-semibold text-gray-800">Amigos <span class="ml-1 text-sm font-normal text-gray-400">{{ $profile->friends()->count() }}</span></h2>
                        <div class="mt-4 flex flex-wrap gap-4">
                            @forelse ($profile->friends()->limit(12)->get() as $friend)
                                <a href="{{ route('profiles.show', $friend) }}" class="group flex w-20 flex-col items-center text-center">
                                    <img src="{{ $friend->profile_photo_url }}" alt="{{ $friend->name }}" class="h-12 w-12 rounded-full object-cover ring-2 ring-gray-100 group-hover:ring-[#b9c9e2]">
                                    <span class="mt-2 w-full truncate text-xs font-medium text-gray-600">{{ $friend->name }}</span>
                                </a>
                            @empty
                                <p class="text-sm text-gray-400">Aún no tiene amigos agregados.</p>
                            @endforelse
                        </div>
                    </article>
                </div>
            </section>
            <template x-teleport="body">
                <div x-show="profilePhotoOpen" x-cloak x-transition.opacity @click.self="profilePhotoOpen = false" class="fixed inset-0 z-[100] grid cursor-zoom-out place-items-center bg-gray-950/55 p-6 backdrop-blur-md" role="dialog" aria-modal="true" aria-label="Foto de perfil ampliada">
                    <button type="button" @click="profilePhotoOpen = false" aria-label="Cerrar foto ampliada" class="absolute right-6 top-6 grid h-11 w-11 place-items-center rounded-full bg-black/45 text-white hover:bg-black/65">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 6l12 12M18 6 6 18" /></svg>
                    </button>
                    <div class="cursor-default" @click.stop>
                        @if ($profile->profile_photo_path)
                            <img src="{{ $profile->profile_photo_url }}" alt="Foto ampliada de {{ $profile->name }}" class="max-h-[82vh] max-w-[82vw] rounded-full border-[6px] border-[#4f4f4f] object-contain shadow-2xl">
                        @else
                            <span class="grid h-[min(70vw,420px)] w-[min(70vw,420px)] place-items-center rounded-full border-[6px] border-[#4f4f4f] bg-[#eaf1fa] text-8xl font-bold text-[#1A3A6B] shadow-2xl">{{ mb_strtoupper(mb_substr($profile->name, 0, 1).mb_substr($profile->last_name ?? '', 0, 1)) }}</span>
                        @endif
                    </div>
                </div>
            </template>
        @else
            @php
                $incomingRequests = $friendships->where('status', \App\Models\Friendship::PENDING)->where('addressee_id', auth()->id());
            @endphp
            <section class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Personas</h1>
                    <p class="mt-1 text-gray-500">Conoce, sigue y agrega a otros colaboradores.</p>
                </div>
                <input type="search" wire:model.live.debounce.300ms="search" placeholder="Buscar por nombre..." class="h-11 w-full rounded-xl border-gray-300 sm:w-80 focus:border-[#1A3A6B] focus:ring-[#1A3A6B]/20">
            </section>
            @if ($incomingRequests->isNotEmpty())
                <section class="rounded-xl border border-[#cbd9ed] bg-[#f7faff] p-5">
                    <h2 class="font-semibold text-[#1A3A6B]">Solicitudes de amistad</h2>
                    <div class="mt-4 flex flex-wrap gap-3">
                        @foreach ($incomingRequests as $request)
                            <div class="flex items-center gap-3 rounded-xl border border-gray-200 bg-white p-3">
                                <img src="{{ $request->requester->profile_photo_url }}" alt="{{ $request->requester->name }}" class="h-10 w-10 rounded-full object-cover">
                                <div>
                                    <a href="{{ route('profiles.show', $request->requester) }}" class="font-semibold text-gray-700 hover:text-[#1A3A6B]">{{ $request->requester->name }} {{ $request->requester->last_name }}</a>
                                    <button wire:click="acceptFriendship({{ $request->id }})" class="mt-1 block text-xs font-semibold text-emerald-600 hover:text-emerald-700">Aceptar solicitud</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif
            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @forelse ($users as $user)
                    @php
                        $friendship = $friendships->first(fn ($item) => in_array($user->id, [$item->requester_id, $item->addressee_id]));
                    @endphp
                    <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-[0_5px_18px_rgba(15,35,66,0.04)]">
                        <div class="flex items-center gap-4">
                            <img src="{{ $user->profile_photo_url }}" alt="{{ $user->name }}" class="h-14 w-14 rounded-full object-cover">
                            <div class="min-w-0">
                                <a href="{{ route('profiles.show', $user) }}" class="block truncate font-semibold text-gray-800 hover:text-[#1A3A6B]">{{ $user->name }} {{ $user->last_name }}</a>
                                <p class="mt-1 truncate text-xs text-gray-400">{{ $user->activeOrganizationalProfile?->physicalArea?->name ?? 'Área no asignada' }}</p>
                            </div>
                        </div>
                        <div class="mt-5 flex gap-2">
                            <button wire:click="toggleFollow({{ $user->id }})" class="h-9 flex-1 rounded-lg border border-gray-300 text-sm font-semibold text-gray-600 hover:bg-gray-50">{{ $followingIds->contains($user->id) ? 'Siguiendo' : 'Seguir' }}</button>
                            <a href="{{ route('profiles.show', $user) }}" class="grid h-9 flex-1 place-items-center rounded-lg bg-[#1A3A6B] text-sm font-semibold text-white hover:bg-[#142f58]">Ver perfil</a>
                        </div>
                    </article>
                @empty
                    <p class="col-span-full rounded-xl border border-dashed border-gray-300 bg-white py-12 text-center text-gray-400">No se encontraron colaboradores.</p>
                @endforelse
            </section>
        @endif
    </main>
</div>
