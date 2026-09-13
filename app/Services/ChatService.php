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
    /** Below this many days left before a conversation closes, the payment reminder switches to "closes soon" copy. */
    private const PAYMENT_REMINDER_FINAL_NOTICE_WITHIN_DAYS = 3;

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

            if ($conversation->wasRecentlyCreated) {
                $this->postHexaWelcome($conversation);
            }

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
            $this->postHexaWelcome($conversation);

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

    public function postMessage(Conversation $conversation, User $sender, string $body, string $type = Message::TYPE_TEXT): Message
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

        if ($type === Message::TYPE_IMAGE) {
            // The client already resizes/compresses before sending (see
            // chats-show.js) — this is a hard backstop, not the primary
            // control, mainly so a broadcast never blows past Ably's
            // per-message size limit.
            if (! preg_match('/^data:image\/(jpeg|png|webp);base64,/', $body)) {
                throw ValidationException::withMessages(['body' => 'Invalid image.']);
            }
            if (strlen($body) > 400_000) {
                throw ValidationException::withMessages(['body' => 'Image is too large.']);
            }
        } else {
            $type = Message::TYPE_TEXT;
            $body = trim($body);
            if ($body === '') {
                throw ValidationException::withMessages(['body' => 'Message cannot be empty.']);
            }
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $sender->id,
            'type' => $type,
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

    /**
     * One-time, per conversation — safety/rules framing for scam prevention
     * (verify the real driver via the car icon next to their name, agree on
     * pay-now-vs-pay-later directly with them) posted as soon as a
     * conversation exists, before any join/group-started system message.
     */
    public function postHexaWelcome(Conversation $conversation): Message
    {
        return $this->postBotMessage($conversation, <<<'TEXT'
        Hi, I'm Hexa 👋 A few quick safety tips for this chat:
        • Keep pickup details and payment arrangements inside this chat. Never deal with anyone who contacts you outside the app.
        • Your driver's name always has a small car icon 🚗 next to it here. Before you pay or share details, make sure you're really talking to them.
        • Agree with your driver whether you're paying before or after the ride, and keep a screenshot or receipt either way.
        • Double-check that the car and plate number match what's shown in the app before you get in. Your safety is ultimately your own responsibility, since CarpoolHub only provides the platform connecting drivers and passengers and isn't liable for the actions of other users.
        • This chat closes automatically a while after the trip ends, so sort out anything important before then.
        Have a safe trip!
        TEXT, Message::TYPE_BOT);
    }

    /**
     * Recurring nudge, named passengers and all — their payment status is
     * already visible to the driver on the Payments page, so naming them
     * here adds no new privacy exposure. See SendChatPaymentReminder for the
     * daily trigger/cooldown.
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\TripPayment>  $outstanding
     */
    public function postPaymentReminder(Conversation $conversation, \Illuminate\Support\Collection $outstanding, int $daysUntilClose): Message
    {
        $names = $outstanding->pluck('user.name')->filter()->unique()->implode(', ');
        $total = number_format((float) $outstanding->sum('amount_due'), 2);
        $verb = $outstanding->pluck('user_id')->unique()->count() === 1 ? 'has' : 'have';

        $body = $daysUntilClose <= self::PAYMENT_REMINDER_FINAL_NOTICE_WITHIN_DAYS
            ? "Hi, it's Hexa. This chat closes in {$daysUntilClose} day(s) and {$names} still {$verb} an outstanding payment for this trip (RM{$total} total). Please settle up before the chat closes."
            : "Hi, it's Hexa again. {$names} still {$verb} an outstanding payment for this trip (RM{$total} total). Please settle up with your driver soon.";

        return $this->postBotMessage($conversation, $body, Message::TYPE_PAYMENT_REMINDER);
    }

    private function postSystemMessage(Conversation $conversation, string $body): Message
    {
        return $this->postBotMessage($conversation, $body, Message::TYPE_SYSTEM);
    }

    private function postBotMessage(Conversation $conversation, string $body, string $type): Message
    {
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => null,
            'type' => $type,
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
