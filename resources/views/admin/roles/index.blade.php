<x-layouts.app title="Roles & permissions">
    <div class="space-y-6">
        <x-page-header
            eyebrow="Administration · Access control"
            title="Roles & permissions"
            description="Permissions drive navigation and every server-side check. Changes apply immediately to the accounts holding the role and are written to the audit trail."
        >
            <a href="{{ route('admin.index') }}" class="btn btn-sm">Administration</a>
        </x-page-header>

        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <x-stat label="Roles" :value="number_format($roles->count())" meta="Bundles of permissions" />
            <x-stat label="Permissions" :value="number_format($permissionCount)" meta="Checked on every request" />
            <x-stat label="Accounts covered" :value="number_format($roles->sum('users_count'))" meta="Active users with a role" />
            <x-stat label="System locked" value="1" meta="System administrator cannot be edited" />
        </div>

        @error('matrix')
            <div class="border border-[#c0392b]/40 bg-[#c0392b]/5 px-4 py-3 text-[12px] text-[#8e2a1f]">{{ $message }}</div>
        @enderror

        <form method="POST" action="{{ route('admin.roles.update') }}">
            @csrf
            @method('PATCH')

            <div class="panel table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="min-w-[260px]">Permission</th>
                            @foreach($roles as $role)
                                <th class="text-center">
                                    <span class="block text-[11.5px] font-semibold">{{ $role->label }}</span>
                                    <span class="mono mt-0.5 block text-[10px] font-normal text-ink-faint">
                                        {{ $role->users_count }} {{ Str::plural('user', $role->users_count) }}
                                    </span>
                                    @if($role->name === 'system_admin')
                                        <span class="mono mt-1 inline-block rounded-[2px] border border-line bg-paper px-1.5 py-0.5 text-[9.5px] uppercase tracking-[0.08em] text-ink-faint">Locked</span>
                                    @endif
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($permissionsByGroup as $group => $permissions)
                            <tr>
                                <td colspan="{{ $roles->count() + 1 }}" class="bg-paper-deep/50">
                                    <span class="eyebrow">{{ $group }}</span>
                                </td>
                            </tr>
                            @foreach($permissions as $permission)
                                <tr>
                                    <td>
                                        <p class="text-[12.5px] font-medium">{{ $permission->label }}</p>
                                        <p class="mono text-[10.5px] text-ink-faint">{{ $permission->name }}</p>
                                    </td>
                                    @foreach($roles as $role)
                                        @php($locked = $role->name === 'system_admin')
                                        @php($checked = $role->permissions->contains('id', $permission->id))
                                        <td class="text-center">
                                            <input
                                                type="checkbox"
                                                name="matrix[{{ $role->id }}][]"
                                                value="{{ $permission->id }}"
                                                class="h-3.5 w-3.5 accent-[#2f45c5]"
                                                @checked($locked || $checked)
                                                @disabled($locked)
                                                aria-label="{{ $permission->label }} for {{ $role->label }}"
                                            >
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                <p class="max-w-[620px] text-[11.5px] leading-relaxed text-ink-soft">
                    The system administrator always holds every permission so access can be recovered. All other roles can be adjusted; at least one active account must keep "Manage users".
                </p>
                <button type="submit" class="btn btn-primary">Save permission matrix</button>
            </div>
        </form>

        <section class="panel">
            <div class="panel-head">
                <div>
                    <p class="eyebrow">Role definitions</p>
                    <h2 class="mt-0.5 text-[14px] font-semibold">What each role is for</h2>
                </div>
            </div>
            <div class="grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($roles as $role)
                    <div class="border border-line bg-paper px-3 py-3">
                        <p class="text-[12.5px] font-medium">{{ $role->label }}</p>
                        <p class="mono mt-0.5 text-[10px] uppercase tracking-[0.06em] text-ink-faint">{{ $role->name }}</p>
                        <p class="mt-1.5 text-[11.5px] leading-relaxed text-ink-soft">{{ $role->description }}</p>
                        <p class="mono mt-2 text-[10.5px] text-ink-faint">{{ $role->permissions->count() }} {{ Str::plural('permission', $role->permissions->count()) }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
</x-layouts.app>
