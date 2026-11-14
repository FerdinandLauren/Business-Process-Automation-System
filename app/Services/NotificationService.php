<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Support\Collection;

class NotificationService
{
    public function notify(
        User|int $recipient,
        string $type,
        string $title,
        string $message,
        ?ServiceRequest $request = null,
        ?string $url = null,
        bool $respectActive = true,
    ): ?AppNotification {
        $userId = $recipient instanceof User ? $recipient->getKey() : $recipient;

        if ($respectActive) {
            $isActive = $recipient instanceof User
                ? $recipient->is_active
                : User::whereKey($userId)->where('is_active', true)->exists();

            if (! $isActive) {
                return null;
            }
        }

        return AppNotification::create([
            'user_id' => $userId,
            'request_id' => $request?->getKey(),
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'url' => $url ?? $request?->routeUrl(),
        ]);
    }

    /**
     * @param  Collection<int, User>|array<int, User>  $recipients
     */
    public function notifyMany(
        Collection|array $recipients,
        string $type,
        string $title,
        string $message,
        ?ServiceRequest $request = null,
        ?string $url = null,
    ): void {
        foreach ($recipients as $recipient) {
            $this->notify($recipient, $type, $title, $message, $request, $url);
        }
    }

    public function markRead(AppNotification $notification): void
    {
        if ($notification->read_at === null) {
            $notification->forceFill(['read_at' => now()])->save();
        }
    }

    public function markAllRead(User $user): int
    {
        return AppNotification::where('user_id', $user->getKey())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
