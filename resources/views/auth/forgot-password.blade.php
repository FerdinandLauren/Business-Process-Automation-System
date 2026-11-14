<x-layouts.guest title="Reset password">
    <p class="font-mono text-[10px] uppercase tracking-[0.14em] text-ink-faint">Account recovery</p>
    <h1 class="mt-2 text-[22px] font-semibold tracking-[-0.02em]">Reset your password</h1>
    <p class="mt-1.5 text-[13px] text-ink-soft">We will email a single-use reset link to your company address.</p>

    @if(session('status'))
        <div class="mt-5 border border-positive/25 bg-positive-soft px-3 py-2.5 text-[12.5px] text-positive" role="status">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="mt-7 space-y-4">
        @csrf
        <div>
            <label for="email" class="label">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="input">
            @error('email')<p class="error">{{ $message }}</p>@enderror
        </div>
        <button type="submit" class="btn btn-primary w-full">Send reset link</button>
    </form>

    <p class="mt-6 text-[12.5px] text-ink-soft">
        <a href="{{ route('login') }}" class="link">Back to sign in</a>
    </p>
</x-layouts.guest>
