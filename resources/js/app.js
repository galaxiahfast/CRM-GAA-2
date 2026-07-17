import './bootstrap';
import 'flowbite';

// Este plugin debe registrarse después de que Livewire 3 expone window.Livewire.
// Cargarlo antes produce un error JavaScript y rompe las peticiones wire:click.
const registerLivewireSortable = () => import('livewire-sortable');

if (window.Livewire) {
    registerLivewireSortable();
} else {
    document.addEventListener('livewire:init', registerLivewireSortable, { once: true });
}
