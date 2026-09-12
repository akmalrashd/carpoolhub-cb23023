<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

/**
 * Broadcast immediately (ShouldBroadcastNow, not ShouldBroadcast) — this app
 * has no queue worker process running anywhere (QUEUE_CONNECTION=database
 * but nothing ever consumes it), so a queued broadcast would just sit in the
 * jobs table forever on Hostinger's shared hosting.
 */
class MessageSent implements ShouldBroadcastNow
{
    use InteractsWithSockets;

    public function __construct(public Message $message) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversation.'.$this->message->conversation_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        $sender = $this->message->sender;

        return [
            'id' => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'type' => $this->message->type,
            'body' => $this->message->body,
            'sender_id' => $this->message->sender_id,
            'sender_name' => $sender?->name ?? 'Deleted user',
            'sender_avatar_url' => $sender?->profile_photo_url,
            'created_at' => $this->message->created_at?->toIso8601String(),
        ];
    }
}
