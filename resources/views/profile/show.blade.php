<x-app-layout>
    <div class="min-h-[calc(100dvh-90px)] bg-gray-100 text-[15px] text-gray-700" x-data="{ peopleOpen: false }" x-on:open-people-drawer.window="peopleOpen = true" x-on:keydown.escape.window="peopleOpen = false">
        <main class="w-full">
            <div class="profile-people-scrollbar overflow-x-auto overscroll-x-contain" style="box-sizing: border-box; padding-left: 50px;">
                <div class="min-h-[calc(100dvh-90px)] min-w-[1000px] overflow-hidden" style="width: 70%; margin-inline: auto;">
                    <div class="min-w-0">
                        @if (Laravel\Fortify\Features::canUpdateProfileInformation()) @livewire('profile.update-profile-information-form') @endif
                        @livewire('profile.logout-other-browser-sessions-form')
                        @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::updatePasswords())) <div class="p-[25px]">@livewire('profile.update-password-form')</div> @endif
                        @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication()) <div class="p-[25px]">@livewire('profile.two-factor-authentication-form')</div> @endif
                        @if (Laravel\Jetstream\Jetstream::hasAccountDeletionFeatures()) <div class="p-[25px]">@livewire('profile.delete-user-form')</div> @endif
                    </div>
                </div>
            </div>
        </main>

        <div x-cloak x-show="peopleOpen" class="fixed inset-x-0 bottom-0 top-[90px] z-[90]" role="dialog" aria-modal="true" aria-label="Agregar amigos">
            <button type="button" x-show="peopleOpen" x-transition.opacity @click="peopleOpen = false" class="absolute inset-0 bg-gray-950/25 backdrop-blur-[2px]" aria-label="Cerrar panel de personas"></button>
            <div x-show="peopleOpen"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="translate-x-0"
                 x-transition:leave-end="translate-x-full"
                 class="absolute inset-y-0 right-0 w-[380px] max-w-full bg-gray-100 shadow-2xl">
                <button type="button" @click="peopleOpen = false" class="absolute right-[25px] top-[25px] z-10 grid h-9 w-9 place-items-center rounded-full text-gray-500 hover:bg-gray-200 hover:text-gray-800" aria-label="Cerrar">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 6l12 12M18 6L6 18" /></svg>
                </button>
                @livewire('profile.people-sidebar')
            </div>
        </div>
    </div>
</x-app-layout>
