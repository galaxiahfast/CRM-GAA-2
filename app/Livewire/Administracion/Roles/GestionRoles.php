<?php

namespace App\Livewire\Administracion\Roles;

use App\Models\Role;
use Livewire\Attributes\Url;
use Livewire\Attributes\On;
use Livewire\Component;

class GestionRoles extends Component
{
    public $roles = null;

    public $search = '';

    #[Url(as: 'tab')]
    public string $activeTab = 'edit';

    public bool $embedded = false;

    public ?int $editingRoleId = null;

    public function mount(bool $embedded = false, string $initialTab = 'edit')
    {
        $this->embedded = $embedded;
        if ($embedded) {
            $this->activeTab = $initialTab === 'eliminar' ? 'delete' : 'edit';
        }
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

            $this->loadRoles();

            if (! $this->embedded) {
                return redirect()->route('administracion.role', ['tab' => 'delete']);
            }
        } catch (\Exception $e) {
            report($e);
            session()->flash('error', 'Ocurrió un error al eliminar el rol: '.$e->getMessage());

            return;
        }
    }

    public function cancel()
    {
        if ($this->embedded) {
            $this->dispatch('role-management-closed');

            return;
        }

        return redirect()->route('administracion.index');
    }

    public function editRole(int $roleId): void
    {
        abort_unless(Role::whereKey($roleId)->exists(), 404);
        $this->editingRoleId = $roleId;
    }

    #[On('role-form-closed')]
    #[On('role-form-saved')]
    public function closeRoleEditor(): void
    {
        $this->editingRoleId = null;
        $this->loadRoles();
    }

    public function render()
    {
        return view('livewire.administracion.roles.gestion-roles')->layout('layouts.app');
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
