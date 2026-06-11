<?php

namespace App\Livewire\Administracion\Users;

use Livewire\Component;
use App\Models\User;

class IndexUser extends Component
{
    public $users = null;
    public $search = '';

    public function mount()
    {
        $this->users = User::with('role')->get();
    }

    public function updatedSearch()
    {
        $this->users = User::with('role')
            ->where('name', 'like', '%' . $this->search . '%')
            ->orWhere('last_name', 'like', '%' . $this->search . '%')
            ->orWhere('email', 'like', '%' . $this->search . '%')
            ->orWhereHas('role', function ($query) {
                $query->where('role', 'like', '%' . $this->search . '%');
            })
            ->get();
    }
    public function delete($id)
    {
        try {
                $user_id = User::find($id);

                if (!$user_id) {
                    throw new \Exception('Rol no encontrado.');
                }
                $user_id->delete();
                session()->flash('success', 'Usuario eliminado exitosamente.');
                return redirect()->to('/administracion/users');
        } catch (\Exception $e) {
            session()->flash('error', 'Ocurrió un error al eliminar el rol: ' . $e->getMessage());
            return;
        }
    }

    public function render()
    {
        return view('livewire.administracion.users.index-user')->layout('layouts.app');
    }
}
