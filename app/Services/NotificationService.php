<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class NotificationService
{
    public function paginateForUser(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return UserNotification::query()
            ->where('user_id', $user->id)
            ->latest()
            ->paginate($perPage);
    }

    public function unreadCount(User $user): int
    {
        return UserNotification::query()
            ->where('user_id', $user->id)
            ->where('is_read', false)
            ->count();
    }

    public function recentForUser(User $user, int $limit = 6): EloquentCollection
    {
        return UserNotification::query()
            ->where('user_id', $user->id)
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function markAsRead(User $user, UserNotification $notification): UserNotification
    {
        abort_if($notification->user_id !== $user->id, 403);

        if (! $notification->is_read) {
            $notification->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }

        return $notification->refresh();
    }

    public function markAllAsRead(User $user): void
    {
        UserNotification::query()
            ->where('user_id', $user->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }
}

