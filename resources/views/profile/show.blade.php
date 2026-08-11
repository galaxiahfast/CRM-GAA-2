<x-app-layout>
    <div class="min-h-[calc(100dvh-90px)] bg-gray-100 text-[15px] text-gray-700">
        <header class="flex items-center gap-3 whitespace-nowrap border-b border-gray-200 px-6 py-6 lg:px-10">
            <span class="font-medium text-gray-500">Configuración</span>
            <span class="text-gray-300">&gt;</span>
            <span class="font-semibold text-[#1A3A6B]">Mi perfil</span>
        </header>

        <main class="mx-auto w-full max-w-[1120px] space-y-6 px-6 py-8 lg:px-10">
            <section class="flex items-start gap-4">
                <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl border border-[#cbd9ed] text-[#1A3A6B]">
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 21a8 8 0 00-16 0m8-10a4 4 0 100-8 4 4 0 000 8z" /></svg>
                </span>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-gray-900">Mi perfil</h1>
                    <p class="mt-1 text-gray-500">Administra tus datos personales, foto y seguridad de acceso.</p>
                </div>
            </section>

            <div class="space-y-6">
            @if (Laravel\Fortify\Features::canUpdateProfileInformation())
                @livewire('profile.update-profile-information-form')
            @endif

            @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::updatePasswords()))
                @livewire('profile.update-password-form')
            @endif
            </div>

            <section class="space-y-6">
            @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
                <div>
                    @livewire('profile.two-factor-authentication-form')
                </div>
            @endif

            <div>
                @livewire('profile.logout-other-browser-sessions-form')
            </div>

            @if (Laravel\Jetstream\Jetstream::hasAccountDeletionFeatures())
                <div>
                    @livewire('profile.delete-user-form')
                </div>
            @endif
            </section>
        </main>
    </div>
</x-app-layout>
