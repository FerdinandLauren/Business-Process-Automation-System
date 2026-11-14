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
