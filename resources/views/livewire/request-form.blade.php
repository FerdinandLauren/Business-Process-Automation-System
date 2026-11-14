<div class="space-y-6">
    <x-page-header
        :eyebrow="'Request center · ' . ($type?->name ?? 'Request')"
        :title="$requestId ? 'Edit request' : 'New ' . strtolower($type?->name ?? 'request')"
        :description="$requestId ? 'Update the draft or revised request. Submitted requests cannot be edited until an approver requests a revision.' : 'Complete the form and attach the supporting documents. Saving a draft does not start the approval workflow.'"
    >
        <a href="{{ route('requests.index') }}" class="btn btn-quiet">Cancel</a>
    </x-page-header>

    @if($errors->any())
        <div class="border border-danger/25 bg-danger-soft px-4 py-3" role="alert">
            <p class="text-[12.5px] font-medium text-danger">The request could not be saved.</p>
            <ul class="mt-1.5 space-y-0.5 text-[12px] text-danger/90">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form wire:submit="submitRequest" class="grid gap-6 lg:grid-cols-12">
        <div class="space-y-5 lg:col-span-8">
            <section class="panel">
                <div class="panel-head">
                    <div>
                        <p class="eyebrow">Section 01</p>
                        <h2 class="mt-1 text-[14px] font-semibold">Request details</h2>
                    </div>
                    @if($requestId)
                        <span class="mono text-[11px] text-ink-faint">{{ $request?->request_number }}</span>
                    @endif
                </div>

                <div class="space-y-4 p-4">
                    <div>
                        <label for="title" class="label">Request title</label>
                        <input id="title" type="text" wire:model="title" class="input" placeholder="e.g. Client visit travel reimbursement — Bandung, 12 November" aria-invalid="@error('title') true @else false @enderror">
                        @error('title')<p class="error">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="description" class="label">Business justification</label>
                        <textarea id="description" wire:model="description" rows="3" class="textarea" placeholder="Explain what this request covers and why it is needed."></textarea>
                        <p class="hint">Visible to every approver in the workflow.</p>
                        @error('description')<p class="error">{{ $message }}</p>@enderror
                    </div>

                    <div class="max-w-[240px]">
                        <label for="priority" class="label">Priority</label>
                        <select id="priority" wire:model="priority" class="select">
                            @foreach(\App\Enums\Priority::cases() as $option)
                                <option value="{{ $option->value }}">{{ $option->label() }}</option>
                            @endforeach
                        </select>
                        <p class="hint">Urgency for the approver's queue, not an SLA override.</p>
                        @error('priority')<p class="error">{{ $message }}</p>@enderror
                    </div>
                </div>
            </section>

            @if(count($fields))
                <section class="panel">
                    <div class="panel-head">
                        <div>
                            <p class="eyebrow">Section 02</p>
                            <h2 class="mt-1 text-[14px] font-semibold">Request information</h2>
                        </div>
                        <span class="font-mono text-[10.5px] uppercase tracking-[0.08em] text-ink-faint">Form v{{ $request?->formTemplate?->version ?? $type?->activeFormTemplate()?->version }}</span>
                    </div>

                    <div class="grid gap-4 p-4 sm:grid-cols-2">
                        @foreach($fields as $field)
                            @php
                                $key = $field['key'] ?? null;
                                $fieldType = $field['type'] ?? 'text';
                                $required = $field['required'] ?? false;
                                $options = $field['options'] ?? [];
                                $wide = in_array($fieldType, ['textarea', 'checkbox', 'radio'], true);
                            @endphp
                            @continue(! $key)

                            <div class="{{ $wide ? 'sm:col-span-2' : '' }}">
                                <label class="label" for="field-{{ $key }}">
                                    {{ $field['label'] ?? $key }}
                                    @if($required)<span class="text-danger">*</span>@endif
                                </label>

                                @switch($fieldType)
                                    @case('textarea')
                                        <textarea id="field-{{ $key }}" wire:model="formData.{{ $key }}" rows="3" class="textarea" placeholder="{{ $field['placeholder'] ?? '' }}"></textarea>
                                        @break

                                    @case('number')
                                        <input id="field-{{ $key }}" type="number" step="any" wire:model="formData.{{ $key }}" class="input" placeholder="{{ $field['placeholder'] ?? '' }}">
                                        @break

                                    @case('date')
                                        <input id="field-{{ $key }}" type="date" wire:model="formData.{{ $key }}" class="input">
                                        @break

                                    @case('dropdown')
                                        <select id="field-{{ $key }}" wire:model="formData.{{ $key }}" class="select">
                                            <option value="">Select an option</option>
                                            @foreach($options as $option)
                                                <option value="{{ is_array($option) ? $option['value'] : $option }}">{{ is_array($option) ? $option['value'] : $option }}</option>
                                            @endforeach
                                        </select>
                                        @break

                                    @case('radio')
                                        <div class="flex flex-wrap gap-x-5 gap-y-2 pt-1">
                                            @foreach($options as $option)
                                                @php($optionValue = is_array($option) ? $option['value'] : $option)
                                                <label class="flex items-center gap-2 text-[13px]">
                                                    <input type="radio" wire:model="formData.{{ $key }}" value="{{ $optionValue }}" class="h-3.5 w-3.5 border-line-strong text-accent focus:ring-accent">
                                                    {{ $optionValue }}
                                                </label>
                                            @endforeach
                                        </div>
                                        @break

                                    @case('checkbox')
                                        <div class="flex flex-wrap gap-x-5 gap-y-2 pt-1">
                                            @foreach($options as $option)
                                                @php($optionValue = is_array($option) ? $option['value'] : $option)
                                                <label class="flex items-center gap-2 text-[13px]">
                                                    <input type="checkbox" wire:model="formData.{{ $key }}" value="{{ $optionValue }}" class="h-3.5 w-3.5 rounded-[2px] border-line-strong text-accent focus:ring-accent">
                                                    {{ $optionValue }}
                                                </label>
                                            @endforeach
                                        </div>
                                        @break

                                    @case('file')
                                        <input id="field-{{ $key }}" type="file" wire:model="fieldFiles.{{ $key }}" class="block w-full text-[12.5px] text-ink-soft file:mr-3 file:border-0 file:bg-ink file:px-3 file:py-2 file:text-[12px] file:font-medium file:text-paper">
                                        <p class="hint">PDF, JPG, PNG, DOCX, XLSX or CSV. Maximum {{ round($maxKb / 1024, 1) }} MB.</p>
                                        <div wire:loading wire:target="fieldFiles.{{ $key }}" class="hint">Uploading…</div>
                                        @foreach($attachments->where('field_key', $key) as $existing)
                                            <p class="mt-1.5 flex items-center gap-1.5 font-mono text-[11px] text-positive">
                                                <span class="h-1 w-1 rounded-full bg-positive"></span> {{ $existing->original_filename }}
                                            </p>
                                        @endforeach
                                        @break

                                    @default
                                        <input id="field-{{ $key }}" type="text" wire:model="formData.{{ $key }}" class="input" placeholder="{{ $field['placeholder'] ?? '' }}">
                                @endswitch

                                @if(! empty($field['help']))
                                    <p class="hint">{{ $field['help'] }}</p>
                                @endif
                                @error('formData.'.$key)<p class="error">{{ $message }}</p>@enderror
                                @error('fieldFiles.'.$key)<p class="error">{{ $message }}</p>@enderror
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            <section class="panel">
                <div class="panel-head">
                    <div>
                        <p class="eyebrow">Section 03</p>
                        <h2 class="mt-1 text-[14px] font-semibold">Supporting documents</h2>
                    </div>
                    <span class="text-[11px] text-ink-faint">Optional unless a field above requires a file</span>
                </div>

                <div class="p-4">
                    <label for="attachments" class="label">Additional attachments</label>
                    <input id="attachments" type="file" wire:model="files" multiple class="block w-full text-[12.5px] text-ink-soft file:mr-3 file:border-0 file:bg-ink file:px-3 file:py-2 file:text-[12px] file:font-medium file:text-paper">
                    <p class="hint">Stored privately. Only the requester, assigned approvers and administrators with permission can download.</p>
                    <div wire:loading wire:target="files" class="hint">Uploading…</div>

                    @if(count($files))
                        <ul class="mt-2 space-y-1">
                            @foreach($files as $file)
                                <li class="font-mono text-[11px] text-ink-soft">{{ $file->getClientOriginalName() }}</li>
                            @endforeach
                        </ul>
                    @endif

                    @error('files')<p class="error">{{ $message }}</p>@enderror

                    @if($attachments->isNotEmpty())
                        <div class="mt-4 border-t border-line pt-3">
                            <p class="eyebrow">Attached</p>
                            <ul class="mt-2 divide-y divide-line">
                                @foreach($attachments as $attachment)
                                    <li class="flex items-center justify-between gap-3 py-2">
                                        <div class="flex min-w-0 items-center gap-2">
                                            <span class="mono flex-none border border-line px-1 py-0.5 text-[9.5px] text-ink-faint">{{ $attachment->extension() }}</span>
                                            <a href="{{ route('requests.attachments.download', [$requestId, $attachment]) }}" class="truncate text-[12.5px] text-ink-soft underline-offset-2 hover:text-accent hover:underline">{{ $attachment->original_filename }}</a>
                                            <span class="mono flex-none text-[10.5px] text-ink-faint">{{ $attachment->humanSize() }}</span>
                                        </div>
                                        <button type="button" wire:click="removeAttachment({{ $attachment->id }})" wire:confirm="Remove this attachment?" class="text-[11.5px] text-danger hover:underline">Remove</button>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </section>

            <div class="flex flex-col-reverse items-stretch gap-2 border-t border-line pt-4 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-[11.5px] text-ink-faint">Submitting locks the request and starts the approval workflow.</p>
                <div class="flex items-center gap-2">
                    <button type="button" wire:click="saveDraft" class="btn" wire:loading.attr="disabled" wire:target="saveDraft">Save draft</button>
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="submitRequest">
                        <span wire:loading.remove wire:target="submitRequest">Submit for approval</span>
                        <span wire:loading wire:target="submitRequest">Submitting…</span>
                    </button>
                </div>
            </div>
        </div>

        <aside class="space-y-4 lg:col-span-4">
            <section class="panel">
                <div class="panel-head">
                    <h2 class="text-[13px] font-semibold">Approval route</h2>
                    @if($stages->isNotEmpty())
                        <span class="mono text-[10.5px] uppercase tracking-[0.08em] text-ink-faint">v{{ $stages->first()->workflowTemplate?->version }}</span>
                    @endif
                </div>
                <div class="p-4">
                    @forelse($stages as $stage)
                        <div class="relative flex gap-3 pb-4 last:pb-0">
                            @if(! $loop->last)
                                <span class="absolute left-[10px] top-5 h-full w-px bg-line"></span>
                            @endif
                            <span class="mono flex h-5 w-5 flex-none items-center justify-center rounded-full border border-line bg-paper text-[10px] text-ink-faint">{{ $stage->stage_order }}</span>
                            <div class="min-w-0">
                                <p class="text-[12.5px] font-medium">{{ $stage->stage_name }}</p>
                                <p class="mt-0.5 text-[11px] text-ink-faint">
                                    {{ $stage->approverScopeLabel() }} · {{ $stage->sla_hours }}h SLA
                                </p>
                            </div>
                        </div>
                    @empty
                        <p class="text-[12px] text-ink-faint">No active workflow is configured. Ask a process administrator to publish one before submitting.</p>
                    @endforelse
                </div>
            </section>

            <section class="panel p-4">
                <p class="eyebrow">Before you submit</p>
                <ul class="mt-2.5 space-y-2 text-[12px] leading-relaxed text-ink-soft">
                    <li class="flex gap-2"><span class="mt-1.5 h-1 w-1 flex-none rounded-full bg-line-strong"></span> Required fields are validated on the server before the request enters the workflow.</li>
                    <li class="flex gap-2"><span class="mt-1.5 h-1 w-1 flex-none rounded-full bg-line-strong"></span> The request number is allocated immediately and stays with the record permanently.</li>
                    <li class="flex gap-2"><span class="mt-1.5 h-1 w-1 flex-none rounded-full bg-line-strong"></span> Each approver in the route receives a notification with an SLA deadline.</li>
                </ul>
            </section>
        </aside>
    </form>
</div>
