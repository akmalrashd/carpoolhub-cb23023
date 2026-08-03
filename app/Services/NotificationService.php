<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;

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
        // Two grouped queries replace seven COUNTs. This runs on the notifications
        // page and is reachable from the 5-second poller, so the six saved round
        // trips are per-tab-per-user. 'all' sums every type returned rather than
        // the five named ones, so it stays identical to the old total count()
        // even for a type no tab covers.
        $byType = UserNotification::query()
            ->where('user_id', $user->id)
            ->select('type', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('type')
            ->pluck('aggregate', 'type');

        return [
            'all'        => (int) $byType->sum(),
            'unread'     => $this->unreadCount($user),
            'trip'       => (int) ($byType['trip'] ?? 0),
            'payment'    => (int) ($byType['payment'] ?? 0),
            'connection' => (int) ($byType['connection'] ?? 0),
            'system'     => (int) ($byType['system'] ?? 0),
            'route'      => (int) ($byType['route'] ?? 0),
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

    public function clearReadNotifications(User $user): void
    {
        UserNotification::query()
            ->where('user_id', $user->id)
            ->where('is_read', true)
            ->delete();
    }
}
