<?php

namespace App\Livewire\Administracion\Relationship;

use App\Models\Customer;
use App\Models\CustomerInterns;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class GestionRelacionesJerarquicas extends Component
{
    use WithPagination;
    
    public $interns = null;
    public $selectedCustomer = false;
    public $assignedInterns = [];
    public $customersWithInterns = [];
    protected $paginationTheme = 'tailwind';

    public function mount()
    {
        $this->interns = collect();
        $this->assignedInterns = collect();
        $this->loadCustomersWithInterns();
    }

    public function loadCustomersWithInterns()
    {
        $this->customersWithInterns = CustomerInterns::pluck('customer_id')->unique()->toArray();
    }

    public function selectCustomer($customerId)
    {
        $this->selectedCustomer = $customerId;

        $authUserId = auth()->id();
        $this->interns = User::with(['interns', 'role'])->where('role_id', 4)
            ->whereHas('interns', function ($query) use ($authUserId) {
                $query->where('user_interns.created_by', $authUserId);
        })->get();

        $this->assignedInterns = CustomerInterns::where('customer_id', $customerId)
            ->pluck('intern_id')
            ->toArray();
    }
    public function registerInternToCustomer($internId)
    {
        if($internId && $this->selectedCustomer){
            try {
                $existIntern = CustomerInterns::where('intern_id', $internId)
                    ->where('customer_id', $this->selectedCustomer)
                    ->first();
                if($existIntern){
                    $existIntern->delete();
                    $this->assignedInterns = array_diff($this->assignedInterns, [$internId]);
                } else {
                    CustomerInterns::create([
                        'intern_id' => $internId,
                        'customer_id' => $this->selectedCustomer
                    ]);
                    $this->assignedInterns[] = $internId;
                }
                $this->selectCustomer($this->selectedCustomer);
                $this->loadCustomersWithInterns();

            } catch (\Throwable $th) {
                throw $th;
            }
        } else {
            dump('no se encontró nada jaja');
        }
    }

    public function render()
    {
        $user = auth()->user();

        $createdCustomers = Customer::where('created_by', $user->id)->whereNull('deleted_at');

        $assignedCustomers = Customer::whereHas('accountants', function ($q) use ($user) {
            $q->where('accountant_id', $user->id);
        });

        $customers = $createdCustomers
            ->union($assignedCustomers)
            ->whereNull('deleted_at')
            ->with(['accountants' => function ($q) {
                $q->wherePivot('status', 1);
            }])
            ->paginate(5);
        return view('livewire.administracion.relationship.gestion-relaciones-jerarquicas', [
            'customers' => $customers
        ])->layout('layouts.app');
    }
}
