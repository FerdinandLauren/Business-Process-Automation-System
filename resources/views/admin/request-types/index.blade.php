<x-layouts.app title="Request types & forms">
    <div class="space-y-6">
        <x-page-header
            eyebrow="Administration · Process catalogue"
            title="Request types & forms"
            description="Every request type carries a versioned form. Published versions are frozen so a submitted request always keeps the questions it was answered against."
        >
            <a href="{{ route('admin.index') }}" class="btn btn-sm">Administration</a>
            <a href="{{ route('admin.request-types.create') }}" class="btn btn-sm btn-primary">New request type</a>
        </x-page-header>

        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <x-stat label="Request types" :value="number_format($types->count())" meta="In the catalogue" />
            <x-stat label="Accepting requests" :value="number_format($activeCount)" meta="Visible to employees" tone="positive" />
            <x-stat label="Without a live form" :value="number_format($formGaps)" :meta="$formGaps > 0 ? 'Cannot be submitted yet' : 'All types are publishable'" :tone="$formGaps > 0 ? 'caution' : 'default'" />
            <x-stat label="Without an approval route" :value="number_format($routeGaps)" :meta="$routeGaps > 0 ? 'Requests would stall' : 'Every type is routed'" :tone="$routeGaps > 0 ? 'caution' : 'default'" />
        </div>

        @if($types->isEmpty())
            <x-empty-state title="The catalogue is empty" description="Add the first request type, then publish its form and approval route.">
                <a href="{{ route('admin.request-types.create') }}" class="btn btn-sm btn-primary">New request type</a>
            </x-empty-state>
        @else
            <div class="panel table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Form</th>
                            <th>Approval route</th>
                            <th>Requests</th>
                            <th>Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($types as $type)
                            @php
                                $form = $type->formTemplates->firstWhere('is_active', true);
                                $draftForm = $type->formTemplates->first(fn ($template) => $template->published_at === null);
                                $route = $type->workflowTemplates->first();
                            @endphp
                            <tr>
                                <td>
                                    <p class="text-[12.5px] font-medium">{{ $type->name }}</p>
                                    <p class="mono text-[10.5px] text-ink-faint">{{ $type->code }}</p>
                                </td>
                                <td>
                                    @if($form)
                                        <p class="text-[12px]">v{{ $form->version }} live</p>
                                    @else
                                        <p class="text-[12px] text-ink-faint">Not published</p>
                                    @endif
                                    @if($draftForm)
                                        <p class="mono text-[10.5px] text-ink-faint">v{{ $draftForm->version }} draft</p>
                                    @endif
                                </td>
                                <td>
                                    @if($route)
                                        <p class="text-[12px]">v{{ $route->version }} active</p>
                                        <p class="mono text-[10.5px] text-ink-faint">{{ $route->stages_count }} {{ Str::plural('stage', $route->stages_count) }}</p>
                                    @else
                                        <p class="text-[12px] text-ink-faint">No active route</p>
                                    @endif
                                </td>
                                <td class="mono text-[11px]">{{ number_format($type->requests_count) }}</td>
                                <td>
                                    <x-status :label="$type->is_active ? 'Open' : 'Retired'" :tone="$type->is_active ? 'positive' : 'neutral'" />
                                </td>
                                <td>
                                    <div class="flex items-center justify-end gap-1.5">
                                        <a href="{{ route('admin.request-types.edit', $type) }}" class="btn btn-sm btn-quiet">Settings</a>
                                        @if($form)
                                            <a href="{{ route('admin.request-types.forms.edit', [$type->code, $form]) }}" class="btn btn-sm">Form v{{ $form->version }}</a>
                                        @elseif($draftForm)
                                            <a href="{{ route('admin.request-types.forms.edit', [$type->code, $draftForm]) }}" class="btn btn-sm">Draft v{{ $draftForm->version }}</a>
                                        @else
                                            <a href="{{ route('admin.request-types.forms.create', $type) }}" class="btn btn-sm btn-primary">Build form</a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-layouts.app>
