<x-guest-layout>
    <x-authentication-card compact>
        @include('auth.partials.access', ['initial' => 'register'])
    </x-authentication-card>
</x-guest-layout>
