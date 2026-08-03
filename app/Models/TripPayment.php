<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripPayment extends Model
{
    use HasFactory;

    /**
     * The payment_status enum, mirroring the column definition in
     * create_trip_payments_table. These exist because the status was previously
     * a bare string repeated across services, controllers and views — and a
     * single wrong literal ('pending_review', which is not a value this column
     * has) silently broke the bulk-approve endpoint for its entire lifetime.
     * Prefer these constants and the scopes below over new literals.
     */
    public const STATUS_UNPAID = 'unpaid';

    public const STATUS_PENDING_CONFIRMATION = 'pending_confirmation';

    public const STATUS_PAID = 'paid';

    /** Statuses that still owe the driver money. */
    public const STATUSES_OUTSTANDING = [self::STATUS_UNPAID, self::STATUS_PENDING_CONFIRMATION];

    protected $fillable = [
        'trip_id',
        'user_id',
        'amount_due',
        'payment_status',
        'marked_paid_at',
        'confirmed_by',
        'confirmed_at',
        'payment_method',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'amount_due' => 'decimal:2',
            'marked_paid_at' => 'datetime',
            'confirmed_at' => 'datetime',
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

    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function scopeUnpaid(Builder $query): Builder
    {
        return $query->where('payment_status', self::STATUS_UNPAID);
    }

    public function scopePendingConfirmation(Builder $query): Builder
    {
        return $query->where('payment_status', self::STATUS_PENDING_CONFIRMATION);
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('payment_status', self::STATUS_PAID);
    }

    public function scopeOutstanding(Builder $query): Builder
    {
        return $query->whereIn('payment_status', self::STATUSES_OUTSTANDING);
    }

    public function isPaid(): bool
    {
        return $this->payment_status === self::STATUS_PAID;
    }

    public function isAwaitingConfirmation(): bool
    {
        return $this->payment_status === self::STATUS_PENDING_CONFIRMATION;
    }
}

