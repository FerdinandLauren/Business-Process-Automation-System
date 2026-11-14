<x-layouts.app title="Audit trail">
    <div class="space-y-6">
        <x-page-header
            eyebrow="Administration · Accountability"
            title="Audit trail"
            description="Append-only record of sign-ins, submissions, approval decisions and configuration changes. Records cannot be edited or deleted from the application."
        >
            <a href="{{ route('admin.index') }}" class="btn btn-sm">Administration</a>
        </x-page-header>

        <form method="GET" action="{{ route('admin.audit.index') }}" class="panel flex flex-wrap items-end gap-3 p-4">
            <div class="min-w-[220px] flex-1">
                <label class="label" for="q">Search</label>
                <input id="q" name="q" type="search" value="{{ $filters['q'] ?? '' }}" class="input" placeholder="Action, entity or actor">
            </div>
            <div class="min-w-[180px]">
                <label class="label" for="action">Action</label>
                <select id="action" name="action" class="select">
                    <option value="">All actions</option>
                    @foreach($actions as $action)
                        <option value="{{ $action }}" @selected(($filters['action'] ?? '') === $action)>{{ $action }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[170px]">
                <label class="label" for="actor">Actor</label>
                <select id="actor" name="actor" class="select">
                    <option value="">Anyone</option>
                    @foreach($actors as $actor)
                        <option value="{{ $actor->id }}" @selected((int) ($filters['actor'] ?? 0) === $actor->id)>{{ $actor->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[140px]">
                <label class="label" for="from">From</label>
                <input id="from" name="from" type="date" value="{{ $filters['from'] ?? '' }}" class="input">
            </div>
            <div class="min-w-[140px]">
                <label class="label" for="to">To</label>
                <input id="to" name="to" type="date" value="{{ $filters['to'] ?? '' }}" class="input">
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="btn btn-primary">Apply</button>
                <a href="{{ route('admin.audit.index') }}" class="btn btn-quiet">Reset</a>
            </div>
        </form>

        @if($logs->isEmpty())
            <x-empty-state title="No audit records match" description="Adjust the filters to widen the search." />
        @else
            <div class="panel table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>When</th>
                            <th>Actor</th>
                            <th>Action</th>
                            <th>Entity</th>
                            <th>Context</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($logs as $log)
                            <tr>
                                <td class="mono whitespace-nowrap text-[11px] text-ink-soft">{{ $log->created_at?->format('d M Y, H:i:s') }}</td>
                                <td>
                                    <p class="text-[12.5px]">{{ $log->actor?->name ?? 'System' }}</p>
                                    <p class="text-[10.5px] text-ink-faint">{{ $log->ip_address ?? '—' }}</p>
                                </td>
                                <td><span class="mono text-[11px]">{{ $log->action }}</span></td>
                                <td>
                                    <p class="text-[11.5px]">{{ class_basename($log->entity_type) }}</p>
                                    <p class="mono text-[10.5px] text-ink-faint">#{{ $log->entity_id ?? '—' }}</p>
                                </td>
                                <td>
                                    @if($log->metadata)
                                        <details>
                                            <summary class="cursor-pointer text-[11.5px] text-ink-soft hover:text-ink">View metadata</summary>
                                            <pre class="mono mt-2 max-w-[420px] overflow-x-auto border border-line bg-paper p-2 text-[10.5px] leading-relaxed">{{ json_encode($log->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                        </details>
                                    @else
                                        <span class="text-[11.5px] text-ink-faint">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div><x-pagination :paginator="$logs" /></div>
        @endif
    </div>
</x-layouts.app>
