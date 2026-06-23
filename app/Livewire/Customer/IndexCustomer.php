<?php

namespace App\Livewire\Customer;

use Livewire\Component;
use App\Models\Customer;
use App\Models\CustomerFile;

class IndexCustomer extends Component
{
    public $search;
    public $customers;
    public function destroy($id)
    {
        try {
            $idCustomer = Customer::where('id', $id)->select('id')->first();

            if (!$idCustomer) {
                return redirect()->route('customers.index')->with('error', 'Cliente no encontrado');
            }

            $idCustomer->update([
                'deleted_at' => now()
            ]);

            session()->flash('success', 'Cliente eliminado');
        } catch (\Exception $e) {
            session()->flash('error', 'Error al eliminar el cliente');
        }

        return redirect()->route('customers.index');
    }

    public function updatedSearch()
    {
        $this->customers = Customer::when($this->search, function ($query) {
            $query->where('name', 'like', "%{$this->search}%")
            ->orWhere('rfc', 'like', "%{$this->search}%")
            ->orWhere('email', 'like', "%{$this->search}%")
            ->orWhere('address', 'like', "%{$this->search}%");
        })
        ->orderByRaw("CASE
            WHEN name LIKE '{$this->search}%' THEN 1
            WHEN rfc LIKE '{$this->search}%' THEN 2
            ELSE 3
            END")
        ->get();
        // dump($this->customers);

    }

    public function renderToCreateCustomer()
    {
        return redirect()->route('customers.create');
    }

    public function render()
    {
        if ($this->search) {
            return view('livewire.customer.index-customer')->layout('layouts.app');
        }
    
        $user = auth()->user();
        $role = auth()->user()->role->role;

        // $createdCustomers = Customer::whereNull('deleted_at');

        // $assignedCustomers = Customer::whereHas('accountants', function ($q) use ($user) {
        //     $q->where('accountant_id', $user->id);
        // });

        if($role === "Administrador" || $role === "Coordinador"){
            $this->customers = Customer::all();
        } else{
            $this->customers = Customer::whereHas('accountants', function ($q) use ($user) {
                $q->where('accountant_id', $user->id);
            })
            ->whereNull('deleted_at')
            ->with(['accountants' => function ($q) {
                $q->wherePivot('status', 1);
            }])
            ->get();
        }


        return view('livewire.customer.index-customer')->layout('layouts.app');
    }
}
