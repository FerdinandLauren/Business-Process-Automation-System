<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RoleController extends Controller
{
    private const GROUPS = [
        'request' => 'Requests',
        'approval' => 'Approvals',
        'workflow' => 'Process configuration',
        'user' => 'Accounts',
        'organisation' => 'Organisation',
        'report' => 'Reporting',
        'audit' => 'Audit',
    ];

    public function index(): View
    {
        $roles = Role::query()
            ->with('permissions')
            ->withCount(['users' => fn ($query) => $query->where('is_active', true)])
            ->orderBy('id')
            ->get();

        $permissions = Permission::query()->orderBy('name')->get()
            ->groupBy(fn (Permission $permission) => self::GROUPS[str($permission->name)->before('.')->toString()] ?? 'Other');

        return view('admin.roles.index', [
            'roles' => $roles,
            'permissionsByGroup' => $permissions,
            'permissionCount' => Permission::query()->count(),
            'lockedRole' => $roles->firstWhere('name', 'system_admin'),
        ]);
    }

    public function update(Request $request, AuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'matrix' => ['array'],
            'matrix.*' => ['array'],
            'matrix.*.*' => ['integer', 'exists:permissions,id'],
        ]);

        $roles = Role::query()
            ->where('name', '!=', 'system_admin')
            ->with('permissions')
            ->get()
            ->keyBy('id');

        $permissionNames = Permission::query()->pluck('name', 'id');

        $changes = DB::transaction(function () use ($data, $roles, $permissionNames, $audit) {
            $changes = [];

            foreach ($roles as $role) {
                $requested = collect($data['matrix'][$role->getKey()] ?? [])
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->sort()
                    ->values();

                $current = $role->permissions->pluck('id')->sort()->values();

                if ($requested->all() === $current->all()) {
                    continue;
                }

                $role->permissions()->sync($requested->all());

                $changes[] = [
                    'role' => $role,
                    'added' => $requested->diff($current)->map(fn ($id) => $permissionNames[$id] ?? "#{$id}")->values()->all(),
                    'removed' => $current->diff($requested)->map(fn ($id) => $permissionNames[$id] ?? "#{$id}")->values()->all(),
                ];
            }

            if ($changes !== [] && ! $this->someoneCanStillManageUsers()) {
                throw ValidationException::withMessages([
                    'matrix' => 'At least one active account must keep the "Manage users" permission, otherwise nobody could administer access again.',
                ]);
            }

            foreach ($changes as $change) {
                $audit->log('role.permissions_updated', $change['role'], [
                    'role' => $change['role']->name,
                    'added' => $change['added'],
                    'removed' => $change['removed'],
                ]);
            }

            return $changes;
        });

        if ($changes === []) {
            return back()->with('status', 'The permission matrix already matched your selection.');
        }

        return back()->with('status', count($changes).' '.str('role')->plural(count($changes)).' updated. Affected users see the change on their next request.');
    }

    private function someoneCanStillManageUsers(): bool
    {
        return User::query()
            ->where('is_active', true)
            ->whereHas('roles.permissions', fn ($query) => $query->where('name', 'user.manage'))
            ->exists();
    }
}
