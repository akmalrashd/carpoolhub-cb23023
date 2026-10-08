<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

/**
 * Broadcast straight away using ShouldBroadcastNow rather than
 * ShouldBroadcast. There is no queue worker running anywhere in this app.
 * QUEUE_CONNECTION is set to database but nothing ever reads that table, so a
 * queued broadcast would simply sit there forever on shared hosting.
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
