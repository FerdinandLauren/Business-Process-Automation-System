<div class="relative" @click.outside="$wire.set('open', false)">
    <button
        type="button"
        wire:click="toggle"
        class="relative grid h-8 w-8 place-items-center rounded-[3px] text-ink-soft transition hover:bg-paper-deep hover:text-ink"
        aria-label="Notifications{{ $unreadCount > 0 ? " ({$unreadCount} unread)" : '' }}"
        aria-expanded="{{ $open ? 'true' : 'false' }}"
    >
        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.4">
            <path d="M10 3a5 5 0 0 0-5 5v3l-1.5 2.5h13L15 11V8a5 5 0 0 0-5-5Z"/>
            <path d="M8 16a2 2 0 0 0 4 0"/>
        </svg>
        @if($unreadCount > 0)
            <span class="absolute right-1 top-1 h-1.5 w-1.5 rounded-full bg-accent ring-2 ring-paper"></span>
        @endif
    </button>

    @if($open)
        <div class="absolute right-0 z-40 mt-1.5 w-[340px] max-w-[calc(100vw-2rem)] border border-line bg-surface shadow-[0_8px_24px_rgba(20,21,24,0.10)]">
            <div class="flex items-center justify-between border-b border-line px-3 py-2.5">
                <p class="eyebrow">Notifications</p>
                @if($unreadCount > 0)
                    <button type="button" wire:click="markAllRead" class="text-[11.5px] text-ink-soft hover:text-accent">
                        Mark all read
                    </button>
                @endif
            </div>

            <div class="max-h-[360px] overflow-y-auto">
                @forelse($notifications as $notification)
                    <button
                        type="button"
                        wire:key="notification-{{ $notification->id }}"
                        wire:click="markRead({{ $notification->id }})"
                        class="flex w-full gap-2.5 border-b border-line px-3 py-3 text-left transition last:border-0 hover:bg-paper {{ $notification->isRead() ? 'opacity-65' : '' }}"
                    >
                        <span class="mt-1.5 h-1.5 w-1.5 flex-none rounded-full {{ $notification->isRead() ? 'bg-line-strong' : 'bg-accent' }}"></span>
                        <span class="min-w-0">
                            <span class="block truncate text-[12.5px] font-medium">{{ $notification->title }}</span>
                            <span class="mt-0.5 block line-clamp-2 text-[11.5px] leading-relaxed text-ink-soft">{{ $notification->message }}</span>
                            <span class="mt-1 block font-mono text-[10px] uppercase tracking-[0.08em] text-ink-faint">{{ $notification->created_at?->diffForHumans() }}</span>
                        </span>
                    </button>
                @empty
                    <p class="px-3 py-8 text-center text-[12px] text-ink-faint">No notifications yet.</p>
                @endforelse
            </div>

            <a href="{{ route('notifications.index') }}" class="block border-t border-line px-3 py-2.5 text-center text-[11.5px] font-medium text-ink-soft hover:text-accent">
                View all notifications
            </a>
        </div>
    @endif
</div>
