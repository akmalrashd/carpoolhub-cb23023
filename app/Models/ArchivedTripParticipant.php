<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArchivedTripParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'archived_trip_id',
        'user_id',
        'is_driver',
        'fare_amount',
        'attendance_status',
        'joined_at',
        'cancelled_at',
        'attendance_marked_at',
        'attendance_source',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'is_driver' => 'boolean',
            'fare_amount' => 'decimal:2',
            'joined_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'attendance_marked_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    public function archivedTrip(): BelongsTo
    {
        return $this->belongsTo(ArchivedTrip::class, 'archived_trip_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
