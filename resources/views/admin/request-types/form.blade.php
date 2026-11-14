<x-layouts.app :title="$requestType->exists ? $requestType->name : 'New request type'">
    <div class="space-y-6">
        <x-page-header
            eyebrow="Administration · Process catalogue"
            :title="$requestType->exists ? $requestType->name : 'New request type'"
            :description="$requestType->exists ? 'Catalogue settings, form versions and approval routes for this request type.' : 'A request type groups one form and one approval route under a stable code that appears on every request number.'"
        >
            <a href="{{ route('admin.request-types.index') }}" class="btn btn-sm">Back to catalogue</a>
        </x-page-header>

        <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_320px]">
            <form
                id="request-type-form"
                method="POST"
                action="{{ $requestType->exists ? route('admin.request-types.update', $requestType) : route('admin.request-types.store') }}"
                class="space-y-5"
            >
                @csrf
                @if($requestType->exists)
                    @method('PUT')
                @endif

                <section class="panel">
                    <div class="panel-head">
                        <div>
                            <p class="eyebrow">Catalogue entry</p>
                            <h2 class="mt-0.5 text-[14px] font-semibold">Identity</h2>
                        </div>
                    </div>
                    <div class="grid gap-4 p-4 sm:grid-cols-2">
                        <div>
                            <label class="label" for="name">Name</label>
                            <input id="name" name="name" type="text" value="{{ old('name', $requestType->name) }}" class="input" required aria-invalid="@error('name') true @else false @enderror">
                            @error('name') <p class="error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label" for="code">Code</label>
                            <input id="code" name="code" type="text" value="{{ old('code', $requestType->code) }}" class="input mono uppercase" required maxlength="32" placeholder="e.g. REIMB" aria-invalid="@error('code') true @else false @enderror">
                            <p class="hint">Uppercase letters, numbers and hyphens. Printed on every request number.</p>
                            @error('code') <p class="error">{{ $message }}</p> @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label class="label" for="description">Description</label>
                            <textarea id="description" name="description" class="textarea" rows="2">{{ old('description', $requestType->description) }}</textarea>
                            @error('description') <p class="error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label" for="is_active">Availability</label>
                            <select id="is_active" name="is_active" class="select" required>
                                <option value="1" @selected((string) old('is_active', (int) $requestType->is_active) === '1')>Open for new requests</option>
                                <option value="0" @selected((string) old('is_active', (int) $requestType->is_active) === '0')>Retired</option>
                            </select>
                            @error('is_active') <p class="error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </section>
            </form>

            <aside class="space-y-4">
                <section class="panel p-4">
                    <p class="eyebrow">{{ $requestType->exists ? 'Save changes' : 'Create the type' }}</p>
                    <p class="mt-2 text-[11.5px] leading-relaxed text-ink-soft">
                        {{ $requestType->exists ? 'Renaming the type updates the catalogue and reports. The code is used on request numbers, so change it only while no requests exist.' : 'After creating the type you can publish a form and an approval route before opening it to employees.' }}
                    </p>
                    <button type="submit" form="request-type-form" class="btn btn-primary mt-4 w-full">{{ $requestType->exists ? 'Save request type' : 'Create request type' }}</button>
                </section>

                @if($requestType->exists)
                    <section class="border border-line bg-paper-deep/40 p-4">
                        <p class="eyebrow">Usage</p>
                        <dl class="mt-3 space-y-2 text-[11.5px]">
                            <div class="flex justify-between gap-3"><dt class="text-ink-faint">Requests raised</dt><dd class="mono">{{ number_format($requestType->requests_count) }}</dd></div>
                            <div class="flex justify-between gap-3"><dt class="text-ink-faint">Form versions</dt><dd class="mono">{{ $requestType->formTemplates->count() }}</dd></div>
                            <div class="flex justify-between gap-3"><dt class="text-ink-faint">Route versions</dt><dd class="mono">{{ $requestType->workflowTemplates->count() }}</dd></div>
                        </dl>
                    </section>

                    @if($requestType->is_active)
                        <section class="panel p-4">
                            <p class="eyebrow">Retire this type</p>
                            <p class="mt-2 text-[11.5px] leading-relaxed text-ink-soft">Retiring hides the type from the request centre. Existing requests keep their route, history and reports.</p>
                            <form method="POST" action="{{ route('admin.request-types.status', $requestType) }}" class="mt-3">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-danger w-full" onclick="return confirm('Retire this request type? Employees will no longer be able to raise it.')">Retire request type</button>
                            </form>
                        </section>
                    @endif
                @endif
            </aside>
        </div>

        @if($requestType->exists)
            <div class="grid gap-5 lg:grid-cols-2">
                <section class="panel">
                    <div class="panel-head flex-wrap">
                        <div>
                            <p class="eyebrow">Form versions</p>
                            <h2 class="mt-0.5 text-[14px] font-semibold">Questions asked of the requester</h2>
                        </div>
                        <a href="{{ route('admin.request-types.forms.create', $requestType) }}" class="btn btn-sm btn-primary">Draft a new version</a>
                    </div>

                    @if($requestType->formTemplates->isEmpty())
                        <div class="p-4">
                            <x-empty-state title="No form yet" description="Employees cannot raise this request until a form version is published.">
                                <a href="{{ route('admin.request-types.forms.create', $requestType) }}" class="btn btn-sm btn-primary">Build the first form</a>
                            </x-empty-state>
                        </div>
                    @else
                        <div class="table-wrap">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Version</th>
                                        <th>Fields</th>
                                        <th>State</th>
                                        <th>Requests</th>
                                        <th class="text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($requestType->formTemplates as $template)
                                        <tr>
                                            <td>
                                                <p class="mono text-[11px]">v{{ $template->version }}</p>
                                                <p class="text-[10.5px] text-ink-faint">{{ $template->name }}</p>
                                            </td>
                                            <td class="mono text-[11px]">{{ count($template->fields()) }}</td>
                                            <td>
                                                @if($template->is_active)
                                                    <x-status label="Live" tone="positive" />
                                                @elseif($template->published_at)
                                                    <x-status label="Superseded" tone="neutral" />
                                                @else
                                                    <x-status label="Draft" tone="caution" />
                                                @endif
                                            </td>
                                            <td class="mono text-[11px]">{{ $template->requests_count }}</td>
                                            <td class="text-right">
                                                <a href="{{ route('admin.request-types.forms.edit', [$requestType->code, $template]) }}" class="btn btn-sm btn-quiet">
                                                    {{ $template->published_at ? 'View' : 'Edit draft' }}
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </section>

                <section class="panel h-fit">
                    <div class="panel-head flex-wrap">
                        <div>
                            <p class="eyebrow">Approval route</p>
                            <h2 class="mt-0.5 text-[14px] font-semibold">Who signs off</h2>
                        </div>
                        <a href="{{ route('workflows.index') }}" class="btn btn-sm">Workflow management</a>
                    </div>
                    <div class="p-4">
                        @php($activeRoute = $requestType->workflowTemplates->firstWhere('is_active', true))
                        @if($activeRoute)
                            <p class="text-[12.5px] leading-relaxed text-ink-soft">
                                Version {{ $activeRoute->version }} is active with
                                {{ $activeRoute->stages_count }} {{ Str::plural('stage', $activeRoute->stages_count) }}.
                                Requests already in flight stay on the version they started with.
                            </p>
                            <a href="{{ route('workflows.edit', $activeRoute) }}" class="btn btn-sm mt-3">Inspect route</a>
                        @else
                            <p class="text-[12.5px] leading-relaxed text-ink-soft">No published approval route. Submitted requests would have nowhere to go.</p>
                            <a href="{{ route('workflows.create', $requestType) }}" class="btn btn-sm btn-primary mt-3">Create route</a>
                        @endif
                    </div>
                </section>
            </div>
        @endif
    </div>
</x-layouts.app>
