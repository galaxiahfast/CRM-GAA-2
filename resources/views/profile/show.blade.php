<x-app-layout>
    <div class="min-h-[calc(100dvh-90px)] bg-white text-[15px] text-zinc-900">
        <main class="w-full">
            <div class="profile-page-scrollbar overflow-x-auto overscroll-x-contain">
                <div class="min-h-[calc(100dvh-90px)] w-full min-w-[1000px] overflow-hidden">
                    <header class="no-print flex items-center justify-between gap-20 whitespace-nowrap border-b border-gray-200 px-[80px] py-[40px]">
                        <nav class="flex items-center gap-3 text-gray-500" aria-label="Ruta de navegación">
                            <span class="font-medium">Centro de Información</span>
                            <span class="text-gray-300">&gt;</span>
                            <a href="{{ route('profiles.index') }}" class="font-medium transition-colors hover:text-black">Perfiles</a>
                            <span class="text-gray-300">&gt;</span>
                            <span class="font-semibold text-black">Información del colaborador</span>
                        </nav>

                        <div class="flex items-center gap-[30px]">
                            <button type="button" aria-label="Descargar perfil en PDF" title="Descargar PDF" class="inline-flex items-center gap-[15px] border-0 bg-transparent p-0 font-medium text-zinc-500 transition-colors hover:text-black">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                <span>Descargar PDF</span>
                            </button>
                            <button type="button" onclick="window.print()" aria-label="Imprimir perfil" class="inline-flex items-center gap-[15px] border-0 bg-transparent p-0 font-medium text-zinc-500 transition-colors hover:text-black">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10zM9 7V3h6v4" />
                                </svg>
                                <span>Imprimir</span>
                            </button>
                        </div>
                    </header>
                    <div class="min-w-0">
                        @if (Laravel\Fortify\Features::canUpdateProfileInformation()) @livewire('profile.update-profile-information-form') @endif
                        @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::updatePasswords())) <div class="px-[80px] py-[25px]">@livewire('profile.update-password-form')</div> @endif
                        @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication()) <div class="px-[80px] py-[25px]">@livewire('profile.two-factor-authentication-form')</div> @endif
                        @if (Laravel\Jetstream\Jetstream::hasAccountDeletionFeatures()) <div class="px-[80px] py-[25px]">@livewire('profile.delete-user-form')</div> @endif
                    </div>
                </div>
            </div>
        </main>

    </div>

    <style>
        @media print {
            #main-nav, #logo-sidebar, .no-print { display: none !important; }
            #main-content { margin-left: 0 !important; padding-top: 0 !important; }
        }
    </style>
</x-app-layout>
