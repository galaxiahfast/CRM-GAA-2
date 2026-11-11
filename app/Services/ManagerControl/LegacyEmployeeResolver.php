<?php

namespace App\Services\ManagerControl;

use App\Models\ManagerControlEmployeeLink;
use App\Models\User;

/**
 * Colaborador de ManagerControl de un usuario del CRM: primero la asignación hecha por un administrador;
 * si no hay, la coincidencia única de nombre (70 % o más).
 */
class LegacyEmployeeResolver
{
    public function __construct(private readonly ManagerControlRepository $repository, private readonly NameMatcher $matcher = new NameMatcher) {}

    /** @return array{status: string, employee: ?array{id: int, name: string}, source: string} */
    public function forUser(User $user): array
    {
        $link = ManagerControlEmployeeLink::query()->where('user_id', $user->getKey())->first();
        if ($link !== null) {
            return ['status' => 'match', 'employee' => ['id' => $link->legacy_employee_id, 'name' => $link->legacy_employee_name], 'source' => 'link'];
        }

        $match = $this->matcher->bestMatch(trim($user->name.' '.$user->last_name), $this->repository->employees());

        return ['status' => $match['status'], 'employee' => $match['employee'], 'source' => $match['employee'] ? 'name' : 'none'];
    }
}
