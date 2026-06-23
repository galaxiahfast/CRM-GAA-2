<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Customer;
use App\Models\CustomerFile;

class CustomerReport extends Dashboard
{
    public $months  = null;
    public $customer = null;
    public $notFound = false;


    public function mount($customerId = null)
    {
        parent::mount($customerId);
    
        $this->customer = Customer::find($customerId);
        
        if(!$this->customer) {
            $this->notFound = true;
            return;
        }

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

                    $complementariaAcuseStates = CustomerFile::where('customer_id', $this->customer->id)
                    ->where('declaration_type', 0)
                    ->where('file_type', 1)
                    ->whereNotNull('state_id')
                    ->when($this->selectedMonth && $this->selectedYear, function ($query){
                        $query->whereYear('upload_period', $this->selectedYear)
                        ->whereMonth('upload_period', $this->selectedMonth);})
                    ->get();
                
                    foreach ($complementariaAcuseStates as $file) {
                        $hasNormalComprobanteState = CustomerFile::where('customer_id', $this->customer->id)
                        ->where('declaration_type', 1)
                        ->where('file_type', 0)
                        ->where('state_id', $file->state_id)
                        ->exists();

                        if($hasNormalComprobanteState) {
                            $totalFilesAvaibles--;
                            $filesCounts--;
                        } else {
                            $totalFilesAvaibles--;
                        }
                    }
                } 
                
                if ($subServicesIds->contains(6)) {
                    $totalFilesAvaibles -= 2;
                    $totalFilesAvaibles += $this->customer->statements->count() * 2;
                    $complementariaAcuseStatements = CustomerFile::where('customer_id', $this->customer->id)
                    ->where('declaration_type', 0)
                    ->where('file_type', 1)
                    ->whereNotNull('statement_id')
                    ->when($this->selectedMonth && $this->selectedYear, function ($query){
                        $query->whereYear('upload_period', $this->selectedYear)
                        ->whereMonth('upload_period', $this->selectedMonth);})
                    ->get();

                    foreach ($complementariaAcuseStatements as $file) {
                        $hasNormalComprobanteStatement = CustomerFile::where('customer_id', $this->customer->id)
                        ->where('declaration_type', 1)
                        ->where('file_type', 0)
                        ->where('statement_id', $file->statement_id)
                        ->exists();

                        if($hasNormalComprobanteStatement) {
                            $totalFilesAvaibles--;
                            $filesCounts--;
                        } else {
                            $totalFilesAvaibles--;
                        }
                    }
                }

                $otherServices = $this->customer->services->whereNotIn('id', [1, 6]);
                if ($otherServices->isNotEmpty()) {
                    $complementariaAcuseSub = CustomerFile::where('customer_id', $this->customer->id)
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
                        $hasNormalComprobanteSub = CustomerFile::where('customer_id', $this->customer->id)
                        ->where('declaration_type', 1)
                        ->where('file_type', 0)
                        ->where('sub_service_id', $file->sub_service_id)
                        ->exists();

                        if ($hasNormalComprobanteSub) {
                            $totalFilesAvaibles--;
                            $filesCounts--;
                        } else {
                            $totalFilesAvaibles--;
                        }
                    }
                }



                $complementaryAcuse = CustomerFile::where('customer_id',  $this->customer->id)
                    ->where('declaration_type', 0)
                    ->where('file_type', 1)
                    ->whereIn('sub_service_id', $subServicesIds)
                    ->when($this->selectedMonth && $this->selectedYear, function ($query){
                        $query->whereYear('upload_period', $this->selectedYear)
                        ->whereMonth('upload_period', $this->selectedMonth);})
                    ->count();
                
                if($complementaryAcuse > 0) {
                    $totalFilesAvaibles += $complementaryAcuse * 2;
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
