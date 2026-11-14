<x-layouts.app title="Overview">
<div class="space-y-6">
    <x-page-header
        :eyebrow="now()->format('l, d F Y')"
        :title="'Good ' . (now()->hour < 12 ? 'morning' : (now()->hour < 18 ? 'afternoon' : 'evening')) . ', ' . explode(' ', auth()->user()->name)[0]"
        :description="$isApprover ? 'Your decision queue, request activity and process health in one view.' : 'Your requests, their progress and anything that needs your attention.'"
    >
        @can('create', App\Models\ServiceRequest::class)
            <a href="{{ route('requests.center') }}" class="btn btn-primary">New request</a>
        @endcan
        @if($isApprover)
            <a href="{{ route('approvals.index') }}" class="btn">Open approval inbox</a>
        @endif
    </x-page-header>

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        @if($isApprover)
            <x-stat
                label="Awaiting my decision"
                :value="number_format($pendingApprovals->count())"
                :meta="$overdueCount > 0 ? $overdueCount . ' overdue against SLA' : 'Nothing overdue'"
                :tone="$overdueCount > 0 ? 'danger' : 'default'"
            />
            <x-stat
                label="Open requests"
                :value="number_format($openRequests)"
                :meta="$viewingAll ? 'Across all departments' : 'Raised by me'"
            />
            <x-stat
                label="Approved this month"
                :value="number_format($approvedThisMonth)"
                :meta="now()->startOfMonth()->format('d M') . ' – today'"
                tone="positive"
            />
            <x-stat
                label="Active workflows"
                :value="number_format($activeWorkflows)"
                meta="Published, route-ready templates"
            />
        @else
            <x-stat
                label="Open requests"
                :value="number_format($openRequests)"
                meta="Submitted or in review"
            />
            <x-stat
                label="Approved this month"
                :value="number_format($approvedThisMonth)"
                :meta="now()->startOfMonth()->format('d M') . ' – today'"
                tone="positive"
            />
            <x-stat
                label="Revision required"
                :value="number_format($revisionRequired)"
                :meta="$revisionRequired > 0 ? 'Action needed from you' : 'Nothing returned'"
                :tone="$revisionRequired > 0 ? 'caution' : 'default'"
            />
            <x-stat
                label="Saved drafts"
                :value="number_format($draftCount)"
                meta="Not yet submitted"
            />
        @endif
    </div>

    <div class="grid gap-6 xl:grid-cols-12">
        <div class="space-y-6 xl:col-span-7">
            @if($isApprover)
                <section class="panel">
                    <div class="panel-head">
                        <div>
                            <p class="eyebrow">Decision queue</p>
                            <h2 class="mt-1 text-[14px] font-semibold">Waiting on you</h2>
                        </div>
                        <a href="{{ route('approvals.index') }}" class="text-[11.5px] font-medium text-ink-soft hover:text-accent">View inbox</a>
                    </div>

                    @if($pendingApprovals->isEmpty())
                        <x-empty-state
                            title="Your queue is clear"
                            description="No approval task is currently assigned to you. New assignments arrive here and in your notifications."
                        />
                    @else
                        <ul class="divide-y divide-line">
                            @foreach($pendingApprovals as $task)
                                @php($item = $task->workflowInstance->request)
                                <li>
                                    <a href="{{ route('approvals.show', $task) }}" class="group flex items-start justify-between gap-4 px-4 py-3.5 transition hover:bg-paper">
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2">
                                                <span class="mono text-[10.5px] text-ink-faint">{{ $item->request_number }}</span>
                                                <span class="h-1 w-1 rounded-full bg-line-strong"></span>
                                                <span class="truncate text-[11px] text-ink-faint">{{ $task->stage->stage_name }}</span>
                                            </div>
                                            <p class="mt-0.5 truncate text-[13.5px] font-medium group-hover:text-accent">{{ $item->title }}</p>
                                            <p class="mt-0.5 truncate text-[11.5px] text-ink-soft">
                                                {{ $item->requester->name }} · {{ $item->department?->name ?? 'No department' }}
                                            </p>
                                        </div>
                                        <div class="flex flex-none flex-col items-end gap-1">
                                            <x-status :label="$item->priority->label()" :tone="$item->priority->tone()" />
                                            @if($task->isOverdue())
                                                <span class="mono text-[10px] uppercase tracking-[0.08em] text-danger">{{ abs((int) round($task->hoursRemaining())) }}h overdue</span>
                                            @elseif($task->hoursRemaining() !== null)
                                                <span class="mono text-[10px] uppercase tracking-[0.08em] text-ink-faint">{{ $task->hoursRemaining() }}h left</span>
                                            @endif
                                        </div>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            @endif

            <section class="panel">
                <div class="panel-head">
                    <div>
                        <p class="eyebrow">My requests</p>
                        <h2 class="mt-1 text-[14px] font-semibold">Recently updated</h2>
                    </div>
                    <a href="{{ route('requests.index') }}" class="text-[11.5px] font-medium text-ink-soft hover:text-accent">Request register</a>
                </div>

                @if($myRequests->isEmpty())
                    <x-empty-state
                        title="No requests yet"
                        description="Start from the Request Center — each type carries its own form and approval route."
                    >
                        @can('create', App\Models\ServiceRequest::class)
                            <a href="{{ route('requests.center') }}" class="btn btn-sm">Open Request Center</a>
                        @endcan
                    </x-empty-state>
                @else
                    <div class="table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Request</th>
                                    <th>Status</th>
                                    <th>Stage</th>
                                    <th class="text-right">Updated</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($myRequests as $item)
                                    <tr>
                                        <td class="max-w-[280px]">
                                            <a href="{{ route('requests.show', $item) }}" class="block truncate font-medium underline-offset-2 hover:text-accent hover:underline">{{ $item->title }}</a>
                                            <span class="mono mt-0.5 block text-[10.5px] text-ink-faint">{{ $item->request_number }} · {{ $item->requestType->name }}</span>
                                        </td>
                                        <td><x-status :label="$item->status->label()" :tone="$item->status->tone()" /></td>
                                        <td class="mono text-[11px] text-ink-soft">
                                            {{ $item->workflowInstance?->current_stage_order ? 'S' . $item->workflowInstance->current_stage_order : '—' }}
                                        </td>
                                        <td class="mono text-right text-[11px] text-ink-faint">{{ $item->updated_at->diffForHumans(short: true) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            @if($recentActivity)
                <section class="panel">
                    <div class="panel-head">
                        <div>
                            <p class="eyebrow">Audit trail</p>
                            <h2 class="mt-1 text-[14px] font-semibold">Latest system activity</h2>
                        </div>
                        <a href="{{ route('admin.audit.index') }}" class="text-[11.5px] font-medium text-ink-soft hover:text-accent">Full log</a>
                    </div>
                    <ul class="divide-y divide-line">
                        @foreach($recentActivity as $entry)
                            <li class="flex items-start justify-between gap-4 px-4 py-2.5">
                                <div class="min-w-0">
                                    <p class="truncate text-[12px]">
                                        <span class="mono text-[10.5px] text-ink-faint">{{ $entry->action }}</span>
                                        <span class="text-ink-soft"> · {{ $entry->actor?->name ?? 'System' }}</span>
                                    </p>
                                    @if($entry->subject_label)
                                        <p class="truncate text-[11px] text-ink-faint">{{ $entry->subject_label }}</p>
                                    @endif
                                </div>
                                <span class="mono flex-none text-[10.5px] text-ink-faint">{{ $entry->created_at->diffForHumans(short: true) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>

        <aside class="space-y-6 xl:col-span-5">
            <section class="panel">
                <div class="panel-head">
                    <div>
                        <p class="eyebrow">Distribution</p>
                        <h2 class="mt-1 text-[14px] font-semibold">Request status</h2>
                    </div>
                    <span class="mono text-[11px] text-ink-faint">{{ $statusTotal }} total</span>
                </div>
                <div class="space-y-3 p-4">
                    @forelse(App\Enums\RequestStatus::cases() as $status)
                        @php($count = (int) ($statusDistribution[$status->value] ?? 0))
                        @continue($count === 0 && $status === App\Enums\RequestStatus::Cancelled)
                        <div>
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-[12px] text-ink-soft">{{ $status->label() }}</span>
                                <span class="mono text-[11px] text-ink">{{ $count }}</span>
                            </div>
                            <div class="mt-1.5 h-1 w-full overflow-hidden bg-paper-deep" role="presentation">
                                <div class="h-full bg-ink/70" style="width: {{ $statusTotal > 0 ? round(($count / $statusTotal) * 100, 1) : 0 }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-[12px] text-ink-faint">No requests recorded yet.</p>
                    @endforelse
                </div>
            </section>

            <section class="panel">
                <div class="panel-head">
                    <h2 class="text-[13px] font-semibold">SLA discipline</h2>
                    <span class="mono text-[10.5px] uppercase tracking-[0.08em] text-ink-faint">{{ config('bpa.sla.workday_start', '08:00') }}–{{ config('bpa.sla.workday_end', '17:00') }}</span>
                </div>
                <div class="p-4">
                    <p class="text-[12.5px] leading-relaxed text-ink-soft">
                        Approval deadlines are calculated in business hours against the {{ config('bpa.sla.working_days_label', 'Monday–Friday') }} calendar. Requests that breach a stage deadline surface as overdue in the approver's queue.                    </p>
                    <dl class="mt-3 space-y-2 border-t border-line pt-3 text-[12px]">
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-ink-soft">Approvals completed (30d)</dt>
                            <dd class="mono text-ink">{{ number_format($completedApprovals) }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-ink-soft">My overdue tasks</dt>
                            <dd class="mono {{ $overdueCount > 0 ? 'text-danger' : 'text-ink' }}">{{ number_format($overdueCount) }}</dd>
                        </div>
                    </dl>
                </div>
            </section>

            <section class="panel p-4">
                <p class="eyebrow">Your profile</p>
                <div class="mt-3 flex items-center gap-3">
                    <span class="grid h-10 w-10 flex-none place-items-center rounded-full bg-ink font-mono text-[12px] text-paper">{{ auth()->user()->initials() }}</span>
                    <div class="min-w-0">
                        <p class="truncate text-[13px] font-medium">{{ auth()->user()->name }}</p>
                        <p class="truncate text-[11.5px] text-ink-faint">{{ auth()->user()->job_title }} · {{ auth()->user()->department?->name }}</p>
                    </div>
                </div>
                <dl class="mt-4 space-y-2 border-t border-line pt-3 text-[12px]">
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-ink-soft">Employee code</dt>
                        <dd class="mono text-[11px] text-ink">{{ auth()->user()->employee_code }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-ink-soft">Role</dt>
                        <dd class="text-[11px] text-ink">{{ auth()->user()->primaryRole()?->label ?? 'Unassigned' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-ink-soft">Last sign-in</dt>
                        <dd class="mono text-[11px] text-ink">{{ auth()->user()->last_login_at?->format('d M · H:i') ?? 'First session' }}</dd>
                    </div>
                </dl>
                <a href="{{ route('settings.edit') }}" class="btn btn-sm mt-4 w-full">Account settings</a>
            </section>
        </aside>
    </div>
</div>

</x-layouts.app>