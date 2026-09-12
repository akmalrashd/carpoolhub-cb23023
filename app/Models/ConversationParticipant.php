<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversationParticipant extends Model
{
    protected $fillable = [
        'conversation_id',
        'user_id',
        'is_chat_admin',
        'joined_at',
        'left_at',
        'last_read_message_id',
    ];

    protected function casts(): array
    {
        return [
            'is_chat_admin' => 'boolean',
            'joined_at' => 'datetime',
            'left_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->left_at === null;
    }

    /**
     * Drives the unread dot on the bottom-nav/sidebar "Chat" icon — a
     * participant row is "unread" once the conversation's latest message id
     * has moved past what they last read. Shared by the layout's per-page
     * computation and RefreshController's 5s poll, so both stay in sync.
     */
    public static function unreadCountFor(User $user): int
    {
        return static::query()
            ->where('user_id', $user->id)
            ->whereNull('left_at')
            ->whereRaw('COALESCE(last_read_message_id, 0) < (SELECT COALESCE(MAX(id), 0) FROM messages WHERE messages.conversation_id = conversation_participants.conversation_id)')
            ->count();
    }
}
