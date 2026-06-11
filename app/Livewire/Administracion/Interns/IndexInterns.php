<?php

namespace App\Livewire\Administracion\Interns;

use App\Livewire\Administracion\Users\IndexUser;
class IndexInterns extends IndexUser
{
    public function mount()
    {
        parent::mount();
        $this->users = $this->users->filter(function($user) {
           return $user->role->role === 'Auxiliar';
        });
        
    }
    public function render()
    {
        return view('livewire.administracion.interns.index-interns')->layout('layouts.app');
    }
}
