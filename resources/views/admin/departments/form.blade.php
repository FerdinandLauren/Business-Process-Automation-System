<x-layouts.app :title="'Edit '.$department->name">
    <div class="space-y-6">
        <x-page-header
            eyebrow="Administration · Organisation"
            :title="'Edit '.$department->name"
            description="Renaming a department updates every request, report and approval route that references it."
        >
            <a href="{{ route('admin.departments.index') }}" class="btn btn-sm">Back to departments</a>
        </x-page-header>

        <form method="POST" action="{{ route('admin.departments.update', $department) }}" class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_300px]">
            @csrf
            @method('PUT')

            <section class="panel">
                <div class="panel-head">
                    <div>
                        <p class="eyebrow">Department</p>
                        <h2 class="mt-0.5 text-[14px] font-semibold">Identity</h2>
                    </div>
                </div>
                <div class="grid gap-4 p-4 sm:grid-cols-2">
                    <div>
                        <label class="label" for="name">Name</label>
                        <input id="name" name="name" type="text" value="{{ old('name', $department->name) }}" class="input" required>
                        @error('name') <p class="error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label" for="code">Code</label>
                        <input id="code" name="code" type="text" value="{{ old('code', $department->code) }}" class="input mono uppercase" required maxlength="12">
                        @error('code') <p class="error">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="label" for="description">Description</label>
                        <textarea id="description" name="description" class="textarea" rows="3">{{ old('description', $department->description) }}</textarea>
                        @error('description') <p class="error">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>

            <aside class="space-y-4">
                <section class="panel p-4">
                    <p class="eyebrow">Save changes</p>
                    <button type="submit" class="btn btn-primary mt-3 w-full">Save department</button>
                </section>
                <section class="border border-line bg-paper-deep/40 p-4">
                    <p class="eyebrow">Current footprint</p>
                    <dl class="mt-3 space-y-2 text-[11.5px]">
                        <div class="flex justify-between gap-3"><dt class="text-ink-faint">People</dt><dd class="mono">{{ $department->users_count }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-ink-faint">Requests</dt><dd class="mono">{{ $department->requests_count }}</dd></div>
                    </dl>
                </section>
            </aside>
        </form>
    </div>
</x-layouts.app>
