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
 * Builds and maintains the group chat that belongs to a trip.
 *
 * There are two kinds. A public trip gets its chat created automatically and
 * kept in step with the driver plus whichever passengers are approved right
 * now. A private trip gets a chat only when the driver starts one, and the
 * members there come from the driver's own saved Connections.
 *
 * One thing to watch in this class is the time columns. The date fields on a
 * conversation are stored as true UTC instants, while trips.trip_datetime is
 * stored as Malaysian wall clock text (see Trip::TIMEZONE). That is why the
 * comparisons in here use a plain now() while the trip code uses Trip::now().
 */
class ChatService
{
    /** When a chat is closing in fewer days than this, the payment reminder uses its "closing soon" wording. */
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

                // The driver already owns the chat from the moment it is
                // created, so announcing that they joined their own trip
                // would just look odd.
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
        if (! $conversation || $conversation->scheduled_purge_at !== null || $conversation->is_circle) {
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

    /**
     * Starts a circle, which is a group chat the driver keeps between trips.
     *
     * A normal trip chat dies with its trip. A circle does not, so it is not
     * scheduled for closure and its old messages are trimmed by
     * PruneCircleMessages instead of being purged wholesale.
     *
     * Creating one takes a single tap because the starting members are simply
     * the passengers already confirmed on this trip. There is no need to show
     * a Connections picker, since TripService already checked that every one
     * of those passengers is a Connection when the trip was created.
     */
    public function createCircle(Trip $trip, User $driver, ?string $name = null): Conversation
    {
        if ((int) $trip->driver_id !== $driver->id) {
            throw ValidationException::withMessages(['chat' => 'Only the trip driver can start a group chat.']);
        }

        if ($trip->visibility !== 'private') {
            throw ValidationException::withMessages(['chat' => 'This action is only for private trips. Public trips get a group chat automatically.']);
        }

        if (Conversation::query()->where('trip_id', $trip->id)->exists()) {
            throw ValidationException::withMessages(['chat' => 'A group chat already exists for this trip.']);
        }

        return DB::transaction(function () use ($trip, $driver, $name): Conversation {
            // Locks the driver's own row so two concurrent "create new
            // circle" submissions from the same driver can't both pass the
            // cap check before either has committed its insert.
            User::query()->whereKey($driver->id)->lockForUpdate()->first();

            $maxCircles = (int) (SystemSetting::get('chat_max_circles_per_driver') ?? 5);
            $currentCircles = Conversation::query()
                ->where('driver_id', $driver->id)
                ->where('is_circle', true)
                ->count();

            if ($currentCircles >= $maxCircles) {
                throw ValidationException::withMessages([
                    'chat' => "You've reached your limit of {$maxCircles} circles. Retire one before starting another.",
                ]);
            }

            $conversation = Conversation::create([
                ...$this->buildConversationAttributes($trip),
                'is_circle' => true,
                'name' => $name,
            ]);
            $this->postHexaWelcome($conversation);

            $memberIds = $this->currentTripRoster($trip);
            foreach ($memberIds as $userId) {
                $this->ensureActiveParticipant($conversation, $userId, isChatAdmin: (int) $userId === $driver->id);
            }

            $names = User::query()
                ->whereIn('id', $memberIds->reject(fn ($id) => (int) $id === $driver->id))
                ->pluck('name')
                ->implode(', ');
            $this->postSystemMessage($conversation, $names !== ''
                ? "Circle chat started with {$names}."
                : 'Circle chat started.');

            return $conversation;
        });
    }

    /**
     * Points an existing circle at a new trip, which is what happens when the
     * driver picks "reuse an existing circle" instead of starting a fresh one.
     *
     * Anyone on the new trip who is not already in the circle gets added, and
     * nobody is ever removed. The circle can only point at one trip at a time,
     * so the trip it was linked to before stops showing this chat. That is
     * fine in practice because the older trip is finished by then.
     */
    public function linkCircleToTrip(Conversation $circle, Trip $trip, User $driver): Conversation
    {
        if (! $circle->is_circle || (int) $circle->driver_id !== $driver->id) {
            throw ValidationException::withMessages(['chat' => 'This is not one of your circles.']);
        }

        if ((int) $trip->driver_id !== $driver->id) {
            throw ValidationException::withMessages(['chat' => 'Only the trip driver can start a group chat.']);
        }

        if ($trip->visibility !== 'private') {
            throw ValidationException::withMessages(['chat' => 'This action is only for private trips. Public trips get a group chat automatically.']);
        }

        if (Conversation::query()->where('trip_id', $trip->id)->exists()) {
            throw ValidationException::withMessages(['chat' => 'A group chat already exists for this trip.']);
        }

        return DB::transaction(function () use ($circle, $trip): Conversation {
            $circle->update($this->buildConversationAttributes($trip));
            $this->postSystemMessage($circle, "This circle is now also being used for {$trip->trip_ref}.");

            $addedNames = [];
            foreach ($this->currentTripRoster($trip) as $userId) {
                if ($this->ensureActiveParticipant($circle, $userId)) {
                    $addedNames[] = User::find($userId)?->name ?? 'Someone';
                }
            }

            if ($addedNames !== []) {
                $verb = count($addedNames) === 1 ? 'was' : 'were';
                $this->postSystemMessage($circle, implode(', ', $addedNames)." {$verb} added to the circle.");
            }

            return $circle;
        });
    }

    /**
     * Retires a circle for good.
     *
     * Drivers are limited to a set number of circles, so this is how they free
     * a slot. The row is deleted outright rather than soft deleted, which
     * matches how expired conversations are handled elsewhere in the app and
     * means the slot is available again straight away.
     */
    public function deleteCircle(Conversation $circle, User $actor): void
    {
        $this->ensureChatAdmin($circle, $actor);

        if (! $circle->is_circle) {
            throw ValidationException::withMessages(['chat' => 'This chat is not a circle.']);
        }

        $circle->delete();
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
            // The browser already shrinks and compresses the photo before it
            // is sent, so these two checks are only a safety net in case
            // someone posts straight to the endpoint. The size limit mainly
            // protects the realtime broadcast, which rejects large payloads.
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

        // Sending a message also marks it as read for the sender. Without
        // this their own message would make the chat look unread to them in
        // the list and in the navigation badge.
        $participant->update(['last_read_message_id' => $message->id]);

        broadcast(new MessageSent($message));

        return $message;
    }

    /**
     * Posts Hexa's opening message, once, as soon as a chat is created.
     *
     * The wording changes depending on the kind of chat. On a public trip the
     * members are strangers the app matched, so the tips lean on verifying the
     * driver and warn that the chat closes after the trip.
     *
     * A circle is different. Its members are the driver's own Connections and
     * the chat never closes, so repeating those two points there would simply
     * be wrong. The real risk in a circle is that it carries over from trip to
     * trip and people assume last week's time, pickup point or fare still
     * applies, so the circle version warns about that instead.
     */
    public function postHexaWelcome(Conversation $conversation): Message
    {
        if ($conversation->is_circle) {
            $retentionDays = (int) (SystemSetting::get('chat_circle_message_retention_days') ?? 60);

            return $this->postBotMessage($conversation, <<<TEXT
            Hi, I'm Hexa 👋 A few quick tips for this circle chat:
            • This circle may be reused for several trips with this group, so always confirm the pickup time, location, and fare for the CURRENT trip here. They can differ from past trips.
            • Keep pickup details and payment arrangements inside this chat so there's a clear record for everyone.
            • Agree with your driver whether you're paying before or after each ride, and keep a screenshot or receipt either way.
            • Double-check that the car and plate number match what's shown in the app before you get in. Your safety is ultimately your own responsibility, since CarpoolHub only provides the platform connecting drivers and passengers and isn't liable for the actions of other users.
            • This circle stays here for your future trips together. Only messages older than {$retentionDays} days are cleared automatically, so nothing you need suddenly disappears.
            Have a safe trip!
            TEXT, Message::TYPE_BOT);
        }

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
     * Posts the reminder about passengers who still owe money for the trip.
     *
     * Passengers are named in the message. That does not leak anything new,
     * because the driver can already see exactly who has paid on the Payments
     * page. SendChatPaymentReminder decides how often this runs.
     *
     * $daysUntilClose is null when the chat is a circle, since a circle never
     * closes and the "settle up before the chat closes" wording would make no
     * sense there. In that case the message names the trip reference instead,
     * because a circle can hold the history of several trips and "this trip"
     * would be ambiguous.
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\TripPayment>  $outstanding
     */
    public function postPaymentReminder(Conversation $conversation, \Illuminate\Support\Collection $outstanding, ?int $daysUntilClose): Message
    {
        $names = $outstanding->pluck('user.name')->filter()->unique()->implode(', ');
        $total = number_format((float) $outstanding->sum('amount_due'), 2);
        $verb = $outstanding->pluck('user_id')->unique()->count() === 1 ? 'has' : 'have';

        $body = match (true) {
            $daysUntilClose === null => "Hi, it's Hexa. {$names} still {$verb} an outstanding payment for {$conversation->trip_ref_snapshot} (RM{$total} total). Please settle up with your driver soon.",
            $daysUntilClose <= self::PAYMENT_REMINDER_FINAL_NOTICE_WITHIN_DAYS => "Hi, it's Hexa. This chat closes in {$daysUntilClose} day(s) and {$names} still {$verb} an outstanding payment for this trip (RM{$total} total). Please settle up before the chat closes.",
            default => "Hi, it's Hexa again. {$names} still {$verb} an outstanding payment for this trip (RM{$total} total). Please settle up with your driver soon.",
        };

        return $this->postBotMessage($conversation, $body, Message::TYPE_PAYMENT_REMINDER);
    }

    /**
     * Posts the "how was your ride" message once a public trip is over.
     *
     * SendDriverRatingInviteReminder calls this a single time per trip. The
     * repeating part of the reminder is the notification and the Telegram
     * message. This one only exists because the chat is where the passenger
     * is already talking to that driver, so it is the most natural place to
     * ask.
     */
    public function postRatingInvite(Trip $trip, Conversation $conversation): Message
    {
        return $this->postBotMessage(
            $conversation,
            "Hi, it's Hexa. Now that {$trip->trip_ref} has wrapped up, how was your ride? Take a second to rate your driver and help other passengers.",
            Message::TYPE_RATING_INVITE
        );
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

    /**
     * @return \Illuminate\Support\Collection<int, int>
     */
    private function currentTripRoster(Trip $trip): \Illuminate\Support\Collection
    {
        return TripParticipant::query()
            ->where('trip_id', $trip->id)
            ->where('attendance_status', 'joined')
            ->pluck('user_id')
            ->push($trip->driver_id)
            ->unique()
            ->values();
    }

    /**
     * Adds or reactivates a participant. Returns false when they were
     * already an active member (a no-op) so callers can build an accurate
     * "X was added" message instead of announcing people who never left.
     */
    private function ensureActiveParticipant(Conversation $conversation, int $userId, bool $isChatAdmin = false): bool
    {
        $participant = ConversationParticipant::query()
            ->where('conversation_id', $conversation->id)
            ->where('user_id', $userId)
            ->first();

        if ($participant && $participant->isActive()) {
            return false;
        }

        if ($participant) {
            $participant->update(['left_at' => null, 'joined_at' => now()]);
        } else {
            ConversationParticipant::create([
                'conversation_id' => $conversation->id,
                'user_id' => $userId,
                'is_chat_admin' => $isChatAdmin,
                'joined_at' => now(),
            ]);
        }

        return true;
    }

    /**
     * Fills in the snapshot columns a conversation keeps about its trip.
     *
     * opens_at is left null, which means every chat can be used
     * the moment it exists. An earlier version only opened a chat a few days
     * before departure, but that worked against the point of having a chat at
     * all. Settling the pickup point, agreeing whether payment is before or
     * after the ride, and checking the car are things people want to sort out
     * early, not at the last minute. Closing is unaffected, because
     * scheduled_purge_at still shuts the chat a few days after the trip.
     */
    private function buildConversationAttributes(Trip $trip): array
    {
        // trips.trip_datetime holds Malaysian wall clock text, so converting
        // it to UTC here keeps every date column on the conversations table
        // comparable with a plain now().
        $tripDatetimeUtc = $trip->trip_datetime?->clone()->utc();

        return [
            'trip_id' => $trip->id,
            'trip_ref_snapshot' => $trip->trip_ref,
            'route_snapshot' => trim(($trip->pickup_name ?: 'Pickup').' → '.($trip->destination_name ?: 'Destination')),
            'trip_datetime_snapshot' => $tripDatetimeUtc,
            'visibility_snapshot' => $trip->visibility,
            'driver_id' => $trip->driver_id,
            'opens_at' => null,
        ];
    }
}
