<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripPaymentStatusLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'trip_payment_id',
        'trip_id',
        'payer_id',
        'amount_due',
        'from_status',
        'to_status',
        'actor_id',
        'actor_role',
        'reason',
        'previous_state',
    ];

    protected function casts(): array
    {
        return [
            'amount_due' => 'decimal:2',
            'previous_state' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(TripPayment::class, 'trip_payment_id');
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payer_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
