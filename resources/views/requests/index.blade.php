<x-layouts.app title="My Requests">
<div class="space-y-6">
    <x-page-header
        eyebrow="My requests"
        title="Request register"
        :description="'Every request you have raised, newest activity first. ' . $requests->total() . ' records.'"
    >
        @can('create', App\Models\ServiceRequest::class)
            <a href="{{ route('requests.center') }}" class="btn btn-primary">New request</a>
        @endcan
    </x-page-header>

    <div class="panel">
        <div class="flex flex-wrap items-end gap-3 border-b border-line px-4 py-3">
            <form method="GET" action="{{ route('requests.index') }}" class="flex flex-1 flex-wrap items-end gap-2">
                <div class="min-w-[200px] flex-1">
                    <label for="q" class="label">Search</label>
                    <input id="q" name="q" type="search" value="{{ $filters['q'] ?? '' }}" placeholder="Request number or title" class="input">
                </div>
                <div class="w-[160px]">
                    <label for="status" class="label">Status</label>
                    <select id="status" name="status" class="select">
                        <option value="">All statuses</option>
                        @foreach($statuses as $status)
                            <option value="{{ $status['value'] }}" @selected(($filters['status'] ?? '') === $status['value'])>{{ $status['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-[180px]">
                    <label for="type" class="label">Type</label>
                    <select id="type" name="type" class="select">
                        <option value="">All types</option>
                        @foreach($types as $type)
                            <option value="{{ $type->code }}" @selected(($filters['type'] ?? '') === $type->code)>{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn">Apply</button>
                @if(array_filter($filters))
                    <a href="{{ route('requests.index') }}" class="btn btn-quiet">Reset</a>
                @endif
            </form>
        </div>

        @if($requests->isEmpty())
            <x-empty-state
                title="No requests match this view"
                description="Try clearing the filters, or start a new request from the Request Center."
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
                            <th>Type</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Stage</th>
                            <th>Submitted</th>
                            <th class="text-right">Updated</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($requests as $item)
                            <tr>
                                <td class="max-w-[340px]">
                                    <a href="{{ route('requests.show', $item) }}" class="block truncate font-medium text-ink underline-offset-2 hover:text-accent hover:underline">
                                        {{ $item->title }}
                                    </a>
                                    <span class="mono mt-0.5 block text-[10.5px] text-ink-faint">{{ $item->request_number }}</span>
                                </td>
                                <td class="text-ink-soft">{{ $item->requestType->name }}</td>
                                <td>
                                    <x-status :label="$item->status->label()" :tone="$item->status->tone()" />
                                </td>
                                <td>
                                    <x-status :label="$item->priority->label()" :tone="$item->priority->tone()" />
                                </td>
                                <td class="mono text-[11px] text-ink-soft">
                                    @if($item->workflowInstance?->current_stage_order)
                                        S{{ $item->workflowInstance->current_stage_order }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="mono text-[11px] text-ink-soft">{{ $item->submitted_at?->format('d M Y') ?? '—' }}</td>
                                <td class="mono text-right text-[11px] text-ink-faint">{{ $item->updated_at->format('d M · H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-pagination :paginator="$requests" />
        @endif
    </div>
</div>

</x-layouts.app>