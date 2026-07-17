<?php

namespace App\Observers;

use App\Models\UserOrganizationalProfile;
use App\Services\Administracion\OrganizationChartService;

class UserOrganizationalProfileHierarchyObserver
{
    public function updated(UserOrganizationalProfile $profile): void
    {
        if ($profile->wasChanged('is_active') && ! $profile->is_active) {
            app(OrganizationChartService::class)->detachSubordinatesFromSuperior($profile->user_id);
        }
    }
}
