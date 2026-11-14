<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:80'],
            'role' => ['nullable', 'string', 'exists:roles,name'],
            'department' => ['nullable', 'integer', 'exists:departments,id'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        $users = User::query()
            ->with(['department', 'roles'])
            ->when($filters['q'] ?? null, function ($query, string $term) {
                $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $term).'%';
                $query->where(fn ($inner) => $inner
                    ->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('employee_code', 'like', $like));
            })
            ->when($filters['role'] ?? null, fn ($query, string $role) => $query->whereHas(
                'roles',
                fn ($inner) => $inner->where('name', $role),
            ))
            ->when($filters['department'] ?? null, fn ($query, int $department) => $query->where('department_id', $department))
            ->when(($filters['status'] ?? null) === 'active', fn ($query) => $query->where('is_active', true))
            ->when(($filters['status'] ?? null) === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'roles' => Role::query()->orderBy('label')->get(),
            'departments' => Department::query()->orderBy('name')->get(),
            'filters' => $filters,
            'activeCount' => User::query()->where('is_active', true)->count(),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.form', [
            'user' => new User(['is_active' => true]),
            'roles' => Role::query()->orderBy('label')->get(),
            'departments' => Department::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, AuditLogger $audit): RedirectResponse
    {
        $data = $this->validated($request);
        $roleIds = $data['roles'];
        unset($data['roles'], $data['password_confirmation']);

        $user = DB::transaction(function () use ($data, $roleIds, $audit) {
            $user = User::create($data);
            $user->roles()->sync($roleIds);

            $audit->log('user.created', $user, [
                'email' => $user->email,
                'roles' => $user->roles()->pluck('name')->all(),
            ]);

            return $user;
        });

        return redirect()
            ->route('admin.users.index')
            ->with('status', "{$user->name} was created. A temporary password must be shared out of band.");
    }

    public function edit(User $user): View
    {
        return view('admin.users.form', [
            'user' => $user->load(['roles', 'department']),
            'roles' => Role::query()->orderBy('label')->get(),
            'departments' => Department::query()->orderBy('name')->get(),
        ]);
    }

    public function toggleStatus(Request $request, User $user, AuditLogger $audit): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->withErrors(['status' => 'You cannot change the status of your own account.']);
        }

        if ($user->is_active) {
            $this->assertAdminCoverage($user);
        }

        $user->forceFill(['is_active' => ! $user->is_active])->save();

        $audit->log($user->is_active ? 'user.activated' : 'user.deactivated', $user, [
            'email' => $user->email,
        ]);

        return back()->with('status', $user->is_active
            ? "{$user->name} can sign in again."
            : "{$user->name} was deactivated and will no longer receive new approval tasks.");
    }

    public function update(Request $request, User $user, AuditLogger $audit): RedirectResponse
    {
        $data = $this->validated($request, $user);
        $roleIds = $data['roles'];
        unset($data['roles'], $data['password_confirmation']);

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        if ($user->is($request->user()) && ! $data['is_active']) {
            return back()->withErrors(['is_active' => 'You cannot deactivate your own account.'])->withInput();
        }

        if ($user->is($request->user()) && empty($roleIds)) {
            return back()->withErrors(['roles' => 'You cannot remove every role from your own account.'])->withInput();
        }

        if (! $data['is_active'] || ! in_array(Role::query()->where('name', 'system_admin')->value('id'), $roleIds, true)) {
            $this->assertAdminCoverage($user);
        }

        DB::transaction(function () use ($user, $data, $roleIds, $audit) {
            $before = $user->only(['name', 'email', 'department_id', 'is_active']);
            $user->update($data);
            $user->roles()->sync($roleIds);

            $audit->log('user.updated', $user, [
                'before' => $before,
                'after' => $user->only(['name', 'email', 'department_id', 'is_active']),
                'roles' => $user->roles()->pluck('name')->all(),
            ]);
        });

        return redirect()
            ->route('admin.users.index')
            ->with('status', "{$user->name} was updated.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160', Rule::unique('users', 'email')->ignore($user?->getKey())],
            'password' => [$user ? 'nullable' : 'required', 'confirmed', Password::min(8)],
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'employee_code' => ['required', 'string', 'max:32', Rule::unique('users', 'employee_code')->ignore($user?->getKey())],
            'job_title' => ['nullable', 'string', 'max:100'],
            'is_active' => ['required', 'boolean'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['integer', 'exists:roles,id'],
        ]);
    }

    private function assertAdminCoverage(User $user): void
    {
        $adminRoleId = Role::query()->where('name', 'system_admin')->value('id');

        $remaining = User::query()
            ->where('is_active', true)
            ->whereKeyNot($user->getKey())
            ->whereHas('roles', fn ($query) => $query->whereKey($adminRoleId))
            ->exists();

        if (! $remaining) {
            abort(422, 'At least one active system administrator must remain.');
        }
    }
}
