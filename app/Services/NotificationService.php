<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class NotificationService
{
    public function paginateForUser(User $user, int $perPage = 15, string $filter = 'all'): LengthAwarePaginator
    {
        $query = UserNotification::query()
            ->where('user_id', $user->id)
            ->latest();

        if ($filter === 'unread') {
            $query->where('is_read', false);
        } elseif (in_array($filter, ['trip', 'payment', 'connection', 'system', 'route'], true)) {
            $query->where('type', $filter);
        }

        return $query->paginate($perPage);
    }

    public function tabCountsForUser(User $user): array
    {
        $base = UserNotification::query()->where('user_id', $user->id);

        return [
            'all'        => (clone $base)->count(),
            'unread'     => (clone $base)->where('is_read', false)->count(),
            'trip'       => (clone $base)->where('type', 'trip')->count(),
            'payment'    => (clone $base)->where('type', 'payment')->count(),
            'connection' => (clone $base)->where('type', 'connection')->count(),
            'system'     => (clone $base)->where('type', 'system')->count(),
        ];
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
