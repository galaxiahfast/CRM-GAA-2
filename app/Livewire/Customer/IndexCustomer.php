<?php

namespace App\Livewire\Customer;

use Livewire\Component;
use App\Models\Customer;
use App\Models\CustomerFile;

class IndexCustomer extends Component
{
    public $customers;
    public function destroy($id)
    {
        try {
            $idCustomer = Customer::where('id', $id)->select('id')->first();

            if (!$idCustomer) {
                return redirect()->route('customers.index')->with('error', 'Cliente no encontrado');
            }

            $idCustomer->delete();

            session()->flash('success', 'Cliente eliminado');
        } catch (\Exception $e) {
            session()->flash('error', 'Error al eliminar el cliente');
        }

        return redirect()->route('customers.index');
    }

    public function renderToCreateCustomer()
    {
        return redirect()->route('customers.create');
    }

    public function render()
    {
        $this->customers = Customer::with(['accountants' => function ($q) {
            $q->wherePivot('status', 1);
        }])->where('created_by', auth()->id())
            ->get();

        return view('livewire.customer.index-customer')->layout('layouts.app');
    }
}
