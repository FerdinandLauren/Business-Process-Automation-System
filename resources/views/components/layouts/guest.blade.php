<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Sign in' }} · {{ config('bpa.company.product') }}</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='4' fill='%2314161a'/><path d='M9 22V10h4l3 6 3-6h4v12h-3v-7l-3 6h-2l-3-6v7z' fill='%23f7f6f3'/></svg>">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-paper text-ink antialiased">
<div class="grid min-h-screen lg:grid-cols-[minmax(0,1fr)_minmax(0,520px)]">

    <section class="relative hidden flex-col justify-between bg-rail p-10 lg:flex">
        <a href="{{ route('login') }}" class="flex items-center gap-3">
            <span class="grid h-8 w-8 place-items-center rounded-[3px] border border-white/25">
                <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="white" stroke-width="1.6"><path d="M4 18V7h4l4 7 4-7h4v11"/></svg>
            </span>
            <span class="leading-none">
                <span class="block text-[13px] font-semibold tracking-[0.16em] text-white">ARUNIKA</span>
                <span class="mt-1 block font-mono text-[9px] uppercase tracking-[0.14em] text-white/35">Process workspace</span>
            </span>
        </a>

        <div class="max-w-[430px]">
            <p class="font-mono text-[10px] uppercase tracking-[0.16em] text-white/35">Business process automation</p>
            <h1 class="mt-4 text-[30px] font-semibold leading-[1.15] tracking-[-0.02em] text-white">
                Every approval has an owner, a deadline, and a record.
            </h1>
            <p class="mt-4 text-[13.5px] leading-relaxed text-white/50">
                Administrative requests move through configured approval stages. Nothing gets lost in an inbox, and every decision is traceable.
            </p>

            <div class="mt-10 border border-white/10 bg-white/[0.03] p-4">
                <div class="flex items-center justify-between">
                    <span class="font-mono text-[10px] uppercase tracking-[0.12em] text-white/35">Workflow trace</span>
                    <span class="font-mono text-[10.5px] text-white/45">REQ-202611-0042</span>
                </div>
                <div class="mt-4 space-y-0">
                    @foreach ([
                        ['Department Manager', 'Approved', 'Andi Pratama', true],
                        ['Finance Manager', 'Approved', 'Budi Santoso', true],
                        ['Finance Processing', 'Awaiting decision', 'Sari Wulandari', false],
                    ] as $index => [$stage, $state, $person, $done])
                        <div class="relative flex gap-3 pb-4 last:pb-0">
                            @if(!$loop->last)
                                <span class="absolute left-[5.5px] top-3 h-full w-px bg-white/10"></span>
                            @endif
                            <span class="relative mt-1 h-[11px] w-[11px] flex-none rounded-full border {{ $done ? 'border-white/40 bg-white/40' : 'border-accent bg-rail' }}"></span>
                            <div class="min-w-0">
                                <p class="text-[12.5px] font-medium text-white/85">{{ $stage }}</p>
                                <p class="mt-0.5 text-[11px] text-white/40">{{ $person }} · {{ $state }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <p class="font-mono text-[10px] uppercase tracking-[0.1em] text-white/25">
            Fictional demonstration environment
        </p>
    </section>

    <section class="flex items-center justify-center px-5 py-10 sm:px-10">
        <div class="w-full max-w-[360px]">
            <div class="mb-8 flex items-center gap-3 lg:hidden">
                <span class="grid h-8 w-8 place-items-center rounded-[3px] bg-rail">
                    <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="white" stroke-width="1.6"><path d="M4 18V7h4l4 7 4-7h4v11"/></svg>
                </span>
                <span class="text-[13px] font-semibold tracking-[0.16em]">ARUNIKA</span>
            </div>
            {{ $slot }}
        </div>
    </section>
</div>
</body>
</html>
