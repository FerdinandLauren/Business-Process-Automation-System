<x-layouts.app title="Departments">
    <div class="space-y-6">
        <x-page-header
            eyebrow="Administration · Organisation"
            title="Departments"
            description="Departments drive requester-department approval routing, data isolation and the department activity report."
        >
            <a href="{{ route('admin.index') }}" class="btn btn-sm">Administration</a>
        </x-page-header>

        <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_340px]">
            <div class="panel table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Department</th>
                            <th>Code</th>
                            <th>People</th>
                            <th>Requests</th>
                            <th class="text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($departments as $department)
                            <tr>
                                <td>
                                    <p class="text-[12.5px] font-medium">{{ $department->name }}</p>
                                    <p class="text-[11px] text-ink-faint">{{ $department->description ?: 'No description' }}</p>
                                </td>
                                <td><span class="mono text-[11px]">{{ $department->code }}</span></td>
                                <td class="mono text-[11px]">{{ $department->users_count }}</td>
                                <td class="mono text-[11px]">{{ $department->requests_count }}</td>
                                <td class="text-right">
                                    <a href="{{ route('admin.departments.edit', $department) }}" class="btn btn-sm btn-quiet">Edit</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <x-empty-state title="No departments yet" description="Add the first department using the form on the right." />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <section class="panel h-fit">
                <div class="panel-head">
                    <div>
                        <p class="eyebrow">New department</p>
                        <h2 class="mt-0.5 text-[14px] font-semibold">Add to the organisation</h2>
                    </div>
                </div>
                <form method="POST" action="{{ route('admin.departments.store') }}" class="space-y-4 p-4">
                    @csrf
                    <div>
                        <label class="label" for="name">Name</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" class="input" required aria-invalid="@error('name') true @else false @enderror">
                        @error('name') <p class="error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label" for="code">Code</label>
                        <input id="code" name="code" type="text" value="{{ old('code') }}" class="input mono uppercase" required maxlength="12" placeholder="e.g. FIN" aria-invalid="@error('code') true @else false @enderror">
                        <p class="hint">Uppercase letters, numbers and hyphens. Used in routing and reports.</p>
                        @error('code') <p class="error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label" for="description">Description</label>
                        <textarea id="description" name="description" class="textarea" rows="2">{{ old('description') }}</textarea>
                        @error('description') <p class="error">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit" class="btn btn-primary w-full">Add department</button>
                </form>
            </section>
        </div>
    </div>
</x-layouts.app>
