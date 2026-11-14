<x-layouts.app title="Administration">
    <div class="space-y-6">
        <x-page-header
            eyebrow="System"
            title="Administration"
            description="Organisation structure, access control and the append-only audit trail. Every change below is recorded with its actor."
        />

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @can('manage-users')
                <a href="{{ route('admin.users.index') }}" class="panel group flex flex-col justify-between p-4 transition hover:border-line-strong">
                    <div>
                        <p class="eyebrow">Identity</p>
                        <h2 class="mt-1 text-[15px] font-semibold">Users & access</h2>
                        <p class="mt-1 text-[12.5px] leading-relaxed text-ink-soft">Create accounts, assign departments and roles, and deactivate leavers.</p>
                    </div>
                    <p class="mono mt-4 text-[11px] text-ink-faint">{{ $activeUsers }} active · {{ $totalUsers }} total</p>
                </a>
            @endcan

            @can('manage-organisation')
                <a href="{{ route('admin.departments.index') }}" class="panel group flex flex-col justify-between p-4 transition hover:border-line-strong">
                    <div>
                        <p class="eyebrow">Organisation</p>
                        <h2 class="mt-1 text-[15px] font-semibold">Departments</h2>
                        <p class="mt-1 text-[12.5px] leading-relaxed text-ink-soft">Maintain the departments used for routing, reporting and data isolation.</p>
                    </div>
                    <p class="mono mt-4 text-[11px] text-ink-faint">{{ $departments }} departments</p>
                </a>
            @endcan

            @can('manage-workflows')
                <a href="{{ route('admin.request-types.index') }}" class="panel group flex flex-col justify-between p-4 transition hover:border-line-strong">
                    <div>
                        <p class="eyebrow">Process catalogue</p>
                        <h2 class="mt-1 text-[15px] font-semibold">Request types & forms</h2>
                        <p class="mt-1 text-[12.5px] leading-relaxed text-ink-soft">Publish versioned forms and keep the request catalogue current.</p>
                    </div>
                    <p class="mono mt-4 text-[11px] text-ink-faint">{{ $requestTypes }} active types</p>
                </a>
            @endcan

            @can('manage-users')
                <a href="{{ route('admin.roles.index') }}" class="panel group flex flex-col justify-between p-4 transition hover:border-line-strong">
                    <div>
                        <p class="eyebrow">Access control</p>
                        <h2 class="mt-1 text-[15px] font-semibold">Roles & permissions</h2>
                        <p class="mt-1 text-[12.5px] leading-relaxed text-ink-soft">Review and adjust the permission matrix that drives navigation and server-side checks.</p>
                    </div>
                    <p class="mono mt-4 text-[11px] text-ink-faint">{{ $roles }} roles</p>
                </a>
            @endcan

            @can('view-audit-logs')
                <a href="{{ route('admin.audit.index') }}" class="panel group flex flex-col justify-between p-4 transition hover:border-line-strong">
                    <div>
                        <p class="eyebrow">Accountability</p>
                        <h2 class="mt-1 text-[15px] font-semibold">Audit trail</h2>
                        <p class="mt-1 text-[12.5px] leading-relaxed text-ink-soft">Inspect sign-ins, submissions, decisions and configuration changes.</p>
                    </div>
                    <p class="mono mt-4 text-[11px] text-ink-faint">{{ number_format($auditEntries) }} records</p>
                </a>
            @endcan
        </div>
    </div>
</x-layouts.app>
