<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverRatingProfile extends Model
{
    protected $fillable = [
        'user_id',
        'rating_count',
        'rating_sum',
        'rating_average',
        'last_rated_at',
    ];

    protected function casts(): array
    {
        return [
            'rating_count' => 'integer',
            'rating_sum' => 'integer',
            'rating_average' => 'decimal:2',
            'last_rated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
