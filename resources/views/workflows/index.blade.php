<x-layouts.app title="Workflow management">
    <div class="space-y-6">
        <x-page-header
            eyebrow="Operations"
            title="Workflow management"
            description="Versioned approval routes for every request type. Publishing a new version never changes a request that is already in flight."
        />

        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <x-stat label="Request types" :value="number_format($types->count())" meta="Configured in the catalogue" />
            <x-stat label="Active routes" :value="number_format($activeCount)" meta="Ready for new submissions" tone="positive" />
            <x-stat label="Needs attention" :value="number_format($missingCount)" :meta="$missingCount > 0 ? 'No active approval route' : 'Every type is covered'" :tone="$missingCount > 0 ? 'caution' : 'default'" />
            <x-stat label="Versions retained" :value="number_format($types->sum(fn ($type) => $type->workflowTemplates->count()))" meta="Immutable history" />
        </div>

        @if($types->isEmpty())
            <x-empty-state
                title="No request types yet"
                description="Create a request type before designing its approval route."
            />
        @else
            <div class="space-y-4">
                @foreach($types as $type)
                    @php
                        $active = $type->workflowTemplates->firstWhere('is_active', true);
                        $drafts = $type->workflowTemplates->filter(fn ($template) => $template->published_at === null);
                        $form = $type->formTemplates->firstWhere('is_active', true);
                    @endphp

                    <section class="panel">
                        <div class="panel-head flex-wrap">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="text-[15px] font-semibold">{{ $type->name }}</h2>
                                    <span class="mono rounded-[2px] border border-line bg-paper px-1.5 py-0.5 text-[10px] uppercase tracking-[0.08em] text-ink-faint">{{ $type->code }}</span>
                                    @if($active)
                                        <x-status :label="'v'.$active->version.' active'" tone="positive" />
                                    @else
                                        <x-status label="No active route" tone="caution" />
                                    @endif
                                    @if($form)
                                        <span class="mono text-[10.5px] text-ink-faint">Form v{{ $form->version }}</span>
                                    @endif
                                </div>
                                <p class="mt-1 max-w-[720px] text-[12.5px] text-ink-soft">{{ $type->description }}</p>
                            </div>

                            <div class="flex flex-none flex-wrap items-center gap-2">
                                @if($active)
                                    <a href="{{ route('workflows.edit', $active) }}" class="btn btn-sm">View v{{ $active->version }}</a>
                                    <form method="POST" action="{{ route('workflows.version', $active) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-primary">New version</button>
                                    </form>
                                @else
                                    <a href="{{ route('workflows.create', $type) }}" class="btn btn-sm btn-primary">Create route</a>
                                @endif
                            </div>
                        </div>

                        <div class="px-4 py-4">
                            @if($active && $active->stages_count > 0)
                                <ol class="flex flex-wrap items-stretch gap-2">
                                    @foreach($active->stages()->with(['approverRole', 'specificUser'])->get() as $stage)
                                        <li class="flex min-w-[190px] flex-1 items-start gap-3 border border-line bg-paper px-3 py-2.5">
                                            <span class="mono mt-0.5 grid h-5 w-5 flex-none place-items-center rounded-full border border-line-strong bg-surface text-[10px] text-ink-soft">{{ $stage->stage_order }}</span>
                                            <div class="min-w-0">
                                                <p class="truncate text-[12.5px] font-medium">{{ $stage->stage_name }}</p>
                                                <p class="mt-0.5 text-[11px] text-ink-faint">{{ $stage->approverScopeLabel() }}</p>
                                                <p class="mono mt-1 text-[10px] uppercase tracking-[0.06em] text-ink-faint">{{ $stage->sla_hours }}h SLA</p>
                                            </div>
                                        </li>
                                    @endforeach
                                </ol>
                            @else
                                <p class="text-[12.5px] text-ink-soft">
                                    No published stages. Create a route to define who approves this request type.
                                </p>
                            @endif

                            @if($drafts->isNotEmpty())
                                <div class="mt-4 border-t border-line pt-3">
                                    <p class="eyebrow">Unpublished drafts</p>
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        @foreach($drafts as $draft)
                                            <a href="{{ route('workflows.edit', $draft) }}" class="inline-flex items-center gap-2 border border-line bg-surface px-2.5 py-1.5 text-[11.5px] text-ink-soft transition hover:border-line-strong hover:text-ink">
                                                <span class="mono text-[10px] uppercase tracking-[0.08em] text-ink-faint">v{{ $draft->version }}</span>
                                                Draft · {{ $draft->stages_count }} {{ Str::plural('stage', $draft->stages_count) }}
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </section>
                @endforeach
            </div>
        @endif
    </div>
</x-layouts.app>
