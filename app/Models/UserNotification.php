<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class UserNotification extends Model
{
    use HasFactory;

    protected $table = 'notifications';

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'related_type',
        'related_id',
        'is_read',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'read_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('is_read', false);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function getTargetUrlAttribute(): string
    {
        $relatedId = (int) ($this->related_id ?? 0);

        return match ($this->related_type) {
            'trip' => $relatedId > 0
                ? route('trips.index', ['focus_trip' => $relatedId])
                : route('trips.index'),
            'trip_payment' => route('payments.index') . '#queue-summary',
            'connection' => route('connections.index'),
            'route' => route('saved-routes.index'),
            'trip_join_request' => $this->resolveJoinRequestUrl($relatedId),
            default => route('notifications.index'),
        };
    }

    private function resolveJoinRequestUrl(int $joinRequestId): string
    {
        if ($joinRequestId <= 0) {
            return route('trips.index');
        }

        $joinRequest = TripJoinRequest::query()
            ->select('id', 'trip_id')
            ->find($joinRequestId);

        if (! $joinRequest || ! $joinRequest->trip_id) {
            return route('trips.index');
        }

        $joinedTripText = Str::lower(trim(($this->title ?? '') . ' ' . ($this->message ?? '')));
        if (Str::contains($joinedTripText, ['approved', 'rejected'])) {
            return route('trips.index', ['focus_trip' => $joinRequest->trip_id]);
        }

        return route('trips.requests.index', $joinRequest->trip_id);
    }
}
