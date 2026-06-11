<?php

namespace App\Livewire;

use App\Models\Customer;
use App\Models\CustomerFile;
use App\Models\Service;
use Livewire\Component;
use Illuminate\Support\Facades\File;

class Dashboard extends Component
{
    public $search = '';
    public $months = null;
    public $years = [2025, 2026, 2027, 2028];
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
    public $totalFilesAvaible = 0;


    public function mount($customer = null)
    {
        $this->months = collect(json_decode(File::get(resource_path('/data/months.json')), true));
        $this->selectedMonth = now()->month - 1;
        $this->selectedYear = now()->year;

        $this->services = Service::with('subServices')->get()->keyBy('id');
        $this->customerIds = Customer::where('created_by', auth()->id())->pluck('id');
        $this->customers = Customer::with(['files', 'accountants'])->where('created_by', auth()->id())->get();

        $this->countTotalFilesMonth();
        $this->countTotalFilesYear();
        $this->countTotalFiles();
        $this->calculatePercentage();
    }

    public function calculatePercentage()
    {
        foreach ($this->customers as $customer) {
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
                } elseif ($subServicesIds->contains(6)) {
                    $totalFilesAvaible -= 2;
                    $totalFilesAvaible += $customer->statements->count() * 2;
                }
                $percentage = $filesCount > 0 ? round(($filesCount / $totalFilesAvaible) * 100) : 0;

                $this->percentages[$key] = $percentage;
            }
        }
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
        $this->customers = Customer::where('created_by', auth()->id())->when($this->search, function ($query) {
            $query->where('name', 'like', "%{$this->search}%")
            ->orWhere('last_name', 'like', "%{$this->search}%")
            ->orWhere('rfc', 'like', "%{$this->search}%");
        })->get();
    }

    public function annualReport($customerId)
    {
        redirect()->to('/dashboard/' . $customerId . '/report', $this->selectedYear);
    }

    public function render()
    {
        return view('livewire.dashboard');
    }
}