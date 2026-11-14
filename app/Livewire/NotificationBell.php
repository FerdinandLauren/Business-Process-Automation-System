<?php

namespace App\Livewire;

use App\Models\AppNotification;
use App\Services\NotificationService;
use Livewire\Component;

class NotificationBell extends Component
{
    public bool $open = false;

    public function toggle(): void
    {
        $this->open = ! $this->open;
    }

    public function markRead(int $notificationId, NotificationService $service): void
    {
        $notification = AppNotification::query()
            ->where('user_id', auth()->id())
            ->findOrFail($notificationId);

        $service->markRead($notification);

        if ($notification->url) {
            $this->redirect($notification->url, navigate: true);

            return;
        }

        $this->open = false;
    }

    public function markAllRead(NotificationService $service): void
    {
        $service->markAllRead(auth()->user());
        $this->open = false;
    }

    public function render()
    {
        $notifications = AppNotification::query()
            ->where('user_id', auth()->id())
            ->latest('id')
            ->limit(6)
            ->get();

        return view('livewire.notification-bell', [
            'notifications' => $notifications,
            'unreadCount' => AppNotification::query()
                ->where('user_id', auth()->id())
                ->whereNull('read_at')
                ->count(),
        ]);
    }
}
