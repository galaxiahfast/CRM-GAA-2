<?php

namespace App\Livewire\Customer;

use App\Models\Customer;
use App\Models\State;
use App\Models\Statement;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UpdateCustomer extends StoreCustomer
{
    public $customer;
    public $customerId;
    public $showModal = false;

    public function mount($customer = null)
    {
        parent::mount($customer);

        $this->customer = Customer::with(['accountants' => function ($query) {
            $query->withPivot('status');
        }, 'services', 'states', 'statements'])->findOrFail($customer);

        $countryCode = $this->customer->codePhone;

        $this->name = $this->customer->name;
        $this->last_name = $this->customer->last_name;
        $this->maternal_last_name = $this->customer->maternal_last_name;
        $this->email = $this->customer->email;
        $this->rfc = $this->customer->rfc;
        $this->url_photo = $this->customer->url_photo;
        $this->phone = $this->customer->phone;
        $this->address = $this->customer->address;
        $this->observation = $this->customer->observation;
        $this->selectedCountry = collect($this->countries)->firstWhere('countryCode', $countryCode) ?? $this->countries[0] ?? null;

        $this->selectedAccountants = $this->customer->accountants->map(function ($acc) {
            return [
                'id' => $acc->id,
                'name' => $acc->name,
                'email' => $acc->email,
                'status' => (bool) $acc->pivot->status
            ];
        })
        ->sortByDesc('status')
        ->values()
        ->toArray();
        $this->selectedSubServices = $this->customer->services->pluck('id')->toArray();
        $this->selectedStates = $this->customer->states->pluck('key')->toArray();
        $this->selectedStatements = $this->customer->statements->pluck('statement')->toArray();

    }
    public function updateCustomer()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'maternal_last_name' => 'nullable|string|max:255',
            'email' => 'nullable|string|email|max:255',
            'rfc' => [
                'required',
                'string',
                'max:15',
                Rule::unique('customers', 'rfc')->ignore($this->customer->id),
            ],
            'phone' => 'nullable|string|regex:/^[0-9\s\-\+\(\)]*$/|max:15',
            'address' => 'nullable|string|max:255',
            'observation' => 'nullable|string|max:225',
            'codePhone' => 'nullable|string|max:10'
        ]);

        if (empty($this->selectedAccountants)) {
            $this->addError('combinado', 'Debe seleccionar al menos un contador');
            return;
        }

        try {
            // Actualizar datos básicos del cliente
            $this->customer->update([
                'name' => $this->name,
                'last_name' => $this->last_name,
                'maternal_last_name' => $this->maternal_last_name,
                'email' => $this->email,
                'rfc' => $this->rfc,
                'phone' => $this->phone,
                'address' => $this->address,
                'observation' => $this->observation,
                'codePhone' => $this->selectedCountry["countryCode"],
                'created_by' => auth()->id()
            ]);

            if (!empty($this->photo)) {
                $this->uploadPhoto();
                $this->customer->url_photo = $this->photo->hashName();
                $this->customer->save();
            }

            // Sincronizar contadores
            $accountantsData = collect($this->selectedAccountants)
                ->mapWithKeys(function ($item) {
                    return [$item['id'] => ['status' => $item['status']]];
                })
                ->toArray();
            $this->customer->accountants()->sync($accountantsData);

            // Sincronizar servicios
            $this->customer->services()->sync($this->selectedSubServices);

            // Sincronizar estados
            $stateIds = State::whereIn('key', $this->selectedStates)->pluck('id');
            $this->customer->states()->sync($stateIds);

            // Sincronizar declaraciones
            $statementIds = Statement::whereIn('statement', $this->selectedStatements)->pluck('id');
            $this->customer->statements()->sync($statementIds);

            session()->flash('success', 'Cliente actualizado correctamente');
            return redirect()->route('customers.index');
        } catch (\Exception $e) {
            report($e);
            session()->flash('error', 'Ocurrió un error al actualizar el cliente');
        }
    }

    public function showPhotoModal()
    {
        $this->showModal = true;
    }

    public function clearPhotoDB()
    {
        try {
            if ($this->customer->url_photo) {
                $path = 'photos/' . $this->customer->url_photo;
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
                $this->customer->url_photo = null;
                $this->customer->save();

                $this->url_photo = null;
                $this->photo = null;

                session()->flash('success', 'Foto eliminada correctamente');
            }
        } catch (\Exception $e) {
            report($e);
            session()->flash('error', 'Ocurrió un error al eliminar la foto');
        }
    }

    public function render()
    {
        return view('livewire.customer.update-customer')->layout('layouts.app');
    }
}
