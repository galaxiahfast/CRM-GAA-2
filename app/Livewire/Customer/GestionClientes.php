<?php

namespace App\Livewire\Customer;

use App\Models\Customer;
use App\Services\Authorization\PermissionAccessService;
use Livewire\Component;

class GestionClientes extends Component
{
    public $search;

    public $customers;

    public function destroy($id)
    {
        $this->ensureCanManageCustomers();

        try {
            $idCustomer = Customer::query()
                ->whereNull('deleted_at')
                ->where('id', $id)
                ->select('id')
                ->first();

            if (! $idCustomer) {
                return redirect()->route('customers.index')->with('error', 'Cliente no encontrado');
            }

            $idCustomer->update([
                'deleted_at' => now(),
            ]);

            session()->flash('success', 'Cliente eliminado');
        } catch (\Exception $e) {
            report($e);
            session()->flash('error', 'Error al eliminar el cliente');
        }

        return redirect()->route('customers.index');
    }

    public function renderToCreateCustomer()
    {
        $this->ensureCanManageCustomers();

        return redirect()->route('customers.create');
    }

    public function render()
    {
        $this->loadCustomers();

        return view('livewire.customer.gestion-clientes')->layout('layouts.app');
    }

    private function loadCustomers(): void
    {
        $user = auth()->user();
        $role = $user?->role?->role;
        $search = trim((string) $this->search);

        $query = Customer::query()
            ->whereNull('deleted_at')
            ->with(['accountants' => function ($query): void {
                $query->wherePivot('status', 1);
            }]);

        if (! $user->isAdmin() && $role !== 'Coordinador') {
            $query->whereHas('accountants', function ($query) use ($user): void {
                $query->where('accountant_id', $user->id);
            });
        }

        if ($search !== '') {
            $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('rfc', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            })->orderByRaw(
                'CASE WHEN name LIKE ? THEN 1 WHEN rfc LIKE ? THEN 2 ELSE 3 END',
                [$search.'%', $search.'%'],
            );
        }

        $this->customers = $query
            ->orderBy('name')
            ->orderBy('last_name')
            ->get();
    }

    protected function ensureCanManageCustomers(): void
    {
        $user = auth()->user();

        abort_unless(
            $user?->isAdmin()
                && app(PermissionAccessService::class)->allows($user, 'customers.manage'),
            403,
        );
    }
}
