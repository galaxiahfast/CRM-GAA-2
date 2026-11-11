<?php

namespace App\Livewire\Testing;

use App\Services\ManagerControl\LegacyEmployeeResolver;
use App\Services\ManagerControl\ManagerControlException;
use Illuminate\Support\Facades\Gate;

/**
 * Prueba becarios: en lugar de elegir colaborador, se compara el nombre del usuario con el de los
 * colaboradores de ManagerControl; con una coincidencia única de al menos 70 % se muestran SOLO sus actividades.
 * El colaborador NUNCA se toma de datos enviados por el navegador.
 */
class ManagerControlOwnHours extends ManagerControlHours
{
    // Solo vive durante la petición (las propiedades privadas no se envían al navegador).
    private ?array $matchCache = null;

    public function mount(): void
    {
        parent::mount();

        // Se consulta de inmediato: no hay nada que seleccionar.
        $this->applyFilters();
        $this->applyDetailFilters();
    }

    protected function authorizeAccess(): void
    {
        abort_unless(Gate::allows('view-legacy-own-hours'), 403);
    }

    protected function ownMode(): bool
    {
        return true;
    }

    protected function matchedName(): ?string
    {
        return $this->match()['employee']['name'] ?? null;
    }

    // Sin asignación ni coincidencia no se muestra ningún aviso: la pantalla queda en ceros y el encabezado dice «sin asignar».
    protected function employeeForQuery(): ?int
    {
        return $this->match()['employee']['id'] ?? null;
    }

    /** @return array{status: string, employee: ?array{id: int, name: string}} */
    private function match(): array
    {
        if ($this->matchCache !== null) {
            return $this->matchCache;
        }

        try {
            $result = app(LegacyEmployeeResolver::class)->forUser(auth()->user());
        } catch (ManagerControlException $e) {
            $this->reportFailure($e);
            $result = ['status' => 'error', 'employee' => null];
        }

        return $this->matchCache = $result;
    }
}
