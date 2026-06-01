<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SavedRoute extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'route_name',
        'point_a_name',
        'point_a_latitude',
        'point_a_longitude',
        'point_b_name',
        'point_b_latitude',
        'point_b_longitude',
        'default_fare',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'point_a_latitude' => 'decimal:7',
            'point_a_longitude' => 'decimal:7',
            'point_b_latitude' => 'decimal:7',
            'point_b_longitude' => 'decimal:7',
            'default_fare' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function getPickupNameAttribute(): ?string
    {
        return $this->attributes['point_a_name'] ?? null;
    }

    public function getPickupLatitudeAttribute(): ?string
    {
        return $this->attributes['point_a_latitude'] ?? null;
    }

    public function getPickupLongitudeAttribute(): ?string
    {
        return $this->attributes['point_a_longitude'] ?? null;
    }

    public function getDestinationNameAttribute(): ?string
    {
        return $this->attributes['point_b_name'] ?? null;
    }

    public function getDestinationLatitudeAttribute(): ?string
    {
        return $this->attributes['point_b_latitude'] ?? null;
    }

    public function getDestinationLongitudeAttribute(): ?string
    {
        return $this->attributes['point_b_longitude'] ?? null;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    public function passengerStops(): HasMany
    {
        return $this->hasMany(SavedRoutePassengerStop::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }
}
