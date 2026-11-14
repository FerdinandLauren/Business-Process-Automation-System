<div class="space-y-6">
    <x-page-header
        eyebrow="Approval inbox"
        title="Decisions assigned to you"
        description="Every task here is routed to you personally by an active workflow stage. Decide with context, not from an email thread."
    />

    <div class="grid grid-cols-3 gap-3">
        <button type="button" wire:click="$set('view', 'pending')" @class(['panel p-4 text-left transition', 'border-ink' => $view === 'pending'])>
            <p class="eyebrow">Pending</p>
            <p class="mono mt-2 text-[24px] leading-none">{{ $counts['pending'] }}</p>
            <p class="mt-1.5 text-[11.5px] text-ink-faint">{{ $counts['overdue'] }} overdue</p>
        </button>
        <button type="button" wire:click="$set('view', 'overdue')" @class(['panel p-4 text-left transition', 'border-ink' => $view === 'overdue'])>
            <p class="eyebrow">Overdue</p>
            <p class="mono mt-2 text-[24px] leading-none {{ $counts['overdue'] > 0 ? 'text-danger' : '' }}">{{ $counts['overdue'] }}</p>
            <p class="mt-1.5 text-[11.5px] text-ink-faint">Past stage SLA</p>
        </button>
        <button type="button" wire:click="$set('view', 'completed')" @class(['panel p-4 text-left transition', 'border-ink' => $view === 'completed'])>
            <p class="eyebrow">Decided</p>
            <p class="mono mt-2 text-[24px] leading-none">{{ $counts['completed'] }}</p>
            <p class="mt-1.5 text-[11.5px] text-ink-faint">Your full history</p>
        </button>
    </div>

    <section class="panel">
        <div class="flex flex-wrap items-end gap-3 border-b border-line px-4 py-3">
            <div class="min-w-[220px] flex-1">
                <label for="approval-search" class="label">Search</label>
                <input id="approval-search" type="search" wire:model.live.debounce.300ms="search" placeholder="Request number, title or requester" class="input">
            </div>
            <div class="w-[200px]">
                <label for="approval-type" class="label">Request type</label>
                <select id="approval-type" wire:model.live="type" class="select">
                    <option value="">All types</option>
                    @foreach($types as $requestType)
                        <option value="{{ $requestType->code }}">{{ $requestType->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center gap-1 rounded-[3px] border border-line p-0.5">
                @foreach(['pending' => 'Pending', 'overdue' => 'Overdue', 'completed' => 'Decided', 'all' => 'All'] as $value => $label)
                    <button type="button" wire:click="$set('view', '{{ $value }}')" @class([
                        'rounded-[2px] px-2.5 py-1.5 text-[11.5px] font-medium transition',
                        'bg-ink text-paper' => $view === $value,
                        'text-ink-soft hover:bg-paper' => $view !== $value,
                    ])>{{ $label }}</button>
                @endforeach
            </div>
        </div>

        <div wire:loading.delay class="border-b border-line bg-paper px-4 py-1.5 text-[11px] text-ink-faint">Refreshing queue…</div>

        @if($tasks->isEmpty())
            <x-empty-state
                :title="$view === 'pending' ? 'No approvals waiting' : ($view === 'overdue' ? 'Nothing overdue' : 'No decisions recorded')"
                :description="$view === 'pending' ? 'New tasks appear here and in your notifications when a workflow reaches your stage.' : 'Adjust the filters or switch views to see your approval history.'"
            />
        @else
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Request</th>
                            <th>Requester</th>
                            <th>Stage</th>
                            <th>Received</th>
                            <th>SLA deadline</th>
                            <th>Status</th>
                            <th class="text-right"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tasks as $task)
                            @php($item = $task->workflowInstance->request)
                            <tr>
                                <td class="max-w-[300px]">
                                    <a href="{{ route('approvals.show', $task) }}" class="block truncate font-medium underline-offset-2 hover:text-accent hover:underline">{{ $item->title }}</a>
                                    <span class="mono mt-0.5 block text-[10.5px] text-ink-faint">{{ $item->request_number }} · {{ $item->requestType->name }} · {{ $item->priority->label() }}</span>
                                </td>
                                <td class="text-ink-soft">
                                    <span class="block text-[12.5px]">{{ $item->requester->name }}</span>
                                    <span class="block text-[10.5px] text-ink-faint">{{ $item->department?->name }}</span>
                                </td>
                                <td class="text-[12px] text-ink-soft">{{ $task->stage->stage_name }}</td>
                                <td class="mono text-[11px] text-ink-soft">{{ $task->started_at?->format('d M · H:i') ?? '—' }}</td>
                                <td class="mono text-[11px]">
                                    @if($task->due_at)
                                        <span class="{{ $task->isOverdue() ? 'text-danger' : 'text-ink-soft' }}">{{ $task->due_at->format('d M · H:i') }}</span>
                                        @if($task->isOverdue())
                                            <span class="mt-0.5 block text-[10px] uppercase tracking-[0.06em] text-danger">{{ abs((int) round($task->hoursRemaining())) }}h overdue</span>
                                        @elseif($task->status->value === 'pending')
                                            <span class="mt-0.5 block text-[10px] uppercase tracking-[0.06em] text-ink-faint">{{ $task->hoursRemaining() }}h left</span>
                                        @endif
                                    @else
                                        —
                                    @endif
                                </td>
                                <td><x-status :label="$task->status->label()" :tone="$task->status->tone()" /></td>
                                <td class="text-right">
                                    <a href="{{ route('approvals.show', $task) }}" class="btn btn-sm">{{ $task->status->value === 'pending' ? 'Review' : 'Open' }}</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-pagination :paginator="$tasks" />
        @endif
    </section>
</div>
