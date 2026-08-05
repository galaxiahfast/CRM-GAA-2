<?php

namespace App\Livewire\Customer;

use App\Models\Customer;
use App\Models\CustomerFile;
use App\Models\Service;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ViewCustomer extends IndexCustomer
{
    use WithFileUploads;
    public $services;
    public $serviceRelation;
    public $customer;
    public $serviceCounts;
    public $selectedServiceId;
    public $subServicesCustomer = null;
    public $selectedSubServices;
    public $selectedSubService = null;
    public $selectedSubServiceId = null;
    public $isComplementaria = false;
    public $pdfAcuseNormalDB = null;
    public $pdfComprobanteNormalDB = null;
    public $pdfAcuseComplementariaDB = null;
    public $pdfComprobanteComplementariaDB = null;
    public $successMessage = null;
    public $typeMessage = 'success';
    public $iconSVG = null;
    public $states = null;
    public $statements = null;
    public $selectedStateId = null;
    public $selectedStatementId = null;
    public $openStates = [];
    public $openStatements = [];
    public $countPDFs = 0;
    public $totalPdfAvailable = 0;
    public $filter_date = null;
    public $months = null;
    public $selectedMonth = null;
    public $selectedYear = null;
    public $years = [
        2023,
        2024,
        2025,
        2026,
        2027,
        2028
    ];
    public $pdfs = [
        'normal' => [
            'acuse' => null,
            'comprobante' => null
        ],
        'complementaria' => [
            'acuse' => null,
            'comprobante' => null
        ]
    ];
    public $pdfsDB = [
        'normal' => [],
        'complementaria' => []
    ];
    public $notFound = false;
    public $percentage = 0;
    public function mount($customer = null)
    {
        $this->customer = Customer::with([
            'accountants' => function ($q) {
                $q->withPivot('status');
            }, 'services', 'files' => function ($q) {
                $q->withPivot('file_path');
            }, 'states', 'statements'])->find(id: $customer);

        $this->months = collect(json_decode(File::get(resource_path('/data/months.json')), true));

        $this->selectedMonth = now()->month -1;
        $this->selectedYear = now()->year;

        if(!$this->customer){
            $this->notFound = true;
            return;
        }
        // Obtiene los id de los servicios de la tabla subservicios

        $this->services = $this->customer->services->pluck('service_id');

        $this->serviceRelation = Service::with('subServices')->whereIn('id', $this->services)->get();

        $this->serviceCounts = collect(value: $this->services)->countBy();

        $this->selectedServiceId = $this->serviceRelation->first()->id ?? null;

        $this->getSubServices($this->selectedServiceId, $this->customer->id);

        $this->states = $this->customer->states;

        $this->statements = $this->customer->statements;

        $this->countPDFs();
    }

    public function countPDFs()
    {
        $this->countPDFs = $this->customer
            ->files()
            ->when($this->selectedMonth && $this->selectedYear, function ($query){
                $query->whereYear('upload_period', $this->selectedYear)
                      ->whereMonth('upload_period', $this->selectedMonth);
        })->count();

        $this->totalPdfAvailable = $this->calculateTotalPdfRequired();

        $this->percentage = $this->countPDFs > 0 ? round(($this->countPDFs / $this->totalPdfAvailable) * 100) : 0;

        $this->percentage = $this->countPDFs > 0 ? round(($this->countPDFs / $this->totalPdfAvailable) * 100) : 0;
    }

    protected function calculateTotalPdfRequired()
    {
        $totalRequired = 0;
        $servicesIds = $this->customer->services->pluck('id');

            // Validar sub_servicio id 1 (states)
            if($servicesIds->contains(1)) {
                $totalRequired += $this->customer->states->count() * 2;

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
                        $totalRequired--;
                        $this->countPDFs--;
                    } else {
                        $totalRequired--;
                    }
                }
            }
            
            // Validar sub_servicio id 6 (statements)
            if ($servicesIds->contains(6)) {
                $totalRequired += $this->customer->statements->count() * 2;
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
                        $totalRequired --;
                        $this->countPDFs--;
                    } else {
                        $totalRequired--;
                    }
                }
            }

        $otherServices = $this->customer->services->whereNotIn('id', [1, 6]);
        if ($otherServices->isNotEmpty()) {
            $subServiceCount = $otherServices->count();
            $totalRequired += $subServiceCount * 2;
            
            $complementariaAcuseSub = CustomerFile::where('customer_id', $this->customer->id)
                ->where('declaration_type', 0)
                ->where('file_type', 1)
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
                    $totalRequired--;
                    $this->countPDFs--;
                } else {
                    $totalRequired--;
                }
            }
        }

        $complementaryAcuse = CustomerFile::where('customer_id',  $this->customer->id)
                ->where('declaration_type', 0)
                ->where('file_type', 1)
                ->when($this->selectedMonth && $this->selectedYear, function ($query){
                    $query->whereYear('upload_period', $this->selectedYear)
                      ->whereMonth('upload_period', $this->selectedMonth);})
                ->count();
        if ($complementaryAcuse > 0) {
            $totalRequired += $complementaryAcuse * 2;
        }
        return $totalRequired;
    }


    // Selecciona un servicio y obtiene sus subservicios
    public function selectButton($serviceId, $customerId)
    {
        $this->selectedServiceId = $serviceId;
        // consulta a la DB para obtener los subservicios asociado
        $this->getSubServices($serviceId, $customerId);
    }

    // Obtiene los subservicios de un servicio específico y consigue los archivos asociados al subservicio
    public function getSubServices($serviceId, $customerId)
    {

        $customer = Customer::with(['services' => function ($q) use ($serviceId) {
            $q->where('service_id', $serviceId); // filtramos sub_services por service_id
        }])->find($customerId);

        $this->subServicesCustomer = $customer->services;

        $this->selectedSubServices = $this->subServicesCustomer->first()->id ?? null;

        $this->selectedSubService = $this->subServicesCustomer->first();

        if($this->selectedSubService) {
            $this->selectedSubServiceId = $this->selectedSubService->id;
        }
        //Obtener los archivos al seleccionar el subservicio
        $this->getFiles($this->selectedSubServiceId);
    }

    public function selectSubServiceButton($subServiceId)
    {
        // cambiar el botón seleccionado
        $this->selectedSubServiceId = $subServiceId;
        // actualizar el id del subservicio seleccionado
        $this->selectedSubServices = $subServiceId;
        // filtrar el subservice por los subservicios de cliente
        $this->selectedSubService = $this->subServicesCustomer->find($subServiceId);

        $this->getFiles($subServiceId);

    }

    //Obtiene los archivos de un subservicio específico en una colección o objeto
    public function getFiles($selectedSubServiceId)
    {
        $this->pdfsDB = [
            'normal' => [
                'acuse' => collect(),
                'comprobante' => collect(),
            ],
            'complementaria' => [
                'acuse' => collect(),
                'comprobante' => collect(),
            ],
        ];


        $pdfsFilterBySubService = $this->customer->files()
            ->withPivot('id')
            ->withPivot('file_path')
            ->withPivot('file_type')
            ->withPivot('original_name')
            ->withPivot('declaration_type')
            ->withPivot('state_id')
            ->withPivot('statement_id')
            ->withPivot('updated_at')
            ->withPivot('upload_period')
            ->where('sub_service_id', $selectedSubServiceId)
            ->whereMonth('upload_period', $this->selectedMonth)
            ->whereYear('upload_period', $this->selectedYear)
            ->get();

        if ($selectedSubServiceId === 1) {
            foreach ($pdfsFilterBySubService->groupBy(groupBy: 'pivot.state_id') as $stateId => $files) {
                $this->assignFilesByKey($stateId, $files);
            }
        } elseif ($selectedSubServiceId === 6) {
            foreach ($pdfsFilterBySubService->groupBy(groupBy: 'pivot.statement_id') as $statementId => $files) {
                $this->assignFilesByKey($statementId, $files);
            }
        } else {
            foreach ($pdfsFilterBySubService->groupBy(groupBy: 'pivot.sub_service_id') as $subServiceId => $files) {
                $this->assignFilesByKey($subServiceId, $files);
            }
        }
    }

    private function assignFilesByKey($key, $files)
    {
        $this->pdfsDB['normal'][$key] = [
            'acuse' => $files->filter(
                fn ($item) => $item->pivot->declaration_type ==  1 && $item->pivot->file_type == 1
            ),
            'comprobante' => $files->filter(
                fn ($item) => $item->pivot->declaration_type == 1 && $item->pivot->file_type == 0
            )
        ];
        $this->pdfsDB['complementaria'][$key] = [
            'acuse' => $files->filter(
                fn ($item) => $item->pivot->declaration_type == 0 && $item->pivot->file_type == 1
            ),
            'comprobante' => $files->filter(
                fn ($item) => $item->pivot->declaration_type == 0 && $item->pivot->file_type == 0
            )
            ];
    }

    public function updatedSelectedYear()
    {
        $this->getFiles($this->selectedSubServiceId);
        $this->countPDFs();

    }

    public function updatedSelectedMonth()
    {
        $this->getFiles($this->selectedSubServiceId);
        $this->countPDFs();
    }

    public function toggle()
    {
        $this->getFiles($this->selectedSubServiceId);

        $acuseComplementaria = $this->pdfsDB['complementaria'][$this->selectedSubServiceId]['acuse'] ?? collect();
        $comprobanteComplementaria = $this->pdfsDB['complementaria'][$this->selectedSubServiceId]['comprobante'] ?? collect();

        if ($acuseComplementaria->isEmpty() && $comprobanteComplementaria->isEmpty()) {
            $this->isComplementaria = !$this->isComplementaria;
        } else {
            $this->isComplementaria = false;
        }
    }

    public function toggleState($stateId)
    {
        $this->getFiles($this->selectedSubServiceId);

        $acuseComplementaria = $this->pdfsDB['complementaria'][$stateId]['acuse'] ?? collect();
        $comprobanteComplementaria = $this->pdfsDB['complementaria'][$stateId]['comprobante'] ?? collect();

        if ($acuseComplementaria->isNotEmpty() || $comprobanteComplementaria->isNotEmpty()) {
            return;        
        }

        if (in_array($stateId, $this->openStates)) {
                // Remover el estado si ya está abierto
                $this->openStates = array_diff($this->openStates, [$stateId]);
        } else {
                // Agregar el estado si está cerrado
                $this->openStates[] = $stateId;
        }
    }

    public function toggleStatement($statementId)
    {
        $this->getFiles($this->selectedSubServiceId);

        $acuseComplementaria = $this->pdfsDB['complementaria'][$statementId]['acuse'] ?? collect();
        $comprobanteComplementaria = $this->pdfsDB['complementaria'][$statementId]['comprobante'] ?? collect();

        if ($acuseComplementaria->isNotEmpty() || $comprobanteComplementaria->isNotEmpty()) {
            return;
        }

        if (in_array($statementId, $this->openStatements)) {
            // Remover el estado si ya está abierto
            $this->openStatements = array_diff($this->openStatements, [$statementId]);
        } else {
            // Agregar el estado si está cerrado
            $this->openStatements[] = $statementId;
        }
    }

    //Función para verificar si un estado de state está abierto
    public function isStateOpen($stateId)
    {
        return in_array($stateId, $this->openStates);
    }

    //Función para verificar si un estado de statement está abierto
    public function isStatementOpen($statementId)
    {
        return in_array($statementId, $this->openStatements);
    }

    //Actualiza los archivos cuando cambia el estado de complementaria
    public function updatedIsComplementariaNoEmpty()
    {
        $this->getFiles($this->selectedSubServiceId);
    }

    //Función para actualizar las variables y poder realizar la subida de archivos
    public function handleUpload($stateId = null, $statementId = null)
    {
        $this->selectedStateId = $stateId;
        $this->selectedStatementId = $statementId;
    }

    //Subir pdf
    public function updatedPdfs($value, $key)
    {
        $keys = explode('.', $key);
        if (count($keys) === 2) {
            [$declarationType, $fileType] = $keys;
            $this->handlePdfUpload($declarationType, $fileType);
        }
    }

    //Función helper 
    private function handlePdfUpload($declarationType, $fileType)
    {
        $validator = Validator::make(
        ["pdfsValidate" => $this->pdfs[$declarationType][$fileType] ?? null],
        ["pdfsValidate" => 'required|file|mimes:pdf|max:8000'],
        ["pdfsValidate.required" => "Debes seleccionar un archivo PDF",
            "pdfsValidate.mimes" => "El archivo debe ser un PDF",
            "pdfsValidate.max" => "El archivo no debe superar los 8MB"]
        );

        if ($validator->fails()) {
            $this->addError(
            "pdfsValidate",
            $validator->errors()->first("pdfsValidate")
            );
            $this->getFiles($this->selectedSubServiceId);
            return;
        }
        // $this->validate([
        //     "pdfsValidate" => 'required|file|mimes:pdf|max:2048',
        // ]);

        $pdfFile = $this->pdfs[$declarationType][$fileType];

        if(!$pdfFile) {
            $this->addError("pdfs.$declarationType.$fileType", 'No se encontró el archivo a subir');
            return;
        }
        $isNormal = $declarationType === 'normal';

        $uploadPeriod = now()->startOfMonth();
        $uploadPeriod = Carbon::create($this->selectedYear, $this->selectedMonth, now()->day);
        
        try {
            $service = Service::find($this->selectedServiceId);
            $taxFolder = $service->service;
            $taxFolderReplace = str_replace(' ', '', $taxFolder);

            $yearFolder = 'pdfs/' . $this->selectedYear;
            $rfcFolder = $yearFolder . '/' . $this->customer->rfc;
            $serviceFolder = $rfcFolder . '/'. $taxFolderReplace;

            if (!Storage::disk('public')->exists($yearFolder)) {
                Storage::disk('public')->makeDirectory($yearFolder);
            }

            if(!Storage::disk('public')->exists($rfcFolder)) {
                Storage::disk('public')->makeDirectory($rfcFolder);
            }

            if(!Storage::disk('public')->exists($serviceFolder)) {
                Storage::disk('public')->makeDirectory($serviceFolder);
            }


            $path = $pdfFile->store($serviceFolder, 'public');
            $originalName = $pdfFile->getClientOriginalName();

            if($declarationType === 'complementaria' && $fileType === 'comprobante'){
                if($this->selectedSubServiceId == 1) {
                    foreach ($this->states as $state) {

                        if ($state->id !== $this->selectedStateId) continue;

                        $isComplementariaAcuse = $this->pdfsDB['complementaria'][$state->id]['acuse'];
                        if($isComplementariaAcuse->isEmpty()) {
                            $this->iconSVG = 'feathericon-alert-circle';
                            $this->typeMessage = 'alert';
                            $this->successMessage = 'Suba un archivo de Acuse Complementario antes';
                            return;
                        }
                    }
                } elseif ($this->selectedSubServiceId == 6) {
                    foreach ($this->statements as $statement) {

                        if ($statement->id !== $this->selectedStatementId) continue;

                        $isComplementariaAcuse = $this->pdfsDB['complementaria'][$statement->id]['acuse'];
                        if($isComplementariaAcuse->isEmpty()) {
                            $this->iconSVG = 'feathericon-alert-circle';
                            $this->typeMessage = 'alert';
                            $this->successMessage = 'Suba un archivo de Acuse Complementario antes';
                            return;
                        }
                    }
                } else {
                    $isComplementariaAcuse = $this->pdfsDB['complementaria'][$this->selectedSubServiceId]['acuse'];
                    if($isComplementariaAcuse->isEmpty()) {
                        $this->iconSVG = 'feathericon-alert-circle';
                        $this->typeMessage = 'alert';
                        $this->successMessage = 'Suba un archivo de Acuse Complementario antes';
                        return;
                    }
                }
            }
            
            $existingFile = CustomerFile::where([
                'customer_id' => $this->customer->id,
                'sub_service_id' => $this->selectedSubServiceId,
                'file_type' => $fileType === 'acuse' ? 1 : 0,
                'declaration_type' => $isNormal ? 1 : 0,
                'statement_id' => $this->selectedStatementId ?? null,
                'state_id' => $this->selectedStateId ?? null,
                'upload_period' => $uploadPeriod
            ])->first();

            if ($existingFile) {
                if ($existingFile->file_path && Storage::disk('public')->exists($existingFile->file_path)) {
                    Storage::disk('public')->delete($existingFile->file_path);
                }
                
                $existingFile->update([
                    'file_path' => $path,
                    'original_name' => $originalName,
                    'user_id' => auth()->id(),
                    'updated_at' => now()
                ]);
                $this->successMessage = 'PDF actualizado exitosamente';
            } else {
                CustomerFile::create([
                    'customer_id' => $this->customer->id,
                    'user_id' => auth()->id(),
                    'sub_service_id' => $this->selectedSubServiceId,
                    'file_path' => $path,
                    'original_name' => $originalName,
                    'file_type' => $fileType === 'acuse' ? 1 : 0,
                    'declaration_type' => $isNormal ? 1 : 0,
                    'statement_id' => $this->selectedStatementId ?? null,
                    'state_id' => $this->selectedStateId ?? null,
                    'upload_period' => $uploadPeriod
                ]);

                if($uploadPeriod->month === $this->selectedMonth && $uploadPeriod->year === $this->selectedYear) {
                    $this->countPDFs();
                    $this->customer->update([
                        'percentage_period'=>$this->percentage
                    ]);
                }
                $this->iconSVG = 'feathericon-arrow-up-circle';
                $this->typeMessage = 'success';
                $this->successMessage = 'PDF cargado exitosamente';
            }
        } catch (\Exception $e) {
            report($e);
            $this->addError("pdfs.$declarationType.$fileType", 'Error al cargar el PDF');
        } finally {
            $pdfFile->delete();
            $this->getFiles($this->selectedSubServiceId);
            $this->selectedStateId = null;
            $this->selectedStatementId = null;
            $this->countPDFs();
        }
    }

    public function updatedSuccessMessage()
    {
        $this->getFiles($this->selectedSubServiceId);
    }

    public function deletePdf($id)
    {
        try {
            $pdf = CustomerFile::find($id);
            if (!$pdf) {
                $this->addError('error', 'PDF no encontrado');
                return;
            }

            if($pdf->declaration_type == 0 && $pdf->file_type == 1){
                if($this->selectedSubServiceId == 1) {
                    foreach ($this->states as $state) {
                        if ($state->id != $pdf->state_id) continue;
                        $isComplementariaComprobante = $this->pdfsDB['complementaria'][$state->id]['comprobante'];
                        if($isComplementariaComprobante->isNotEmpty()) {
                            $this->iconSVG = 'feathericon-alert-circle';
                            $this->typeMessage = 'alert';
                            $this->successMessage = 'Elimina primero el acuse complementario';
                            return;
                        }
                    }
                } elseif ($this->selectedSubServiceId == 6) {

                    foreach ($this->statements as $statement) {
                        if ($statement->id != $pdf->statement_id) continue;
                        $isComplementariaComprobante = $this->pdfsDB['complementaria'][$statement->id]['comprobante'];
                        if($isComplementariaComprobante->isNotEmpty()) {
                            $this->iconSVG = 'feathericon-alert-circle';
                            $this->typeMessage = 'alert';
                            $this->successMessage = 'Elimina primero el acuse complementario';
                            return;
                        }
                    }
                } else {
                    $isComplementariaComprobante = $this->pdfsDB['complementaria'][$this->selectedSubServiceId]['comprobante'];
                    if($isComplementariaComprobante->isNotEmpty()) {
                        $this->iconSVG = 'feathericon-alert-circle';
                        $this->typeMessage = 'alert';
                        $this->successMessage = 'Elimina primero el Comprobante Complementario';
                        return;
                    }
                }
            }

            $rfcFolder = 'pdfs/' . $this->customer->rfc;
            if ($pdf->file_path && Storage::disk('public')->exists($pdf->file_path)) {
                Storage::disk('public')->delete($pdf->file_path);
            }
            $pdf->delete();

            $uploadPeriod = now()->startOfMonth();
            $uploadPeriod = Carbon::create($this->selectedYear, $this->selectedMonth, now()->day);
            
            if($uploadPeriod->month === $this->selectedMonth && $uploadPeriod->year === $this->selectedYear) {
                    $this->countPDFs();
                    $this->customer->update([
                        'percentage_period'=>$this->percentage
                    ]);
                }
            
            $filesLeft = Storage::disk('public')->files($rfcFolder);
            if (empty($filesLeft)) {
                Storage::disk('public')->deleteDirectory($rfcFolder);
            }

            $this->typeMessage = 'error';
            $this->successMessage = 'PDF eliminado con éxito';
        } catch (\Exception $e) {
            report($e);
            session()->flash('error', 'Error al eliminar el PDF');
        } finally {
            $this->iconSVG = 'feathericon-trash';
            $this->getFiles($this->selectedSubServiceId);
            $this->countPDFs();
        }
    }

    public function render(): mixed
    {
        return view('livewire.customer.view-customer')->layout('layouts.app');
    }

}
