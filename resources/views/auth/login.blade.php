<x-layouts.guest title="Sign in">
    <div>
        <p class="font-mono text-[10px] uppercase tracking-[0.14em] text-ink-faint">Secure access</p>
        <h1 class="mt-2 text-[22px] font-semibold tracking-[-0.02em]">Sign in to your workspace</h1>
        <p class="mt-1.5 text-[13px] text-ink-soft">Use your company email and password.</p>
    </div>

    @if(session('error'))
        <div class="mt-5 border border-danger/25 bg-danger-soft px-3 py-2.5 text-[12.5px] text-danger" role="alert">
            {{ session('error') }}
        </div>
    @endif

    @if(session('status'))
        <div class="mt-5 border border-positive/25 bg-positive-soft px-3 py-2.5 text-[12.5px] text-positive" role="status">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login.store') }}" class="mt-7 space-y-4">
        @csrf

        <div>
            <label for="email" class="label">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                   class="input @error('email') border-danger @enderror" aria-invalid="@error('email') true @else false @enderror">
            @error('email')
                <p class="error">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <div class="flex items-baseline justify-between">
                <label for="password" class="label">Password</label>
                <a href="{{ route('password.request') }}" class="text-[11.5px] text-ink-soft underline-offset-2 hover:text-accent hover:underline">Forgot password?</a>
            </div>
            <input id="password" name="password" type="password" required autocomplete="current-password"
                   class="input @error('password') border-danger @enderror" aria-invalid="@error('password') true @else false @enderror">
            @error('password')
                <p class="error">{{ $message }}</p>
            @enderror
        </div>

        <label class="flex items-center gap-2 text-[12.5px] text-ink-soft">
            <input type="checkbox" name="remember" value="1" class="h-3.5 w-3.5 rounded-[2px] border-line-strong text-accent focus:ring-accent">
            Keep me signed in on this device
        </label>

        <button type="submit" class="btn btn-primary w-full">Sign in</button>
    </form>

    <div class="mt-8 border-t border-line pt-5">
        <p class="eyebrow">Demo accounts</p>
        <dl class="mt-2.5 space-y-1.5 font-mono text-[11.5px] text-ink-soft">
            <div class="flex justify-between gap-3"><dt>Employee</dt><dd>ferdinand@arunika.test</dd></div>
            <div class="flex justify-between gap-3"><dt>Approver</dt><dd>andi@arunika.test</dd></div>
            <div class="flex justify-between gap-3"><dt>Admin</dt><dd>admin@arunika.test</dd></div>
            <div class="flex justify-between gap-3"><dt>Password</dt><dd>password</dd></div>
        </dl>
    </div>
</x-layouts.guest>
