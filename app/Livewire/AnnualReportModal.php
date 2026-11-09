<?php

namespace App\Livewire;

/**
 * Resumen anual de un cliente como ventana emergente del Panel general (antes era una página aparte).
 * Reutiliza el cálculo de CustomerReport, pero se monta dentro del panel y no cambia de pantalla.
 */
class AnnualReportModal extends CustomerReport
{
    public function mount($customerId = null, $year = null)
    {
        $this->loadReport($customerId, (int) ($year ?: now()->year));
    }

    public function render()
    {
        return view('livewire.annual-report-modal');
    }
}
