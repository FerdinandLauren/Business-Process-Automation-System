<x-layouts.guest title="Choose a new password">
    <p class="font-mono text-[10px] uppercase tracking-[0.14em] text-ink-faint">Account recovery</p>
    <h1 class="mt-2 text-[22px] font-semibold tracking-[-0.02em]">Choose a new password</h1>
    <p class="mt-1.5 text-[13px] text-ink-soft">Minimum 10 characters. Use a passphrase you do not reuse elsewhere.</p>

    <form method="POST" action="{{ route('password.update') }}" class="mt-7 space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div>
            <label for="email" class="label">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email', $email) }}" required class="input">
            @error('email')<p class="error">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="password" class="label">New password</label>
            <input id="password" name="password" type="password" required autocomplete="new-password" class="input">
            @error('password')<p class="error">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="password_confirmation" class="label">Confirm new password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="input">
        </div>

        <button type="submit" class="btn btn-primary w-full">Update password</button>
    </form>
</x-layouts.guest>
