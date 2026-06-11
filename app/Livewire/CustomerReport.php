<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Customer;

class CustomerReport extends Dashboard
{
    public $months  = null;
    public $customer = null;

    public function mount($customerId = null)
    {
        parent::mount($customerId);
        $this->customer = Customer::find($customerId);
        $this->calculatePercentageAnnual();
    }

    public function calculatePercentageAnnual()
    {
        $uniqueServiceIds = $this->customer->services->pluck("service_id")->unique();
        foreach ($this->months as $month)
        {
            $selectedMonthLoop = $month['number'];
            foreach($uniqueServiceIds as $serviceId)
                {
                    $key = "{$this->customer->id}-{$serviceId}-{$month["number"]}";
                    $subServicesIds = $this->customer->services
                        ->where('service_id', $serviceId)
                        ->pluck('id');
                    $filesCounts = $this->customer->files()
                        ->whereIn('sub_service_id', $subServicesIds)
                        ->when($this->selectedYear && $month, function ($query) use ($selectedMonthLoop) {
                            $query->whereYear('upload_period', $this->selectedYear)
                            ->whereMonth('upload_period', $selectedMonthLoop);
                        })->count();

                    $totalFilesAvaibles = $subServicesIds->count() * 2;

                    if($subServicesIds->contains(1)) {
                        $totalFilesAvaibles -= 2;
                        $totalFilesAvaibles += $this->customer->states->count() * 2;
                    } elseif ($subServicesIds->contains(6)) {
                        $totalFilesAvaibles -= 2;
                        $totalFilesAvaibles += $this->customer->statements->count() * 2;
                    }
                    $percentage = $filesCounts > 0 ? round(($filesCounts / $totalFilesAvaibles) * 100) : 0;
                    $this->percentages[$key] = $percentage;
                }            
        }
    }
    public function render()
    {
        return view('livewire.customer-report')->layout('layouts.app');
    }
}
