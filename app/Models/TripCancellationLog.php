<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripCancellationLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'trip_id',
        'driver_id',
        'cancelled_by',
        'cancelled_by_role',
        'reason',
        'trip_datetime',
        'trip_snapshot',
        'participants_snapshot',
        'payments_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'trip_datetime' => 'datetime',
            'trip_snapshot' => 'array',
            'participants_snapshot' => 'array',
            'payments_snapshot' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /** trip_id is intentionally not a real FK — see the migration docblock. */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
}
