<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PassengerRiskProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'risk_score',
        'risk_level',
        'payment_reliability_score',
        'join_request_count',
        'approved_request_count',
        'rejected_request_count',
        'cancelled_request_count',
        'attendance_absent_count',
        'outstanding_amount',
        'overdue_case_count',
        'avg_payment_delay_hours',
        'last_scored_at',
        'feature_payload',
    ];

    protected function casts(): array
    {
        return [
            'risk_score' => 'integer',
            'payment_reliability_score' => 'decimal:1',
            'outstanding_amount' => 'decimal:2',
            'avg_payment_delay_hours' => 'decimal:2',
            'last_scored_at' => 'datetime',
            'feature_payload' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
