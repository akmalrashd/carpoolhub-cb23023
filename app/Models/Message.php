<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    public const UPDATED_AT = null;

    public const TYPE_TEXT = 'text';

    public const TYPE_SYSTEM = 'system';

    public const TYPE_IMAGE = 'image';

    public const TYPE_BOT = 'bot';

    public const TYPE_PAYMENT_REMINDER = 'payment_reminder';

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'type',
        'body',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function isSystem(): bool
    {
        return $this->type === self::TYPE_SYSTEM;
    }

    public function isFromHexa(): bool
    {
        return in_array($this->type, [self::TYPE_BOT, self::TYPE_PAYMENT_REMINDER], true);
    }
}
