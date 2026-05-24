<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TripJoinRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id',
        'user_id',
        'status',
        'request_note',
        'response_note',
        'responded_by',
        'responded_at',
        'decision_duration_minutes',
        'decision_feature_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'responded_at' => 'datetime',
            'decision_feature_snapshot' => 'array',
        ];
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function responder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responded_by');
    }

    public function routePoint(): HasOne
    {
        return $this->hasOne(TripPassengerRoutePoint::class);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }
}
