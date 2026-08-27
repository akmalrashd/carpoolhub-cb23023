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
     * The part before the dot in e.g. "payment.reversed" — used to group and
     * color-code actions in the audit log UI without hardcoding every exact
     * action string there.
     */
    public function getCategoryAttribute(): string
    {
        return explode('.', $this->action)[0] ?? $this->action;
    }

    /**
     * (badge CSS class, icon class, human label) per category — new action
     * categories fall back to a neutral badge rather than breaking.
     *
     * @return array{0: string, 1: string, 2: string}
     */
    public function getBadgeAttribute(): array
    {
        return match ($this->category) {
            'user' => ['badge-info', 'fa-user-gear', 'User'],
            'driver' => ['badge-yellow', 'fa-id-card', 'Driver'],
            'payment' => ['badge-success', 'fa-wallet', 'Payment'],
            'message' => ['badge-warning', 'fa-paper-plane', 'Message'],
            'settings' => ['badge-dark', 'fa-sliders', 'Settings'],
            default => ['badge', 'fa-circle-info', ucfirst($this->category)],
        };
    }
}
