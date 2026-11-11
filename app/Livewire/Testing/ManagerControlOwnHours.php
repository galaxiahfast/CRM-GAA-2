<?php

namespace App\Livewire\Testing;

use App\Services\ManagerControl\ManagerControlException;
use App\Services\ManagerControl\ManagerControlRepository;
use App\Services\ManagerControl\NameMatcher;
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

    protected function employeeForQuery(): ?int
    {
        $employee = $this->match()['employee'];
        if ($employee === null) {
            $this->error = $this->match()['status'] === 'ambiguous'
                ? 'Hay más de un colaborador de ManagerControl con un nombre muy parecido al tuyo; pide a un administrador que lo confirme.'
                : 'No se encontró en ManagerControl un colaborador con nombre parecido al tuyo (mínimo 70 % de coincidencia).';

            return null;
        }

        return $employee['id'];
    }

    /** @return array{status: string, employee: ?array{id: int, name: string}, score: float} */
    private function match(): array
    {
        if ($this->matchCache !== null) {
            return $this->matchCache;
        }
        $user = auth()->user();

        try {
            $fullName = trim(($user->name ?? '').' '.($user->last_name ?? ''));
            $result = (new NameMatcher)->bestMatch($fullName, app(ManagerControlRepository::class)->employees());
        } catch (ManagerControlException $e) {
            $this->reportFailure($e);
            $result = ['status' => 'error', 'employee' => null, 'score' => 0.0];
        }

        return $this->matchCache = $result;
    }
}
