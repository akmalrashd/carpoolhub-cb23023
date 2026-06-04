<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Trip extends Model
{
    use HasFactory;

    protected $fillable = [
        'driver_id',
        'saved_route_id',
        'trip_ref',
        'pickup_name',
        'pickup_latitude',
        'pickup_longitude',
        'destination_name',
        'destination_latitude',
        'destination_longitude',
        'parent_trip_id',
        'trip_datetime',
        'trip_mode',
        'visibility',
        'is_return_trip',
        'status',
        'fare_total',
        'fare_per_person',
        'participant_count',
        'seat_limit',
        'is_open_for_request',
        'note',
        'public_note',
    ];

    protected function casts(): array
    {
        return [
            'trip_datetime' => 'datetime',
            'pickup_latitude' => 'decimal:7',
            'pickup_longitude' => 'decimal:7',
            'destination_latitude' => 'decimal:7',
            'destination_longitude' => 'decimal:7',
            'is_return_trip' => 'boolean',
            'is_open_for_request' => 'boolean',
            'fare_total' => 'decimal:2',
            'fare_per_person' => 'decimal:2',
            'participant_count' => 'integer',
            'seat_limit' => 'integer',
        ];
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function savedRoute(): BelongsTo
    {
        return $this->belongsTo(SavedRoute::class);
    }

    public function parentTrip(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_trip_id');
    }

    public function returnTrip(): HasOne
    {
        return $this->hasOne(self::class, 'parent_trip_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(TripParticipant::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(TripPayment::class);
    }

    public function joinRequests(): HasMany
    {
        return $this->hasMany(TripJoinRequest::class);
    }

    public function passengerRoutePoints(): HasMany
    {
        return $this->hasMany(TripPassengerRoutePoint::class);
    }

}
