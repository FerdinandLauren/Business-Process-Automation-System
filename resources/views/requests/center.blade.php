<x-layouts.app title="Request Center">
<div class="space-y-6">
    <x-page-header
        eyebrow="Request center"
        title="Start a request"
        description="Choose the administrative process you need. Every type carries its own form, approval route and SLA."
    />

    <div class="grid gap-6 lg:grid-cols-12">
        <div class="lg:col-span-8">
            @if($types->isEmpty())
                <div class="panel">
                    <x-empty-state
                        title="No request types are available"
                        description="A process administrator must publish an active form and workflow before requests can be raised."
                    />
                </div>
            @else
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach($types as $type)
                        <a href="{{ route('requests.create', $type) }}"
                           class="group panel flex flex-col p-4 transition-colors duration-150 hover:border-ink-faint">
                            <div class="flex items-start justify-between gap-3">
                                <span class="mono text-[10px] uppercase tracking-[0.12em] text-ink-faint">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                <span class="mono text-[10px] uppercase tracking-[0.08em] text-ink-faint">{{ $type->code }}</span>
                            </div>

                            <h2 class="mt-6 text-[15px] font-semibold leading-snug">{{ $type->name }}</h2>
                            <p class="mt-1.5 line-clamp-2 text-[12px] leading-relaxed text-ink-soft">{{ $type->description }}</p>

                            @php
                                $stageCount = $type->activeWorkflowTemplate()?->stages->count() ?? 0;
                            @endphp
                            <div class="mt-4 flex items-center justify-between border-t border-line pt-3">
                                <span class="mono text-[10.5px] text-ink-faint">
                                    {{ $stageCount }} {{ str('stage')->plural($stageCount) }} ·
                                    {{ $type->activeWorkflowTemplate()?->stages->sum('sla_hours') }}h total SLA
                                </span>
                                <span class="flex items-center gap-1 text-[11.5px] font-medium text-ink-soft transition-colors group-hover:text-accent">
                                    Start
                                    <svg class="h-3 w-3" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M2.5 6h7M6.5 3l3 3-3 3"/></svg>
                                </span>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif

            <div class="mt-4 border border-line bg-paper-deep/40 px-4 py-3">
                <p class="text-[12px] leading-relaxed text-ink-soft">
                    <span class="font-medium text-ink">Drafting is allowed.</span> Save a partial form and return later. Nothing enters the approval workflow until you submit it.
                </p>
            </div>
        </div>

        <aside class="space-y-4 lg:col-span-4">
            <section class="panel">
                <div class="panel-head"><h2 class="text-[13px] font-semibold">How a request moves</h2></div>
                <div class="p-4">
                    @foreach([
                        ['Draft and submit', 'Complete the form, attach evidence, and submit. A request number is reserved immediately.'],
                        ['Sequential approval', 'Each stage is assigned to a role. The next stage only opens after the current one approves.'],
                        ['Outcome recorded', 'Approval completes the workflow. Rejection stops it. Revision returns it to you with instructions.'],
                    ] as $index => [$title, $body])
                        <div class="relative flex gap-3 pb-5 last:pb-0">
                            @if(! $loop->last)
                                <span class="absolute left-[10px] top-5 h-full w-px bg-line"></span>
                            @endif
                            <span class="mono flex h-5 w-5 flex-none items-center justify-center rounded-full border border-line bg-paper text-[10px] text-ink-faint">{{ $index + 1 }}</span>
                            <div>
                                <p class="text-[12.5px] font-medium">{{ $title }}</p>
                                <p class="mt-1 text-[11.5px] leading-relaxed text-ink-faint">{{ $body }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="panel p-4">
                <p class="eyebrow">Document policy</p>
                <dl class="mt-3 space-y-2 text-[12px]">
                    <div class="flex justify-between gap-3">
                        <dt class="text-ink-soft">Accepted</dt>
                        <dd class="text-right font-mono text-[11px] text-ink">{{ implode(' · ', array_map('strtoupper', config('bpa.attachments.extensions'))) }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-ink-soft">Maximum size</dt>
                        <dd class="font-mono text-[11px] text-ink">{{ round(config('bpa.attachments.max_kb') / 1024, 1) }} MB per file</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-ink-soft">Storage</dt>
                        <dd class="text-right text-[11px] text-ink">Private, authorised download only</dd>
                    </div>
                </dl>
            </section>
        </aside>
    </div>
</div>

</x-layouts.app>