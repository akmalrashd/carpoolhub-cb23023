<?php

namespace App\Http\Controllers;

use Ably\AblyRest;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\Trip;
use App\Models\User;
use App\Services\ChatService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ChatController extends Controller
{
    public function __construct(
        private readonly ChatService $chatService,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        $conversations = Conversation::query()
            ->whereHas('participants', fn ($query) => $query->where('user_id', $user->id)->whereNull('left_at'))
            ->with([
                'driver',
                'participants' => fn ($query) => $query->whereNull('left_at')->with('user'),
                'messages' => fn ($query) => $query->latest('id')->limit(1),
            ])
            ->orderByDesc(
                Message::query()->select('created_at')
                    ->whereColumn('messages.conversation_id', 'conversations.id')
                    ->latest('id')->limit(1)
            )
            ->get();

        $unreadByConversation = ConversationParticipant::query()
            ->where('user_id', $user->id)
            ->whereNull('left_at')
            ->get()
            ->keyBy('conversation_id');

        return view('chats.index', [
            'conversations' => $conversations,
            'unreadByConversation' => $unreadByConversation,
        ]);
    }

    public function show(Request $request, Conversation $conversation): View
    {
        $participant = $this->activeParticipantOrFail($request, $conversation);

        $conversation->load(['participants' => fn ($query) => $query->whereNull('left_at')->with('user'), 'trip']);

        $messages = Message::query()
            ->where('conversation_id', $conversation->id)
            ->with('sender')
            ->orderBy('id')
            ->get();

        $this->markRead($request, $conversation);

        return view('chats.show', [
            'conversation' => $conversation,
            'messages' => $messages,
            'isChatAdmin' => (bool) $participant->is_chat_admin,
            'isOpen' => ! $conversation->opens_at || now()->gte($conversation->opens_at),
            // Feeds the shared "Trip Details" popup (trips/partials/trip-details-modal
            // + public/js/trip-details-modal.js) — null once the trip itself has been
            // hard-deleted (cancelled), since there's nothing left to show.
            'tripModalData' => $conversation->trip ? $this->buildTripModalData($conversation->trip, $request->user()) : null,
        ]);
    }

    /**
     * Mirrors the per-row @php block in trips/index.blade.php that feeds the
     * same modal there — kept separate rather than extracted into a shared
     * service, since that page's own two copies of this logic (mobile card +
     * desktop table) are pre-existing and out of scope to touch here.
     */
    private function buildTripModalData(Trip $trip, User $viewer): array
    {
        $trip->loadMissing(['driver', 'savedRoute', 'returnTrip', 'participants.user', 'passengerRoutePoints.user']);

        $hasReturn = (bool) $trip->returnTrip;
        $pickupName = $trip->pickup_name ?? 'Pickup';
        $destinationName = $trip->destination_name ?? 'Destination';
        $directionText = $pickupName.' → '.$destinationName;
        $routeName = $trip->savedRoute?->route_name ?: $directionText;
        $combinedFare = (float) $trip->fare_total + (float) ($trip->returnTrip?->fare_total ?? 0);
        $tripRef = $trip->trip_ref ?: 'TRP-'.str_pad((string) $trip->id, 5, '0', STR_PAD_LEFT);

        $participantPayload = $trip->participants
            ->map(fn ($participant) => [
                'user_id' => $participant->user_id,
                'name' => $participant->user?->name ?? '-',
                'email' => $participant->user?->email ?? '',
                'photo_url' => $participant->user?->profile_photo_url ?? null,
                'is_driver' => (bool) $participant->is_driver,
            ])
            ->values();

        $routePointPayload = $trip->passengerRoutePoints
            ->filter(fn ($point) => in_array((string) $point->status, ['accepted', 'approved'], true))
            ->filter(fn ($point) => ! $point->uses_default_pickup || ! $point->uses_default_dropoff)
            ->map(fn ($point) => [
                'name' => $point->user?->name ?? 'Passenger',
                'pickup' => $point->uses_default_pickup ? null : [
                    'lat' => $point->pickup_latitude !== null ? (float) $point->pickup_latitude : null,
                    'lng' => $point->pickup_longitude !== null ? (float) $point->pickup_longitude : null,
                    'label' => ($point->user?->name ?? 'Passenger').' pickup',
                ],
                'dropoff' => $point->uses_default_dropoff ? null : [
                    'lat' => $point->dropoff_latitude !== null ? (float) $point->dropoff_latitude : null,
                    'lng' => $point->dropoff_longitude !== null ? (float) $point->dropoff_longitude : null,
                    'label' => ($point->user?->name ?? 'Passenger').' drop-off',
                ],
            ])
            ->values();

        $passengerCount = (int) $trip->participants->where('is_driver', false)->count();
        $driverIncludedInSplit = ((int) $trip->participant_count) > $passengerCount;
        $splitType = $driverIncludedInSplit ? 'Driver Included in Fare Split' : 'Driver Excluded from Fare Split';

        $statusSlug = strtolower((string) $trip->status);
        $statusLabel = match ($statusSlug) {
            'scheduled' => 'Scheduled',
            'recorded' => 'Recorded',
            'confirmed' => 'Confirmed',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'draft' => 'Draft',
            default => Str::headline($statusSlug),
        };

        $isAdmin = $viewer->role === 'admin';
        $canManage = $isAdmin || $viewer->id === $trip->driver_id;
        $canDelete = $isAdmin || ! in_array($trip->status, ['cancelled'], true);

        return [
            'tripId' => $trip->id,
            'tripRef' => $tripRef,
            'routeName' => $routeName,
            'driverId' => $trip->driver_id,
            'driverName' => $trip->driver?->name ?: '-',
            'driverPhoto' => $trip->driver?->profile_photo_url,
            'driverEmail' => $trip->driver?->email ?: '',
            'driverWhatsappUrl' => $trip->driver?->whatsapp_url ?: '',
            'driverPhone' => $trip->driver?->whatsapp_digits ?: '',
            'mode' => $hasReturn ? 'Two-Way' : 'One-Way',
            'status' => $statusLabel,
            'outboundDatetime' => $trip->trip_datetime?->format('Y-m-d H:i') ?: '-',
            'fareLabel' => 'Total Fare',
            'fareDisplay' => 'RM '.number_format($combinedFare, 2),
            'pickupName' => $pickupName,
            'pickupLat' => $trip->pickup_latitude ?? '',
            'pickupLng' => $trip->pickup_longitude ?? '',
            'destinationName' => $destinationName,
            'destinationLat' => $trip->destination_latitude ?? '',
            'destinationLng' => $trip->destination_longitude ?? '',
            'totalPassengers' => $passengerCount,
            'splitType' => $splitType,
            'participantsB64' => base64_encode($participantPayload->toJson()),
            'routePointsB64' => base64_encode($routePointPayload->toJson()),
            'canManage' => $canManage ? '1' : '0',
            'canDelete' => $canDelete ? '1' : '0',
            'editUrl' => route('trips.edit', $trip),
            'deleteUrl' => route('trips.destroy', $trip),
        ];
    }

    /**
     * Returns the created message as JSON (not a redirect) — the composer
     * sends this via fetch and uses the response to reconcile its own
     * optimistic bubble, since the Ably echo of the same message (or the
     * polling fallback) would otherwise render it a second time. Both paths
     * key off the same message id, so whichever arrives first wins and the
     * other is a no-op.
     */
    public function store(Request $request, Conversation $conversation): JsonResponse
    {
        $this->activeParticipantOrFail($request, $conversation);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        try {
            $message = $this->chatService->postMessage($conversation, $request->user(), $validated['body']);
        } catch (ValidationException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['message' => [
            'id' => $message->id,
            'type' => $message->type,
            'body' => $message->body,
            'sender_id' => $message->sender_id,
            'created_at' => $message->created_at?->toIso8601String(),
        ]]);
    }

    /**
     * Scopes an Ably token to exactly this conversation's channel —
     * see routes/channels.php for why this replaces Laravel's stock
     * /broadcasting/auth for this app.
     */
    public function ablyToken(Request $request): JsonResponse
    {
        $conversation = Conversation::query()->findOrFail((int) $request->query('conversation_id'));
        $this->activeParticipantOrFail($request, $conversation);

        $key = config('broadcasting.connections.ably.key');
        if (! $key) {
            // Not configured yet (ABLY_KEY unset / BROADCAST_CONNECTION != ably)
            // — the composer/poll fallback still works without this, so fail
            // quietly rather than filling the log with a stack trace on every
            // chat page load.
            return response()->json(['error' => 'Realtime chat is not configured.'], 503);
        }

        $ably = new AblyRest(['key' => $key]);
        $tokenRequest = $ably->auth->createTokenRequest([
            'capability' => json_encode(['private:conversation.'.$conversation->id => ['subscribe']]),
            'clientId' => (string) $request->user()->id,
        ]);

        return response()->json($tokenRequest->toArray());
    }

    public function markRead(Request $request, Conversation $conversation): JsonResponse|null
    {
        $participant = ConversationParticipant::query()
            ->where('conversation_id', $conversation->id)
            ->where('user_id', $request->user()->id)
            ->whereNull('left_at')
            ->first();

        if (! $participant) {
            return $request->wantsJson() ? response()->json(['ok' => false], 404) : null;
        }

        $latestId = Message::query()->where('conversation_id', $conversation->id)->max('id');
        if ($latestId) {
            $participant->update(['last_read_message_id' => $latestId]);
        }

        return $request->wantsJson() ? response()->json(['ok' => true]) : null;
    }

    private function activeParticipantOrFail(Request $request, Conversation $conversation): ConversationParticipant
    {
        $participant = ConversationParticipant::query()
            ->where('conversation_id', $conversation->id)
            ->where('user_id', $request->user()->id)
            ->whereNull('left_at')
            ->first();

        if (! $participant) {
            abort(403);
        }

        return $participant;
    }
}
