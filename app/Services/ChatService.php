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
     * A "circle" is a persistent, driver-owned group chat — not tied to any
     * one trip's lifecycle (see linkCircleToTrip(), scheduleClosure()'s
     * is_circle guard, and PruneCircleMessages instead of
     * PurgeExpiredConversations). One tap: no connections-picker for the
     * common case — membership starts as whoever's already confirmed on
     * this trip (same roster query syncParticipants() trusts for public
     * trips), since TripService::buildParticipantIds() already
     * Connections-validated every one of them at trip-creation time.
     */
    public function createCircle(Trip $trip, User $driver, ?string $name = null): Conversation
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
                ...$this->buildConversationAttributes($trip, forCircle: true),
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
     * Relinks an existing circle to a different trip — the "reuse an
     * existing circle" branch of the chooser. Merges in anyone on the new
     * trip's roster who isn't already an active member; never removes
     * anyone. An older trip this circle used to be linked to simply stops
     * resolving via Trip::conversation() once trip_id moves on — accepted
     * trade-off, see the plan.
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
            throw ValidationException::withMessages(['chat' => 'This action is only for private trips — public trips get a group chat automatically.']);
        }

        if (Conversation::query()->where('trip_id', $trip->id)->exists()) {
            throw ValidationException::withMessages(['chat' => 'A group chat already exists for this trip.']);
        }

        return DB::transaction(function () use ($circle, $trip): Conversation {
            $circle->update($this->buildConversationAttributes($trip, forCircle: true));
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
     * The cap's release valve — no soft-delete, matches this app's existing
     * convention (see PurgeExpiredConversations' docblock), frees the
     * driver's circle-count immediately.
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
     * One-time, per conversation — posted as soon as a conversation exists,
     * before any join/group-started system message. Branches on is_circle:
     * the public-trip version assumes strangers matched by the app (hence
     * the stronger "verify you're really talking to them" scam framing and
     * the accurate "this chat closes after the trip" note); a circle's
     * members are the driver's own accepted Connections and the chat itself
     * never closes (see PurgeExpiredConversations' is_circle guard), so
     * those two points would be actively misleading here — see the
     * fabricated-deletion-date bug this whole redesign fixed in the banner
     * above the thread for the same reason. Reused-across-many-trips is the
     * one risk that's actually specific to circles (assuming last trip's
     * time/pickup/fare still applies), so that replaces them instead.
     */
    public function postHexaWelcome(Conversation $conversation): Message
    {
        if ($conversation->is_circle) {
            $retentionDays = (int) (SystemSetting::get('chat_circle_message_retention_days') ?? 60);

            return $this->postBotMessage($conversation, <<<TEXT
            Hi, I'm Hexa 👋 A few quick tips for this circle chat:
            • This circle may be reused for several trips with this group — always confirm the pickup time, location, and fare for the CURRENT trip here, since they can differ from past trips.
            • Keep pickup details and payment arrangements inside this chat so there's a clear record for everyone.
            • Agree with your driver whether you're paying before or after each ride, and keep a screenshot or receipt either way.
            • Double-check that the car and plate number match what's shown in the app before you get in. Your safety is ultimately your own responsibility, since CarpoolHub only provides the platform connecting drivers and passengers and isn't liable for the actions of other users.
            • This circle stays here for future trips together — only messages older than {$retentionDays} days are cleared automatically, so nothing you need suddenly disappears.
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
     * Recurring nudge, named passengers and all — their payment status is
     * already visible to the driver on the Payments page, so naming them
     * here adds no new privacy exposure. See SendChatPaymentReminder for the
     * daily trigger/cooldown.
     *
     * $daysUntilClose is null for circles — they never close (see
     * PurgeExpiredConversations' is_circle guard), so there's no "closes
     * soon" urgency framing to apply; SendChatPaymentReminder always passes
     * null here for a circle rather than computing one against the
     * currently-linked trip's own (irrelevant) retention window. The trip
     * ref is named explicitly in that case since a circle's history can
     * span several different trips, unlike a one-trip conversation where
     * "this trip" is unambiguous.
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
     * One-time, per trip — posted by SendDriverRatingInviteReminder once a
     * public trip completes, not repeated daily itself (the notification/
     * Telegram reminder is what repeats; this chat message is just the
     * first, most in-context nudge). Public-trip only by construction —
     * only public trips ever reach this call site.
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
     * $forCircle forces opens_at to null — a circle's chat must never lock
     * sending behind a future trip's "opens N days before" window (see
     * postMessage()'s opens_at check), since relinking an already-active
     * circle to a trip scheduled more than chat_open_days_before days out
     * would otherwise silently block a mid-conversation group from sending
     * until that future date arrives.
     */
    private function buildConversationAttributes(Trip $trip, bool $forCircle = false): array
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
            'opens_at' => $forCircle ? null : ($tripDatetimeUtc?->clone()->subDays($daysBefore) ?? now()),
        ];
    }
}
