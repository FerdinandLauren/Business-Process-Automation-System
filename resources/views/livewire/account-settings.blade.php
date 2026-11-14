<div class="space-y-6">
    <x-page-header
        eyebrow="Account"
        title="Settings"
        description="Your identity, access and sign-in credentials. Role and department are controlled by a system administrator."
    />

    <div class="grid gap-6 lg:grid-cols-12">
        <div class="space-y-5 lg:col-span-7">
            <section class="panel">
                <div class="panel-head">
                    <div>
                        <p class="eyebrow">Profile</p>
                        <h2 class="mt-1 text-[14px] font-semibold">Personal information</h2>
                    </div>
                </div>
                <form wire:submit="updateProfile" class="space-y-4 p-4">
                    <div>
                        <label for="name" class="label">Full name</label>
                        <input id="name" type="text" wire:model="name" class="input" aria-invalid="@error('name') true @else false @enderror">
                        @error('name')<p class="error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="email" class="label">Email address</label>
                        <input id="email" type="email" wire:model="email" class="input" aria-invalid="@error('email') true @else false @enderror">
                        <p class="hint">Used to sign in and as the recipient for notifications.</p>
                        @error('email')<p class="error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="job_title" class="label">Job title</label>
                        <input id="job_title" type="text" wire:model="job_title" class="input" aria-invalid="@error('job_title') true @else false @enderror">
                        @error('job_title')<p class="error">{{ $message }}</p>@enderror
                    </div>
                    <div class="flex items-center justify-between gap-3 border-t border-line pt-4">
                        <p class="text-[11.5px] text-ink-faint">Employee code and department are managed by administration.</p>
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">Save profile</button>
                    </div>
                </form>
            </section>

            <section class="panel">
                <div class="panel-head">
                    <div>
                        <p class="eyebrow">Security</p>
                        <h2 class="mt-1 text-[14px] font-semibold">Change password</h2>
                    </div>
                </div>
                <form wire:submit="updatePassword" class="space-y-4 p-4">
                    <div>
                        <label for="current_password" class="label">Current password</label>
                        <input id="current_password" type="password" wire:model="current_password" class="input" autocomplete="current-password" aria-invalid="@error('current_password') true @else false @enderror">
                        @error('current_password')<p class="error">{{ $message }}</p>@enderror
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="password" class="label">New password</label>
                            <input id="password" type="password" wire:model="password" class="input" autocomplete="new-password">
                            <p class="hint">Minimum 10 characters with letters and numbers.</p>
                            @error('password')<p class="error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="password_confirmation" class="label">Confirm new password</label>
                            <input id="password_confirmation" type="password" wire:model="password_confirmation" class="input" autocomplete="new-password">
                        </div>
                    </div>
                    <div class="flex justify-end border-t border-line pt-4">
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">Update password</button>
                    </div>
                </form>
            </section>
        </div>

        <aside class="space-y-5 lg:col-span-5">
            <section class="panel">
                <div class="panel-head"><h2 class="text-[13px] font-semibold">Access</h2></div>
                <div class="p-4">
                    <div class="flex items-center gap-3">
                        <span class="grid h-10 w-10 flex-none place-items-center rounded-full bg-ink font-mono text-[12px] text-paper">{{ $user->initials() }}</span>
                        <div class="min-w-0">
                            <p class="truncate text-[13px] font-medium">{{ $user->name }}</p>
                            <p class="truncate text-[11.5px] text-ink-faint">{{ $user->department?->name }} · {{ $user->employee_code }}</p>
                        </div>
                    </div>
                    <dl class="mt-4 space-y-2.5 border-t border-line pt-3 text-[12px]">
                        @foreach($roles as $role)
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-ink-soft">Role</dt>
                                <dd class="text-right text-[11.5px] font-medium">{{ $role->label }}</dd>
                            </div>
                        @endforeach
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-ink-soft">Account status</dt>
                            <dd><x-status :label="$user->is_active ? 'Active' : 'Inactive'" :tone="$user->is_active ? 'positive' : 'danger'" /></dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-ink-soft">Last sign-in</dt>
                            <dd class="mono text-[11px]">{{ $user->last_login_at?->format('d M Y, H:i') ?? '—' }}</dd>
                        </div>
                    </dl>
                </div>
            </section>

            <section class="panel">
                <div class="panel-head"><h2 class="text-[13px] font-semibold">Effective permissions</h2></div>
                <div class="p-4">
                    @if($permissions->isEmpty())
                        <p class="text-[12px] text-ink-faint">No permissions assigned.</p>
                    @else
                        <ul class="space-y-1.5">
                            @foreach($permissions as $permission)
                                <li class="flex items-center gap-2 text-[12px] text-ink-soft">
                                    <span class="h-1 w-1 rounded-full bg-line-strong"></span>{{ $permission }}
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </section>
        </aside>
    </div>
</div>
