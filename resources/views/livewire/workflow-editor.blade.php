<x-layouts.app :title="$isPublished ? 'Workflow version' : 'Workflow editor'">
    <div class="space-y-6">
        <x-page-header
            :eyebrow="'Workflow management · '.$typeName"
            :title="$templateId ? ($isPublished ? 'Workflow version '.$version : 'Edit approval route') : 'Design an approval route'"
            description="Stages run in sequence. Each stage resolves one active approver and carries its own business-hour SLA."
        >
            <a href="{{ route('workflows.index') }}" class="btn btn-sm">Back to workflows</a>
        </x-page-header>

        @if($locked)
            <div class="flex flex-col gap-3 border border-caution/30 bg-caution-soft px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-[13px] font-medium text-caution">This version is read-only.</p>
                    <p class="mt-0.5 text-[12px] text-ink-soft">
                        Published routes and versions used by live requests are immutable. Clone the route to make changes.
                    </p>
                </div>
                @if($isPublished)
                    <form method="POST" action="{{ route('workflows.version', $templateId) }}" class="flex-none">
                        @csrf
                        <button type="submit" class="btn btn-sm">Create new version</button>
                    </form>
                @endif
            </div>
        @endif

        @error('workflow')
            <div class="border border-danger/30 bg-danger-soft px-4 py-3 text-[12.5px] text-danger">{{ $message }}</div>
        @enderror

        <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_300px]">
            <div class="space-y-5">
                <section class="panel">
                    <div class="panel-head">
                        <div>
                            <p class="eyebrow">Route identity</p>
                            <h2 class="mt-0.5 text-[14px] font-semibold">Name and purpose</h2>
                        </div>
                        @if($templateId)
                            <x-status :label="$isPublished ? 'Published' : 'Draft'" :tone="$isPublished ? 'positive' : 'neutral'" />
                        @endif
                    </div>
                    <div class="grid gap-4 p-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="label" for="workflow-name">Workflow name</label>
                            <input id="workflow-name" type="text" wire:model="name" class="input" @disabled($locked) aria-invalid="@error('name') true @else false @enderror">
                            @error('name') <p class="error">{{ $message }}</p> @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label class="label" for="workflow-description">Description</label>
                            <textarea id="workflow-description" wire:model="description" class="textarea" rows="2" @disabled($locked)></textarea>
                            @error('description') <p class="error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </section>

                <section class="panel">
                    <div class="panel-head">
                        <div>
                            <p class="eyebrow">Approval stages</p>
                            <h2 class="mt-0.5 text-[14px] font-semibold">Sequential route</h2>
                        </div>
                        @unless($locked)
                            <button type="button" class="btn btn-sm" wire:click="addStage">Add stage</button>
                        @endunless
                    </div>

                    @error('stages')
                        <div class="border-b border-line bg-danger-soft px-4 py-2 text-[12px] text-danger">{{ $message }}</div>
                    @enderror

                    <div class="divide-y divide-line">
                        @foreach($stages as $index => $stage)
                            <div class="p-4" wire:key="stage-{{ $index }}">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-2">
                                        <span class="mono grid h-6 w-6 place-items-center rounded-full border border-line-strong bg-paper text-[11px] text-ink-soft">{{ $index + 1 }}</span>
                                        <p class="text-[12.5px] font-medium">Stage {{ $index + 1 }}</p>
                                    </div>
                                    @unless($locked)
                                        <div class="flex items-center gap-1">
                                            <button type="button" class="btn btn-sm btn-quiet" wire:click="moveStage({{ $index }}, -1)" @disabled($index === 0) aria-label="Move stage up">↑</button>
                                            <button type="button" class="btn btn-sm btn-quiet" wire:click="moveStage({{ $index }}, 1)" @disabled($index === count($stages) - 1) aria-label="Move stage down">↓</button>
                                            <button type="button" class="btn btn-sm btn-quiet text-danger" wire:click="removeStage({{ $index }})" aria-label="Remove stage">Remove</button>
                                        </div>
                                    @endunless
                                </div>

                                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                    <div>
                                        <label class="label">Stage name</label>
                                        <input type="text" wire:model="stages.{{ $index }}.stage_name" class="input" @disabled($locked)>
                                        @error("stages.{$index}.stage_name") <p class="error">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="label">Approver role</label>
                                        <select wire:model="stages.{{ $index }}.approver_role_id" class="select" @disabled($locked)>
                                            @foreach($roles as $role)
                                                <option value="{{ $role->id }}">{{ $role->label }}</option>
                                            @endforeach
                                        </select>
                                        @error("stages.{$index}.approver_role_id") <p class="error">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="label">Who is eligible</label>
                                        <select wire:model.live="stages.{{ $index }}.approver_scope" class="select" @disabled($locked)>
                                            @foreach($scopeOptions as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error("stages.{$index}.approver_scope") <p class="error">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="label">SLA (business hours)</label>
                                        <input type="number" min="1" max="720" wire:model="stages.{{ $index }}.sla_hours" class="input" @disabled($locked)>
                                        @error("stages.{$index}.sla_hours") <p class="error">{{ $message }}</p> @enderror
                                    </div>
                                    @if($stage['approver_scope'] === 'specific_user')
                                        <div class="sm:col-span-2">
                                            <label class="label">Named approver</label>
                                            <select wire:model="stages.{{ $index }}.specific_user_id" class="select" @disabled($locked)>
                                                <option value="">Choose a person</option>
                                                @foreach($users as $user)
                                                    <option value="{{ $user->id }}">{{ $user->name }} · {{ $user->department?->name ?? 'No department' }}</option>
                                                @endforeach
                                            </select>
                                            @error("stages.{$index}.specific_user_id") <p class="error">{{ $message }}</p> @enderror
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            </div>

            <aside class="space-y-4">
                <section class="panel p-4">
                    <p class="eyebrow">Route summary</p>
                    <dl class="mt-3 space-y-2.5 text-[12px]">
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-ink-faint">Request type</dt>
                            <dd class="mono text-[11px] uppercase tracking-[0.06em]">{{ $typeName }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-ink-faint">Version</dt>
                            <dd class="mono text-[11px]">{{ $version ? 'v'.$version : 'Next' }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-ink-faint">Stages</dt>
                            <dd class="mono text-[11px]">{{ count($stages) }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-ink-faint">Total SLA</dt>
                            <dd class="mono text-[11px]">{{ array_sum(array_column($stages, 'sla_hours')) }}h</dd>
                        </div>
                    </dl>

                    <div class="mt-4 space-y-2">
                        @unless($locked)
                            <button type="button" class="btn w-full" wire:click="saveDraft" wire:loading.attr="disabled" wire:target="saveDraft,publish">
                                <span wire:loading.remove wire:target="saveDraft">Save draft</span>
                                <span wire:loading wire:target="saveDraft">Saving…</span>
                            </button>
                            <button
                                type="button"
                                class="btn btn-primary w-full"
                                wire:click="publish"
                                wire:loading.attr="disabled"
                                wire:target="saveDraft,publish"
                                wire:confirm="Publish this version? It becomes the active route for new {{ strtolower($typeName) }} requests. Requests already in flight keep their pinned version."
                            >
                                <span wire:loading.remove wire:target="publish">Publish version</span>
                                <span wire:loading wire:target="publish">Publishing…</span>
                            </button>
                        @else
                            <p class="text-[11.5px] leading-relaxed text-ink-faint">
                                This version cannot be edited. Use “Create new version” to work on a copy.
                            </p>
                        @endunless
                    </div>
                </section>

                <section class="border border-line bg-paper-deep/40 p-4">
                    <p class="eyebrow">How resolution works</p>
                    <ul class="mt-2 space-y-2 text-[11.5px] leading-relaxed text-ink-soft">
                        <li><span class="font-medium text-ink">Department scope</span> picks an active holder of the role inside the requester’s department.</li>
                        <li><span class="font-medium text-ink">Global scope</span> picks any active holder of the role, excluding the requester when there is more than one candidate.</li>
                        <li><span class="font-medium text-ink">Named user</span> sends the stage to one specific person, provided the account is active.</li>
                    </ul>
                </section>
            </aside>
        </div>
    </div>
</x-layouts.app>
