<?php

namespace App\Livewire\Administracion\Relationship;

use App\Models\Customer;
use App\Models\CustomerInterns;
use App\Models\User;
use App\Services\Authorization\PermissionAccessService;
use App\Services\ReferenceDataCache;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class GestionRelacionesJerarquicas extends Component
{
    public bool $embedded = false;
    public bool $cardActions = false;
    public bool $showModal = false;
    public string $mode = 'crear';
    public ?int $selectedCustomer = null;
    public ?int $selectedAccountantId = null;
    public array $assignedInterns = [];
    public string $deleteConfirmation = '';
    public ?string $notice = null;

    public function mount(bool $embedded = false, bool $cardActions = false, string $mode = 'crear'): void
    {
        $this->embedded = $embedded;
        $this->cardActions = $cardActions;
        $this->mode = in_array($mode, ['relationships', 'interns'], true) ? 'editar' : $mode;
        $this->authorizeManagement();
    }

    public function openModal(string $mode): void
    {
        abort_unless(in_array($mode, ['crear', 'editar', 'eliminar'], true), 404);
        $this->authorizeManagement();
        $this->mode = $mode;
        $this->resetAssignmentState();
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetAssignmentState();
    }

    public function updatedSelectedCustomer($customerId): void
    {
        $this->selectedCustomer = filled($customerId) ? (int) $customerId : null;
        $this->selectedAccountantId = null;
        $this->assignedInterns = [];
        $this->deleteConfirmation = '';
        $this->resetValidation();

        if (! $this->selectedCustomer || $this->mode === 'crear') return;

        $customer = Customer::query()->findOrFail($this->selectedCustomer);
        $this->selectedAccountantId = $customer->accountants()->wherePivot('status', true)->value('users.id');
        $this->assignedInterns = CustomerInterns::query()->where('customer_id', $customer->id)->pluck('intern_id')->map(fn ($id) => (int) $id)->all();
    }

    public function save(): void
    {
        $this->authorizeManagement();
        $data = $this->validate([
            'selectedCustomer' => ['required', 'integer', 'exists:customers,id'],
            'selectedAccountantId' => [$this->mode === 'eliminar' ? 'nullable' : 'required', 'nullable', 'integer', 'exists:users,id'],
            'assignedInterns' => ['array'],
            'assignedInterns.*' => ['integer', 'exists:users,id'],
            'deleteConfirmation' => [$this->mode === 'eliminar' ? 'required' : 'nullable', 'string'],
        ]);
        $customer = Customer::query()->findOrFail((int) $data['selectedCustomer']);

        if ($this->mode === 'eliminar') {
            $expected = trim($customer->name.' '.$customer->last_name.' '.$customer->maternal_last_name);
            if (mb_strtolower(trim($data['deleteConfirmation'])) !== mb_strtolower($expected)) {
                $this->addError('deleteConfirmation', 'Escribe exactamente el nombre completo del cliente.');
                return;
            }
            DB::transaction(function () use ($customer): void {
                CustomerInterns::query()->where('customer_id', $customer->id)->delete();
                $accountantIds = $customer->accountants()->pluck('users.id');
                if ($accountantIds->isNotEmpty()) $customer->accountants()->updateExistingPivot($accountantIds, ['status' => false]);
            });
            $this->finish('Asignaciones eliminadas correctamente.');
            return;
        }

        abort_unless(User::query()->whereKey($data['selectedAccountantId'])->whereHas('role', fn ($query) => $query->whereIn('role', ['Coordinador', 'Contador']))->exists(), 422);
        $validInternIds = User::query()->whereKey($data['assignedInterns'] ?? [])->whereHas('role', fn ($query) => $query->where('role', 'Auxiliar'))->pluck('id')->all();
        abort_unless(count($validInternIds) === count(array_unique($data['assignedInterns'] ?? [])), 422);

        DB::transaction(function () use ($customer, $data, $validInternIds): void {
            $accountantIds = $customer->accountants()->pluck('users.id');
            if ($accountantIds->isNotEmpty()) $customer->accountants()->updateExistingPivot($accountantIds, ['status' => false]);
            $customer->accountants()->syncWithoutDetaching([(int) $data['selectedAccountantId'] => ['status' => true]]);
            CustomerInterns::query()->where('customer_id', $customer->id)->delete();
            foreach ($validInternIds as $internId) CustomerInterns::create(['customer_id' => $customer->id, 'intern_id' => $internId]);
        });
        $this->finish($this->mode === 'crear' ? 'Asignación creada correctamente.' : 'Asignación actualizada correctamente.');
    }

    public function render()
    {
        $catalog = app(ReferenceDataCache::class)->assignmentCatalog();

        return view('livewire.administracion.relationship.gestion-relaciones-jerarquicas', [
            'customers' => $catalog['customers'],
            'accountants' => $catalog['accountants'],
            'interns' => $catalog['interns'],
            'selectedCustomerModel' => $this->selectedCustomer ? $catalog['customers']->firstWhere('id', $this->selectedCustomer) : null,
        ]);
    }

    private function finish(string $message): void
    {
        app(ReferenceDataCache::class)->forgetAssignmentCatalog();
        $this->resetAssignmentState();
        $this->notice = $message;
        $this->dispatch('assignments-updated');
    }

    private function resetAssignmentState(): void
    {
        $this->selectedCustomer = null;
        $this->selectedAccountantId = null;
        $this->assignedInterns = [];
        $this->deleteConfirmation = '';
        $this->notice = null;
        $this->resetValidation();
    }

    private function authorizeManagement(): void
    {
        abort_unless(app(PermissionAccessService::class)->allows(auth()->user(), 'administration.assignments.manage'), 403);
    }
}
