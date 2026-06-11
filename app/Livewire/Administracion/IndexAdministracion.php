<?php

namespace App\Livewire\Administracion;

use Livewire\Component;
use App\Models\User;
use App\Models\Role;

class IndexAdministracion extends Component
{
    public $totalUsers = 0;
    public $totalRoles = 0;
    public $totalPermissions = 0;

    public function mount()
    {
        $this->totalUsers = User::count();
        $this->totalRoles = Role::count();
    }
    public function goToSecction($section)
    {
        return redirect()->to('/administracion/' . $section);
    }
    public function render()
    {
        return view('livewire.administracion.index-administracion')->layout('layouts.app');
    }

}
