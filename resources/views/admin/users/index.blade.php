<x-layouts.app title="Users & access">
    <div class="space-y-6">
        <x-page-header
            eyebrow="Administration · Identity"
            title="Users & access"
            description="Every account carries one department and at least one role. Deactivated accounts keep their history but cannot sign in or receive new approval tasks."
        >
            <a href="{{ route('admin.index') }}" class="btn btn-sm">Administration</a>
            <a href="{{ route('admin.users.create') }}" class="btn btn-sm btn-primary">New user</a>
        </x-page-header>

        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <x-stat label="Accounts" :value="number_format($users->total())" meta="Matching the current filters" />
            <x-stat label="Active" :value="number_format($activeCount)" meta="Can sign in today" tone="positive" />
            <x-stat label="Departments" :value="number_format($departments->count())" meta="Available for assignment" />
            <x-stat label="Roles" :value="number_format($roles->count())" meta="Permission bundles" />
        </div>

        <form method="GET" action="{{ route('admin.users.index') }}" class="panel flex flex-wrap items-end gap-3 p-4">
            <div class="min-w-[220px] flex-1">
                <label class="label" for="q">Search</label>
                <input id="q" name="q" type="search" value="{{ $filters['q'] ?? '' }}" class="input" placeholder="Name, email or employee code">
            </div>
            <div class="min-w-[160px]">
                <label class="label" for="role">Role</label>
                <select id="role" name="role" class="select">
                    <option value="">All roles</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->name }}" @selected(($filters['role'] ?? '') === $role->name)>{{ $role->label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[170px]">
                <label class="label" for="department">Department</label>
                <select id="department" name="department" class="select">
                    <option value="">All departments</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}" @selected((int) ($filters['department'] ?? 0) === $department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[130px]">
                <label class="label" for="status">Status</label>
                <select id="status" name="status" class="select">
                    <option value="">Any status</option>
                    <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
                    <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive</option>
                </select>
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="btn btn-primary">Apply</button>
                <a href="{{ route('admin.users.index') }}" class="btn btn-quiet">Reset</a>
            </div>
        </form>

        @if($users->isEmpty())
            <x-empty-state title="No accounts match these filters" description="Clear the filters or create a new user account.">
                <a href="{{ route('admin.users.create') }}" class="btn btn-sm btn-primary">New user</a>
            </x-empty-state>
        @else
            <div class="panel table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Department</th>
                            <th>Roles</th>
                            <th>Status</th>
                            <th>Last sign-in</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $user)
                            <tr>
                                <td>
                                    <div class="flex items-center gap-2.5">
                                        <span class="grid h-7 w-7 flex-none place-items-center rounded-full bg-paper-deep font-mono text-[10px] text-ink-soft">{{ $user->initials() }}</span>
                                        <div class="min-w-0">
                                            <p class="truncate text-[12.5px] font-medium">{{ $user->name }}</p>
                                            <p class="truncate text-[11px] text-ink-faint">{{ $user->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <p class="text-[12.5px]">{{ $user->department?->name ?? '—' }}</p>
                                    <p class="mono text-[10.5px] text-ink-faint">{{ $user->employee_code }}</p>
                                </td>
                                <td>
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($user->roles as $role)
                                            <span class="rounded-[2px] border border-line bg-paper px-1.5 py-0.5 text-[10.5px] text-ink-soft">{{ $role->label }}</span>
                                        @endforeach
                                    </div>
                                </td>
                                <td>
                                    <x-status :label="$user->is_active ? 'Active' : 'Inactive'" :tone="$user->is_active ? 'positive' : 'neutral'" />
                                </td>
                                <td class="mono text-[11px] text-ink-soft">{{ $user->last_login_at?->format('d M Y, H:i') ?? 'Never' }}</td>
                                <td>
                                    <div class="flex items-center justify-end gap-1.5">
                                        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-quiet">Edit</a>
                                        @unless($user->is(auth()->user()))
                                            <form method="POST" action="{{ route('admin.users.status', $user) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-sm {{ $user->is_active ? 'btn-danger' : '' }}" @if($user->is_active) onclick="return confirm('Deactivate this account? They will no longer receive new approval tasks.')" @endif>
                                                    {{ $user->is_active ? 'Deactivate' : 'Activate' }}
                                                </button>
                                            </form>
                                        @endunless
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div><x-pagination :paginator="$users" /></div>
        @endif
    </div>
</x-layouts.app>
