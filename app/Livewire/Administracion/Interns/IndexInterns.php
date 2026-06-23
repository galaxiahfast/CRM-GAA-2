<?php

namespace App\Livewire\Administracion\Interns;

use App\Livewire\Administracion\Users\IndexUser;
use App\Models\User;

class IndexInterns extends IndexUser
{
    public function mount()
    {
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
        return view('livewire.administracion.interns.index-interns')->layout('layouts.app');
    }
}
