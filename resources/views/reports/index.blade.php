<x-layouts.app title="Reports">
    <div class="space-y-5">
        <x-page-header
            eyebrow="Operations · Reporting"
            title="Operational reports"
            description="Request volume, approval performance and SLA compliance computed from live records. Filter by period, department, request type and status, then export the exact view you are reading."
        >
            <a href="{{ $exportUrl }}" class="btn btn-primary">Export CSV</a>
        </x-page-header>

        <nav class="panel flex flex-wrap gap-1 p-1.5" aria-label="Report selection">
            @foreach($catalogue as $key => $meta)
                <a
                    href="{{ route('reports.index', array_merge(request()->except(['page', 'report']), ['report' => $key])) }}"
                    @class([
                        'rounded-[2px] px-3 py-2 text-[12px] font-medium transition',
                        'bg-ink text-paper' => $filters['report'] === $key,
                        'text-ink-soft hover:bg-paper-deep hover:text-ink' => $filters['report'] !== $key,
                    ])
                    @if($filters['report'] === $key) aria-current="page" @endif
                >{{ $meta['label'] }}</a>
            @endforeach
        </nav>

        <form method="GET" action="{{ route('reports.index') }}" class="panel">
            <input type="hidden" name="report" value="{{ $filters['report'] }}">

            <div class="grid gap-3 border-b border-line px-4 py-3.5 sm:grid-cols-2 lg:grid-cols-5">
                <div>
                    <label for="from" class="label">From</label>
                    <input id="from" name="from" type="date" value="{{ $filters['from'] }}" class="input mono">
                </div>
                <div>
                    <label for="to" class="label">To</label>
                    <input id="to" name="to" type="date" value="{{ $filters['to'] }}" class="input mono" aria-invalid="{{ $errors->has('to') ? 'true' : 'false' }}">
                    @error('to') <p class="error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="department" class="label">Department</label>
                    <select id="department" name="department" class="select">
                        <option value="">All departments</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}" @selected((string) $filters['department'] === (string) $department->id)>{{ $department->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="type" class="label">Request type</label>
                    <select id="type" name="type" class="select">
                        <option value="">All types</option>
                        @foreach($types as $type)
                            <option value="{{ $type->code }}" @selected($filters['type'] === $type->code)>{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="status" class="label">{{ in_array($filters['report'], ['approvals', 'sla'], true) ? 'Task status' : 'Request status' }}</label>
                    <select id="status" name="status" class="select">
                        <option value="">All statuses</option>
                        @foreach($statusOptions as $status)
                            <option value="{{ $status['value'] }}" @selected($filters['status'] === $status['value'])>{{ $status['label'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                <p class="mono text-[10.5px] uppercase tracking-[0.06em] text-ink-faint">
                    {{ \Illuminate\Support\Carbon::parse($filters['from'])->format('d M Y') }}
                    <span aria-hidden="true">→</span>
                    {{ \Illuminate\Support\Carbon::parse($filters['to'])->format('d M Y') }}
                </p>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('reports.index', ['report' => $filters['report']]) }}" class="btn btn-quiet btn-sm">Reset</a>
                    <a href="{{ $exportUrl }}" class="btn btn-sm">Export this view</a>
                    <button type="submit" class="btn btn-sm btn-primary">Apply filters</button>
                </div>
            </div>
        </form>

        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            @foreach($payload['stats'] as $stat)
                <x-stat
                    :label="$stat['label']"
                    :value="$stat['value']"
                    :meta="$stat['meta'] ?? null"
                    :tone="$stat['tone'] ?? 'default'"
                />
            @endforeach
        </div>

        <section class="panel">
            <div class="panel-head flex-wrap gap-3">
                <div>
                    <p class="eyebrow">Report</p>
                    <h2 class="mt-0.5 text-[14px] font-semibold">{{ $catalogue[$filters['report']]['label'] }}</h2>
                    <p class="mt-1 max-w-[620px] text-[11.5px] leading-relaxed text-ink-soft">{{ $catalogue[$filters['report']]['description'] }}</p>
                </div>
                <p class="mono text-[10.5px] uppercase tracking-[0.06em] text-ink-faint">
                    @if($payload['paginator'])
                        {{ number_format($payload['paginator']->total()) }} rows
                    @else
                        {{ count($payload['rows']) }} rows
                    @endif
                </p>
            </div>

            @include('reports.table', ['payload' => $payload])
        </section>
    </div>
</x-layouts.app>
