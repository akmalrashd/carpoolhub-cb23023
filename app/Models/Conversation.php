<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    use HasPublicId;

    public const PURGE_REASON_TRIP_COMPLETED = 'trip_completed';

    public const PURGE_REASON_TRIP_CANCELLED = 'trip_cancelled';

    protected $fillable = [
        'trip_id',
        'trip_ref_snapshot',
        'route_snapshot',
        'trip_datetime_snapshot',
        'visibility_snapshot',
        'driver_id',
        'opens_at',
        'scheduled_purge_at',
        'purge_reason',
    ];

    protected function casts(): array
    {
        return [
            'trip_datetime_snapshot' => 'datetime',
            'opens_at' => 'datetime',
            'scheduled_purge_at' => 'datetime',
        ];
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function isPrivate(): bool
    {
        return $this->visibility_snapshot === 'private';
    }
}
