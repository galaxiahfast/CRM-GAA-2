<?php

namespace App\Livewire\Testing;

use App\Services\ManagerControl\HoursSummary;
use App\Services\ManagerControl\ManagerControlException;
use App\Services\ManagerControl\ManagerControlRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Pruebas: horas laboradas del legacy ManagerControl, en modo ESTRICTAMENTE de solo lectura.
 * Solo Administrador. No escribe nada, ni en el legacy ni en la base del CRM.
 */
class ManagerControlHours extends Component
{
    public string $employeeId = '';

    public string $from;

    public string $to;

    public bool $loaded = false;

    public ?string $error = null;

    // Periodo realmente consultado (el PDF y los encabezados usan este, no el que se está editando).
    public string $appliedFrom = '';

    public string $appliedTo = '';

    // El detalle por día tiene su propio periodo (independiente del de arriba), como en Mi productividad.
    public string $detailFrom;

    public string $detailTo;

    public string $appliedDetailFrom = '';

    public string $appliedDetailTo = '';

    public bool $detailLoaded = false;

    public function mount(): void
    {
        $this->authorizeAccess();
        $this->to = $this->today();
        $this->from = Carbon::parse($this->to)->subDays(14)->toDateString(); // hoy y 14 días previos = 15 días
        $this->appliedFrom = $this->from;
        $this->appliedTo = $this->to;
        $this->detailFrom = $this->appliedDetailFrom = $this->from;
        $this->detailTo = $this->appliedDetailTo = $this->to;
    }

    protected function authorizeAccess(): void
    {
        abort_unless(Gate::allows('view-legacy-managercontrol'), 403);
    }

    protected function ownMode(): bool
    {
        return false;
    }

    protected function matchedName(): ?string
    {
        return null;
    }

    /** Colaborador de ManagerControl a consultar (null si no hay uno válido). */
    protected function employeeForQuery(): ?int
    {
        return ctype_digit($this->employeeId) && (int) $this->employeeId >= 1 ? (int) $this->employeeId : null;
    }

    public function updatedDetailFrom(): void
    {
        $this->detailFrom = Carbon::hasFormat($this->detailFrom, 'Y-m-d') ? $this->detailFrom : Carbon::parse($this->detailTo)->subDays(14)->toDateString();
    }

    public function updatedDetailTo(): void
    {
        $this->detailTo = Carbon::hasFormat($this->detailTo, 'Y-m-d') ? $this->detailTo : $this->today();
    }

    public function applyDetailFilters(): void
    {
        $this->authorizeAccess();

        if ($this->detailFrom > $this->detailTo) {
            [$this->detailFrom, $this->detailTo] = [$this->detailTo, $this->detailFrom];
        }
        if ($this->employeeForQuery() === null) {
            $this->detailLoaded = false;
            $this->error = 'Seleccione un colaborador antes de consultar.';

            return;
        }

        $this->error = null;
        $this->appliedDetailFrom = $this->detailFrom;
        $this->appliedDetailTo = $this->detailTo;
        $this->detailLoaded = true;
    }

    public function updatedFrom(): void
    {
        $this->from = Carbon::hasFormat($this->from, 'Y-m-d') ? $this->from : Carbon::parse($this->to)->subDays(14)->toDateString();
    }

    public function updatedTo(): void
    {
        $this->to = Carbon::hasFormat($this->to, 'Y-m-d') ? $this->to : $this->today();
    }

    public function applyFilters(): void
    {
        $this->authorizeAccess();

        if ($this->from > $this->to) {
            [$this->from, $this->to] = [$this->to, $this->from];
        }
        if ($this->employeeForQuery() === null) {
            $this->loaded = false;
            $this->error = 'Seleccione un colaborador antes de consultar.';

            return;
        }

        $this->error = null;
        $this->appliedFrom = $this->from;
        $this->appliedTo = $this->to;
        $this->loaded = true;
    }

    #[Computed]
    public function employees(): array
    {
        try {
            return app(ManagerControlRepository::class)->employees();
        } catch (ManagerControlException $e) {
            $this->reportFailure($e);

            return [];
        }
    }

    public function render()
    {
        $summary = HoursSummary::fromRows([]);
        $detail = HoursSummary::fromRows([]);
        $employeeId = $this->employeeForQuery();
        try {
            if ($employeeId !== null && $this->loaded) {
                $summary = HoursSummary::fromRows(app(ManagerControlRepository::class)->workedHours($employeeId, $this->appliedFrom, $this->appliedTo));
            }
            if ($employeeId !== null && $this->detailLoaded) {
                $detail = HoursSummary::fromRows(app(ManagerControlRepository::class)->workedHours($employeeId, $this->appliedDetailFrom, $this->appliedDetailTo));
            }
        } catch (ManagerControlException $e) {
            $this->reportFailure($e);
            $this->loaded = $this->detailLoaded = false;
        }

        return view('livewire.testing.managercontrol-hours', [
            'summary' => $summary,
            'detail' => $detail,
            'employees' => $this->ownMode() ? [] : $this->employees,
            'ownMode' => $this->ownMode(),
            'matchedName' => $this->matchedName(),
            'pdfRoute' => $this->ownMode() ? 'testing.own-hours.pdf' : 'testing.managercontrol.pdf',
            'pdfExtra' => $this->ownMode() ? [] : ['employee' => $this->employeeId],
        ])->layout('layouts.app');
    }

    // Nunca se silencia un error: se muestra al administrador y se registra sin datos sensibles.
    protected function reportFailure(ManagerControlException $e): void
    {
        $this->error = $e->getMessage();
        Log::warning('ManagerControl (solo lectura): '.$e->getMessage());
    }

    private function today(): string
    {
        $timezone = (string) config('app.timezone', 'America/Mexico_City');

        return Carbon::now($timezone === 'UTC' ? 'America/Mexico_City' : $timezone)->toDateString();
    }
}
