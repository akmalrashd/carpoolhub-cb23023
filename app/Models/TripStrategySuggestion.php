<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripStrategySuggestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id',
        'driver_id',
        'saved_route_id',
        'suggested_fare_total',
        'suggested_fare_per_person',
        'suggested_seat_limit',
        'demand_score',
        'confidence_level',
        'strategy_type',
        'input_payload',
        'explanation_json',
    ];

    protected function casts(): array
    {
        return [
            'suggested_fare_total' => 'decimal:2',
            'suggested_fare_per_person' => 'decimal:2',
            'suggested_seat_limit' => 'integer',
            'demand_score' => 'integer',
            'input_payload' => 'array',
            'explanation_json' => 'array',
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

    public function savedRoute(): BelongsTo
    {
        return $this->belongsTo(SavedRoute::class);
    }
}
