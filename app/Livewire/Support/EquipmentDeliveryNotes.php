<?php

namespace App\Livewire\Support;

use App\Models\Customer;
use App\Models\EquipmentCatalogItem;
use App\Models\EquipmentDeliveryReport;
use App\Services\Support\EquipmentAutofillService;
use App\Services\Support\EquipmentDeliveryImageService;
use App\Services\Support\EquipmentDeliveryReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Throwable;

class EquipmentDeliveryNotes extends Component
{
    use WithFileUploads;
    use WithPagination;

    public const DELIVERERS = [
        'Julián Emiliano Ortiz Rivero',
        'Abigail Ku Vera',
    ];

    public string $activeView = 'create';

    public string $movementType = 'recepcion';

    public ?int $customerId = null;

    public string $customerName = '';

    public string $customerContact = '';

    public string $deliveredBy = self::DELIVERERS[0];

    /** @var array<int, array{id: int, name: string, rfc: string}> */
    public array $customerSuggestions = [];

    public bool $showClientDropdown = false;

    /** @var array<int, string> */
    public array $contactSuggestions = [];

    public bool $showContactDropdown = false;

    public string $equipmentType = '';

    public string $brand = '';

    public string $model = '';

    public string $serialNumber = '';

    public bool $withoutSerial = false;

    public string $physicalCondition = 'bueno';

    public string $observations = '';

    public string $accessories = '';

    public $photo = null;

    public string $autofillInput = '';

    public string $autofillMessage = '';

    public string $autofillWarning = '';

    public string $autofillRecognizedText = '';

    /** @var array<int, array{id: int, source: string, title: string, serial: string, type: string, folio: string}> */
    public array $equipmentSuggestions = [];

    public bool $showEquipmentDropdown = false;

    /** @var array<int, string> */
    public array $brandSuggestions = [];

    public bool $showBrandDropdown = false;

    /** @var array<int, string> */
    public array $modelSuggestions = [];

    public bool $showModelDropdown = false;

    /** @var array<int, string> */
    public array $serialSuggestions = [];

    public bool $showSerialDropdown = false;

    /** @var array<int, string> */
    public array $accessorySuggestions = [];

    public bool $showAccessoryDropdown = false;

    public string $requestToken = '';

    public string $search = '';

    public string $movementFilter = '';

    public ?int $selectedReportId = null;

    public string $successMessage = '';

    public function mount(): void
    {
        Gate::authorize('manage-delivery-notes');
        $this->requestToken = (string) Str::uuid();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedMovementFilter(): void
    {
        $this->resetPage();
    }

    public function updatedCustomerName(string $value): void
    {
        $value = trim($value);

        if ($this->customerId) {
            $selectedName = Customer::query()
                ->whereKey($this->customerId)
                ->whereNull('deleted_at')
                ->first()
                ?->getAttribute('name');

            if (! $selectedName || $this->clean($selectedName) !== $this->clean($value)) {
                $this->customerId = null;
                $this->contactSuggestions = [];
                $this->showContactDropdown = false;
            }
        }

        $this->loadCustomerSuggestions($value);
        $this->showClientDropdown = true;
    }

    public function openClientSuggestions(): void
    {
        $this->loadCustomerSuggestions();
        $this->showClientDropdown = true;
    }

    private function loadCustomerSuggestions(string $value = ''): void
    {
        $value = trim($value);
        $this->customerSuggestions = Customer::query()
            ->select(['id', 'name', 'last_name', 'maternal_last_name', 'rfc'])
            ->whereNull('deleted_at')
            ->when($value !== '', function ($query) use ($value): void {
                $query->where(function ($query) use ($value): void {
                    $query->where('name', 'like', '%'.$value.'%')
                        ->orWhere('last_name', 'like', '%'.$value.'%')
                        ->orWhere('maternal_last_name', 'like', '%'.$value.'%')
                        ->orWhere('rfc', 'like', '%'.$value.'%');
                });
            })
            ->orderBy('name')
            ->limit(8)
            ->get()
            ->map(fn (Customer $customer): array => [
                'id' => $customer->id,
                'name' => $this->customerDisplayName($customer),
                'rfc' => (string) $customer->rfc,
            ])
            ->all();
    }

    public function selectCustomer(int $customerId): void
    {
        Gate::authorize('manage-delivery-notes');
        $customer = Customer::query()->whereNull('deleted_at')->findOrFail($customerId);

        $this->customerId = $customer->id;
        $this->customerName = $this->customerDisplayName($customer);
        $this->customerSuggestions = [];
        $this->showClientDropdown = false;
        $this->customerContact = '';
        $this->loadContactSuggestions($customer);
        $this->showContactDropdown = $this->contactSuggestions !== [];
        $this->resetValidation(['customerId', 'customerName', 'customerContact']);
    }

    public function updatedCustomerContact(string $value): void
    {
        if (! $this->customerId) {
            $this->contactSuggestions = [];
            $this->showContactDropdown = false;

            return;
        }

        $customer = Customer::query()->whereNull('deleted_at')->find($this->customerId);
        if (! $customer) {
            $this->customerId = null;
            $this->contactSuggestions = [];
            $this->showContactDropdown = false;

            return;
        }

        $this->loadContactSuggestions($customer, $value);
        $this->showContactDropdown = true;
    }

    public function openContactSuggestions(): void
    {
        if (! $this->customerId) {
            return;
        }

        $customer = Customer::query()->whereNull('deleted_at')->find($this->customerId);
        if ($customer) {
            $this->loadContactSuggestions($customer);
            $this->showContactDropdown = true;
        }
    }

    public function selectContactSuggestion(int $index): void
    {
        if (! $this->customerId) {
            return;
        }

        $customer = Customer::query()->whereNull('deleted_at')->find($this->customerId);
        $contact = $this->contactSuggestions[$index] ?? null;
        if (! $customer || ! in_array($contact, $this->customerContacts($customer), true)) {
            return;
        }

        $this->customerContact = $contact;
        $this->showContactDropdown = false;
        $this->resetValidation('customerContact');
    }

    public function updatedAutofillInput(string $value): void
    {
        $this->loadEquipmentSuggestions($value);
        $this->showEquipmentDropdown = true;
    }

    public function openEquipmentSuggestions(): void
    {
        $this->loadEquipmentSuggestions();
        $this->showEquipmentDropdown = true;
    }

    private function loadEquipmentSuggestions(string $value = ''): void
    {
        $value = trim($value);
        $catalog = EquipmentCatalogItem::query()
            ->when($value !== '', function ($query) use ($value): void {
                $query->where(function ($query) use ($value): void {
                    $query->where('brand', 'like', '%'.$value.'%')
                        ->orWhere('model', 'like', '%'.$value.'%')
                        ->orWhere('equipment_type', 'like', '%'.$value.'%')
                        ->orWhere('keywords', 'like', '%'.$value.'%');
                });
            })
            ->orderBy('brand')
            ->orderBy('model')
            ->limit(8)
            ->get()
            ->map(fn (EquipmentCatalogItem $item): array => [
                'id' => $item->id,
                'source' => 'catalog',
                'title' => trim($item->brand.' '.$item->model),
                'serial' => '',
                'type' => $item->equipment_type,
                'folio' => 'Catálogo local',
            ]);

        $history = EquipmentDeliveryReport::query()
            ->select(['id', 'folio', 'equipment_type', 'brand', 'model', 'serial_number'])
            ->when($value !== '', function ($query) use ($value): void {
                $query->where(function ($query) use ($value): void {
                    $query->where('brand', 'like', '%'.$value.'%')
                        ->orWhere('model', 'like', '%'.$value.'%')
                        ->orWhere('serial_number', 'like', '%'.$value.'%')
                        ->orWhere('equipment_type', 'like', '%'.$value.'%');
                });
            })
            ->latest('id')
            ->limit(16)
            ->get()
            ->unique(fn (EquipmentDeliveryReport $report): string => mb_strtolower($report->brand.'|'.$report->model.'|'.$report->serial_number))
            ->take(6)
            ->map(fn (EquipmentDeliveryReport $report): array => [
                'id' => $report->id,
                'source' => 'history',
                'title' => trim($report->brand.' '.$report->model),
                'serial' => $report->serial_number,
                'type' => $report->equipment_type,
                'folio' => $report->folio,
            ]);

        $this->equipmentSuggestions = $history
            ->concat($catalog)
            ->unique(fn (array $item): string => mb_strtolower($item['type'].'|'.$item['title'].'|'.$item['serial']))
            ->take(10)
            ->values()
            ->all();
    }

    public function selectEquipmentSuggestion(string $source, int $itemId): void
    {
        Gate::authorize('manage-delivery-notes');

        if ($source === 'catalog') {
            $item = EquipmentCatalogItem::query()->findOrFail($itemId);
            $this->equipmentType = $item->equipment_type;
            $this->brand = $item->brand;
            $this->model = $item->model;
            $this->autofillInput = trim($item->brand.' '.$item->model);
            $message = 'Se cargaron marca, modelo y tipo desde el catálogo local.';
        } else {
            $report = EquipmentDeliveryReport::query()->findOrFail($itemId);
            $this->equipmentType = $report->equipment_type;
            $this->brand = $report->brand;
            $this->model = $report->model;
            $this->serialNumber = $report->serial_number === 'SIN SERIE' ? '' : $report->serial_number;
            $this->withoutSerial = $report->serial_number === 'SIN SERIE';
            $this->accessories = $report->accessories;
            $this->autofillInput = trim($report->brand.' '.$report->model.' '.$report->serial_number);
            $message = 'Se cargaron los datos conocidos de la hoja '.$report->folio.'.';
        }

        $this->equipmentSuggestions = [];
        $this->showEquipmentDropdown = false;
        $this->autofillWarning = '';
        $this->autofillMessage = $message.' Revisa la información antes de guardar.';
        $this->refreshEquipmentFieldSuggestions();
        $this->resetValidation(['equipmentType', 'brand', 'model', 'serialNumber', 'accessories']);
    }

    public function updatedBrand(string $value): void
    {
        $this->brandSuggestions = $this->equipmentFieldSuggestions('brand', $value);
        $this->showBrandDropdown = true;
        $this->modelSuggestions = $this->equipmentFieldSuggestions('model', $this->model);
    }

    public function updatedEquipmentType(): void
    {
        $this->brandSuggestions = $this->equipmentFieldSuggestions('brand');
        $this->modelSuggestions = $this->equipmentFieldSuggestions('model');
    }

    public function updatedModel(string $value): void
    {
        $this->modelSuggestions = $this->equipmentFieldSuggestions('model', $value);
        $this->showModelDropdown = true;
    }

    public function updatedSerialNumber(string $value): void
    {
        $this->serialSuggestions = $this->equipmentFieldSuggestions('serial', $value);
        $this->showSerialDropdown = true;
    }

    public function updatedAccessories(string $value): void
    {
        $this->accessorySuggestions = $this->equipmentFieldSuggestions('accessories', $value);
        $this->showAccessoryDropdown = true;
    }

    public function openEquipmentFieldSuggestions(string $field): void
    {
        $map = [
            'brand' => ['brand', 'brandSuggestions', 'showBrandDropdown'],
            'model' => ['model', 'modelSuggestions', 'showModelDropdown'],
            'serial' => ['serialNumber', 'serialSuggestions', 'showSerialDropdown'],
            'accessories' => ['accessories', 'accessorySuggestions', 'showAccessoryDropdown'],
        ];

        if (! isset($map[$field])) {
            return;
        }

        [$property, $suggestions, $dropdown] = $map[$field];
        $this->{$suggestions} = $this->equipmentFieldSuggestions($field);
        $this->{$dropdown} = true;
    }

    public function selectEquipmentFieldSuggestion(string $field, int $index): void
    {
        $map = [
            'brand' => ['brand', 'brandSuggestions', 'showBrandDropdown'],
            'model' => ['model', 'modelSuggestions', 'showModelDropdown'],
            'serial' => ['serialNumber', 'serialSuggestions', 'showSerialDropdown'],
            'accessories' => ['accessories', 'accessorySuggestions', 'showAccessoryDropdown'],
        ];

        if (! isset($map[$field])) {
            return;
        }

        [$property, $suggestions, $dropdown] = $map[$field];
        $value = $this->{$suggestions}[$index] ?? null;
        if (! is_string($value)) {
            return;
        }

        $this->{$property} = $value;
        $this->{$dropdown} = false;

        if ($field === 'brand') {
            $this->modelSuggestions = $this->equipmentFieldSuggestions('model', $this->model);
        }

        if ($field === 'serial') {
            $this->withoutSerial = false;
        }
    }

    public function updatedWithoutSerial(bool $withoutSerial): void
    {
        if ($withoutSerial) {
            $this->serialNumber = '';
            $this->resetValidation('serialNumber');
        }
    }

    public function updatedPhoto(): void
    {
        $this->autofillMessage = '';
        $this->autofillWarning = '';
        $this->autofillRecognizedText = '';

        if (! $this->photo) {
            return;
        }

        $this->validateOnly('photo', $this->rules(), [], $this->validationAttributes());
        $this->runPhotoAutofill(app(EquipmentAutofillService::class));
    }

    public function autofillFromPhoto(EquipmentAutofillService $autofill): void
    {
        if (! $this->photo) {
            $this->autofillWarning = 'Primero toma o selecciona una fotografía de la etiqueta.';

            return;
        }

        $this->validateOnly('photo', $this->rules(), [], $this->validationAttributes());
        $this->runPhotoAutofill($autofill);
    }

    public function autofillFromText(EquipmentAutofillService $autofill): void
    {
        $validated = $this->validate([
            'autofillInput' => ['required', 'string', 'max:1000'],
        ], [], [
            'autofillInput' => 'texto de la etiqueta',
        ]);

        $this->autofillMessage = '';
        $this->autofillWarning = '';
        $this->autofillRecognizedText = '';

        try {
            $this->applyAutofill($autofill->fromText($validated['autofillInput']), 'el texto escrito');
        } catch (Throwable $exception) {
            Log::warning('No fue posible autocompletar la hoja desde texto.', [
                'user_id' => auth()->id(),
                'exception' => $exception,
            ]);
            $this->autofillWarning = 'No fue posible interpretar esos datos. Puedes completar los campos manualmente.';
        }
    }

    public function showCreate(): void
    {
        $this->activeView = 'create';
        $this->selectedReportId = null;
        $this->successMessage = '';
        $this->resetValidation();
    }

    public function showHistory(): void
    {
        $this->activeView = 'history';
        $this->selectedReportId = null;
        $this->successMessage = '';
        $this->resetValidation();
    }

    public function openReport(int $reportId): void
    {
        Gate::authorize('manage-delivery-notes');
        $this->selectedReportId = EquipmentDeliveryReport::query()->findOrFail($reportId)->id;
        $this->activeView = 'detail';
        $this->resetValidation();
    }

    public function save(
        EquipmentDeliveryReportService $reportService,
        EquipmentDeliveryImageService $imageService,
    ): void {
        Gate::authorize('manage-delivery-notes');
        $validated = $this->validate($this->rules(), [], $this->validationAttributes());
        $photoPath = null;

        try {
            if ($this->photo) {
                $photoPath = $imageService->store($this->photo);
            }

            $report = $reportService->create([
                'movement_type' => $validated['movementType'],
                'customer_id' => $validated['customerId'] ?? null,
                'customer_name' => $this->clean($validated['customerName']),
                'customer_contact' => $this->clean($validated['customerContact'] ?? ''),
                'delivered_by' => $validated['deliveredBy'],
                'equipment_type' => $this->clean($validated['equipmentType']),
                'brand' => $this->clean($validated['brand'] ?? ''),
                'model' => $this->clean($validated['model']),
                'serial_number' => $this->withoutSerial ? 'SIN SERIE' : $this->clean($validated['serialNumber']),
                'accessories' => $this->clean($validated['accessories'] ?? ''),
                'physical_condition' => $validated['physicalCondition'],
                'observations' => $this->clean($validated['observations'] ?? ''),
                'photo_path' => $photoPath,
            ], $this->requestToken, auth()->id());
        } catch (Throwable $exception) {
            $imageService->delete($photoPath);
            Log::error('No fue posible crear la hoja de entrega.', [
                'user_id' => auth()->id(),
                'exception' => $exception,
            ]);
            $this->addError('form', 'No fue posible guardar la hoja. Intenta nuevamente.');

            return;
        }

        $this->resetForm();
        $this->selectedReportId = $report->id;
        $this->activeView = 'detail';
        $this->successMessage = 'La hoja '.$report->folio.' se creó correctamente.';
    }

    public function render(): View
    {
        Gate::authorize('manage-delivery-notes');
        $query = EquipmentDeliveryReport::query()->with('creator:id,name,last_name');
        $search = trim($this->search);

        if ($search !== '') {
            $query->where(function ($query) use ($search): void {
                $query->where('folio', 'like', '%'.$search.'%')
                    ->orWhere('customer_name', 'like', '%'.$search.'%')
                    ->orWhere('customer_contact', 'like', '%'.$search.'%')
                    ->orWhere('equipment_type', 'like', '%'.$search.'%')
                    ->orWhere('brand', 'like', '%'.$search.'%')
                    ->orWhere('model', 'like', '%'.$search.'%')
                    ->orWhere('serial_number', 'like', '%'.$search.'%')
                    ->orWhere('observations', 'like', '%'.$search.'%');
            });
        }

        if (array_key_exists($this->movementFilter, EquipmentDeliveryReport::MOVEMENTS)) {
            $query->where('movement_type', $this->movementFilter);
        }

        return view('livewire.support.equipment-delivery-notes', [
            'reports' => $query->latest()->paginate(25),
            'recentReports' => EquipmentDeliveryReport::query()->latest()->limit(5)->get(),
            'selectedReport' => $this->selectedReportId
                ? EquipmentDeliveryReport::query()->with('creator:id,name,last_name')->find($this->selectedReportId)
                : null,
            'movements' => EquipmentDeliveryReport::MOVEMENTS,
            'conditions' => EquipmentDeliveryReport::CONDITIONS,
        ])->layout('layouts.app');
    }

    /** @return array<string, mixed> */
    private function rules(): array
    {
        return [
            'movementType' => ['required', Rule::in(array_keys(EquipmentDeliveryReport::MOVEMENTS))],
            'customerId' => ['nullable', 'integer', 'exists:customers,id'],
            'customerName' => ['required', 'string', 'max:120'],
            'customerContact' => ['nullable', 'string', 'max:120'],
            'deliveredBy' => ['required', 'string', Rule::in(self::DELIVERERS)],
            'equipmentType' => ['required', 'string', 'max:80'],
            'brand' => ['nullable', 'string', 'max:100'],
            'model' => ['required', 'string', 'max:100'],
            'serialNumber' => [Rule::requiredIf(! $this->withoutSerial), 'nullable', 'string', 'max:100'],
            'withoutSerial' => ['boolean'],
            'physicalCondition' => ['required', Rule::in(array_keys(EquipmentDeliveryReport::CONDITIONS))],
            'observations' => [Rule::requiredIf($this->physicalCondition !== 'bueno'), 'nullable', 'string', 'max:600'],
            'accessories' => ['nullable', 'string', 'max:300'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240', 'dimensions:max_width=10000,max_height=10000'],
        ];
    }

    /** @return array<string, string> */
    private function validationAttributes(): array
    {
        return [
            'movementType' => 'tipo de movimiento',
            'customerId' => 'cliente seleccionado',
            'customerName' => 'cliente',
            'customerContact' => 'contacto',
            'deliveredBy' => 'quién entrega',
            'equipmentType' => 'tipo de equipo',
            'brand' => 'marca',
            'model' => 'modelo',
            'serialNumber' => 'número de serie',
            'physicalCondition' => 'estado físico',
            'observations' => 'observaciones',
            'accessories' => 'accesorios',
            'photo' => 'fotografía',
        ];
    }

    private function resetForm(): void
    {
        $this->reset([
            'customerId',
            'customerName',
            'customerContact',
            'customerSuggestions',
            'showClientDropdown',
            'contactSuggestions',
            'showContactDropdown',
            'equipmentType',
            'brand',
            'model',
            'serialNumber',
            'withoutSerial',
            'observations',
            'accessories',
            'photo',
            'autofillInput',
            'autofillMessage',
            'autofillWarning',
            'autofillRecognizedText',
            'equipmentSuggestions',
            'showEquipmentDropdown',
            'brandSuggestions',
            'showBrandDropdown',
            'modelSuggestions',
            'showModelDropdown',
            'serialSuggestions',
            'showSerialDropdown',
            'accessorySuggestions',
            'showAccessoryDropdown',
        ]);
        $this->movementType = 'recepcion';
        $this->deliveredBy = self::DELIVERERS[0];
        $this->physicalCondition = 'bueno';
        $this->requestToken = (string) Str::uuid();
        $this->resetValidation();
    }

    private function clean(?string $value): string
    {
        return preg_replace('/\s+/u', ' ', str_replace("\0", '', trim((string) $value))) ?? '';
    }

    private function runPhotoAutofill(EquipmentAutofillService $autofill): void
    {
        try {
            $this->applyAutofill($autofill->fromImage($this->photo->getRealPath()), 'la fotografía');
        } catch (Throwable $exception) {
            Log::notice('No fue posible autocompletar la hoja desde la fotografía.', [
                'user_id' => auth()->id(),
                'exception' => $exception,
            ]);
            $this->autofillWarning = $exception->getMessage();
        }
    }

    /** @param array{fields?: array<string, string>, recognized_text?: string, catalog_used?: bool} $result */
    private function applyAutofill(array $result, string $source): void
    {
        $fields = $result['fields'] ?? [];
        $propertyMap = [
            'brand' => 'brand',
            'model' => 'model',
            'serial_number' => 'serialNumber',
            'equipment_type' => 'equipmentType',
        ];
        $applied = [];

        foreach ($propertyMap as $field => $property) {
            $value = $this->clean($fields[$field] ?? '');
            if ($value === '') {
                continue;
            }

            $this->{$property} = $value;
            $applied[] = $field;
        }

        $accessories = $this->clean($fields['accessories'] ?? '');
        if ($accessories !== '') {
            $current = $this->clean($this->accessories);
            $this->accessories = $current === '' ? $accessories : $current.', '.$accessories;
            $applied[] = 'accessories';
        }

        if (in_array('serial_number', $applied, true)) {
            $this->withoutSerial = false;
        }

        $this->autofillRecognizedText = mb_substr($this->clean($result['recognized_text'] ?? ''), 0, 500);

        if ($applied === []) {
            $this->autofillWarning = 'Se leyó '.$source.', pero no se identificaron datos suficientes. Revisa el texto detectado o captura los campos manualmente.';

            return;
        }

        $count = count(array_unique($applied));
        $catalog = ($result['catalog_used'] ?? false) ? ' También se encontró información en el catálogo gratuito.' : '';
        $this->autofillMessage = "Se completaron {$count} campos desde {$source}. Revisa los datos antes de guardar.".$catalog;
        $this->equipmentSuggestions = [];
        $this->showEquipmentDropdown = false;
        $this->resetValidation(['equipmentType', 'brand', 'model', 'serialNumber', 'accessories']);
    }

    private function customerDisplayName(Customer $customer): string
    {
        return mb_substr(trim(implode(' ', array_filter([
            $customer->name,
            $customer->last_name,
            $customer->maternal_last_name,
        ]))), 0, 120);
    }

    /** @return array<int, string> */
    private function customerContacts(Customer $customer): array
    {
        $phone = trim(implode(' ', array_filter([
            $customer->codePhone ? '+'.ltrim((string) $customer->codePhone, '+') : null,
            $customer->phone,
        ])));

        return array_values(array_unique(array_filter([
            trim((string) $customer->email),
            $phone,
        ])));
    }

    private function loadContactSuggestions(Customer $customer, string $search = ''): void
    {
        $search = Str::lower(trim($search));
        $this->contactSuggestions = collect($this->customerContacts($customer))
            ->filter(fn (string $contact): bool => $search === '' || str_contains(Str::lower($contact), $search))
            ->take(8)
            ->values()
            ->all();
    }

    private function refreshEquipmentFieldSuggestions(): void
    {
        $this->brandSuggestions = $this->equipmentFieldSuggestions('brand', $this->brand);
        $this->modelSuggestions = $this->equipmentFieldSuggestions('model', $this->model);
        $this->serialSuggestions = $this->equipmentFieldSuggestions('serial', $this->serialNumber);
        $this->accessorySuggestions = $this->equipmentFieldSuggestions('accessories', $this->accessories);
    }

    /** @return array<int, string> */
    private function equipmentFieldSuggestions(string $field, string $search = ''): array
    {
        $search = trim($search);
        $values = collect();

        if (in_array($field, ['brand', 'model'], true)) {
            $catalogColumn = $field;
            $catalog = EquipmentCatalogItem::query()
                ->when($this->equipmentType !== '', fn ($query) => $query->where('equipment_type', $this->equipmentType))
                ->when($field === 'model' && $this->brand !== '', fn ($query) => $query->where('brand', $this->brand))
                ->when($search !== '', fn ($query) => $query->where($catalogColumn, 'like', '%'.$search.'%'))
                ->orderBy($catalogColumn)
                ->distinct()
                ->limit(12)
                ->pluck($catalogColumn);

            $historyColumn = $field;
            $history = EquipmentDeliveryReport::query()
                ->when($this->equipmentType !== '', fn ($query) => $query->where('equipment_type', $this->equipmentType))
                ->when($field === 'model' && $this->brand !== '', fn ($query) => $query->where('brand', $this->brand))
                ->when($search !== '', fn ($query) => $query->where($historyColumn, 'like', '%'.$search.'%'))
                ->where($historyColumn, '!=', '')
                ->latest('id')
                ->limit(12)
                ->pluck($historyColumn);

            $values = $history->concat($catalog);
        } elseif ($field === 'serial') {
            $values = EquipmentDeliveryReport::query()
                ->when($search !== '', fn ($query) => $query->where('serial_number', 'like', '%'.$search.'%'))
                ->whereNotIn('serial_number', ['', 'SIN SERIE'])
                ->latest('id')
                ->limit(12)
                ->pluck('serial_number');
        } elseif ($field === 'accessories') {
            $common = collect([
                'Cargador',
                'Cargador USB-C',
                'Adaptador de corriente',
                'Cable de corriente',
                'Cable USB',
                'Cable HDMI',
                'Cable de red',
                'Mouse',
                'Teclado',
                'Funda',
                'Base',
                'Batería',
                'Stylus',
                'Sin accesorios',
            ]);
            $history = EquipmentDeliveryReport::query()
                ->where('accessories', '!=', '')
                ->latest('id')
                ->limit(20)
                ->pluck('accessories');
            $values = $history->concat($common)
                ->filter(fn (string $value): bool => $search === '' || str_contains(Str::lower($value), Str::lower($search)));
        }

        return $values
            ->map(fn ($value): string => trim((string) $value))
            ->filter()
            ->unique(fn (string $value): string => Str::lower($value))
            ->take(8)
            ->values()
            ->all();
    }
}
