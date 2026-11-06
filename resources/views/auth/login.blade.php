<x-guest-layout>
    <x-authentication-card compact>
        @include('auth.partials.access', ['initial' => 'login'])
    </x-authentication-card>
</x-guest-layout>
