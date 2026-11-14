<?php

namespace App\Policies;

use App\Models\ServiceRequest;
use App\Models\User;

class ServiceRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ServiceRequest $request): bool
    {
        if ((int) $request->requester_id === (int) $user->getKey()) {
            return true;
        }

        if ($user->hasPermission('request.view.any')) {
            return true;
        }

        return $request->workflowInstance?->tasks()
            ->where('assigned_approver_id', $user->getKey())
            ->exists() ?? false;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('request.create');
    }

    public function update(User $user, ServiceRequest $request): bool
    {
        return (int) $request->requester_id === (int) $user->getKey() && $request->isEditable();
    }

    public function submit(User $user, ServiceRequest $request): bool
    {
        return (int) $request->requester_id === (int) $user->getKey() && $request->isEditable();
    }

    public function cancel(User $user, ServiceRequest $request): bool
    {
        return (int) $request->requester_id === (int) $user->getKey() && $request->isCancellable();
    }

    public function downloadAttachment(User $user, ServiceRequest $request): bool
    {
        return $this->view($user, $request);
    }
}
