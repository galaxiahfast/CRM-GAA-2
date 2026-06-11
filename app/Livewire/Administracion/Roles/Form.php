<?php

namespace App\Livewire\Administracion\Roles;

use Livewire\Component;
use App\Models\Role;
use Illuminate\Validation\Rule;

class Form extends Component
{
    public $roles = null;
    public $role = null;
    public $description = null;
    public $mode = 'create';

    public function mount($role = null)
    {
        if ($role && $role->exists) {
            $this->roles = $role;
            $this->role = $role->role;
            $this->description = $role->description;
            $this->mode = 'edit';
        }
    }
    public function save()
    {
        $rules = [
            'role' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'role')->ignore($this->roles ? $this->roles->id : null)
            ],
            'description' => 'nullable|string|max:255',
        ];
        try {
            $data = $this->validate($rules);
            
            if ($this->mode === 'create') {
                $role = Role::create($data);
            } elseif ($this->mode === 'edit' && $this->roles) {
                $this->roles->update($data);
            } else {
                throw new \Exception('Modo inválido o rol no encontrado.');
            }
            session()->flash('success', 'Rol guardado exitosamente.');
            return redirect()->to('/administracion/roles');
        } catch (\Exception $e) {
            session()->flash('error', 'Ocurrió un error al guardar el rol: ' . $e->getMessage());
            return;
        }
    }
    
    public function cancel()
    {
        return redirect()->to('/administracion/roles');
    }

    public function render()
    {
        return view('livewire.administracion.roles.form');
    }
}
