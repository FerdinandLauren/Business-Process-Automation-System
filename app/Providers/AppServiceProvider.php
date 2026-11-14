<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::define('manage-users', fn (User $user) => $user->hasPermission('user.manage'));
        Gate::define('manage-organisation', fn (User $user) => $user->hasPermission('organisation.manage'));
        Gate::define('manage-workflows', fn (User $user) => $user->hasPermission('workflow.manage'));
        Gate::define('view-audit-logs', fn (User $user) => $user->hasPermission('audit.view'));
        Gate::define('view-reports', fn (User $user) => $user->hasPermission('report.view'));
        Gate::define('process-approvals', fn (User $user) => $user->hasPermission('approval.process'));
        Gate::define('access-admin', fn (User $user) => $user->hasPermission('user.manage')
            || $user->hasPermission('organisation.manage')
            || $user->hasPermission('workflow.manage')
            || $user->hasPermission('audit.view'));
    }
}
