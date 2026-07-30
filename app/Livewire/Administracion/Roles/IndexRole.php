<?php

namespace App\Livewire\Administracion\Roles;

use App\Models\Role;
use Livewire\Attributes\Url;
use Livewire\Component;

class IndexRole extends Component
{
    public $roles = null;

    public $search = '';

    #[Url(as: 'tab')]
    public string $activeTab = 'edit';

    public function mount()
    {
        if (! in_array($this->activeTab, ['edit', 'delete'], true)) {
            $this->activeTab = 'edit';
        }

        $this->loadRoles();
    }

    public function updatedSearch()
    {
        $this->loadRoles();
    }

    public function deleteRole($id)
    {
        try {
            $role_id = Role::find($id);

            if (! $role_id) {
                throw new \Exception('Rol no encontrado.');
            }

            if (in_array($role_id->role, ['Administrador', 'Coordinador', 'Contador', 'Auxiliar'], true)
                || $role_id->users()->exists()) {
                session()->flash('error', 'No se puede eliminar un rol base ni un rol con usuarios asociados.');

                return;
            }

            $role_id->delete();
            session()->flash('success', 'Rol eliminado exitosamente.');

            return redirect()->route('administracion.role', ['tab' => 'delete']);
        } catch (\Exception $e) {
            session()->flash('error', 'Ocurrió un error al eliminar el rol: '.$e->getMessage());

            return;
        }
    }

    public function cancel()
    {
        return redirect()->route('administracion.index');
    }

    public function render()
    {
        return view('livewire.administracion.roles.index-role')->layout('layouts.app');
    }

    private function loadRoles(): void
    {
        $this->roles = Role::query()
            ->withCount(['users', 'accessPermissions'])
            ->when($this->search !== '', fn ($query) => $query->where('role', 'like', '%'.$this->search.'%'))
            ->orderBy('role')
            ->get();
    }
}
