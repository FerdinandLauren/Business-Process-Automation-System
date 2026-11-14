<div class="space-y-6">
    <x-page-header
        eyebrow="Notification center"
        title="Activity feed"
        description="Assignments, decisions, revisions and SLA alerts delivered to your account."
    >
        @if($unread > 0)
            <button type="button" wire:click="markAllRead" class="btn">Mark all read</button>
        @endif
    </x-page-header>

    <section class="panel">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-3">
            <div class="flex items-center gap-1 rounded-[3px] border border-line p-0.5">
                @foreach(['all' => 'All (' . $total . ')', 'unread' => 'Unread (' . $unread . ')', 'read' => 'Read'] as $value => $label)
                    <button type="button" wire:click="$set('view', '{{ $value }}')" @class([
                        'rounded-[2px] px-2.5 py-1.5 text-[11.5px] font-medium transition',
                        'bg-ink text-paper' => $view === $value,
                        'text-ink-soft hover:bg-paper' => $view !== $value,
                    ])>{{ $label }}</button>
                @endforeach
            </div>
            <p class="mono text-[10.5px] uppercase tracking-[0.08em] text-ink-faint">In-app delivery · demo environment</p>
        </div>

        @if($notifications->isEmpty())
            <x-empty-state
                title="Nothing to read"
                :description="$view === 'unread' ? 'You are fully caught up.' : 'Notifications appear here when a request you own or approve changes state.'"
            />
        @else
            <ul class="divide-y divide-line">
                @foreach($notifications as $notification)
                    <li class="flex items-start gap-3 px-4 py-3.5 {{ $notification->isRead() ? '' : 'bg-accent-soft/40' }}">
                        <span class="mt-1.5 h-1.5 w-1.5 flex-none rounded-full {{ $notification->isRead() ? 'bg-line-strong' : 'bg-accent' }}"></span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
                                <p class="text-[13px] font-medium">{{ $notification->title }}</p>
                                <p class="mono text-[10.5px] text-ink-faint">{{ $notification->created_at?->format('d M Y, H:i') }}</p>
                            </div>
                            <p class="mt-1 text-[12.5px] leading-relaxed text-ink-soft">{{ $notification->message }}</p>
                            <div class="mt-2 flex items-center gap-3">
                                <span class="mono text-[10px] uppercase tracking-[0.08em] text-ink-faint">{{ $notification->type }}</span>
                                @if($notification->url)
                                    <button type="button" wire:click="open({{ $notification->id }})" class="text-[11.5px] font-medium text-accent hover:underline">
                                        {{ $notification->isRead() ? 'Open record' : 'Open and mark read' }}
                                    </button>
                                @elseif(! $notification->isRead())
                                    <button type="button" wire:click="open({{ $notification->id }})" class="text-[11.5px] text-ink-soft hover:text-accent">Mark read</button>
                                @endif
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
            <x-pagination :paginator="$notifications" />
        @endif
    </section>
</div>
