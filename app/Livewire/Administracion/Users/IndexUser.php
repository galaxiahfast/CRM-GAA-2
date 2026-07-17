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
        // La eliminación se centraliza en el modal del organigrama, donde se
        // exige escribir el nombre completo del usuario y se desvinculan jefes.
        abort(403, 'Usa la confirmación segura del organigrama para eliminar usuarios.');

        try {
                $user_id = User::with('interns')->find($id);

                if (!$user_id) {
                    throw new \Exception('Usuario no encontrado.');
                }
                if($user_id->role_id === 4) {
                    $user_id_intern = UserInterns::where('intern_id', $user_id->id);

                    if(!$user_id_intern){
                        throw new \Exception('Becario no encontrado');
                    }
                    $user_id_intern->delete();
                }
                $user_id->delete();
                session()->flash('success', 'Usuario eliminado exitosamente.');
                if($user_id->role_id === 4) {
                    return redirect()->to('/administracion/interns');
                } else {
                    return redirect()->to('/administracion/users');
                }
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
