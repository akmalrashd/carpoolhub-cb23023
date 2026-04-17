<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripPayment extends Model
{
    use HasFactory;

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
}

