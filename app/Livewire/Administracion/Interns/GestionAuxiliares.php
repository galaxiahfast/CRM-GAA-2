<?php

namespace App\Livewire\Administracion\Interns;

use App\Livewire\Administracion\Users\GestionUsuarios;
use App\Models\User;

class GestionAuxiliares extends GestionUsuarios
{
    public bool $embedded = false;

    public function mount(bool $embedded = false)
    {
        $this->embedded = $embedded;
        parent::mount();
        $authUserId = auth()->id();
        $this->users = User::with(['interns', 'role'])->where('role_id', 4)
            ->whereHas('interns', function ($query) use ($authUserId) {
                $query->where('user_interns.created_by', $authUserId);
            })
            ->get();
    }

    public function render()
    {
        return view('livewire.administracion.interns.gestion-auxiliares')->layout('layouts.app');
    }
}
