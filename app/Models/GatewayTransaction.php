<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GatewayTransaction extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_FAILED = 'failed';

    public const STATUS_ERROR = 'error';

    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'gateway',
        'environment',
        'trip_payment_id',
        'trip_payment_ids',
        'trip_id',
        'payer_id',
        'driver_id',
        'order_id',
        'bill_code',
        'amount_due',
        'gateway_fee',
        'amount_charged',
        'amount_credited',
        'status',
        'toyyibpay_refno',
        'error_message',
        'raw_callback_payload',
        'finalized_via',
        'finalized_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_due' => 'decimal:2',
            'gateway_fee' => 'decimal:2',
            'amount_charged' => 'decimal:2',
            'amount_credited' => 'decimal:2',
            'raw_callback_payload' => 'array',
            'trip_payment_ids' => 'array',
            'finalized_at' => 'datetime',
        ];
    }

    public function tripPayment(): BelongsTo
    {
        return $this->belongsTo(TripPayment::class);
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payer_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
