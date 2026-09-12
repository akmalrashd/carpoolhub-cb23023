<?php

namespace App\Services;

use App\Events\MessageSent;
use App\Models\Connection;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\SystemSetting;
use App\Models\Trip;
use App\Models\TripParticipant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Trip-scoped chat: an automatic group for public trips (kept in sync with
 * the driver + currently-approved passengers), or a driver-created group for
 * private trips (hand-picked from their Connections). See the "Trip-Scoped
 * Inbox / Chat" plan for the full design.
 *
 * trip_datetime_snapshot/opens_at/scheduled_purge_at are stored as real UTC
 * instants (unlike trips.trip_datetime, which is a KL-local wall-clock
 * string — see Trip::TIMEZONE) so every comparison here can use a plain
 * now() instead of Trip::now().
 */
class ChatService
{
    public function syncParticipants(Trip $trip): ?Conversation
    {
        if ($trip->visibility !== 'public') {
            return null;
        }

        $activeUserIds = TripParticipant::query()
            ->where('trip_id', $trip->id)
            ->where('attendance_status', 'joined')
            ->pluck('user_id')
            ->push($trip->driver_id)
            ->unique()
            ->values();

        if ($activeUserIds->isEmpty()) {
            return null;
        }

        return DB::transaction(function () use ($trip, $activeUserIds): Conversation {
            $conversation = Conversation::query()->firstOrCreate(
                ['trip_id' => $trip->id],
                $this->buildConversationAttributes($trip)
            );

            $existing = ConversationParticipant::query()
                ->where('conversation_id', $conversation->id)
                ->get()
                ->keyBy('user_id');

            foreach ($activeUserIds as $userId) {
                $participant = $existing->get($userId);
                if ($participant && $participant->isActive()) {
                    continue;
                }

                if ($participant) {
                    $participant->update(['left_at' => null, 'joined_at' => now()]);
                } else {
                    ConversationParticipant::create([
                        'conversation_id' => $conversation->id,
                        'user_id' => $userId,
                        'is_chat_admin' => $userId === $trip->driver_id,
                        'joined_at' => now(),
                    ]);
                }

                // The driver silently owns the chat from creation — no need to
                // announce them "joining" their own trip's conversation.
                if ($userId !== $trip->driver_id) {
                    $name = User::find($userId)?->name ?? 'A passenger';
                    $this->postSystemMessage($conversation, "{$name} joined the trip.");
                }
            }

            foreach ($existing as $userId => $participant) {
                if (! $participant->isActive() || $activeUserIds->contains($userId)) {
                    continue;
                }

                $participant->update(['left_at' => now()]);
                $name = User::find($userId)?->name ?? 'A passenger';
                $this->postSystemMessage($conversation, "{$name} left the trip.");
            }

            return $conversation;
        });
    }

    public function scheduleClosure(Trip $trip, string $reason): void
    {
        $conversation = Conversation::query()->where('trip_id', $trip->id)->first();
        if (! $conversation || $conversation->scheduled_purge_at !== null) {
            return;
        }

        $retentionDays = (int) (SystemSetting::get('chat_retention_days_after') ?? 3);

        $conversation->update([
            'scheduled_purge_at' => now()->addDays($retentionDays),
            'purge_reason' => $reason,
        ]);

        $dayWord = $retentionDays === 1 ? 'day' : 'days';
        $prefix = $reason === Conversation::PURGE_REASON_TRIP_CANCELLED ? 'This trip was cancelled. ' : '';
        $this->postSystemMessage($conversation, "{$prefix}This chat will close in {$retentionDays} {$dayWord}.");
    }

    public function createPrivateGroup(Trip $trip, User $driver, array $connectionUserIds): Conversation
    {
        if ((int) $trip->driver_id !== $driver->id) {
            throw ValidationException::withMessages(['chat' => 'Only the trip driver can start a group chat.']);
        }

        if ($trip->visibility !== 'private') {
            throw ValidationException::withMessages(['chat' => 'This action is only for private trips — public trips get a group chat automatically.']);
        }

        if (Conversation::query()->where('trip_id', $trip->id)->exists()) {
            throw ValidationException::withMessages(['chat' => 'A group chat already exists for this trip.']);
        }

        $memberIds = $this->assertAcceptedConnections($driver, $connectionUserIds);

        return DB::transaction(function () use ($trip, $driver, $memberIds): Conversation {
            $conversation = Conversation::create($this->buildConversationAttributes($trip));

            ConversationParticipant::create([
                'conversation_id' => $conversation->id,
                'user_id' => $driver->id,
                'is_chat_admin' => true,
                'joined_at' => now(),
            ]);

            foreach ($memberIds as $userId) {
                ConversationParticipant::create([
                    'conversation_id' => $conversation->id,
                    'user_id' => $userId,
                    'is_chat_admin' => false,
                    'joined_at' => now(),
                ]);
            }

            $names = User::query()->whereIn('id', $memberIds)->pluck('name')->implode(', ');
            if ($names !== '') {
                $this->postSystemMessage($conversation, "Group chat started with {$names}.");
            }

            return $conversation;
        });
    }

    public function addToPrivateGroup(Conversation $conversation, User $actor, array $connectionUserIds): void
    {
        $this->ensureChatAdmin($conversation, $actor);

        $memberIds = $this->assertAcceptedConnections($actor, $connectionUserIds);
        $existingIds = $conversation->participants()->pluck('user_id');

        foreach ($memberIds as $userId) {
            if ($existingIds->contains($userId)) {
                continue;
            }

            ConversationParticipant::create([
                'conversation_id' => $conversation->id,
                'user_id' => $userId,
                'is_chat_admin' => false,
                'joined_at' => now(),
            ]);

            $name = User::find($userId)?->name ?? 'Someone';
            $this->postSystemMessage($conversation, "{$name} was added to the group.");
        }
    }

    public function removeFromPrivateGroup(Conversation $conversation, User $actor, int $userId): void
    {
        $this->ensureChatAdmin($conversation, $actor);

        $participant = ConversationParticipant::query()
            ->where('conversation_id', $conversation->id)
            ->where('user_id', $userId)
            ->whereNull('left_at')
            ->first();

        if (! $participant) {
            return;
        }

        $participant->update(['left_at' => now()]);
        $name = User::find($userId)?->name ?? 'Someone';
        $this->postSystemMessage($conversation, "{$name} was removed from the group.");
    }

    public function postMessage(Conversation $conversation, User $sender, string $body): Message
    {
        $participant = ConversationParticipant::query()
            ->where('conversation_id', $conversation->id)
            ->where('user_id', $sender->id)
            ->whereNull('left_at')
            ->first();

        if (! $participant) {
            abort(403);
        }

        if ($conversation->opens_at && now()->lt($conversation->opens_at)) {
            throw ValidationException::withMessages(['body' => 'This chat is not open yet.']);
        }

        $body = trim($body);
        if ($body === '') {
            throw ValidationException::withMessages(['body' => 'Message cannot be empty.']);
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $sender->id,
            'type' => Message::TYPE_TEXT,
            'body' => $body,
            'created_at' => now(),
        ]);

        // Sending a message counts as having read up to it — otherwise the
        // sender's own conversation would immediately show as "unread" to
        // themselves in the list/nav badge.
        $participant->update(['last_read_message_id' => $message->id]);

        broadcast(new MessageSent($message));

        return $message;
    }

    private function postSystemMessage(Conversation $conversation, string $body): Message
    {
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => null,
            'type' => Message::TYPE_SYSTEM,
            'body' => $body,
            'created_at' => now(),
        ]);

        broadcast(new MessageSent($message));

        return $message;
    }

    private function ensureChatAdmin(Conversation $conversation, User $actor): void
    {
        $isAdmin = ConversationParticipant::query()
            ->where('conversation_id', $conversation->id)
            ->where('user_id', $actor->id)
            ->where('is_chat_admin', true)
            ->whereNull('left_at')
            ->exists();

        if (! $isAdmin) {
            abort(403);
        }
    }

    /**
     * @return \Illuminate\Support\Collection<int, int>
     */
    private function assertAcceptedConnections(User $actor, array $connectionUserIds): \Illuminate\Support\Collection
    {
        $allowedIds = Connection::acceptedUserIdsFor($actor)->flip();
        $memberIds = collect($connectionUserIds)->map(fn ($id) => (int) $id)->unique()->values();
        $invalid = $memberIds->filter(fn ($id) => ! $allowedIds->has($id));

        if ($invalid->isNotEmpty()) {
            throw ValidationException::withMessages(['chat' => 'You can only invite your accepted connections.']);
        }

        return $memberIds;
    }

    private function buildConversationAttributes(Trip $trip): array
    {
        $daysBefore = (int) (SystemSetting::get('chat_open_days_before') ?? 3);
        // trips.trip_datetime is a KL-local wall-clock string (Trip::TIMEZONE)
        // — ->utc() converts the underlying Carbon to the real UTC instant so
        // every column on this table can be compared with a plain now().
        $tripDatetimeUtc = $trip->trip_datetime?->clone()->utc();

        return [
            'trip_id' => $trip->id,
            'trip_ref_snapshot' => $trip->trip_ref,
            'route_snapshot' => trim(($trip->pickup_name ?: 'Pickup').' → '.($trip->destination_name ?: 'Destination')),
            'trip_datetime_snapshot' => $tripDatetimeUtc,
            'visibility_snapshot' => $trip->visibility,
            'driver_id' => $trip->driver_id,
            'opens_at' => $tripDatetimeUtc?->clone()->subDays($daysBefore) ?? now(),
        ];
    }
}
