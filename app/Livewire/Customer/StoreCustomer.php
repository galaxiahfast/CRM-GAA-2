<?php

namespace App\Livewire\Customer;

use Livewire\Component;
use App\Models\Customer;
use App\Models\User;
use App\Models\Service;
use App\Models\CustomerAccountant;
use App\Models\CustomerService;
use App\Models\CustomerState;
use App\Models\CustomerStatement;
use App\Models\State;
use App\Models\Statement;
use Illuminate\Support\Facades\File;
use Livewire\WithFileUploads;

class StoreCustomer extends IndexCustomer
{
    use WithFileUploads;
    public $name;
    public $last_name;
    public $maternal_last_name;
    public $email;
    public string $rfc;
    public $codePhone;
    public $phone;
    public $address;
    public $showStatesModal = false;
    public $observation;
    public $accountants;
    public $services;
    public $states;
    public $statesTest;
    public $url_photo;
    public $statements;
    public $statementsTest;
    public $countries = [];
    public $selectedStates = [];
    public $selectedStatements = [];
    public $selectedState = 'initial';
    public array $selectedAccountants = [];
    public $selectedCountry = null;
    public $selectedSubServices = [];
    public array $selectedServices = [];
    public bool $showSpecialModal = false;
    public ?int $specialModalId = null;
    public $selectedStatement = "initial";
    public $photo = null;

    public $rules = [
        'name' => 'required|string|max:255',
        'last_name' => 'nullable|string|max:255',
        'maternal_last_name' => 'nullable|string|max:255',
        'email' => 'nullable|string|email|max:255',
        'rfc' => 'required|string|max:15|unique:customers,rfc',
        'phone' => 'nullable|string|regex:/^[0-9\s\-\+\(\)]*$/|max:15',
        'address' => 'nullable|string|max:255',
        'observation' => 'nullable|string|max:225',
        'codePhone' => 'nullable|string|max:10'
    ];

    public $messages = [
        'name.required' => 'El nombre es obligatorio.',
        'name.string' => 'Nombre inválido.',

        'rfc.required' => 'El RFC es obligatorio.',
        'rfc.string' => 'RFC inválido.',
        'rfc.unique' => 'RFC ya existe.',
        'rfc.max' => 'RFC muy largo (máx. 15).',

        'phone.string' => 'Teléfono inválido.',
        'phone.max' => 'Teléfono muy largo (máx. 15).',
        'phone.regex' => 'Formato de teléfono no válido.',

        'email.string' => 'Correo inválido.',
        'email.email' => 'Formato de correo no válido.',
        'email.max' => 'Correo muy largo (máx. 255).',

        'address.string' => 'Dirección inválida.',
        'address.max' => 'Dirección muy larga (máx. 255).',
    ];

    public function mount($customer = null)
    {
        $this->statements = Statement::all();
        $this->states = State::all();
        $this->services = Service::with('subServices')->get();
        $this->accountants = User::where('role_id', 3)->get();
        $this->statementsTest = collect(json_decode(File::get(resource_path('/data/statements.json')), true));
        $this->statesTest = collect(json_decode(File::get(resource_path('/data/states-mexico.json')), true));
        $this->countries = collect(json_decode(File::get(resource_path('/data/countries.json')), true))
        ->sortBy('country')
        ->values()
        ->all();

        $this->selectedCountry = collect($this->countries)->firstWhere('countryCode', 52)
        ?? $this->countries[0]
        ?? null;
    }

    // Etapa de pruebas DB
    public function subirStatements()
    {
        if ($this->statements->isEmpty())
        {
            foreach ($this->statementsTest as $statement) {
                Statement::create([
                    'statement' => $statement["statement"],
                ]);
            }
            dd('Impuestos subidos con éxito');

        };
    }
    public function subirStates()
    {
        if ($this->states->isEmpty())
        {
            foreach ($this->statesTest as $state) {
                State::create([
                    'key' => $state['clave'],
                    'name' => $state["nombre"],
                ]);
            }
            dd('Estados subidos con éxito');
        };
    }
    public function uploadPhoto()
    {
        $this->validate([
            'photo' => 'image|max:1024', // 1MB Max
        ]);

        $this->photo->store('photos', 'public');
    }

    public function toggleSubService($subServiceId)
    {
        if (in_array($subServiceId, $this->selectedSubServices)) {
            $this->selectedSubServices = array_filter(
                $this->selectedSubServices,
                fn ($id) => $id !== $subServiceId
            );
        } else {
            // Si no está, lo agregamos
            $this->selectedSubServices[] = $subServiceId;
        }

    }
    public function clearPhoto()
    {
        $this->photo = null;
    }

    public function handleClick($id)
    {
        if ($id === 1) {
            $alreadySelected = in_array(1, $this->selectedSubServices);
            $countStates = count($this->selectedStates);

            if ($countStates === 0) {
                // Solo abre la modal si no hay estados
                $this->specialModalId = $id;
                $this->showSpecialModal = true;
                return;
            }

            if ($alreadySelected) {
                $this->specialModalId = $id;
                $this->showSpecialModal = true;
                return;
            }

            // Si hay estados y aún no está seleccionado → ahora sí marcar y abrir modal
            $this->toggleSubService($id);
            $this->specialModalId = $id;
            $this->showSpecialModal = true;
            return;
        }

        if ($id === 6) {
            $alreadySelected = in_array(6, $this->selectedSubServices);
            $countStates2 = count($this->selectedStatements);

            if ($countStates2 === 0) {
                $this->specialModalId = $id;
                $this->showSpecialModal = true;
            }
            if ($alreadySelected) {
                $this->specialModalId = $id;
                $this->showSpecialModal = true;
                return;
            }
            $this->toggleSubService($id);
            $this->specialModalId = $id;
            $this->showSpecialModal = true;
        }
        // Otros subservicios
        $this->toggleSubService($id);
    }

    public function updatedSelectedStates()
    {
        if (count($this->selectedStates) === 0 && in_array(1, $this->selectedSubServices)) {
            $this->toggleSubService(1);
        }
    }

    public function updatedSelectedStatements()
    {
        if (count($this->selectedStatements) === 0 && in_array(6, $this->selectedSubServices)) {
            $this->toggleSubService(6);
        }
    }

    public function addStatement()
    {
        if (!in_array($this->selectedStatement, $this->selectedStatements)) {
            $this->selectedStatements[] = $this->selectedStatement;
        }
        if (!in_array(6, $this->selectedSubServices)) {
            $this->selectedSubServices[] = 6;
        }
        $this->selectedStatement = "initial";

    }
    public function addState()
    {
        if (!in_array($this->selectedState, $this->selectedStates)) {
            $this->selectedStates[] = $this->selectedState;
        }

        if (!in_array(1, $this->selectedSubServices)) {
            $this->selectedSubServices[] = 1;
        }
        $this->selectedState = 'initial';
    }

    public function removeStatement($statement)
    {
        if (($key = array_search($statement, $this->selectedStatements)) !== false) {
            unset($this->selectedStatements[$key]);
        }
        $this->updatedSelectedStatements();
        $this->selectedState = 'initial';
    }

    public function removeState($state)
    {
        if (($key = array_search($state, $this->selectedStates)) !== false) {
            unset($this->selectedStates[$key]);
        }
        $this->selectedState = 'initial';
        $this->updatedSelectedStates();
    }
    public function storeCustomer()
    {

        try {
            $this->validate();
        if (empty($this->selectedAccountants)) {
            $this->addError('combinado', 'Debe selecionar al menos un contador');
            return;
        }
            $customer = Customer::create([
                'name'      => $this->name,
                'last_name' => $this->last_name,
                'maternal_last_name' => $this->maternal_last_name,
                'email'     => $this->email,
                'rfc'       => $this->rfc,
                'phone'     => $this->phone,
                'address'   => $this->address,
                'observation' => $this->observation,
                'codePhone' => "+" . $this->selectedCountry['countryCode'],
                'created_by' => auth()->id(),
            ]);

            foreach ($this->selectedAccountants as $accountantId) {
                CustomerAccountant::create([
                    'customer_id' => $customer->id,
                    'accountant_id' => $accountantId['id'],
                    'status' => $accountantId['status'] ?? false
                ]);
            }
            foreach ($this->selectedSubServices as $subServiceId) {
                CustomerService::create([
                    'customer_id' => $customer->id,
                    'sub_service_id' => $subServiceId,
                ]);
            }

            if (!empty($this->photo)) {
                $this->uploadPhoto();
                $customer->url_photo = $this->photo->hashName();
                $customer->save();
            }

            if (!empty($this->selectedStates)) {
                foreach ($this->selectedStates as $stateKey) {
                    $state = State::where('key', $stateKey)->first();
                    if ($state) {
                        CustomerState::create([
                            'state_id' => $state->id,
                            'customer_id' => $customer->id
                        ]);
                    }
                }
            }

            if (!empty($this->selectedStatements)) {
                foreach ($this->selectedStatements as $statementKey) {
                    $statement = Statement::where('statement', $statementKey)->first();
                    if ($statement) {
                        CustomerStatement::create([
                            'customer_id' => $customer->id,
                            'statement_id' => $statement->id
                        ]);
                    }
                }
            }


            session()->flash('success', 'Cliente creado');
            return redirect()->route('customers.index');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->addError('validationError', $e->validator->errors()->first());
        } 
        catch (\Exception $e) {
            session()->flash('error', 'Ocurrió un error al crear el cliente.' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.customer.store-customer')->layout('layouts.app');
    }

    public function returnPage()
    {
        return redirect()->route('customers.index');
    }

    public function selectCountry($code)
    {
        $this->selectedCountry = collect($this->countries)->firstWhere('countryCode', $code);
    }

    public function updateAccountantOrder($orderedItems)
    {
        $accountantsById = collect($this->selectedAccountants)->keyBy('id');

        $this->selectedAccountants = collect($orderedItems)
            ->map(function ($item) use ($accountantsById) {
                $id = (int) $item['value']; // ← usa el 'value'
                return $accountantsById->get($id);
            })
            ->filter()
            ->values()
            ->toArray();

        foreach ($this->selectedAccountants as $index => &$accountant) {
            $accountant['status'] = $index === 0;
        }
    }
}
