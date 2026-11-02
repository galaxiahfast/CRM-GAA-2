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
        $this->updatedSearch();
    }

    public function updatedSearch(): void
    {
        $authUserId = auth()->id();
        $search = trim((string) $this->search);

        $this->users = User::with('role')
            ->whereHas('interns', fn ($query) => $query->where('user_interns.created_by', $authUserId))
            ->where(function ($query) {
                $query->whereHas('role', fn ($role) => $role->whereIn('role', ['Auxiliar', 'Becario']))
                    ->orWhere('role_id', 4);
            })
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('name', 'like', '%'.$search.'%')
                    ->orWhere('last_name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%');
            }))
            ->get();
    }

    public function render()
    {
        return view('livewire.administracion.interns.gestion-auxiliares')->layout('layouts.app');
    }
}
