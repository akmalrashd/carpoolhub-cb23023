<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripRecommendationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'trip_id',
        'context_source',
        'match_score',
        'route_score',
        'time_score',
        'seat_score',
        'connection_score',
        'fare_score',
        'history_score',
        'explanation_json',
    ];

    protected function casts(): array
    {
        return [
            'match_score' => 'decimal:2',
            'route_score' => 'decimal:2',
            'time_score' => 'decimal:2',
            'seat_score' => 'decimal:2',
            'connection_score' => 'decimal:2',
            'fare_score' => 'decimal:2',
            'history_score' => 'decimal:2',
            'explanation_json' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }
}
