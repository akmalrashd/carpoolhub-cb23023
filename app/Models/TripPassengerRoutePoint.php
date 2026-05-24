<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripPassengerRoutePoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id',
        'user_id',
        'trip_join_request_id',
        'trip_participant_id',
        'pickup_name',
        'pickup_latitude',
        'pickup_longitude',
        'dropoff_name',
        'dropoff_latitude',
        'dropoff_longitude',
        'uses_default_pickup',
        'uses_default_dropoff',
        'requested_pickup_time',
        'route_fit_score',
        'route_fit_label',
        'pickup_distance_km',
        'dropoff_distance_km',
        'detour_distance_km',
        'detour_duration_minutes',
        'extra_fee_amount',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'pickup_latitude' => 'decimal:7',
            'pickup_longitude' => 'decimal:7',
            'dropoff_latitude' => 'decimal:7',
            'dropoff_longitude' => 'decimal:7',
            'uses_default_pickup' => 'boolean',
            'uses_default_dropoff' => 'boolean',
            'requested_pickup_time' => 'datetime',
            'route_fit_score' => 'integer',
            'pickup_distance_km' => 'decimal:2',
            'dropoff_distance_km' => 'decimal:2',
            'detour_distance_km' => 'decimal:2',
            'detour_duration_minutes' => 'integer',
            'extra_fee_amount' => 'decimal:2',
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

    public function joinRequest(): BelongsTo
    {
        return $this->belongsTo(TripJoinRequest::class, 'trip_join_request_id');
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(TripParticipant::class, 'trip_participant_id');
    }
}
