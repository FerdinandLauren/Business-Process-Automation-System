<?php

namespace App\Policies;

use App\Models\User;

class AdminPolicy
{
    public function manageUsers(User $user): bool
    {
        return $user->hasPermission('user.manage');
    }

    public function manageOrganisation(User $user): bool
    {
        return $user->hasPermission('organisation.manage');
    }

    public function manageWorkflows(User $user): bool
    {
        return $user->hasPermission('workflow.manage');
    }

    public function viewAuditLogs(User $user): bool
    {
        return $user->hasPermission('audit.view');
    }

    public function viewReports(User $user): bool
    {
        return $user->hasPermission('report.view');
    }
}
