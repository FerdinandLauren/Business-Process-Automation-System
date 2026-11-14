<div class="space-y-6">
    <div>
        <a href="{{ route('approvals.index') }}" class="inline-flex items-center gap-1.5 text-[11.5px] font-medium text-ink-faint transition hover:text-ink">
            <svg class="h-3 w-3" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M7.5 2.5L4 6l3.5 3.5"/></svg>
            Approval inbox
        </a>

        <div class="mt-3 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="mono text-[11px] uppercase tracking-[0.1em] text-ink-faint">{{ $serviceRequest->request_number }}</span>
                    <span class="h-1 w-1 rounded-full bg-line-strong"></span>
                    <span class="text-[11.5px] text-ink-soft">{{ $serviceRequest->requestType->name }}</span>
                    <x-status :label="$serviceRequest->status->label()" :tone="$serviceRequest->status->tone()" />
                    @if($task->isOverdue())
                        <span class="mono border border-danger/30 bg-danger-soft px-1.5 py-0.5 text-[10px] uppercase tracking-[0.08em] text-danger">Overdue</span>
                    @endif
                </div>
                <h1 class="mt-2 max-w-[820px] text-[24px] font-semibold leading-tight tracking-[-0.02em]">{{ $serviceRequest->title }}</h1>
                <p class="mt-1.5 text-[12.5px] text-ink-soft">
                    {{ $serviceRequest->requester->name }} · {{ $serviceRequest->department?->name ?? 'No department' }}
                    · submitted {{ $serviceRequest->submitted_at?->format('d M Y, H:i') ?? '—' }}
                </p>
            </div>

            <div class="flex flex-none items-center gap-2">
                <a href="{{ route('requests.show', $serviceRequest) }}" class="btn">Full request record</a>
            </div>
        </div>
    </div>

    @error('decision')
        <div class="border border-danger/25 bg-danger-soft px-4 py-3 text-[12.5px] text-danger" role="alert">{{ $message }}</div>
    @enderror

    <div class="grid gap-6 lg:grid-cols-12">
        <div class="space-y-5 lg:col-span-7">
            <section class="panel">
                <div class="panel-head">
                    <div>
                        <p class="eyebrow">Decision context</p>
                        <h2 class="mt-1 text-[14px] font-semibold">What you are approving</h2>
                    </div>
                    <x-status :label="$serviceRequest->priority->label()" :tone="$serviceRequest->priority->tone()" />
                </div>
                <dl class="grid gap-x-8 gap-y-4 p-4 sm:grid-cols-2">
                    <div>
                        <dt class="eyebrow">Requester</dt>
                        <dd class="mt-1.5 text-[13px]">{{ $serviceRequest->requester->name }}</dd>
                        <dd class="text-[11px] text-ink-faint">{{ $serviceRequest->requester->job_title }}</dd>
                    </div>
                    <div>
                        <dt class="eyebrow">Department</dt>
                        <dd class="mt-1.5 text-[13px]">{{ $serviceRequest->department?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="eyebrow">Current stage</dt>
                        <dd class="mt-1.5 text-[13px]">{{ $task->stage->stage_name }}</dd>
                        <dd class="text-[11px] text-ink-faint">{{ $task->stage->approverScopeLabel() }}</dd>
                    </div>
                    <div>
                        <dt class="eyebrow">SLA deadline</dt>
                        <dd class="mt-1.5 text-[13px] {{ $task->isOverdue() ? 'text-danger' : '' }}">
                            {{ $task->due_at?->format('d M Y, H:i') ?? 'Not configured' }}
                        </dd>
                        @if($task->due_at && $task->status->value === 'pending')
                            <dd class="text-[11px] {{ $task->isOverdue() ? 'text-danger' : 'text-ink-faint' }}">
                                {{ $task->isOverdue() ? abs((int) round($task->hoursRemaining())).' hours past deadline' : $task->hoursRemaining().' business hours remaining' }}
                            </dd>
                        @endif
                        @if($task->breached_at)
                            <dd class="mono mt-1 text-[10px] uppercase tracking-[0.06em] text-danger">
                                SLA breach recorded {{ $task->breached_at->format('d M Y, H:i') }}
                            </dd>
                        @endif
                    </div>
                    <div class="sm:col-span-2 border-t border-line pt-4">
                        <dt class="eyebrow">Business justification</dt>
                        <dd class="mt-1.5 whitespace-pre-line text-[13px] leading-relaxed text-ink-soft">{{ $serviceRequest->description ?: 'No justification recorded.' }}</dd>
                    </div>
                </dl>
            </section>

            @if(count($fields))
                <section class="panel">
                    <div class="panel-head">
                        <div>
                            <p class="eyebrow">Request information</p>
                            <h2 class="mt-1 text-[14px] font-semibold">Submitted form</h2>
                        </div>
                        <span class="mono text-[10.5px] uppercase tracking-[0.08em] text-ink-faint">Form v{{ $serviceRequest->formTemplate?->version ?? '—' }}</span>
                    </div>
                    <dl class="grid gap-x-8 gap-y-4 p-4 sm:grid-cols-2">
                        @foreach($fields as $field)
                            @php
                                $key = $field['key'] ?? null;
                            @endphp
                            @continue(! $key)
                            @php
                                $value = $serviceRequest->form_data[$key] ?? null;
                            @endphp
                            <div class="{{ ($field['type'] ?? 'text') === 'textarea' ? 'sm:col-span-2' : '' }}">
                                <dt class="eyebrow">{{ $field['label'] ?? $key }}</dt>
                                <dd class="mt-1.5 whitespace-pre-line text-[13px] leading-relaxed text-ink-soft">
                                    @if(is_array($value))
                                        {{ implode(', ', $value) ?: '—' }}
                                    @elseif($value === null || $value === '')
                                        <span class="text-ink-faint">Not provided</span>
                                    @elseif(($field['type'] ?? null) === 'file')
                                        <span class="font-mono text-[11.5px] text-positive">Attached</span>
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
                        <p class="eyebrow">Evidence</p>
                        <h2 class="mt-1 text-[14px] font-semibold">Supporting documents</h2>
                    </div>
                    <span class="mono text-[11px] text-ink-faint">{{ $serviceRequest->attachments->count() }}</span>
                </div>
                @if($serviceRequest->attachments->isEmpty())
                    <x-empty-state title="No documents attached" description="The requester submitted this request without supporting files." />
                @else
                    <ul class="divide-y divide-line">
                        @foreach($serviceRequest->attachments as $attachment)
                            <li class="flex items-center justify-between gap-4 px-4 py-3">
                                <div class="flex min-w-0 items-center gap-2.5">
                                    <span class="mono flex-none border border-line px-1.5 py-0.5 text-[9.5px] text-ink-faint">{{ $attachment->extension() }}</span>
                                    <div class="min-w-0">
                                        <p class="truncate text-[12.5px]">{{ $attachment->original_filename }}</p>
                                        <p class="mono text-[10.5px] text-ink-faint">{{ $attachment->humanSize() }}</p>
                                    </div>
                                </div>
                                <a href="{{ route('requests.attachments.download', [$serviceRequest, $attachment]) }}" class="btn btn-sm flex-none">Download</a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section class="panel">
                <div class="panel-head">
                    <div>
                        <p class="eyebrow">History</p>
                        <h2 class="mt-1 text-[14px] font-semibold">Decisions so far</h2>
                    </div>
                </div>
                @if($decisions->isEmpty())
                    <div class="px-4 py-6 text-[12px] text-ink-faint">You are the first approver in this workflow.</div>
                @else
                    <ul class="divide-y divide-line">
                        @foreach($decisions as $history)
                            <li class="flex items-start gap-3 px-4 py-3">
                                <span class="mt-1 h-1.5 w-1.5 flex-none rounded-full {{ $history->action === 'approved' ? 'bg-positive' : ($history->action === 'rejected' ? 'bg-danger' : 'bg-caution') }}"></span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-[12.5px]">
                                        <span class="font-medium">{{ $history->actor?->name ?? 'System' }}</span>
                                        <span class="text-ink-soft">{{ str_replace('_', ' ', $history->action) }}</span>
                                        <span class="text-ink-faint">· {{ $history->task?->stage?->stage_name }}</span>
                                    </p>
                                    @if($history->comment)<p class="mt-0.5 text-[12px] leading-relaxed text-ink-soft">{{ $history->comment }}</p>@endif
                                </div>
                                <span class="mono flex-none text-[10.5px] text-ink-faint">{{ $history->created_at->format('d M · H:i') }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>

        <aside class="space-y-5 lg:col-span-5">
            <section @class(['panel', 'border-accent/40' => $canDecide])>
                <div class="panel-head">
                    <div>
                        <p class="eyebrow {{ $canDecide ? 'text-accent' : '' }}">{{ $canDecide ? 'Action required' : 'Decision recorded' }}</p>
                        <h2 class="mt-1 text-[14px] font-semibold">{{ $task->stage->stage_name }}</h2>
                    </div>
                    <x-status :label="$task->status->label()" :tone="$task->status->tone()" />
                </div>

                <div class="p-4">
                    @if($canDecide)
                        <p class="text-[12.5px] leading-relaxed text-ink-soft">Choose an outcome. Approval moves the request to the next stage; rejection stops the workflow; a revision returns it to the requester and resumes here afterwards.</p>
                        <div class="mt-4 space-y-2">
                            <button type="button" wire:click="openDecision('approve')" class="btn btn-primary w-full">Approve</button>
                            <button type="button" wire:click="openDecision('revision')" class="btn w-full">Request revision</button>
                            <button type="button" wire:click="openDecision('reject')" class="btn btn-danger w-full">Reject</button>
                        </div>
                        <p class="mt-3 text-[11px] leading-relaxed text-ink-faint">Every decision is timestamped, attributed to you and written to the audit trail. Rejection and revision require a written reason.</p>
                    @elseif($task->isPending() && ! $canDecide)
                        <p class="text-[12.5px] leading-relaxed text-ink-soft">This task is waiting for {{ $task->approver?->name }}. You can read the record but cannot decide on their behalf.</p>
                    @else
                        <p class="text-[12.5px] leading-relaxed text-ink-soft">You {{ str_replace('_', ' ', $task->status->value) }} this task on {{ $task->completed_at?->format('d M Y, H:i') }}.</p>
                        @php
                            $myHistory = $task->histories->last();
                        @endphp
                        @if($myHistory?->comment)
                            <div class="mt-3 border-l-2 border-line-strong pl-3">
                                <p class="eyebrow">Your note</p>
                                <p class="mt-1 text-[12px] leading-relaxed text-ink-soft">{{ $myHistory->comment }}</p>
                            </div>
                        @endif
                    @endif
                </div>
            </section>

            <section class="panel">
                <div class="panel-head">
                    <h2 class="text-[13px] font-semibold">Workflow route</h2>
                    <span class="mono text-[10.5px] uppercase tracking-[0.08em] text-ink-faint">v{{ $instance->workflow_version }}</span>
                </div>
                <div class="p-4">
                    @foreach($instance->workflowTemplate->stages as $stage)
                        @php
                            $stageTask = $instance->tasks->where('workflow_stage_id', $stage->id)->sortByDesc('id')->first();
                            $isCurrent = $instance->current_stage_order === $stage->stage_order && $instance->isRunning();
                        @endphp
                        <div class="relative flex gap-3 pb-5 last:pb-0">
                            @if(! $loop->last)<span class="absolute left-[10px] top-5 h-full w-px bg-line"></span>@endif
                            <span class="mono relative z-10 flex h-5 w-5 flex-none items-center justify-center rounded-full border text-[10px]
                                {{ $stageTask?->status->value === 'approved' ? 'border-positive bg-positive text-white' : ($isCurrent ? 'border-accent bg-accent text-white' : 'border-line bg-paper text-ink-faint') }}">
                                {{ $stage->stage_order }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-2">
                                    <p class="text-[12.5px] font-medium">{{ $stage->stage_name }}</p>
                                    @if($stageTask)<x-status :label="$stageTask->status->label()" :tone="$stageTask->status->tone()" />@endif
                                </div>
                                <p class="mt-0.5 text-[11px] text-ink-faint">{{ $stage->approverScopeLabel() }} · {{ $stage->sla_hours }}h</p>
                                @if($stageTask?->approver)
                                    <p class="mt-0.5 text-[11px] text-ink-soft">{{ $stageTask->approver->name }}</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        </aside>
    </div>

    @if($pendingAction !== null)
        @php
            $copy = [
                'approve' => ['label' => 'Approve request', 'button' => 'Confirm approval', 'tone' => 'btn-primary', 'body' => 'This records your approval and moves the request to the next stage, or completes the workflow if this is the final stage.'],
                'reject' => ['label' => 'Reject request', 'button' => 'Confirm rejection', 'tone' => 'btn-danger', 'body' => 'This stops the workflow immediately. The requester is notified and the request cannot be revived without a new submission.'],
                'revision' => ['label' => 'Request revision', 'button' => 'Send back for revision', 'tone' => 'btn-primary', 'body' => 'The request returns to the requester with your instructions. The workflow resumes at this stage after resubmission.'],
            ][$pendingAction];
        @endphp
        <div class="fixed inset-0 z-50 grid place-items-center bg-rail/50 px-4" @keydown.escape.window="$wire.closeDecision()">
            <div class="w-full max-w-[480px] border border-line bg-surface shadow-[0_16px_48px_rgba(20,21,24,0.18)]" role="dialog" aria-modal="true" aria-labelledby="decision-title">
                <div class="border-b border-line px-5 py-3.5">
                    <p class="eyebrow">{{ $serviceRequest->request_number }}</p>
                    <h2 id="decision-title" class="mt-1 text-[15px] font-semibold">{{ $copy['label'] }}</h2>
                    <p class="mt-1 text-[12px] leading-relaxed text-ink-soft">{{ $copy['body'] }}</p>
                </div>
                <form wire:submit="confirmDecision" class="p-5">
                    <label for="decision-comment" class="label">
                        Note {{ $pendingAction === 'approve' ? '(optional)' : '(required)' }}
                    </label>
                    <textarea
                        id="decision-comment"
                        wire:model="comment"
                        rows="3"
                        class="textarea"
                        placeholder="{{ $pendingAction === 'approve' ? 'Add context for the audit trail.' : 'Explain the reason clearly for the requester.' }}"
                        aria-invalid="@error('comment') true @else false @enderror"
                    ></textarea>
                    @error('comment')<p class="error">{{ $message }}</p>@enderror
                    <p class="hint">Minimum 5 characters is required for rejection and revision.</p>

                    <div class="mt-4 flex items-center justify-between gap-3">
                        <button type="button" wire:click="closeDecision" class="btn btn-quiet">Cancel</button>
                        <button type="submit" class="btn {{ $copy['tone'] }}" wire:loading.attr="disabled" wire:target="confirmDecision">
                            <span wire:loading.remove wire:target="confirmDecision">{{ $copy['button'] }}</span>
                            <span wire:loading wire:target="confirmDecision">Recording…</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
