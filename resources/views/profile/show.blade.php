<x-app-layout>
    @if (Laravel\Fortify\Features::canUpdateProfileInformation())
        @livewire('profile.update-profile-information-form')
    @elseif (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
        <div class="min-h-[calc(100dvh-100px)] bg-white p-[50px] text-[15px] text-black">@livewire('profile.two-factor-authentication-form')</div>
    @endif
</x-app-layout>
