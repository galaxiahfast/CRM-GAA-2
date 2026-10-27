<?php

namespace App\Livewire\Administracion\Permissions;

use App\Models\PermissionGroup;
use App\Services\Authorization\PermissionAccessService;
use App\Services\ReferenceDataCache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class CatalogManager extends Component
{
    public bool $cardActions = false;

    public bool $autoOpen = false;

    public bool $showModal = false;

    public string $activeTab = 'crear';

    public ?int $selectedGroupId = null;

    /** Registro fijo cuando el formulario se abre desde un registro (editar/eliminar). */
    #[Locked]
    public ?int $lockedRecordId = null;

    public string $name = '';

    public string $description = '';

    public array $permissionIds = [];

    public string $deleteConfirmation = '';

    public ?string $notice = null;

    public function mount(bool $cardActions = false, bool $autoOpen = false, string $initialTab = 'crear', ?int $initialRecordId = null): void
    {
        $this->cardActions = $cardActions;
        $this->autoOpen = $autoOpen;
        $this->authorizeManagement();

        if ($autoOpen) {
            $this->openFromDirectory($initialTab, $initialRecordId);
        }
    }

    public function openModal(string $tab = 'crear'): void
    {
        $this->lockedRecordId = null;
        $this->authorizeManagement();
        abort_unless(in_array($tab, ['crear', 'editar', 'eliminar'], true), 404);
        $this->activeTab = $tab;
        $this->resetFormState();
        $this->showModal = true;
    }

    #[On('open-permission-catalog-from-directory')]
    public function openFromDirectory(string $tab, ?int $recordId = null): void
    {
        $this->openModal($tab);
        if ($recordId !== null) {
            $this->lockedRecordId = in_array($tab, ['editar', 'eliminar'], true) ? (int) $recordId : null;
            $this->updatedSelectedGroupId($recordId);
        }
    }

    public function closeModal(): void
    {
        $this->lockedRecordId = null;
        $this->showModal = false;
        $this->resetFormState();
    }

    public function updatedSelectedGroupId($value): void
    {
        if ($this->lockedRecordId !== null && (int) $value !== $this->lockedRecordId) {
            $this->selectedGroupId = $this->lockedRecordId;

            return;
        }

        $this->selectedGroupId = filled($value) ? (int) $value : null;
        $this->clearFields();
        $this->resetValidation();

        if (! $this->selectedGroupId) {
            return;
        }

        $group = PermissionGroup::query()->with('permissions:id')->findOrFail($this->selectedGroupId);
        if (in_array($this->activeTab, ['editar', 'eliminar'], true)) {
            $this->name = (string) $group->name;
            $this->description = (string) $group->description;
            $this->permissionIds = $group->permissions->pluck('id')->map(fn ($id) => (int) $id)->all();
        }
    }

    public function save(): void
    {
        $this->authorizeManagement();
        if ($this->lockedRecordId !== null && in_array($this->activeTab, ['editar', 'eliminar'], true)) {
            $this->selectedGroupId = $this->lockedRecordId;
        }

        if ($this->activeTab === 'eliminar') {
            $data = $this->validate([
                'selectedGroupId' => ['required', 'integer', 'exists:permission_groups,id'],
                'deleteConfirmation' => ['required', 'string'],
            ]);
            $group = PermissionGroup::query()->withCount('roles')->findOrFail($data['selectedGroupId']);
            if ($group->is_system) {
                $this->addError('selectedGroupId', 'Los grupos base del sistema no se pueden eliminar.');

                return;
            }
            if ($group->roles_count > 0) {
                $this->addError('selectedGroupId', 'Desasigna este grupo de todos los roles antes de eliminarlo.');

                return;
            }
            if (mb_strtolower(trim($data['deleteConfirmation'])) !== mb_strtolower($group->name)) {
                $this->addError('deleteConfirmation', 'Escribe exactamente el nombre del grupo de permisos.');

                return;
            }
            $group->delete();
            $this->finish('Grupo de permisos eliminado correctamente.');

            return;
        }

        $this->name = trim((string) preg_replace('/\s+/u', ' ', $this->name));
        $this->description = trim($this->description);
        $uniqueName = Rule::unique('permission_groups', 'name');
        if ($this->selectedGroupId) {
            $uniqueName->ignore($this->selectedGroupId);
        }
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255', $uniqueName],
            'description' => ['nullable', 'string', 'max:255'],
            'permissionIds' => ['required', 'array', 'min:1'],
            'permissionIds.*' => ['integer', Rule::exists('access_permissions', 'id')->where('is_active', true)],
            'selectedGroupId' => [Rule::requiredIf($this->activeTab === 'editar'), 'nullable', 'integer', 'exists:permission_groups,id'],
        ]);

        DB::transaction(function () use ($data): void {
            $group = $this->activeTab === 'editar'
                ? PermissionGroup::query()->findOrFail((int) $data['selectedGroupId'])
                : new PermissionGroup;
            $group->fill(['name' => $data['name'], 'description' => $data['description'] ?: null]);
            $group->save();
            $group->permissions()->sync($data['permissionIds']);
            $group->roles()->each(fn ($role) => $role->accessPermissions()->sync($data['permissionIds']));
        });

        $this->finish($this->activeTab === 'editar' ? 'Grupo de permisos actualizado correctamente.' : 'Grupo de permisos creado correctamente.');
    }

    public function render()
    {
        $catalog = app(ReferenceDataCache::class)->permissionCatalog();

        return view('livewire.administracion.permissions.catalog-manager', [
            'groups' => $catalog['groups'],
            'availablePermissions' => $catalog['permissions'],
        ]);
    }

    private function resetFormState(): void
    {
        $this->selectedGroupId = null;
        $this->notice = null;
        $this->clearFields();
        $this->resetValidation();
    }

    private function clearFields(): void
    {
        $this->name = '';
        $this->description = '';
        $this->permissionIds = [];
        $this->deleteConfirmation = '';
    }

    private function finish(string $message): void
    {
        $lockedRecordId = $this->lockedRecordId;
        $completedTab = $this->activeTab;
        app(ReferenceDataCache::class)->forgetPermissionCatalog();
        $this->resetFormState();

        if ($lockedRecordId !== null && $completedTab === 'editar') {
            $this->updatedSelectedGroupId($lockedRecordId);
        } elseif ($lockedRecordId !== null) {
            $this->lockedRecordId = null;
            $this->showModal = false;
        }

        $this->notice = $message;
        $this->dispatch('permission-catalog-updated');
    }

    private function authorizeManagement(): void
    {
        abort_unless(app(PermissionAccessService::class)->allows(auth()->user(), 'administration.permissions.manage'), 403);
    }
}
