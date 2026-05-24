<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedRoutePassengerStop extends Model
{
    use HasFactory;

    protected $fillable = [
        'saved_route_id',
        'user_id',
        'pickup_name',
        'pickup_latitude',
        'pickup_longitude',
        'dropoff_name',
        'dropoff_latitude',
        'dropoff_longitude',
        'extra_fee_amount',
        'note',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'pickup_latitude' => 'decimal:7',
            'pickup_longitude' => 'decimal:7',
            'dropoff_latitude' => 'decimal:7',
            'dropoff_longitude' => 'decimal:7',
            'extra_fee_amount' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function savedRoute(): BelongsTo
    {
        return $this->belongsTo(SavedRoute::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
