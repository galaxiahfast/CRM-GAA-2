<x-app-layout>
    @foreach (session()->all() as $key => $message)
        @if (in_array($key, ['success', 'error', 'warning', 'info']))
            <x-alert-message type="{{ $key }}">
                {{ $message }}
            </x-alert-message>
        @endif
    @endforeach

    @php
        $isAuxiliar = request()->query('Intern') === '1' ? true : false;
    @endphp


    <livewire:administracion.users.form :isAuxiliar="$isAuxiliar" />
</x-app-layout>
