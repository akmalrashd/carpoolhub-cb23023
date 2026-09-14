<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverRating extends Model
{
    protected $fillable = [
        'trip_id',
        'driver_id',
        'rater_user_id',
        'stars',
    ];

    protected function casts(): array
    {
        return [
            'stars' => 'integer',
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

    public function rater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rater_user_id');
    }

    /**
     * @return \Illuminate\Support\Collection<int, int>
     */
    public static function ratedTripIdsFor(User $user): \Illuminate\Support\Collection
    {
        return static::query()->where('rater_user_id', $user->id)->pluck('trip_id');
    }
}
