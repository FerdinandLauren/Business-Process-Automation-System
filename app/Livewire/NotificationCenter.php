<?php

namespace App\Livewire;

use App\Models\AppNotification;
use App\Services\NotificationService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Notifications')]
class NotificationCenter extends Component
{
    use WithPagination;

    #[Url(as: 'view', history: true)]
    public string $view = 'all';

    public function updating(string $property): void
    {
        if ($property === 'view') {
            $this->resetPage();
        }
    }

    public function open(int $notificationId, NotificationService $service): void
    {
        $notification = AppNotification::query()
            ->where('user_id', auth()->id())
            ->findOrFail($notificationId);

        $service->markRead($notification);

        if ($notification->url) {
            $this->redirect($notification->url, navigate: true);

            return;
        }

        $this->dispatch('$refresh');
    }

    public function markAllRead(NotificationService $service): void
    {
        $service->markAllRead(auth()->user());
        session()->flash('status', 'All notifications marked as read.');
    }

    public function render()
    {
        $query = AppNotification::query()->where('user_id', auth()->id());

        $notifications = (clone $query)
            ->when($this->view === 'unread', fn ($inner) => $inner->whereNull('read_at'))
            ->when($this->view === 'read', fn ($inner) => $inner->whereNotNull('read_at'))
            ->latest('id')
            ->paginate(20);

        return view('livewire.notification-center', [
            'notifications' => $notifications,
            'unread' => (clone $query)->whereNull('read_at')->count(),
            'total' => (clone $query)->count(),
        ]);
    }
}
