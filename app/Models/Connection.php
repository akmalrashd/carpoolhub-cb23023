<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Connection extends Model
{
    use HasFactory;

    protected $fillable = [
        'requester_id',
        'receiver_id',
        'status',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'responded_at' => 'datetime',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function scopeAccepted(Builder $query): Builder
    {
        return $query->where('status', 'accepted');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where(function (Builder $q) use ($userId): void {
            $q->where('requester_id', $userId)
                ->orWhere('receiver_id', $userId);
        });
    }

    /**
     * The other side of every accepted connection $user has, regardless of
     * which of the two rows they were on (requester or receiver). Was
     * duplicated identically in ConnectionService, SavedRouteService and
     * TripService — kept here as the one place that owns the query.
     */
    public static function acceptedUserIdsFor(User $user): \Illuminate\Support\Collection
    {
        return static::query()
            ->accepted()
            ->forUser($user->id)
            ->selectRaw(
                'CASE WHEN requester_id = ? THEN receiver_id ELSE requester_id END as connected_user_id',
                [$user->id]
            )
            ->pluck('connected_user_id')
            ->unique()
            ->values();
    }
}

