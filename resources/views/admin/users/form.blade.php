<x-layouts.app :title="$user->exists ? 'Edit user' : 'New user'">
    <div class="space-y-6">
        <x-page-header
            eyebrow="Administration · Identity"
            :title="$user->exists ? 'Edit '.$user->name : 'Create a user'"
            :description="$user->exists ? 'Changes to roles and status take effect on the next request. Historical records keep the original attribution.' : 'The account is created with the password you set here. Share it out of band and ask the user to change it after signing in.'"
        >
            <a href="{{ route('admin.users.index') }}" class="btn btn-sm">Back to users</a>
        </x-page-header>

        <form method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}" class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_300px]">
            @csrf
            @if($user->exists)
                @method('PUT')
            @endif

            <div class="space-y-5">
                <section class="panel">
                    <div class="panel-head">
                        <div>
                            <p class="eyebrow">Identity</p>
                            <h2 class="mt-0.5 text-[14px] font-semibold">Personal and contact details</h2>
                        </div>
                    </div>
                    <div class="grid gap-4 p-4 sm:grid-cols-2">
                        <div>
                            <label class="label" for="name">Full name</label>
                            <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" class="input" required aria-invalid="@error('name') true @else false @enderror">
                            @error('name') <p class="error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label" for="email">Work email</label>
                            <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" class="input" required aria-invalid="@error('email') true @else false @enderror">
                            @error('email') <p class="error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label" for="employee_code">Employee code</label>
                            <input id="employee_code" name="employee_code" type="text" value="{{ old('employee_code', $user->employee_code) }}" class="input" required aria-invalid="@error('employee_code') true @else false @enderror">
                            @error('employee_code') <p class="error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label" for="job_title">Job title</label>
                            <input id="job_title" name="job_title" type="text" value="{{ old('job_title', $user->job_title) }}" class="input">
                            @error('job_title') <p class="error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </section>

                <section class="panel">
                    <div class="panel-head">
                        <div>
                            <p class="eyebrow">Placement</p>
                            <h2 class="mt-0.5 text-[14px] font-semibold">Department and roles</h2>
                        </div>
                    </div>
                    <div class="grid gap-4 p-4 sm:grid-cols-2">
                        <div>
                            <label class="label" for="department_id">Department</label>
                            <select id="department_id" name="department_id" class="select" required>
                                <option value="">Choose a department</option>
                                @foreach($departments as $department)
                                    <option value="{{ $department->id }}" @selected((int) old('department_id', $user->department_id) === $department->id)>{{ $department->name }}</option>
                                @endforeach
                            </select>
                            @error('department_id') <p class="error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label" for="is_active">Account status</label>
                            <select id="is_active" name="is_active" class="select" required>
                                <option value="1" @selected((string) old('is_active', (int) $user->is_active) === '1')>Active</option>
                                <option value="0" @selected((string) old('is_active', (int) $user->is_active) === '0')>Inactive</option>
                            </select>
                            @error('is_active') <p class="error">{{ $message }}</p> @enderror
                        </div>

                        <div class="sm:col-span-2">
                            <span class="label">Roles</span>
                            <div class="grid gap-2 sm:grid-cols-2">
                                @foreach($roles as $role)
                                    @php($checked = in_array($role->id, old('roles', $user->roles->pluck('id')->all()), true))
                                    <label class="flex cursor-pointer items-start gap-2.5 border border-line bg-paper px-3 py-2.5 transition hover:border-line-strong">
                                        <input type="checkbox" name="roles[]" value="{{ $role->id }}" @checked($checked) class="mt-0.5 h-3.5 w-3.5 accent-[#2f45c5]">
                                        <span class="min-w-0">
                                            <span class="block text-[12.5px] font-medium">{{ $role->label }}</span>
                                            <span class="mt-0.5 block text-[11px] leading-snug text-ink-faint">{{ $role->description }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            @error('roles') <p class="error">{{ $message }}</p> @enderror
                            @error('roles.*') <p class="error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </section>

                <section class="panel">
                    <div class="panel-head">
                        <div>
                            <p class="eyebrow">Credentials</p>
                            <h2 class="mt-0.5 text-[14px] font-semibold">{{ $user->exists ? 'Set a new password' : 'Initial password' }}</h2>
                        </div>
                    </div>
                    <div class="grid gap-4 p-4 sm:grid-cols-2">
                        <div>
                            <label class="label" for="password">Password</label>
                            <input id="password" name="password" type="password" class="input" autocomplete="new-password" @if(! $user->exists) required @endif>
                            <p class="hint">{{ $user->exists ? 'Leave blank to keep the current password.' : 'At least 8 characters.' }}</p>
                            @error('password') <p class="error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label" for="password_confirmation">Confirm password</label>
                            <input id="password_confirmation" name="password_confirmation" type="password" class="input" autocomplete="new-password" @if(! $user->exists) required @endif>
                        </div>
                    </div>
                </section>
            </div>

            <aside class="space-y-4">
                <section class="panel p-4">
                    <p class="eyebrow">Save changes</p>
                    <p class="mt-2 text-[11.5px] leading-relaxed text-ink-soft">
                        Role and status changes are audited with your identity. A user cannot lose their last role, and the final active system administrator cannot be deactivated.
                    </p>
                    <button type="submit" class="btn btn-primary mt-4 w-full">{{ $user->exists ? 'Save user' : 'Create user' }}</button>
                </section>

                @if($user->exists)
                    <section class="border border-line bg-paper-deep/40 p-4">
                        <p class="eyebrow">Record</p>
                        <dl class="mt-3 space-y-2 text-[11.5px]">
                            <div class="flex justify-between gap-3"><dt class="text-ink-faint">Created</dt><dd class="mono">{{ $user->created_at?->format('d M Y') }}</dd></div>
                            <div class="flex justify-between gap-3"><dt class="text-ink-faint">Last sign-in</dt><dd class="mono">{{ $user->last_login_at?->format('d M Y, H:i') ?? 'Never' }}</dd></div>
                            <div class="flex justify-between gap-3"><dt class="text-ink-faint">Requests raised</dt><dd class="mono">{{ $user->requests()->count() }}</dd></div>
                            <div class="flex justify-between gap-3"><dt class="text-ink-faint">Tasks assigned</dt><dd class="mono">{{ $user->approvalTasks()->count() }}</dd></div>
                        </dl>
                    </section>
                @endif
            </aside>
        </form>
    </div>
</x-layouts.app>
