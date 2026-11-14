@props(['title' => 'Overview'])
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Overview' }} · {{ config('bpa.company.product') }}</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='4' fill='%2314161a'/><path d='M9 22V10h4l3 6 3-6h4v12h-3v-7l-3 6h-2l-3-6v7z' fill='%23f7f6f3'/></svg>">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-full bg-paper text-ink antialiased">
<div x-data="{ navOpen: false }" class="min-h-screen lg:flex">

    {{-- Navigation rail --}}
    <div x-cloak x-show="navOpen" x-transition.opacity class="fixed inset-0 z-30 bg-rail/60 lg:hidden" @click="navOpen = false"></div>

    <aside
        x-cloak
        :class="navOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
        class="fixed inset-y-0 left-0 z-40 flex w-[244px] flex-col bg-rail transition-transform duration-150 ease-out lg:sticky lg:top-0 lg:h-screen lg:translate-x-0"
    >
        <div class="flex h-14 items-center justify-between border-b border-white/10 px-5">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5">
                <span class="grid h-7 w-7 place-items-center rounded-[3px] border border-white/25">
                    <svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="white" stroke-width="1.6">
                        <path d="M4 18V7h4l4 7 4-7h4v11"/>
                    </svg>
                </span>
                <span class="leading-none">
                    <span class="block text-[12.5px] font-semibold tracking-[0.16em] text-white">ARUNIKA</span>
                    <span class="mt-1 block font-mono text-[9px] uppercase tracking-[0.14em] text-white/35">Process workspace</span>
                </span>
            </a>
            <button type="button" class="text-white/50 hover:text-white lg:hidden" @click="navOpen = false" aria-label="Close navigation">
                <svg class="h-4 w-4" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 3l10 10M13 3L3 13"/></svg>
            </button>
        </div>

        <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-5" aria-label="Main">
            <div>
                <p class="px-2 pb-2 font-mono text-[9.5px] uppercase tracking-[0.16em] text-white/30">Workspace</p>
                <div class="space-y-0.5">
                    <a href="{{ route('dashboard') }}" @class(['nav-link']) @if(request()->routeIs('dashboard')) aria-current="page" @endif>
                        <span class="nav-mark"></span> Overview
                    </a>
                    <a href="{{ route('requests.index') }}" @class(['nav-link']) @if(request()->routeIs('requests.index', 'requests.show', 'requests.edit')) aria-current="page" @endif>
                        <span class="nav-mark"></span> My Requests
                    </a>
                    @can('create', App\Models\ServiceRequest::class)
                        <a href="{{ route('requests.center') }}" @class(['nav-link']) @if(request()->routeIs('requests.center', 'requests.create')) aria-current="page" @endif>
                            <span class="nav-mark"></span> Request Center
                        </a>
                    @endcan
                    @if(auth()->user()->hasPermission('approval.process'))
                        <a href="{{ route('approvals.index') }}" @class(['nav-link']) @if(request()->routeIs('approvals.*')) aria-current="page" @endif>
                            <span class="nav-mark"></span>
                            <span class="flex-1">Approval Inbox</span>
                            @php($pendingCount = auth()->user()->approvalTasks()->where('status', 'pending')->count())
                            @if($pendingCount > 0)
                                <span class="mono rounded-[2px] bg-white/10 px-1.5 py-0.5 text-[10px] text-white/70">{{ $pendingCount }}</span>
                            @endif
                        </a>
                    @endif
                </div>
            </div>

            <div>
                <p class="px-2 pb-2 font-mono text-[9.5px] uppercase tracking-[0.16em] text-white/30">Operations</p>
                <div class="space-y-0.5">
                    @can('manage-workflows')
                        <a href="{{ route('workflows.index') }}" @class(['nav-link']) @if(request()->routeIs('workflows.*')) aria-current="page" @endif>
                            <span class="nav-mark"></span> Workflow Management
                        </a>
                    @endcan
                    @can('view-reports')
                        <a href="{{ route('reports.index') }}" @class(['nav-link']) @if(request()->routeIs('reports.*')) aria-current="page" @endif>
                            <span class="nav-mark"></span> Reports
                        </a>
                    @endcan
                    <a href="{{ route('notifications.index') }}" @class(['nav-link']) @if(request()->routeIs('notifications.*')) aria-current="page" @endif>
                        <span class="nav-mark"></span>
                        <span class="flex-1">Notifications</span>
                        @php($unread = auth()->user()->notifications()->whereNull('read_at')->count())
                        @if($unread > 0)
                            <span class="mono rounded-[2px] bg-accent px-1.5 py-0.5 text-[10px] text-white">{{ $unread }}</span>
                        @endif
                    </a>
                </div>
            </div>

            @canany(['manage-users', 'manage-organisation', 'view-audit-logs'])
                <div>
                    <p class="px-2 pb-2 font-mono text-[9.5px] uppercase tracking-[0.16em] text-white/30">System</p>
                    <div class="space-y-0.5">
                        @canany(['manage-users', 'manage-organisation'])
                            <a href="{{ route('admin.users.index') }}" @class(['nav-link']) @if(request()->routeIs('admin.*') && !request()->routeIs('admin.audit.*')) aria-current="page" @endif>
                                <span class="nav-mark"></span> Administration
                            </a>
                        @endcanany
                        @can('view-audit-logs')
                            <a href="{{ route('admin.audit.index') }}" @class(['nav-link']) @if(request()->routeIs('admin.audit.*')) aria-current="page" @endif>
                                <span class="nav-mark"></span> Audit Trail
                            </a>
                        @endcan
                    </div>
                </div>
            @endcanany
        </nav>

        <div class="border-t border-white/10 px-4 py-4">
            <div class="flex items-center gap-2.5">
                <span class="grid h-8 w-8 flex-none place-items-center rounded-full bg-white/10 font-mono text-[11px] text-white/80">{{ auth()->user()->initials() }}</span>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-[12.5px] font-medium text-white">{{ auth()->user()->name }}</p>
                    <p class="truncate text-[10.5px] text-white/40">{{ auth()->user()->primaryRole()?->label ?? 'No role' }}</p>
                </div>
                <a href="{{ route('settings.edit') }}" class="text-white/40 transition hover:text-white" aria-label="Account settings">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.4"><circle cx="10" cy="10" r="3"/><path d="M10 2v2M10 16v2M2 10h2M16 10h2M4.3 4.3l1.4 1.4M14.3 14.3l1.4 1.4M15.7 4.3l-1.4 1.4M5.7 14.3l-1.4 1.4"/></svg>
                </a>
            </div>
            <p class="mt-3 font-mono text-[9px] uppercase tracking-[0.1em] text-white/25">Demo data · fictional company</p>
        </div>
    </aside>

    {{-- Main column --}}
    <div class="flex min-w-0 flex-1 flex-col">
        <header class="sticky top-0 z-20 flex h-14 items-center gap-3 border-b border-line bg-paper/95 px-5 backdrop-blur-sm lg:px-8">
            <button type="button" class="grid h-8 w-8 place-items-center rounded-[3px] border border-line text-ink-soft lg:hidden" @click="navOpen = true" aria-label="Open navigation">
                <svg class="h-4 w-4" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M2 4h12M2 8h12M2 12h12"/></svg>
            </button>

            <div class="flex min-w-0 items-center gap-2 text-[12.5px] text-ink-soft">
                <span class="hidden font-mono text-[10px] uppercase tracking-[0.12em] text-ink-faint sm:inline">{{ config('bpa.company.short_name') }}</span>
                <span class="hidden text-line-strong sm:inline">/</span>
                <span class="truncate font-medium text-ink">{{ $title ?? 'Overview' }}</span>
            </div>

            <div class="ml-auto flex items-center gap-1.5">
                <span class="mono mr-2 hidden text-[11px] text-ink-faint md:inline">{{ now()->format('D, d M Y') }}</span>
                <livewire:notification-bell />
                <details class="relative" data-user-menu>
                    <summary class="flex cursor-pointer list-none items-center gap-2 rounded-[3px] px-1.5 py-1 transition hover:bg-paper-deep">
                        <span class="grid h-7 w-7 place-items-center rounded-full bg-ink font-mono text-[10px] font-medium text-paper">{{ auth()->user()->initials() }}</span>
                        <svg class="h-3 w-3 text-ink-faint" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M3 4.5L6 7.5L9 4.5"/></svg>
                    </summary>
                    <div class="absolute right-0 z-30 mt-1.5 w-56 border border-line bg-surface p-1 shadow-[0_8px_24px_rgba(20,21,24,0.08)]">
                        <div class="border-b border-line px-3 py-2.5">
                            <p class="truncate text-[12.5px] font-medium">{{ auth()->user()->name }}</p>
                            <p class="truncate text-[11px] text-ink-faint">{{ auth()->user()->email }}</p>
                        </div>
                        <a href="{{ route('settings.edit') }}" class="block rounded-[2px] px-3 py-2 text-[12.5px] text-ink-soft hover:bg-paper hover:text-ink">Account settings</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full rounded-[2px] px-3 py-2 text-left text-[12.5px] text-ink-soft hover:bg-paper hover:text-ink">Sign out</button>
                        </form>
                    </div>
                </details>
            </div>
        </header>

        @if(session('status'))
            <div class="border-b border-positive/20 bg-positive-soft px-5 py-2.5 lg:px-8" role="status">
                <p class="mx-auto max-w-[1400px] text-[12.5px] text-positive">{{ session('status') }}</p>
            </div>
        @endif

        <main class="flex-1 px-5 py-6 lg:px-8 lg:py-8">
            <div class="mx-auto w-full max-w-[1400px]">
                {{ $slot }}
            </div>
        </main>

        <footer class="border-t border-line px-5 py-4 lg:px-8">
            <div class="mx-auto flex max-w-[1400px] items-center justify-between gap-4">
                <p class="font-mono text-[10px] uppercase tracking-[0.1em] text-ink-faint">{{ config('bpa.company.name') }} · Demo workspace</p>
                <p class="font-mono text-[10px] uppercase tracking-[0.1em] text-ink-faint">{{ config('bpa.company.product') }} v1.0</p>
            </div>
        </footer>
    </div>
</div>

<script>
    document.addEventListener('click', function (event) {
        document.querySelectorAll('details[data-user-menu][open]').forEach(function (details) {
            if (!details.contains(event.target)) {
                details.removeAttribute('open');
            }
        });
    });
</script>
@livewireScripts
</body>
</html>
