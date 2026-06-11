<x-app-layout>
    @foreach (session()->all() as $key => $message)
        @if (in_array($key, ['success', 'error', 'warning', 'info']))
            <x-alert-message type="{{ $key }}">
                {{ $message }}
            </x-alert-message>
        @endif
    @endforeach
    <livewire:administracion.roles.form />
</x-app-layout>
