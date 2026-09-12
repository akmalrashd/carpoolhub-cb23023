<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    public const UPDATED_AT = null;

    public const DIRECTION_CREDIT = 'credit';

    public const DIRECTION_DEBIT = 'debit';

    public const REASON_GATEWAY_PAYMENT = 'gateway_payment';

    public const REASON_WITHDRAWAL_RESERVED = 'withdrawal_reserved';

    public const REASON_WITHDRAWAL_REFUND = 'withdrawal_refund';

    public const REASON_ADMIN_ADJUSTMENT = 'admin_adjustment';

    protected $fillable = [
        'wallet_id',
        'user_id',
        'direction',
        'reason',
        'amount',
        'balance_after',
        'related_type',
        'related_id',
        'description',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
