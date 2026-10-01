<?php

namespace App\Livewire\Support;

use App\Models\Customer;
use App\Models\EquipmentCatalogIndex;
use App\Models\EquipmentCatalogItem;
use App\Models\ServiceOrder;
use App\Services\Support\EquipmentAutofillService;
use App\Services\Support\EquipmentCatalogService;
use App\Services\Support\EquipmentDeliveryImageService;
use App\Services\Support\ServiceOrderService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
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
        'David Santiago Cen Pool',
    ];

    public const ACCESSORY_OPTIONS = [
        'Cargador',
        'Funda',
        'Cable USB',
        'Adaptador',
        'Mouse',
        'Teclado',
        'Memoria USB',
        'Cable HDMI',
        'Webcam',
        'Audífonos',
    ];

    public const WORK_OPTIONS = [
        'Solo revisión',
        'Instalación de Microsoft 365',
        'Instalación de CONTPAQi',
        'Instalación de iFacture',
        'Soporte iFacture',
        'Revisión de correo',
        'Instalación de base de datos',
        'Exportación de catálogo y productos',
        'Respaldo de datos',
        'Clonación',
    ];

    public string $activeView = 'create';

    public string $tab = 'nueva';

    public string $movementType = 'recepcion';

    public string $loanAction = 'prestamo';

    public ?int $selectedOrderId = null;

    public string $orderSearch = '';

    /** @var array<int, array{id: int, folio: string, customer: string, equipment: string, serial: string, status: string}> */
    public array $orderSuggestions = [];

    public bool $showOrderDropdown = false;

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

    public string $reportedFailure = '';

    public string $diagnosis = '';

    public string $workPerformed = '';

    public string $promisedDeliveryDate = '';

    public string $loanDueDate = '';

    public string $purchasePrice = '';

    public string $paymentMethod = '';

    public string $warranty = '';

    public bool $receptionSigned = false;

    public bool $deliverySigned = false;

    public string $accessories = '';

    /** @var array<int, string> */
    public array $selectedAccessories = [];

    /** @var array<int, string> */
    public array $selectedWorkItems = [];

    public $photo = null;

    public string $autofillInput = '';

    public string $autofillMessage = '';

    public string $autofillWarning = '';

    public string $autofillRecognizedText = '';

    /** @var array<string, string> */
    public array $ocrSuggestions = [];

    /** @var array<int, string> */
    public array $autofilledFields = [];

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

    public string $statusFilter = '';

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

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedMovementType(): void
    {
        $this->selectedOrderId = null;
        $this->orderSearch = '';
        $this->orderSuggestions = [];
        $this->showOrderDropdown = false;
        $this->loanAction = 'prestamo';
        $this->resetValidation();
    }

    public function selectDeliverer(string $name): void
    {
        if (in_array($name, self::DELIVERERS, true)) {
            $this->deliveredBy = $name;
            $this->resetValidation('deliveredBy');
        }
    }

    public function updatedLoanAction(): void
    {
        $this->selectedOrderId = null;
        $this->orderSearch = '';
        $this->orderSuggestions = [];
        $this->showOrderDropdown = false;
        $this->resetValidation();
    }

    public function updatedOrderSearch(string $value): void
    {
        $this->loadOrderSuggestions($value);
        $this->showOrderDropdown = true;
    }

    public function openOrderSuggestions(): void
    {
        $this->loadOrderSuggestions();
        $this->showOrderDropdown = true;
    }

    public function selectServiceOrder(int $orderId): void
    {
        $order = $this->eligibleOrders()->findOrFail($orderId);
        $this->selectedOrderId = $order->id;
        $this->orderSearch = $order->folio.' · '.$order->cliente_nombre;
        $this->orderSuggestions = [];
        $this->showOrderDropdown = false;
        $this->customerId = $order->cliente_id;
        $this->customerName = $order->cliente_nombre;
        $this->customerContact = $order->contacto;
        $this->deliveredBy = $order->quien_entrega;
        $this->equipmentType = $order->tipo_equipo;
        $this->brand = $order->equipo_marca;
        $this->model = $order->equipo_modelo;
        $this->serialNumber = $order->equipo_serie === 'SIN SERIE' ? '' : $order->equipo_serie;
        $this->withoutSerial = $order->equipo_serie === 'SIN SERIE';
        $this->accessories = implode(', ', $order->accesorios ?? []);
        $this->selectedAccessories = array_values(array_intersect($order->accesorios ?? [], self::ACCESSORY_OPTIONS));
        $this->physicalCondition = $order->estado_fisico;
        $this->resetValidation();
    }

    private function loadOrderSuggestions(string $search = ''): void
    {
        $search = trim($search);
        $this->orderSuggestions = $this->eligibleOrders()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('folio', 'like', '%'.$search.'%')
                        ->orWhere('cliente_nombre', 'like', '%'.$search.'%')
                        ->orWhere('equipo_serie', 'like', '%'.$search.'%');
                });
            })
            ->latest('id')
            ->limit(8)
            ->get()
            ->map(fn (ServiceOrder $order): array => [
                'id' => $order->id,
                'folio' => $order->folio,
                'customer' => $order->cliente_nombre,
                'equipment' => trim($order->equipo_marca.' '.$order->equipo_modelo),
                'serial' => $order->equipo_serie,
                'status' => $order->statusLabel(),
            ])
            ->all();
    }

    private function eligibleOrders(): Builder
    {
        $query = ServiceOrder::query();

        if ($this->movementType === 'prestamo' && $this->loanAction === 'devolucion') {
            return $query->where('tipo', 'prestamo')->where('estado', 'prestado');
        }

        return $query
            ->where('tipo', 'recepcion')
            ->whereNotIn('estado', ['entregado', 'cancelado']);
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
        $indexed = collect(app(EquipmentCatalogService::class)->fuzzySearch($value, 10))
            ->map(fn (array $item): array => [
                'id' => $item['id'],
                'source' => 'index',
                'title' => $item['title'],
                'serial' => $item['serial_number'],
                'type' => $item['equipment_type'],
                'folio' => $item['usage_count'] > 0 ? 'Usado '.$item['usage_count'].' veces' : 'Catálogo',
                'usage_count' => $item['usage_count'],
                'accessories' => $item['typical_accessories'],
            ]);

        $catalog = EquipmentCatalogItem::query()
            ->when($indexed->isNotEmpty(), fn ($query) => $query->whereRaw('1 = 0'))
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
                'usage_count' => 0,
                'accessories' => [],
            ]);

        $this->equipmentSuggestions = $indexed
            ->concat($catalog)
            ->unique(fn (array $item): string => mb_strtolower($item['type'].'|'.$item['title'].'|'.$item['serial']))
            ->take(10)
            ->values()
            ->all();
    }

    public function selectEquipmentSuggestion(string $source, int $itemId): void
    {
        Gate::authorize('manage-delivery-notes');

        if ($source === 'index') {
            $item = EquipmentCatalogIndex::query()->findOrFail($itemId);
            $this->equipmentType = $item->equipment_type;
            $this->brand = $item->brand;
            $this->model = $item->model;
            if ($this->serialNumber === '' && $item->serial_number) {
                $this->serialNumber = $item->serial_number;
                $this->withoutSerial = false;
            }
            if ($this->accessories === '' && $item->typical_accessories) {
                $this->accessories = implode(', ', $item->typical_accessories);
                $this->syncSelectedAccessories($item->typical_accessories);
            }
            $this->autofillInput = trim($item->brand.' '.$item->model);
            $message = 'Se cargaron los datos conocidos del índice local.';
        } elseif ($source === 'catalog') {
            $item = EquipmentCatalogItem::query()->findOrFail($itemId);
            $this->equipmentType = $item->equipment_type;
            $this->brand = $item->brand;
            $this->model = $item->model;
            $this->autofillInput = trim($item->brand.' '.$item->model);
            $message = 'Se cargaron marca, modelo y tipo desde el catálogo local.';
        } else {
            $order = ServiceOrder::query()->findOrFail($itemId);
            $this->equipmentType = $order->tipo_equipo;
            $this->brand = $order->equipo_marca;
            $this->model = $order->equipo_modelo;
            $this->serialNumber = $order->equipo_serie === 'SIN SERIE' ? '' : $order->equipo_serie;
            $this->withoutSerial = $order->equipo_serie === 'SIN SERIE';
            $this->accessories = implode(', ', $order->accesorios ?? []);
            $this->syncSelectedAccessories($order->accesorios ?? []);
            $this->autofillInput = trim($order->equipo_marca.' '.$order->equipo_modelo.' '.$order->equipo_serie);
            $message = 'Se cargaron los datos conocidos de la orden '.$order->folio.'.';
        }

        $this->equipmentSuggestions = [];
        $this->showEquipmentDropdown = false;
        $this->autofillWarning = '';
        $this->autofillMessage = $message.' Revisa la información antes de guardar.';
        $this->refreshEquipmentFieldSuggestions();
        $this->resetValidation(['equipmentType', 'brand', 'model', 'serialNumber', 'accessories']);
    }

    public function registerNewEquipment(EquipmentCatalogService $catalog): void
    {
        Gate::authorize('manage-delivery-notes');
        $brand = $this->clean($this->brand);
        $model = $this->clean($this->model);

        if ($brand === '' || $model === '') {
            $parts = preg_split('/\s+/u', $this->clean($this->autofillInput), 2) ?: [];
            $brand = $brand ?: ($parts[0] ?? '');
            $model = $model ?: ($parts[1] ?? '');
        }
        if ($brand === '' || $model === '') {
            $this->autofillWarning = 'Escribe al menos marca y modelo para registrar un equipo nuevo.';

            return;
        }

        $item = $catalog->register(
            $brand,
            $model,
            $this->equipmentType ?: 'Otro',
            $this->serialNumber ?: null,
            array_values(array_filter(array_map('trim', preg_split('/[,;\n]+/u', $this->accessories) ?: []))),
            [$this->autofillInput],
        );
        $this->selectEquipmentSuggestion('index', $item->id);
        $this->autofillMessage = 'El equipo quedó registrado en el índice local para búsquedas futuras.';
    }

    public function updatedBrand(string $value): void
    {
        $this->forgetAutofilledField('brand');
        $this->brandSuggestions = $this->equipmentFieldSuggestions('brand', $value);
        $this->showBrandDropdown = true;
        $this->modelSuggestions = $this->equipmentFieldSuggestions('model', $this->model);
    }

    public function updatedEquipmentType(): void
    {
        $this->forgetAutofilledField('equipmentType');
        $this->brandSuggestions = $this->equipmentFieldSuggestions('brand');
        $this->modelSuggestions = $this->equipmentFieldSuggestions('model');
    }

    public function updatedModel(string $value): void
    {
        $this->forgetAutofilledField('model');
        $this->modelSuggestions = $this->equipmentFieldSuggestions('model', $value);
        $this->showModelDropdown = true;
    }

    public function updatedSerialNumber(string $value): void
    {
        $this->forgetAutofilledField('serialNumber');
        $this->serialSuggestions = $this->equipmentFieldSuggestions('serial', $value);
        $this->showSerialDropdown = true;
    }

    public function updatedAccessories(string $value): void
    {
        $this->forgetAutofilledField('accessories');
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
        $this->ocrSuggestions = [];
        $this->autofilledFields = [];

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

    public function retryOcr(string $preprocessing, EquipmentAutofillService $autofill): void
    {
        if (! in_array($preprocessing, ['grayscale', 'contrast', 'rotate'], true) || ! $this->photo) {
            $this->autofillWarning = 'Selecciona una fotografía antes de reintentar el OCR.';

            return;
        }

        $this->validateOnly('photo', $this->rules(), [], $this->validationAttributes());
        $this->runPhotoAutofill($autofill, $preprocessing);
    }

    public function applyAllOcrSuggestions(): void
    {
        foreach (array_keys($this->ocrSuggestions) as $field) {
            $this->applyOcrSuggestion($field);
        }
    }

    public function applyOcrSuggestion(string $field): void
    {
        $map = [
            'brand' => 'brand',
            'model' => 'model',
            'serial_number' => 'serialNumber',
            'equipment_type' => 'equipmentType',
            'accessories' => 'accessories',
        ];
        if (! isset($map[$field], $this->ocrSuggestions[$field])) {
            return;
        }

        $property = $map[$field];
        $this->{$property} = $this->clean($this->ocrSuggestions[$field]);
        if ($field === 'serial_number') {
            $this->withoutSerial = false;
        }
        if ($field === 'accessories') {
            $this->syncSelectedAccessories($this->accessories);
        }
        if (! in_array($property, $this->autofilledFields, true)) {
            $this->autofilledFields[] = $property;
        }
        unset($this->ocrSuggestions[$field]);
        $this->autofillMessage = 'Sugerencias aplicadas. Puedes editar cualquier campo antes de guardar.';
        $this->resetValidation($property);
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
        $this->switchTab('nueva');
        $this->successMessage = '';
    }

    public function showHistory(): void
    {
        $this->switchTab('historial');
        $this->successMessage = '';
    }

    public function switchTab(string $tab): void
    {
        if (! in_array($tab, ['nueva', 'historial'], true)) {
            return;
        }

        $this->tab = $tab;
        $this->activeView = $tab === 'nueva' ? 'create' : 'history';
        $this->selectedReportId = null;
        $this->resetPage();
        $this->resetValidation();
    }

    public function openReport(int $reportId): void
    {
        Gate::authorize('manage-delivery-notes');
        $this->selectedReportId = ServiceOrder::query()->findOrFail($reportId)->id;
        $this->tab = 'historial';
        $this->activeView = 'detail';
        $this->resetValidation();
    }

    public function deleteOrder(int $orderId, EquipmentDeliveryImageService $imageService): void
    {
        Gate::authorize('manage-delivery-notes');
        $order = ServiceOrder::query()->with('movimientos')->findOrFail($orderId);
        $paths = collect([$order->foto_path])
            ->merge($order->movimientos->flatMap(fn ($movement): array => $movement->evidencia ?? []))
            ->filter(fn ($path): bool => is_string($path) && $path !== '')
            ->unique();

        $folio = $order->folio;
        $order->delete();
        $paths->each(fn (string $path) => $imageService->delete($path));

        $this->tab = 'historial';
        $this->activeView = 'history';
        $this->selectedReportId = null;
        $this->successMessage = "La orden {$folio} se eliminó correctamente.";
        $this->resetPage();
    }

    public function save(
        ServiceOrderService $orderService,
        EquipmentDeliveryImageService $imageService,
    ): void {
        Gate::authorize('manage-delivery-notes');
        $validated = $this->validate($this->rules(), [], $this->validationAttributes());
        $photoPath = null;

        try {
            if ($this->photo) {
                $photoPath = $imageService->store($this->photo);
            }

            $accessories = $validated['selectedAccessories'] ?: array_values(array_filter(array_map(
                fn (string $item): string => $this->clean($item),
                preg_split('/[,;\n]+/u', $validated['accessories'] ?? '') ?: [],
            )));
            $workSummary = implode('; ', $validated['selectedWorkItems'] ?? []);
            $attributes = [
                'tipo' => $validated['movementType'],
                'cliente_id' => $validated['customerId'] ?? null,
                'contacto_id' => null,
                'cliente_nombre' => $this->clean($validated['customerName']),
                'contacto' => $this->clean($validated['customerContact'] ?? ''),
                'quien_entrega' => $validated['deliveredBy'],
                'tipo_equipo' => $this->clean($validated['equipmentType']),
                'equipo_marca' => $this->clean($validated['brand'] ?? ''),
                'equipo_modelo' => $this->clean($validated['model']),
                'equipo_serie' => $this->withoutSerial ? 'SIN SERIE' : $this->clean($validated['serialNumber']),
                'accesorios' => $accessories,
                'estado_fisico' => $validated['physicalCondition'],
                'falla_reportada' => $this->clean($validated['reportedFailure'] ?: ($this->movementType === 'recepcion' ? $workSummary : '')),
                'diagnostico' => $this->clean($validated['diagnosis'] ?: ($this->movementType === 'entrega' ? 'Servicios seleccionados por soporte' : '')),
                'reparacion_realizada' => $this->clean($validated['workPerformed'] ?: $workSummary),
                'fecha_entrega_prometida' => null,
                'fecha_limite_devolucion' => $validated['loanDueDate'] ?: null,
                'firma_recepcion' => (bool) $validated['receptionSigned'],
                'firma_entrega' => (bool) $validated['deliverySigned'],
                'observaciones' => $this->clean($validated['observations'] ?: $workSummary),
                'precio' => $validated['purchasePrice'] ?: null,
                'forma_pago' => $this->clean($validated['paymentMethod'] ?? ''),
                'garantia' => $this->clean($validated['warranty'] ?? ''),
                'foto_path' => $photoPath,
                'ocr_raw_text' => $this->autofillRecognizedText ?: null,
            ];

            if ($this->movementType === 'entrega' && $this->selectedOrderId) {
                $order = $orderService->deliver(
                    $this->eligibleOrders()->findOrFail($this->selectedOrderId),
                    $attributes,
                    auth()->id(),
                );
            } elseif ($this->movementType === 'prestamo' && $this->loanAction === 'devolucion') {
                $order = $orderService->returnLoan(
                    $this->eligibleOrders()->findOrFail((int) $this->selectedOrderId),
                    $attributes,
                    auth()->id(),
                );
            } else {
                $order = $orderService->create($attributes, $this->requestToken, auth()->id());
            }
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
        $this->selectedReportId = $order->id;
        $this->tab = 'historial';
        $this->activeView = 'detail';
        $this->successMessage = 'La orden '.$order->folio.' se actualizó correctamente.';
    }

    public function isFormValid(): bool
    {
        return $this->formIssues() === [];
    }

    /** @return array<int, array{field: string, label: string, step: int}> */
    public function formIssues(): array
    {
        $rules = $this->rules();
        $data = [];
        foreach (array_keys($rules) as $field) {
            $property = Str::before($field, '.');
            $data[$property] = $this->{$property};
        }

        $errors = Validator::make($data, $rules, [], $this->validationAttributes())->errors();
        $steps = [
            'movementType' => 1, 'loanAction' => 1, 'selectedOrderId' => 1,
            'customerId' => 2, 'customerName' => 2, 'customerContact' => 2, 'deliveredBy' => 2,
            'equipmentType' => 4, 'brand' => 4, 'model' => 4, 'serialNumber' => 4, 'withoutSerial' => 4, 'accessories' => 4, 'selectedAccessories' => 4,
            'physicalCondition' => 5, 'observations' => 5, 'reportedFailure' => 5, 'diagnosis' => 5,
            'workPerformed' => 5, 'selectedWorkItems' => 5, 'loanDueDate' => 5,
            'purchasePrice' => 5, 'paymentMethod' => 5, 'warranty' => 5,
        ];
        $labels = $this->validationAttributes();

        return collect($errors->keys())
            ->map(fn (string $field): array => [
                'field' => $field,
                'label' => ucfirst($labels[$field] ?? $field),
                'step' => $steps[$field] ?? 5,
            ])
            ->values()
            ->all();
    }

    public function render(): View
    {
        Gate::authorize('manage-delivery-notes');
        $query = ServiceOrder::query()->with(['creator:id,name,last_name', 'movimientos']);
        $search = trim($this->search);

        if ($search !== '') {
            $query->where(function ($query) use ($search): void {
                $query->where('folio', 'like', '%'.$search.'%')
                    ->orWhere('cliente_nombre', 'like', '%'.$search.'%')
                    ->orWhere('contacto', 'like', '%'.$search.'%')
                    ->orWhere('tipo_equipo', 'like', '%'.$search.'%')
                    ->orWhere('equipo_marca', 'like', '%'.$search.'%')
                    ->orWhere('equipo_modelo', 'like', '%'.$search.'%')
                    ->orWhere('equipo_serie', 'like', '%'.$search.'%')
                    ->orWhere('observaciones', 'like', '%'.$search.'%');
            });
        }

        if (array_key_exists($this->movementFilter, ServiceOrder::TYPES)) {
            $query->where('tipo', $this->movementFilter);
        }

        if (array_key_exists($this->statusFilter, ServiceOrder::STATUSES)) {
            $query->where('estado', $this->statusFilter);
        }

        $reports = $this->tab === 'historial' && $this->activeView === 'history'
            ? $query->latest()->paginate(25)
            : new LengthAwarePaginator([], 0, 25, 1, ['path' => request()->url()]);

        return view('livewire.support.equipment-delivery-notes', [
            'reports' => $reports,
            'selectedReport' => $this->selectedReportId
                ? ServiceOrder::query()->with(['creator:id,name,last_name', 'movimientos'])->find($this->selectedReportId)
                : null,
            'movements' => ServiceOrder::TYPES,
            'conditions' => ServiceOrder::CONDITIONS,
            'statuses' => ServiceOrder::STATUSES,
            'formIssues' => $this->tab === 'nueva' ? $this->formIssues() : [],
        ])->layout('layouts.app');
    }

    /** @return array<string, mixed> */
    private function rules(): array
    {
        $selectedWorkRules = ['array'];
        if (in_array($this->movementType, ['recepcion', 'entrega'], true)
            && $this->reportedFailure === ''
            && $this->diagnosis === ''
            && $this->workPerformed === '') {
            $selectedWorkRules[] = 'required';
            $selectedWorkRules[] = 'min:1';
        }

        return [
            'movementType' => ['required', Rule::in(array_keys(ServiceOrder::TYPES))],
            'loanAction' => ['required', Rule::in(['prestamo', 'devolucion'])],
            'selectedOrderId' => [Rule::requiredIf($this->movementType === 'prestamo' && $this->loanAction === 'devolucion'), 'nullable', 'integer', 'exists:ordenes_servicio,id'],
            'customerId' => ['nullable', 'integer', 'exists:customers,id'],
            'customerName' => ['required', 'string', 'max:120'],
            'customerContact' => ['nullable', 'string', 'max:120'],
            'deliveredBy' => ['required', 'string', Rule::in(self::DELIVERERS)],
            'equipmentType' => ['required', 'string', 'max:80'],
            'brand' => ['nullable', 'string', 'max:100'],
            'model' => ['required', 'string', 'max:100'],
            'serialNumber' => [Rule::requiredIf(! $this->withoutSerial), 'nullable', 'string', 'max:100'],
            'withoutSerial' => ['boolean'],
            'physicalCondition' => ['required', Rule::in(array_keys(ServiceOrder::CONDITIONS))],
            'observations' => ['nullable', 'string', 'max:600'],
            'reportedFailure' => ['nullable', 'string', 'max:2000'],
            'diagnosis' => ['nullable', 'string', 'max:2000'],
            'workPerformed' => ['nullable', 'string', 'max:2000'],
            'loanDueDate' => [Rule::requiredIf($this->movementType === 'prestamo' && $this->loanAction === 'prestamo'), 'nullable', 'date', 'after_or_equal:today'],
            'purchasePrice' => [Rule::requiredIf($this->movementType === 'compra'), 'nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'paymentMethod' => [Rule::requiredIf($this->movementType === 'compra'), 'nullable', 'string', 'max:60'],
            'warranty' => ['nullable', 'string', 'max:160'],
            'receptionSigned' => ['boolean'],
            'deliverySigned' => ['boolean'],
            'accessories' => ['nullable', 'string', 'max:300'],
            'selectedAccessories' => ['array'],
            'selectedAccessories.*' => [Rule::in(self::ACCESSORY_OPTIONS)],
            'selectedWorkItems' => $selectedWorkRules,
            'selectedWorkItems.*' => [Rule::in(self::WORK_OPTIONS)],
            'photo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240', 'dimensions:max_width=10000,max_height=10000'],
        ];
    }

    /** @return array<string, string> */
    private function validationAttributes(): array
    {
        return [
            'movementType' => 'tipo de movimiento',
            'selectedOrderId' => 'orden vinculada',
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
            'reportedFailure' => 'falla reportada',
            'diagnosis' => 'diagnóstico',
            'workPerformed' => 'reparación realizada',
            'loanDueDate' => 'fecha límite de devolución',
            'purchasePrice' => 'precio',
            'paymentMethod' => 'forma de pago',
            'warranty' => 'garantía',
            'accessories' => 'accesorios',
            'selectedAccessories' => 'accesorios',
            'selectedWorkItems' => 'trabajo realizado',
            'photo' => 'fotografía',
        ];
    }

    private function resetForm(): void
    {
        $this->reset([
            'selectedOrderId',
            'orderSearch',
            'orderSuggestions',
            'showOrderDropdown',
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
            'reportedFailure',
            'diagnosis',
            'workPerformed',
            'promisedDeliveryDate',
            'loanDueDate',
            'purchasePrice',
            'paymentMethod',
            'warranty',
            'receptionSigned',
            'deliverySigned',
            'accessories',
            'selectedAccessories',
            'selectedWorkItems',
            'photo',
            'autofillInput',
            'autofillMessage',
            'autofillWarning',
            'autofillRecognizedText',
            'ocrSuggestions',
            'autofilledFields',
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
        $this->loanAction = 'prestamo';
        $this->deliveredBy = self::DELIVERERS[0];
        $this->physicalCondition = 'bueno';
        $this->requestToken = (string) Str::uuid();
        $this->resetValidation();
    }

    private function clean(?string $value): string
    {
        return preg_replace('/\s+/u', ' ', str_replace("\0", '', trim((string) $value))) ?? '';
    }

    private function runPhotoAutofill(EquipmentAutofillService $autofill, string $preprocessing = 'default'): void
    {
        try {
            $this->stageOcrSuggestions($autofill->fromImage($this->photo->getRealPath(), $preprocessing));
        } catch (Throwable $exception) {
            Log::notice('No fue posible autocompletar la hoja desde la fotografía.', [
                'user_id' => auth()->id(),
                'exception' => $exception,
            ]);
            $this->autofillWarning = 'No se pudo leer la etiqueta. Intenta con mejor iluminación, otro modo de procesamiento o escribe los datos manualmente.';
        }
    }

    /** @param array{fields?: array<string, string>, recognized_text?: string, catalog_used?: bool} $result */
    private function stageOcrSuggestions(array $result): void
    {
        $this->autofillRecognizedText = mb_substr(trim((string) ($result['recognized_text'] ?? '')), 0, 10000);
        $this->ocrSuggestions = collect($result['fields'] ?? [])
            ->map(fn ($value): string => $this->clean((string) $value))
            ->filter()
            ->all();
        $this->autofilledFields = [];

        if ($this->autofillRecognizedText === '' || $this->ocrSuggestions === []) {
            $this->autofillWarning = 'No se pudo leer la etiqueta. Intenta con mejor iluminación o escribe los datos manualmente.';

            return;
        }

        $this->autofillWarning = '';
        $this->autofillMessage = 'Se detectaron posibles datos. Confirma cada campo o aplica todas las sugerencias.';
        $this->equipmentSuggestions = [];
        $this->showEquipmentDropdown = false;
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
            $this->syncSelectedAccessories($this->accessories);
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

    private function forgetAutofilledField(string $property): void
    {
        $this->autofilledFields = array_values(array_filter(
            $this->autofilledFields,
            fn (string $field): bool => $field !== $property,
        ));
    }

    /** @param array<int, string>|string $accessories */
    private function syncSelectedAccessories(array|string $accessories): void
    {
        $text = mb_strtolower(is_array($accessories) ? implode(' ', $accessories) : $accessories);
        $this->selectedAccessories = collect(self::ACCESSORY_OPTIONS)
            ->filter(fn (string $option): bool => str_contains($text, mb_strtolower($option)))
            ->values()
            ->all();
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

            $historyColumn = $field === 'brand' ? 'equipo_marca' : 'equipo_modelo';
            $history = ServiceOrder::query()
                ->when($this->equipmentType !== '', fn ($query) => $query->where('tipo_equipo', $this->equipmentType))
                ->when($field === 'model' && $this->brand !== '', fn ($query) => $query->where('equipo_marca', $this->brand))
                ->when($search !== '', fn ($query) => $query->where($historyColumn, 'like', '%'.$search.'%'))
                ->where($historyColumn, '!=', '')
                ->latest('id')
                ->limit(12)
                ->pluck($historyColumn);

            $values = $history->concat($catalog);
        } elseif ($field === 'serial') {
            $values = ServiceOrder::query()
                ->when($search !== '', fn ($query) => $query->where('equipo_serie', 'like', '%'.$search.'%'))
                ->whereNotIn('equipo_serie', ['', 'SIN SERIE'])
                ->latest('id')
                ->limit(12)
                ->pluck('equipo_serie');
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
            $history = ServiceOrder::query()
                ->whereNotNull('accesorios')
                ->latest('id')
                ->limit(20)
                ->pluck('accesorios')
                ->flatMap(function ($accessories): array {
                    if (is_string($accessories)) {
                        $accessories = json_decode($accessories, true) ?: [];
                    }

                    return is_array($accessories) ? $accessories : [];
                });
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
