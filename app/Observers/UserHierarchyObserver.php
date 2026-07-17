<?php

namespace App\Observers;

use App\Models\User;
use App\Services\Administracion\OrganizationChartService;

class UserHierarchyObserver
{
    public function deleting(User $user): void
    {
        app(OrganizationChartService::class)->detachSubordinatesFromSuperior($user->id);
    }
}
