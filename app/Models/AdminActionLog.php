<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminActionLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'admin_id',
        'action',
        'target_type',
        'target_id',
        'description',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * The part before the dot, so "payment" in "payment.reversed". The audit
     * log page uses it to group and colour the entries without having to list
     * every exact action string.
     */
    public function getCategoryAttribute(): string
    {
        return explode('.', $this->action)[0] ?? $this->action;
    }

    /**
     * The badge class, icon class and label for each category. A category that
     * is not listed falls back to a neutral badge instead of breaking the
     * page.
     *
     * @return array{0: string, 1: string, 2: string}
     */
    public function getBadgeAttribute(): array
    {
        return match ($this->category) {
            'user' => ['badge-info', 'fa-user-gear', 'User'],
            'driver' => ['badge-yellow', 'fa-id-card', 'Driver'],
            'payment' => ['badge-success', 'fa-wallet', 'Payment'],
            'withdrawal' => ['badge-success', 'fa-money-bill-transfer', 'Withdrawal'],
            'message' => ['badge-warning', 'fa-paper-plane', 'Message'],
            'settings' => ['badge-dark', 'fa-sliders', 'Settings'],
            default => ['badge', 'fa-circle-info', ucfirst($this->category)],
        };
    }
}
