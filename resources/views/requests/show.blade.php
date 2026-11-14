<x-layouts.app title="Request detail">
@php
    $status = $serviceRequest->status;
    $canEdit = auth()->user()->can('update', $serviceRequest);
    $canSubmit = auth()->user()->can('submit', $serviceRequest);
    $canCancel = auth()->user()->can('cancel', $serviceRequest);
    $latestHistory = $instance?->tasks
        ->flatMap->histories
        ->sortByDesc('created_at')
        ->first();
@endphp

<div class="space-y-6" x-data="{ confirmCancel: false }">
    <div>
        <a href="{{ route('requests.index') }}" class="inline-flex items-center gap-1.5 text-[11.5px] font-medium text-ink-faint transition hover:text-ink">
            <svg class="h-3 w-3" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M7.5 2.5L4 6l3.5 3.5"/></svg>
            Request register
        </a>

        <div class="mt-3 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="mono text-[11px] uppercase tracking-[0.1em] text-ink-faint">{{ $serviceRequest->request_number }}</span>
                    <span class="h-1 w-1 rounded-full bg-line-strong"></span>
                    <span class="text-[11.5px] text-ink-soft">{{ $serviceRequest->requestType->name }}</span>
                    <x-status :label="$status->label()" :tone="$status->tone()" />
                </div>
                <h1 class="mt-2 max-w-[820px] text-[24px] font-semibold leading-tight tracking-[-0.02em]">{{ $serviceRequest->title }}</h1>
                <p class="mt-1.5 text-[12.5px] text-ink-soft">
                    Raised by <span class="font-medium text-ink">{{ $serviceRequest->requester->name }}</span>
                    · {{ $serviceRequest->department?->name ?? 'No department' }}
                    · {{ $serviceRequest->created_at->format('d M Y, H:i') }}
                </p>
            </div>

            <div class="flex flex-none flex-wrap items-center gap-2">
                @if($canEdit)
                    <a href="{{ route('requests.edit', $serviceRequest) }}" class="btn">{{ $status->value === 'revision_required' ? 'Revise request' : 'Edit draft' }}</a>
                @endif
                @if($canSubmit)
                    <form method="POST" action="{{ route('requests.submit', $serviceRequest) }}">
                        @csrf
                        <button type="submit" class="btn btn-primary">{{ $status->value === 'revision_required' ? 'Resubmit' : 'Submit for approval' }}</button>
                    </form>
                @endif
                @if($canCancel)
                    <button type="button" class="btn btn-danger" @click="confirmCancel = true">Cancel request</button>
                @endif
            </div>
        </div>
    </div>

    @if($status->value === 'revision_required' && $latestHistory)
        <div class="border border-caution/30 bg-caution-soft px-4 py-3">
            <p class="text-[12.5px] font-medium text-caution">Revision requested by {{ $latestHistory->actor?->name ?? 'an approver' }}</p>
            <p class="mt-1 text-[12.5px] leading-relaxed text-caution">{{ $latestHistory->comment }}</p>
            <p class="mt-1.5 text-[11.5px] text-caution/80">Update the request and resubmit. The workflow resumes at the stage that requested the change.</p>
        </div>
    @endif

    @if(session('error'))
        <div class="border border-danger/25 bg-danger-soft px-4 py-3 text-[12.5px] text-danger" role="alert">{{ session('error') }}</div>
    @endif

    @if($errors->any())
        <div class="border border-danger/25 bg-danger-soft px-4 py-3" role="alert">
            @foreach($errors->all() as $error)
                <p class="text-[12.5px] text-danger">{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-12">
        <div class="space-y-5 lg:col-span-8">
            <section class="panel">
                <div class="panel-head">
                    <div>
                        <p class="eyebrow">Section 01</p>
                        <h2 class="mt-1 text-[14px] font-semibold">Request details</h2>
                    </div>
                    <span class="mono text-[10.5px] uppercase tracking-[0.08em] text-ink-faint">Form v{{ $serviceRequest->formTemplate?->version ?? '—' }}</span>
                </div>
                <dl class="grid gap-x-8 gap-y-4 p-4 sm:grid-cols-2">
                    <div>
                        <dt class="eyebrow">Priority</dt>
                        <dd class="mt-1.5"><x-status :label="$serviceRequest->priority->label()" :tone="$serviceRequest->priority->tone()" /></dd>
                    </div>
                    <div>
                        <dt class="eyebrow">Submitted</dt>
                        <dd class="mt-1.5 text-[13px]">{{ $serviceRequest->submitted_at?->format('d M Y, H:i') ?? 'Not submitted yet' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="eyebrow">Business justification</dt>
                        <dd class="mt-1.5 whitespace-pre-line text-[13px] leading-relaxed text-ink-soft">{{ $serviceRequest->description ?: 'No justification recorded.' }}</dd>
                    </div>
                </dl>
            </section>

            @php($fields = $serviceRequest->formTemplate?->fields() ?? [])
            @if(count($fields))
                <section class="panel">
                    <div class="panel-head">
                        <div>
                            <p class="eyebrow">Section 02</p>
                            <h2 class="mt-1 text-[14px] font-semibold">Request information</h2>
                        </div>
                    </div>
                    <dl class="grid gap-x-8 gap-y-4 p-4 sm:grid-cols-2">
                        @foreach($fields as $field)
                            @php($key = $field['key'] ?? null)
                            @continue(! $key)
                            @php($value = $serviceRequest->form_data[$key] ?? null)
                            <div class="{{ ($field['type'] ?? 'text') === 'textarea' ? 'sm:col-span-2' : '' }}">
                                <dt class="eyebrow">{{ $field['label'] ?? $key }}</dt>
                                <dd class="mt-1.5 whitespace-pre-line text-[13px] leading-relaxed text-ink-soft">
                                    @if(is_array($value))
                                        {{ implode(', ', $value) ?: '—' }}
                                    @elseif(is_bool($value))
                                        {{ $value ? 'Yes' : 'No' }}
                                    @elseif($value === null || $value === '')
                                        <span class="text-ink-faint">Not provided</span>
                                    @elseif(($field['type'] ?? null) === 'file')
                                        <span class="font-mono text-[11.5px] text-positive">Attached below</span>
                                    @else
                                        {{ $value }}
                                    @endif
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                </section>
            @endif

            <section class="panel">
                <div class="panel-head">
                    <div>
                        <p class="eyebrow">Section 03</p>
                        <h2 class="mt-1 text-[14px] font-semibold">Attachments</h2>
                    </div>
                    <span class="mono text-[11px] text-ink-faint">{{ $serviceRequest->attachments->count() }}</span>
                </div>

                @if($serviceRequest->attachments->isEmpty())
                    <x-empty-state title="No documents attached" description="This request carries no supporting files." />
                @else
                    <ul class="divide-y divide-line">
                        @foreach($serviceRequest->attachments as $attachment)
                            <li class="flex items-center justify-between gap-4 px-4 py-3">
                                <div class="flex min-w-0 items-center gap-2.5">
                                    <span class="mono flex-none border border-line px-1.5 py-0.5 text-[9.5px] text-ink-faint">{{ $attachment->extension() }}</span>
                                    <div class="min-w-0">
                                        <a href="{{ route('requests.attachments.download', [$serviceRequest, $attachment]) }}" class="block truncate text-[12.5px] font-medium underline-offset-2 hover:text-accent hover:underline">{{ $attachment->original_filename }}</a>
                                        <p class="mono mt-0.5 text-[10.5px] text-ink-faint">
                                            {{ $attachment->humanSize() }} · uploaded by {{ $attachment->uploader?->name ?? 'system' }}
                                            @if($attachment->field_key) · field {{ $attachment->field_key }} @endif
                                        </p>
                                    </div>
                                </div>
                                <a href="{{ route('requests.attachments.download', [$serviceRequest, $attachment]) }}" class="btn btn-sm flex-none">Download</a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            @if($instance)
                <section class="panel">
                    <div class="panel-head">
                        <div>
                            <p class="eyebrow">History</p>
                            <h2 class="mt-1 text-[14px] font-semibold">Approval decisions</h2>
                        </div>
                        <span class="mono text-[10.5px] uppercase tracking-[0.08em] text-ink-faint">Workflow v{{ $instance->workflow_version }}</span>
                    </div>
                    @php($decisions = $instance->tasks->flatMap->histories->sortBy('created_at'))
                    @if($decisions->isEmpty())
                        <x-empty-state title="No decisions recorded" description="Approval decisions appear here with the approver, timestamp and note." />
                    @else
                        <ul class="divide-y divide-line">
                            @foreach($decisions as $history)
                                <li class="flex items-start gap-3 px-4 py-3">
                                    <span class="mt-1 grid h-5 w-5 flex-none place-items-center rounded-full border {{ $history->action === 'approved' ? 'border-positive/40 text-positive' : ($history->action === 'rejected' ? 'border-danger/40 text-danger' : 'border-caution/40 text-caution') }}">
                                        <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-[12.5px]">
                                            <span class="font-medium">{{ $history->actor?->name ?? 'System' }}</span>
                                            <span class="text-ink-soft">{{ str_replace('_', ' ', $history->action) }}</span>
                                            <span class="text-ink-faint">· {{ $history->task?->stage?->stage_name }}</span>
                                        </p>
                                        @if($history->comment)
                                            <p class="mt-0.5 text-[12px] leading-relaxed text-ink-soft">{{ $history->comment }}</p>
                                        @endif
                                    </div>
                                    <span class="mono flex-none text-[10.5px] text-ink-faint">{{ $history->created_at->format('d M · H:i') }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            @endif
        </div>

        <aside class="space-y-5 lg:col-span-4">
            <section class="panel">
                <div class="panel-head">
                    <h2 class="text-[13px] font-semibold">Approval route</h2>
                    @if($instance)
                        <span class="mono text-[10.5px] uppercase tracking-[0.08em] text-ink-faint">{{ str_replace('_', ' ', $instance->status->value) }}</span>
                    @endif
                </div>

                @if($instance)
                    @php($tasksByStage = $instance->tasks->keyBy('workflow_stage_id'))
                    <div class="p-4">
                        @foreach($instance->workflowTemplate->stages as $stage)
                            @php($task = $instance->tasks->where('workflow_stage_id', $stage->id)->sortByDesc('id')->first())
                            @php($isCurrent = $instance->current_stage_order === $stage->stage_order && $instance->isRunning())
                            <div class="relative flex gap-3 pb-5 last:pb-0">
                                @if(! $loop->last)
                                    <span class="absolute left-[10px] top-5 h-full w-px bg-line"></span>
                                @endif
                                <span class="mono relative z-10 flex h-5 w-5 flex-none items-center justify-center rounded-full border text-[10px]
                                    {{ $task?->status->value === 'approved' ? 'border-positive bg-positive text-white' : ($isCurrent ? 'border-accent bg-accent text-white' : 'border-line bg-paper text-ink-faint') }}">
                                    {{ $stage->stage_order }}
                                </span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-start justify-between gap-2">
                                        <p class="text-[12.5px] font-medium">{{ $stage->stage_name }}</p>
                                        @if($task)
                                            <x-status :label="$task->status->label()" :tone="$task->status->tone()" />
                                        @endif
                                    </div>
                                    <p class="mt-0.5 text-[11px] text-ink-faint">{{ $stage->approverScopeLabel() }} · {{ $stage->sla_hours }}h SLA</p>
                                    @if($task)
                                        <p class="mt-1 text-[11px] text-ink-soft">
                                            @if($task->status->value === 'pending')
                                                Waiting on {{ $task->approver?->name }}
                                                @if($task->due_at)
                                                    · due {{ $task->due_at->format('d M, H:i') }}
                                                @endif
                                            @else
                                                {{ $task->approver?->name }} · {{ $task->completed_at?->format('d M Y, H:i') }}
                                            @endif
                                        </p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-4">
                        <p class="text-[12px] leading-relaxed text-ink-faint">
                            The approval route opens when the request is submitted. Until then this request has no assigned approvers.
                        </p>
                    </div>
                @endif
            </section>

            @if($currentTask && $canDecide)
                <section class="panel border-accent/30">
                    <div class="panel-head">
                        <div>
                            <p class="eyebrow text-accent">Action required</p>
                            <h2 class="mt-1 text-[13px] font-semibold">Your decision is pending</h2>
                        </div>
                    </div>
                    <div class="p-4">
                        <p class="text-[12px] leading-relaxed text-ink-soft">You are the assigned approver for <span class="font-medium text-ink">{{ $currentTask->stage->stage_name }}</span>.</p>
                        <a href="{{ route('approvals.show', $currentTask) }}" class="btn btn-primary mt-3 w-full">Open decision workspace</a>
                    </div>
                </section>
            @endif

            <section class="panel">
                <div class="panel-head"><h2 class="text-[13px] font-semibold">Record</h2></div>
                <dl class="space-y-2.5 p-4 text-[12px]">
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-ink-soft">Request number</dt>
                        <dd class="mono text-[11px]">{{ $serviceRequest->request_number }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-ink-soft">Request type</dt>
                        <dd class="text-[11.5px] text-right">{{ $serviceRequest->requestType->name }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-ink-soft">Department</dt>
                        <dd class="text-[11.5px] text-right">{{ $serviceRequest->department?->name ?? '—' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-ink-soft">Created</dt>
                        <dd class="mono text-[11px]">{{ $serviceRequest->created_at->format('d M Y, H:i') }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-ink-soft">Last updated</dt>
                        <dd class="mono text-[11px]">{{ $serviceRequest->updated_at->format('d M Y, H:i') }}</dd>
                    </div>
                    @if($serviceRequest->completed_at)
                        <div class="flex items-center justify-between gap-3 border-t border-line pt-2.5">
                            <dt class="text-ink-soft">Closed</dt>
                            <dd class="mono text-[11px]">{{ $serviceRequest->completed_at->format('d M Y, H:i') }}</dd>
                        </div>
                    @endif
                </dl>
            </section>
        </aside>
    </div>

    @if($canCancel)
        <div x-cloak x-show="confirmCancel" class="fixed inset-0 z-50 grid place-items-center bg-rail/50 px-4" @keydown.escape.window="confirmCancel = false">
            <div class="w-full max-w-[440px] border border-line bg-surface shadow-[0_16px_48px_rgba(20,21,24,0.18)]" role="dialog" aria-modal="true" aria-labelledby="cancel-title">
                <div class="border-b border-line px-5 py-3.5">
                    <h2 id="cancel-title" class="text-[14px] font-semibold">Cancel this request?</h2>
                    <p class="mt-1 text-[12px] text-ink-soft">The workflow stops immediately and any pending approval tasks are withdrawn. This cannot be undone.</p>
                </div>
                <form method="POST" action="{{ route('requests.cancel', $serviceRequest) }}" class="p-5">
                    @csrf
                    <label for="reason" class="label">Reason (optional)</label>
                    <textarea id="reason" name="reason" rows="2" class="textarea" placeholder="Explain why this request is no longer needed."></textarea>
                    <div class="mt-4 flex items-center justify-end gap-2">
                        <button type="button" class="btn btn-quiet" @click="confirmCancel = false">Keep request</button>
                        <button type="submit" class="btn btn-danger">Cancel request</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>

</x-layouts.app>