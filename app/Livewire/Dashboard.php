<?php

namespace App\Livewire;

use App\Models\Customer;
use App\Models\CustomerFile;
use App\Models\Service;
use Illuminate\Support\Facades\File;
use Livewire\Component;
use Livewire\WithPagination;

class Dashboard extends Component
{
    use WithPagination;

    public $search = '';

    public $months = null;

    public $years = [2023, 2024, 2025, 2026, 2027, 2028];

    public $selectedMonth = null;

    public $selectedYear = null;

    public $totalFilesMonth = 0;

    public $totalFilesYear = 0;

    public $totalFiles = 0;

    public $customers = [];

    public $customerIds = null;

    public $services = null;

    public $serviceRelation = null;

    public $percentage = 0;

    public $percentages = [];

    public $customerId = null;

    public $filterType = 'incomplete';

    public $totalFilesAvaible = 0;

    public bool $dashboardReady = false;

    public int $visibleCustomerLimit = 12;

    protected $paginationTheme = 'tailwind';

    public function mount($customer = null)
    {
        $this->months = collect(json_decode(File::get(resource_path('/data/months.json')), true));
        $this->selectedMonth = now()->month - 1;
        $this->selectedYear = now()->year;
        $this->loadDashboard();
    }

    public function loadDashboard(): void
    {
        if ($this->dashboardReady) {
            return;
        }

        $this->services = Service::with('subServices')->get()->keyBy('id');
        $this->customerIds = Customer::whereNull('deleted_at')
            ->where('created_by', auth()->id())
            ->pluck('id');

        $this->getCustomers();
        $this->countTotalFilesMonth();
        $this->countTotalFilesYear();
        $this->countTotalFiles();
        $this->calculatePercentage();
        $this->dashboardReady = true;
    }

    public function getCustomers()
    {
        $user = auth()->user();
        $role = $user->role?->role;
        $dashboardRelations = [
            'services:id,service_id',
            'states:id',
            'statements:id',
        ];

        // Un rol nuevo puede no tener todavía una regla de clientes asociada.
        // En ese caso el dashboard debe mostrarse vacío, no fallar al iniciar sesión.
        $this->customers = collect();

        if ($user->isAdmin() || $role === 'Coordinador') {
            $this->customers = Customer::query()
                ->with($dashboardRelations)
                ->whereNull('deleted_at')
                ->get();
        } elseif ($role === 'Contador') {
            $this->customers = Customer::whereHas('accountants', function ($q) use ($user) {
                $q->where('accountant_id', $user->id);
            })
                ->whereNull('deleted_at')
                ->with(array_merge($dashboardRelations, [
                    'accountants' => function ($q) {
                        $q->wherePivot('status', 1);
                    },
                ]))
                ->get();
        } elseif ($user->role?->usesPermissionProfile(\App\Models\Role::PROFILE_AUXILIARY) || $role === 'Auxiliar') {
            $this->customers = Customer::whereHas('interns', function ($q) use ($user) {
                $q->where('intern_id', $user->id);
            })
                ->whereNull('deleted_at')
                ->with(array_merge($dashboardRelations, ['interns']))
                ->get();
        }
    }

    public function calculatePercentage()
    {
        $customers = collect($this->customers);
        $this->percentages = [];

        if ($customers->isEmpty()) {
            return;
        }

        $customerIds = $customers->pluck('id')->all();
        $periodFilesByCustomer = CustomerFile::query()
            ->select([
                'id',
                'customer_id',
                'sub_service_id',
                'file_type',
                'declaration_type',
                'statement_id',
                'state_id',
            ])
            ->whereIn('customer_id', $customerIds)
            ->when($this->selectedMonth || $this->selectedYear, function ($query) {
                $query->whereYear('upload_period', $this->selectedYear)
                    ->whereMonth('upload_period', $this->selectedMonth);
            })
            ->get()
            ->groupBy('customer_id');

        // Las comprobaciones de comprobantes normales históricamente no están
        // limitadas al periodo seleccionado. Se cargan una sola vez y se
        // conservan las mismas reglas sin hacer una consulta por cada archivo.
        $normalFilesByCustomer = CustomerFile::query()
            ->select(['customer_id', 'sub_service_id', 'statement_id', 'state_id'])
            ->whereIn('customer_id', $customerIds)
            ->where('declaration_type', 1)
            ->where('file_type', 0)
            ->get()
            ->groupBy('customer_id');

        foreach ($customers as $customer) {
            $customerFiles = $periodFilesByCustomer->get($customer->id, collect());
            $normalFiles = $normalFilesByCustomer->get($customer->id, collect());
            $normalStateIds = $normalFiles->pluck('state_id')->filter(fn ($id) => $id !== null)->map(fn ($id) => (int) $id)->flip();
            $normalStatementIds = $normalFiles->pluck('statement_id')->filter(fn ($id) => $id !== null)->map(fn ($id) => (int) $id)->flip();
            $normalSubServiceIds = $normalFiles->pluck('sub_service_id')->filter(fn ($id) => $id !== null)->map(fn ($id) => (int) $id)->flip();
            $complementaryFiles = $customerFiles
                ->where('declaration_type', 0)
                ->where('file_type', 1);

            // Servicio 1 y 2 cliente 1
            $uniqueServiceIds = $customer->services->pluck('service_id')->unique();

            foreach ($uniqueServiceIds as $serviceId) {
                $key = "{$customer->id}-{$serviceId}";
                $subServicesIds = $customer->services
                    ->where('service_id', $serviceId)
                    ->pluck('id');
                $filesCount = $customerFiles->whereIn('sub_service_id', $subServicesIds)->count();

                $totalFilesAvaible = $subServicesIds->count() * 2;

                if ($subServicesIds->contains(1)) {
                    $totalFilesAvaible -= 2;
                    $totalFilesAvaible += $customer->states->count() * 2;

                    $complementariaAcuseStates = $complementaryFiles->whereNotNull('state_id');

                    foreach ($complementariaAcuseStates as $file) {
                        $hasNormalComprobanteState = $normalStateIds->has((int) $file->state_id);

                        if ($hasNormalComprobanteState) {
                            $totalFilesAvaible--;
                            $filesCount--;
                        } else {
                            $totalFilesAvaible--;
                        }
                    }
                }

                if ($subServicesIds->contains(6)) {
                    $totalFilesAvaible -= 2;
                    $totalFilesAvaible += $customer->statements->count() * 2;
                    $complementariaAcuseStatements = $complementaryFiles->whereNotNull('statement_id');

                    foreach ($complementariaAcuseStatements as $file) {
                        $hasNormalComprobanteStatement = $normalStatementIds->has((int) $file->statement_id);

                        if ($hasNormalComprobanteStatement) {
                            $totalFilesAvaible--;
                            $filesCount--;
                        } else {
                            $totalFilesAvaible--;
                        }
                    }
                }

                $otherServices = $customer->services->whereNotIn('id', [1, 6]);
                if ($otherServices->isNotEmpty()) {
                    $complementariaAcuseSub = $complementaryFiles
                        ->whereIn('sub_service_id', $subServicesIds)
                        ->whereIn('sub_service_id', $otherServices->pluck('id'))
                        ->whereNotNull('sub_service_id');

                    foreach ($complementariaAcuseSub as $file) {
                        $hasNormalComprobanteSub = $normalSubServiceIds->has((int) $file->sub_service_id);

                        if ($hasNormalComprobanteSub) {
                            $totalFilesAvaible--;
                            $filesCount--;
                        } else {
                            $totalFilesAvaible--;
                        }
                    }
                }

                $complementaryAcuse = $complementaryFiles
                    ->whereIn('sub_service_id', $subServicesIds)
                    ->count();

                if ($complementaryAcuse > 0) {
                    $totalFilesAvaible += $complementaryAcuse * 2;
                }
                $percentage = $filesCount > 0 ? round(($filesCount / $totalFilesAvaible) * 100) : 0;

                $this->percentages[$key] = $percentage;
            }
        }
    }

    public function customersPorcentageIncomplete()
    {
        $this->filterType = 'incomplete';
        $this->visibleCustomerLimit = 12;
    }

    public function customersPorcentageComplete()
    {
        $this->filterType = 'complete';
        $this->visibleCustomerLimit = 12;
    }

    public function getFilteredCustomersProperty()
    {
        $search = mb_strtolower(trim((string) $this->search));
        $customers = collect($this->customers)
            ->when($search !== '', function ($customers) use ($search) {
                return $customers->filter(function ($customer) use ($search): bool {
                    $searchable = mb_strtolower(implode(' ', array_filter([
                        $customer->name,
                        $customer->last_name,
                        $customer->maternal_last_name,
                        $customer->rfc,
                    ])));

                    return str_contains($searchable, $search);
                });
            });

        if (empty($this->percentages)) {
            return $customers;
        }

        return $customers->filter(function ($customer) {
            $customerPercentages = collect($this->percentages)
                ->filter(function ($percentage, $key) use ($customer) {
                    return str_starts_with($key, "{$customer->id}-");
                });

            if ($customerPercentages->isEmpty()) {
                return false;
            }

            $average = $customerPercentages->avg();

            if ($this->filterType === 'complete') {
                return $average == 100;
            }

            return $average < 100;
        });
    }

    public function countTotalFilesMonth()
    {
        $this->totalFilesMonth = CustomerFile::whereIn('customer_id', $this->customerIds)->when($this->selectedMonth && $this->selectedYear, function ($query) {
            $query->whereYear('upload_period', $this->selectedYear)
                ->whereMonth('upload_period', $this->selectedMonth);
        })->count();
    }

    public function countTotalFilesYear()
    {
        $this->totalFilesYear = CustomerFile::whereIn('customer_id', $this->customerIds)->when($this->selectedYear, function ($query) {
            $query->whereYear('upload_period', $this->selectedYear);
        })->count();
    }

    public function countTotalFiles()
    {
        $this->totalFiles = CustomerFile::whereIn('customer_id', $this->customerIds)->count();
    }

    public function updatedSelectedMonth()
    {
        $this->calculatePercentage();
        $this->countTotalFilesMonth();
    }

    public function updatedSelectedYear()
    {
        $this->calculatePercentage();
        $this->countTotalFilesYear();
        $this->countTotalFilesMonth();
    }

    public function updatedSearch()
    {
        $this->visibleCustomerLimit = 12;
    }

    public function showMoreCustomers(): void
    {
        $this->visibleCustomerLimit += 12;
    }

    public function annualReport($customerId)
    {
        redirect()->to('/dashboard/'.$customerId.'/report', $this->selectedYear);
    }

    public function redirectToCreateCustomer()
    {
        redirect()->to('/customers/create');
    }

    public function render()
    {
        return view('livewire.dashboard', [
            // Conserva el contrato usado por vistas y pruebas existentes sin
            // volver a ejecutar la antigua consulta duplicada del dashboard.
            'customersPaginate' => collect($this->customers),
        ]);
    }
}
