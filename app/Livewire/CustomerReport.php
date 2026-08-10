<?php

namespace App\Livewire;

use App\Models\Customer;
use App\Models\CustomerFile;
use App\Models\Service;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;

class CustomerReport extends Dashboard
{
    public $months = null;

    public $customer = null;

    public $notFound = false;

    public function mount($customerId = null)
    {
        // El reporte solo necesita un cliente. Evitar parent::mount() impide
        // cargar y calcular el dashboard completo antes de abrir este detalle.
        $this->months = collect(json_decode(File::get(resource_path('/data/months.json')), true));
        $this->selectedMonth = now()->month - 1;
        $this->selectedYear = now()->year;
        $this->customer = Customer::query()
            ->with(['services:id,service_id', 'states:id', 'statements:id'])
            ->find($customerId);

        if (! $this->customer) {
            $this->notFound = true;

            return;
        }

        $serviceIds = $this->customer->services->pluck('service_id')->unique();
        $this->services = Service::query()
            ->whereKey($serviceIds)
            ->get()
            ->keyBy('id');

        $this->calculatePercentageAnnual();
    }

    public function calculatePercentageAnnual(): void
    {
        $customerId = (int) $this->customer->id;
        $uniqueServiceIds = $this->customer->services->pluck('service_id')->unique();

        $annualFilesByMonth = CustomerFile::query()
            ->select(['id', 'sub_service_id', 'file_type', 'declaration_type', 'statement_id', 'state_id', 'upload_period'])
            ->where('customer_id', $customerId)
            ->whereYear('upload_period', $this->selectedYear)
            ->get()
            ->groupBy(fn (CustomerFile $file): int => Carbon::parse($file->upload_period)->month);

        // Se conserva la regla histórica: los acuses complementarios se
        // comparan contra el mes seleccionado, no contra cada fila anual.
        $complementaryFiles = CustomerFile::query()
            ->select(['id', 'sub_service_id', 'statement_id', 'state_id'])
            ->where('customer_id', $customerId)
            ->where('declaration_type', 0)
            ->where('file_type', 1)
            ->when($this->selectedMonth && $this->selectedYear, function ($query): void {
                $query->whereYear('upload_period', $this->selectedYear)
                    ->whereMonth('upload_period', $this->selectedMonth);
            })
            ->get();

        $normalFiles = CustomerFile::query()
            ->select(['sub_service_id', 'statement_id', 'state_id'])
            ->where('customer_id', $customerId)
            ->where('declaration_type', 1)
            ->where('file_type', 0)
            ->get();

        $normalStateIds = $normalFiles->pluck('state_id')->filter()->map(fn ($id) => (int) $id)->flip();
        $normalStatementIds = $normalFiles->pluck('statement_id')->filter()->map(fn ($id) => (int) $id)->flip();
        $normalSubServiceIds = $normalFiles->pluck('sub_service_id')->filter()->map(fn ($id) => (int) $id)->flip();
        $otherServiceIds = $this->customer->services->whereNotIn('id', [1, 6])->pluck('id');

        foreach ($this->months as $month) {
            $monthNumber = (int) $month['number'];
            $monthFiles = $annualFilesByMonth->get($monthNumber, collect());

            foreach ($uniqueServiceIds as $serviceId) {
                $key = "{$customerId}-{$serviceId}-{$monthNumber}";
                $subServiceIds = $this->customer->services
                    ->where('service_id', $serviceId)
                    ->pluck('id');
                $filesCount = $monthFiles->whereIn('sub_service_id', $subServiceIds)->count();
                $totalFilesAvailable = $subServiceIds->count() * 2;

                if ($subServiceIds->contains(1)) {
                    $totalFilesAvailable -= 2;
                    $totalFilesAvailable += $this->customer->states->count() * 2;

                    foreach ($complementaryFiles->whereNotNull('state_id') as $file) {
                        $totalFilesAvailable--;
                        if ($normalStateIds->has((int) $file->state_id)) {
                            $filesCount--;
                        }
                    }
                }

                if ($subServiceIds->contains(6)) {
                    $totalFilesAvailable -= 2;
                    $totalFilesAvailable += $this->customer->statements->count() * 2;

                    foreach ($complementaryFiles->whereNotNull('statement_id') as $file) {
                        $totalFilesAvailable--;
                        if ($normalStatementIds->has((int) $file->statement_id)) {
                            $filesCount--;
                        }
                    }
                }

                if ($otherServiceIds->isNotEmpty()) {
                    $complementarySubServices = $complementaryFiles
                        ->whereIn('sub_service_id', $subServiceIds)
                        ->whereIn('sub_service_id', $otherServiceIds)
                        ->whereNotNull('sub_service_id');

                    foreach ($complementarySubServices as $file) {
                        $totalFilesAvailable--;
                        if ($normalSubServiceIds->has((int) $file->sub_service_id)) {
                            $filesCount--;
                        }
                    }
                }

                $complementaryCount = $complementaryFiles
                    ->whereIn('sub_service_id', $subServiceIds)
                    ->count();

                if ($complementaryCount > 0) {
                    $totalFilesAvailable += $complementaryCount * 2;
                }

                $this->percentages[$key] = $filesCount > 0
                    ? round(($filesCount / $totalFilesAvailable) * 100)
                    : 0;
            }
        }
    }

    public function render()
    {
        return view('livewire.customer-report')->layout('layouts.app');
    }
}
