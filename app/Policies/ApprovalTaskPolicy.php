<?php

namespace App\Policies;

use App\Models\ApprovalTask;
use App\Models\User;

class ApprovalTaskPolicy
{
    public function view(User $user, ApprovalTask $task): bool
    {
        return (int) $task->assigned_approver_id === (int) $user->getKey()
            || $user->hasPermission('request.view.any');
    }

    public function decide(User $user, ApprovalTask $task): bool
    {
        return (int) $task->assigned_approver_id === (int) $user->getKey()
            && $task->isPending()
            && $user->is_active;
    }
}
