<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Trip extends Model
{
    use HasFactory;

    /**
     * This app has no multi-timezone support — every trip_datetime is
     * entered and displayed as Malaysia local time, but APP_TIMEZONE stays
     * UTC for everything else (created_at, payments, notifications, etc.
     * are already correct in UTC). Casting just this one column explicitly
     * avoids an 8-hour skew without touching any other timestamp's meaning.
     * Raw query-builder comparisons against this column (which bypass the
     * accessor below) must use self::now() instead of the bare now() helper.
     */
    public const TIMEZONE = 'Asia/Kuala_Lumpur';

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

    protected function tripDatetime(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? Carbon::parse($value, self::TIMEZONE) : null,
            set: fn ($value) => $value instanceof \DateTimeInterface
                ? Carbon::instance($value)->clone()->setTimezone(self::TIMEZONE)->format('Y-m-d H:i:s')
                : $value,
        );
    }

    /** Current time in the same timezone trip_datetime values are stored in
     *  — use this instead of the bare now() helper when comparing against
     *  trip_datetime in a raw query-builder clause (those bypass the
     *  accessor above and compare directly against the raw DB string). */
    public static function now(): Carbon
    {
        return Carbon::now(self::TIMEZONE);
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
