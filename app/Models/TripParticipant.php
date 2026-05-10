<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TripParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id',
        'user_id',
        'is_driver',
        'fare_amount',
        'attendance_status',
        'joined_at',
        'cancelled_at',
        'attendance_marked_at',
        'attendance_source',
    ];

    protected function casts(): array
    {
        return [
            'is_driver' => 'boolean',
            'fare_amount' => 'decimal:2',
            'joined_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'attendance_marked_at' => 'datetime',
        ];
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function routePoint(): HasOne
    {
        return $this->hasOne(TripPassengerRoutePoint::class);
    }
}
