<?php

namespace App\Livewire;

use App\Models\Customer;
use App\Models\CustomerFile;
use App\Models\Service;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\File;

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
    public $customers = null;
    public $customerIds = null;
    public $services = null;
    public $serviceRelation = null;
    public $percentage = 0;
    public $percentages = [];
    public $customerId = null;
    public $filterType = 'incomplete';
    public $totalFilesAvaible = 0;
    protected $paginationTheme = 'tailwind';

    public function mount($customer = null)
    {
        $this->months = collect(json_decode(File::get(resource_path('/data/months.json')), true));
        $this->selectedMonth = now()->month - 1;
        $this->selectedYear = now()->year;

        $this->services = Service::with('subServices')->get()->keyBy('id');
        $this->customerIds = Customer::where('created_by', auth()->id())->pluck('id');

        $this->getCustomers();
        $this->countTotalFilesMonth();
        $this->countTotalFilesYear();
        $this->countTotalFiles();
        $this->calculatePercentage();
    }

    public function getCustomers()
    {
        $user = auth()->user();
        $role = auth()->user()->role->role;
        if($role === "Administrador" || $role === "Coordinador"){
            $this->customers = Customer::all();
        } elseif($role === 'Contador'){
            $this->customers = Customer::whereHas('accountants', function ($q) use ($user) {
                $q->where('accountant_id', $user->id);
            })
            ->whereNull('deleted_at')
            ->with(['accountants' => function ($q) {
                $q->wherePivot('status', 1);
            }])
            ->get();
        } elseif ($role === 'Auxiliar') {
            {
                $this->customers = Customer::whereHas('interns', function ($q) use ($user) {
                    $q->where('intern_id', $user->id);
                })
                ->whereNull('deleted_at')
                ->with(['interns'])
                ->get();
            }
        }
    }

    public function calculatePercentage()
    {
        foreach ($this->customers as $customer) {
            //Servicio 1 y 2 cliente 1
            $uniqueServiceIds = $customer->services->pluck('service_id')->unique();
        
            foreach ($uniqueServiceIds as $serviceId) {
                $key = "{$customer->id}-{$serviceId}";
                $subServicesIds = $customer->services
                    ->where('service_id', $serviceId)
                    ->pluck('id');
                $filesCount = $customer->files()
                    ->whereIn('sub_service_id', $subServicesIds)
                    ->when($this->selectedMonth || $this->selectedYear, function ($query) {
                        $query->whereYear('upload_period', $this->selectedYear)
                        ->whereMonth('upload_period', $this->selectedMonth);
                    })
                    ->count();

                $totalFilesAvaible = $subServicesIds->count() * 2;

                if($subServicesIds->contains(1)) {
                    $totalFilesAvaible -= 2;
                    $totalFilesAvaible += $customer->states->count() * 2;

                    $complementariaAcuseStates = CustomerFile::where('customer_id', $customer->id)
                    ->where('declaration_type', 0)
                    ->where('file_type', 1)
                    ->whereNotNull('state_id')
                    ->when($this->selectedMonth && $this->selectedYear, function ($query){
                        $query->whereYear('upload_period', $this->selectedYear)
                        ->whereMonth('upload_period', $this->selectedMonth);})
                    ->get();
                
                    foreach ($complementariaAcuseStates as $file) {
                        $hasNormalComprobanteState = CustomerFile::where('customer_id', $customer->id)
                        ->where('declaration_type', 1)
                        ->where('file_type', 0)
                        ->where('state_id', $file->state_id)
                        ->exists();

                        if($hasNormalComprobanteState) {
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
                    $complementariaAcuseStatements = CustomerFile::where('customer_id', $customer->id)
                    ->where('declaration_type', 0)
                    ->where('file_type', 1)
                    ->whereNotNull('statement_id')
                    ->when($this->selectedMonth && $this->selectedYear, function ($query){
                        $query->whereYear('upload_period', $this->selectedYear)
                        ->whereMonth('upload_period', $this->selectedMonth);})
                    ->get();

                    foreach ($complementariaAcuseStatements as $file) {
                        $hasNormalComprobanteStatement = CustomerFile::where('customer_id', $customer->id)
                        ->where('declaration_type', 1)
                        ->where('file_type', 0)
                        ->where('statement_id', $file->statement_id)
                        ->exists();

                        if($hasNormalComprobanteStatement) {
                            $totalFilesAvaible --;
                            $filesCount--;
                        } else {
                            $totalFilesAvaible--;
                        }
                    }
                }

                $otherServices = $customer->services->whereNotIn('id', [1, 6]);
                if ($otherServices->isNotEmpty()) {
                    $complementariaAcuseSub = CustomerFile::where('customer_id', $customer->id)
                        ->where('declaration_type', 0)
                        ->where('file_type', 1)
                        ->whereIn('sub_service_id', $subServicesIds)
                        ->whereIn('sub_service_id', $otherServices->pluck('id'))
                        ->whereNotNull('sub_service_id')
                        ->when($this->selectedMonth && $this->selectedYear, function ($query){
                            $query->whereYear('upload_period', $this->selectedYear)
                            ->whereMonth('upload_period', $this->selectedMonth);})
                        ->get();
                    
                    foreach ($complementariaAcuseSub as $file) {
                        $hasNormalComprobanteSub = CustomerFile::where('customer_id', $customer->id)
                        ->where('declaration_type', 1)
                        ->where('file_type', 0)
                        ->where('sub_service_id', $file->sub_service_id)
                        ->exists();

                        if ($hasNormalComprobanteSub) {
                            $totalFilesAvaible--;
                            $filesCount--;
                        } else {
                            $totalFilesAvaible--;
                        }
                    }
                }



                $complementaryAcuse = CustomerFile::where('customer_id',  $customer->id)
                    ->where('declaration_type', 0)
                    ->where('file_type', 1)
                    ->whereIn('sub_service_id', $subServicesIds)
                    ->when($this->selectedMonth && $this->selectedYear, function ($query){
                        $query->whereYear('upload_period', $this->selectedYear)
                        ->whereMonth('upload_period', $this->selectedMonth);})
                    ->count();
                
                if($complementaryAcuse > 0) {
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
    }

    public function customersPorcentageComplete()
    {
        $this->filterType = 'complete';
    }

    public function getFilteredCustomersProperty()
    {
        if (empty($this->percentages)) {
            return $this->customers;
        }

        return $this->customers->filter(function ($customer) {
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
        $this->totalFilesMonth = CustomerFile::whereIn('customer_id', $this->customerIds)->when($this->selectedMonth && $this->selectedYear, function ($query){
                $query->whereYear('upload_period', $this->selectedYear)
            ->whereMonth('upload_period', $this->selectedMonth);
        })->count();
    }

    public function countTotalFilesYear()
    {
        $this->totalFilesYear = CustomerFile::whereIn('customer_id', $this->customerIds)->when($this->selectedYear, function ($query){
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
        $this->customers = Customer::when($this->search, function ($query) {
            $query->where('name', 'like', "%{$this->search}%")
            ->orWhere('rfc', 'like', "%{$this->search}%")
            ->orWhere('last_name', 'like', "%{$this->search}%");
        })
        ->orderByRaw("CASE
            WHEN name LIKE '{$this->search}%' THEN 1
            WHEN rfc LIKE '{$this->search}%' THEN 2
            ELSE 3
            END")
        ->get();
    }

    public function annualReport($customerId)
    {
        redirect()->to('/dashboard/' . $customerId . '/report', $this->selectedYear);
    }

    public function redirectToCreateCustomer()
    {
        redirect()->to('/customers/create');
    }

    public function render()
    {
        $user = auth()->user();
        $role = auth()->user()->role->role;
        $customersPaginate = collect();

        // if($role === "Administrador"){
        //     $customersPaginate = Customer::with(['services', 'accountants'])
        //         ->paginate(9);
        // } elseif($role === "Coordinador"){
        //     $customersPaginate = Customer::with(['services', 'accountants'])
        //         ->where('created_by', $user->id)
        //         ->whereNull('deleted_at')
        //         ->paginate(9);
        // } elseif($role === 'Contador'){
        //     $customersPaginate = Customer::with(['services', 'accountants'])
        //         ->whereHas('accountants', function ($q) use  ($user) {
        //             $q->where('accountant_id', $user->id);
        //         })
        //         ->whereNull('deleted_at')
        //         ->paginate(9);
        // } elseif($role === 'Auxiliar') {
        //     $customersPaginate = Customer::with(['services', 'accountants'])
        //         ->whereHas('interns', function ($q) use  ($user) {
        //             $q->where('intern_id', $user->id);
        //         })
        //         ->whereNull('deleted_at')
        //         ->paginate(9);
        // }
        $createdCustomers = Customer::whereNull('deleted_at');
        $assignedCustomers = Customer::whereHas('accountants', function ($q) use ($user) {
            $q->where('accountant_id', $user->id);
        });


        $customersPaginate = $createdCustomers
            ->union($assignedCustomers)
            ->whereNull('deleted_at')
            ->with([ 'accountants' => function ($q) {
                $q->wherePivot('status', 1);
            }])
            ->paginate(9); 
        return view('livewire.dashboard', [
            'customersPaginate' => $customersPaginate
        ]);
    }
}