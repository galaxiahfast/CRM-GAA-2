<?php

namespace App\Livewire\Administracion\Roles;

use App\Models\Role;
use Livewire\Component;

class IndexRole extends Component
{
    public $roles = null;
    public $search = '';

    public function mount()
    {
        $this->roles = Role::all();
    }

    public function updatedSearch()
    {
        $this->roles = Role::where('role', 'like', '%' . $this->search . '%')->get();
    }

    public function deleteRole($id)
    {
        try {
                $role_id = Role::find($id);

                if (!$role_id) {
                    throw new \Exception('Rol no encontrado.');
                }
                $role_id->delete();
                session()->flash('success', 'Rol eliminado exitosamente.');
                return redirect()->to('/administracion/roles');
        } catch (\Exception $e) {
            session()->flash('error', 'Ocurrió un error al eliminar el rol: ' . $e->getMessage());
            return;
        }
    }

    public function render()
    {
        return view('livewire.administracion.roles.index-role')->layout('layouts.app');
    }
}
