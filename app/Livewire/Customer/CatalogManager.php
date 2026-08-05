<?php

namespace App\Livewire\Customer;

use App\Models\Customer;
use App\Models\User;
use App\Services\Authorization\PermissionAccessService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Throwable;

class CatalogManager extends Component
{
    public bool $showModal = false;

    public string $activeTab = 'crear';

    public ?int $selectedCustomerId = null;

    public ?int $principalAccountantId = null;

    public string $name = '';

    public string $lastName = '';

    public string $maternalLastName = '';

    public string $rfc = '';

    public string $email = '';

    public string $codePhone = '52';

    public string $phone = '';

    public string $address = '';

    public string $observation = '';

    public ?string $notice = null;

    public function mount(): void
    {
        $this->ensureCanManageCustomers();
    }

    public function openModal(): void
    {
        $this->ensureCanManageCustomers();
        $this->activeTab = 'crear';
        $this->resetManagementState();
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->activeTab = 'crear';
        $this->resetManagementState();
    }

    public function setActiveTab(string $tab): void
    {
        $this->ensureCanManageCustomers();
        abort_unless(in_array($tab, ['crear', 'editar', 'eliminar'], true), 404);

        $this->activeTab = $tab;
        $this->resetManagementState();
    }

    public function updatedSelectedCustomerId($customerId): void
    {
        $this->ensureCanManageCustomers();
        $this->selectedCustomerId = filled($customerId) ? (int) $customerId : null;
        $this->resetCustomerFields();
        $this->notice = null;
        $this->resetValidation();

        if (! $this->selectedCustomerId || $this->activeTab !== 'editar') {
            return;
        }

        $customer = Customer::query()
            ->whereNull('deleted_at')
            ->findOrFail($this->selectedCustomerId);

        $this->name = (string) $customer->name;
        $this->lastName = (string) ($customer->last_name ?? '');
        $this->maternalLastName = (string) ($customer->maternal_last_name ?? '');
        $this->rfc = (string) $customer->rfc;
        $this->email = (string) ($customer->email ?? '');
        $this->codePhone = ltrim((string) ($customer->codePhone ?? '52'), '+') ?: '52';
        $this->phone = (string) ($customer->phone ?? '');
        $this->address = (string) ($customer->address ?? '');
        $this->observation = (string) ($customer->observation ?? '');
    }

    public function save(): void
    {
        match ($this->activeTab) {
            'crear' => $this->createCustomer(),
            'editar' => $this->updateCustomer(),
            'eliminar' => $this->deleteCustomer(),
            default => abort(404),
        };
    }

    public function createCustomer(): void
    {
        $this->ensureCanManageCustomers();
        $this->normalizeFields();

        $data = $this->validate($this->customerRules(), $this->validationMessages());

        if (! $this->availableAccountants()->contains('id', (int) $data['principalAccountantId'])) {
            $this->addError('principalAccountantId', 'Selecciona un contador o coordinador disponible.');

            return;
        }

        try {
            $customer = DB::transaction(function () use ($data): Customer {
                $customer = Customer::create($this->customerPayload($data) + [
                    'created_by' => auth()->id(),
                ]);

                $customer->accountants()->attach((int) $data['principalAccountantId'], [
                    'status' => true,
                ]);

                return $customer;
            });
        } catch (Throwable $exception) {
            report($exception);
            $this->addError('catalog', 'No fue posible crear el cliente. Inténtalo nuevamente.');

            return;
        }

        $this->resetManagementState();
        $this->notice = 'Cliente creado correctamente.';
        $this->dispatch('customer-catalog-updated', customerId: $customer->id, action: 'created');
    }

    public function updateCustomer(): void
    {
        $this->ensureCanManageCustomers();
        $this->normalizeFields();

        $data = $this->validate(
            ['selectedCustomerId' => $this->activeCustomerRule()] + $this->customerRules(false, true),
            $this->validationMessages(),
        );

        try {
            DB::transaction(function () use ($data): void {
                Customer::query()
                    ->whereNull('deleted_at')
                    ->lockForUpdate()
                    ->findOrFail((int) $data['selectedCustomerId'])
                    ->update($this->customerPayload($data));
            });
        } catch (Throwable $exception) {
            report($exception);
            $this->addError('catalog', 'No fue posible actualizar el cliente. Inténtalo nuevamente.');

            return;
        }

        $this->notice = 'Cliente actualizado correctamente.';
        $this->dispatch('customer-catalog-updated', customerId: (int) $data['selectedCustomerId'], action: 'updated');
    }

    public function deleteCustomer(): void
    {
        $this->ensureCanManageCustomers();

        $data = $this->validate([
            'selectedCustomerId' => $this->activeCustomerRule(),
        ]);

        try {
            DB::transaction(function () use ($data): void {
                Customer::query()
                    ->whereNull('deleted_at')
                    ->lockForUpdate()
                    ->findOrFail((int) $data['selectedCustomerId'])
                    ->update(['deleted_at' => now()]);
            });
        } catch (Throwable $exception) {
            report($exception);
            $this->addError('catalog', 'No fue posible eliminar el cliente. Inténtalo nuevamente.');

            return;
        }

        $deletedCustomerId = (int) $data['selectedCustomerId'];
        $this->resetManagementState();
        $this->notice = 'Cliente eliminado. Su historial y relaciones se conservaron.';
        $this->dispatch('customer-catalog-updated', customerId: $deletedCustomerId, action: 'deleted');
    }

    public function render()
    {
        $this->ensureCanManageCustomers();

        $customers = Customer::query()
            ->whereNull('deleted_at')
            ->with(['accountants:id,name,last_name'])
            ->orderBy('name')
            ->orderBy('last_name')
            ->get();

        return view('livewire.customer.catalog-manager', [
            'customers' => $customers,
            'availableAccountants' => $this->availableAccountants(),
            'selectedCustomer' => $this->selectedCustomerId
                ? $customers->firstWhere('id', $this->selectedCustomerId)
                : null,
        ]);
    }

    private function customerRules(
        bool $requiresAccountant = true,
        bool $ignoreSelectedCustomer = false,
    ): array {
        $uniqueRfc = Rule::unique('customers', 'rfc');

        if ($ignoreSelectedCustomer && $this->selectedCustomerId) {
            $uniqueRfc->ignore($this->selectedCustomerId);
        }

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'lastName' => ['nullable', 'string', 'max:255'],
            'maternalLastName' => ['nullable', 'string', 'max:255'],
            'rfc' => [
                'required',
                'string',
                'max:15',
                $uniqueRfc,
            ],
            'email' => ['nullable', 'email', 'max:255'],
            'codePhone' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'phone' => ['nullable', 'string', 'regex:/^[0-9\s\-\+\(\)]*$/', 'max:15'],
            'address' => ['nullable', 'string', 'max:255'],
            'observation' => ['nullable', 'string', 'max:225'],
        ];

        if ($requiresAccountant) {
            $rules['principalAccountantId'] = ['required', 'integer', 'exists:users,id'];
        }

        return $rules;
    }

    private function activeCustomerRule(): array
    {
        return [
            'required',
            'integer',
            Rule::exists('customers', 'id')->whereNull('deleted_at'),
        ];
    }

    private function validationMessages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'rfc.required' => 'El RFC es obligatorio.',
            'rfc.unique' => 'Este RFC ya está registrado, incluso si el cliente fue eliminado anteriormente.',
            'email.email' => 'Ingresa un correo electrónico válido.',
            'phone.regex' => 'Ingresa un teléfono válido.',
            'principalAccountantId.required' => 'Selecciona el contador principal.',
        ];
    }

    private function customerPayload(array $data): array
    {
        return [
            'name' => $data['name'],
            'last_name' => $this->nullIfEmpty($data['lastName'] ?? null),
            'maternal_last_name' => $this->nullIfEmpty($data['maternalLastName'] ?? null),
            'rfc' => $data['rfc'],
            'email' => $this->nullIfEmpty($data['email'] ?? null),
            'codePhone' => filled($data['codePhone'] ?? null) ? (int) $data['codePhone'] : null,
            'phone' => $this->nullIfEmpty($data['phone'] ?? null),
            'address' => $this->nullIfEmpty($data['address'] ?? null),
            'observation' => $this->nullIfEmpty($data['observation'] ?? null),
        ];
    }

    private function availableAccountants()
    {
        return User::query()
            ->whereHas('role', fn ($query) => $query->whereIn('role', ['Coordinador', 'Contador']))
            ->orderBy('name')
            ->orderBy('last_name')
            ->get(['id', 'name', 'last_name', 'email']);
    }

    private function normalizeFields(): void
    {
        $this->name = Str::squish($this->name);
        $this->lastName = Str::squish($this->lastName);
        $this->maternalLastName = Str::squish($this->maternalLastName);
        $this->rfc = Str::upper(preg_replace('/\s+/', '', $this->rfc) ?? '');
        $this->email = Str::lower(trim($this->email));
        $this->codePhone = ltrim(trim($this->codePhone), '+');
        $this->phone = trim($this->phone);
        $this->address = Str::squish($this->address);
        $this->observation = trim($this->observation);
    }

    private function resetManagementState(): void
    {
        $this->selectedCustomerId = null;
        $this->principalAccountantId = null;
        $this->notice = null;
        $this->resetCustomerFields();
        $this->resetValidation();
    }

    private function resetCustomerFields(): void
    {
        $this->name = '';
        $this->lastName = '';
        $this->maternalLastName = '';
        $this->rfc = '';
        $this->email = '';
        $this->codePhone = '52';
        $this->phone = '';
        $this->address = '';
        $this->observation = '';
    }

    private function nullIfEmpty(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function ensureCanManageCustomers(): void
    {
        $user = auth()->user();

        abort_unless(
            $user?->isAdmin()
                && app(PermissionAccessService::class)->allows($user, 'administration.organization.manage'),
            403,
        );
    }
}
